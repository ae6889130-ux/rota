<?php
declare(strict_types=1);

function app_config(): array {
    static $config;
    if ($config) return $config;
    $file = __DIR__ . '/config.php';
    if (!is_file($file)) { header('Location: install.php'); exit; }
    return $config = require $file;
}

function db(): PDO {
    static $pdo;
    if ($pdo) return $pdo;
    $c = app_config()['db'];
    $pdo = new PDO($c['dsn'], $c['user'] ?? null, $c['pass'] ?? null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    // Keep older installations compatible when new attendee fields are released.
    foreach ([
        'street'=>'VARCHAR(190) NULL', 'district'=>'VARCHAR(120) NULL',
        'postal_code'=>'VARCHAR(12) NULL', 'city'=>'VARCHAR(120) NULL',
        'state'=>'VARCHAR(2) NULL', 'address_number'=>'VARCHAR(20) NULL',
        'ticket_label'=>'VARCHAR(80) NULL'
    ] as $column=>$definition) {
        try { $pdo->exec("ALTER TABLE orders ADD COLUMN $column $definition"); } catch (PDOException) {}
    }
    return $pdo;
}

function start_session(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_set_cookie_params(['httponly'=>true,'secure'=>!empty($_SERVER['HTTPS']),'samesite'=>'Lax']);
        session_start();
    }
}
function csrf(): string { start_session(); return $_SESSION['csrf'] ??= bin2hex(random_bytes(24)); }
function check_csrf(): void { start_session(); if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) { http_response_code(419); exit('Sessão expirada. Atualize a página.'); } }
function e(?string $v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function setting(string $key, string $fallback=''): string { $s=db()->prepare('SELECT value FROM settings WHERE name=?'); $s->execute([$key]); return (string)($s->fetchColumn() ?: $fallback); }
function money(float $v): string { return 'R$ '.number_format($v, 2, ',', '.'); }
function admin_required(): void { start_session(); if (empty($_SESSION['admin'])) { header('Location: admin.php'); exit; } }
function ticket_code(): string { return strtoupper(bin2hex(random_bytes(3))).'-'.strtoupper(bin2hex(random_bytes(2))); }

function pix_payload(string $key, float $amount, string $name, string $city, string $txid): string {
    $field = fn(string $id,string $v) => $id.str_pad((string)strlen($v),2,'0',STR_PAD_LEFT).$v;
    $clean = fn(string $v) => strtoupper(substr(iconv('UTF-8','ASCII//TRANSLIT',$v) ?: $v,0,25));
    $p = $field('00','01').$field('26',$field('00','BR.GOV.BCB.PIX').$field('01',$key)).$field('52','0000').$field('53','986').$field('54',number_format($amount,2,'.','')).$field('58','BR').$field('59',$clean($name)).$field('60',$clean($city)).$field('62',$field('05',substr($txid,0,25))).'6304';
    $crc=0xFFFF; for($i=0;$i<strlen($p);$i++){ $crc^=ord($p[$i])<<8; for($j=0;$j<8;$j++) $crc=($crc&0x8000)?(($crc<<1)^0x1021):($crc<<1); $crc&=0xFFFF; }
    return $p.strtoupper(str_pad(dechex($crc),4,'0',STR_PAD_LEFT));
}

function send_ticket_email(array $order): void {
    $url=rtrim(setting('site_url'),'/').'/ticket.php?code='.urlencode($order['ticket_code']);
    $subject=setting('ticket_email_subject','Seu ingresso — Rota da Prosperidade');
    $template=setting('ticket_email_body','Olá, {{nome}}! Seu ingresso está pronto. Acesse: {{link_ingresso}}');
    $body=strtr($template,['{{nome}}'=>$order['name'],'{{codigo}}'=>$order['ticket_code'],'{{link_ingresso}}'=>$url]);
    smtp_mail($order['email'],$order['name'],$subject,nl2br(e($body)));
}

function smtp_mail(string $to, string $name, string $subject, string $html): bool {
    $host=setting('smtp_host');
    if (!$host) return mail($to,$subject,$html,'From: '.setting('mail_from','ingressos@localhost')."\r\nMIME-Version: 1.0\r\nContent-Type: text/html; charset=UTF-8");
    $port=(int)setting('smtp_port','587'); $secure=setting('smtp_secure','tls');
    $socket=@stream_socket_client(($secure==='ssl'?'ssl://':'').$host.':'.$port,$errno,$errstr,15);
    if(!$socket) return false;
    $read=function()use($socket){$out='';while(($line=fgets($socket))!==false){$out.=$line;if(isset($line[3])&&$line[3]===' ')break;}return (int)substr($out,0,3);};
    $send=function(string $command,array $ok=[250])use($socket,$read){fwrite($socket,$command."\r\n");return in_array($read(),$ok,true);};
    if($read()!==220)return false;
    if(!$send('EHLO '.($_SERVER['SERVER_NAME']??'localhost')))return false;
    if($secure==='tls'){if(!$send('STARTTLS',[220])||!stream_socket_enable_crypto($socket,true,STREAM_CRYPTO_METHOD_TLS_CLIENT))return false;if(!$send('EHLO '.($_SERVER['SERVER_NAME']??'localhost')))return false;}
    $user=setting('smtp_user');$pass=setting('smtp_pass');
    if($user&&(!$send('AUTH LOGIN',[334])||!$send(base64_encode($user),[334])||!$send(base64_encode($pass),[235])))return false;
    $from=setting('mail_from','ingressos@localhost');
    if(!$send('MAIL FROM:<'.$from.'>')||!$send('RCPT TO:<'.$to.'>',[250,251])||!$send('DATA',[354]))return false;
    $headers=['From: '.setting('mail_name','Rota da Prosperidade').' <'.$from.'>','To: '.$name.' <'.$to.'>','Subject: =?UTF-8?B?'.base64_encode($subject).'?=','MIME-Version: 1.0','Content-Type: text/html; charset=UTF-8'];
    $message=implode("\r\n",$headers)."\r\n\r\n".$html;
    fwrite($socket,str_replace("\n.","\n..",$message)."\r\n.\r\n");$ok=$read()===250;$send('QUIT',[221]);fclose($socket);return $ok;
}

function save_setting(string $name,string $value): void {
    $s=db()->prepare('SELECT COUNT(*) FROM settings WHERE name=?');$s->execute([$name]);
    $sql=$s->fetchColumn()?'UPDATE settings SET value=? WHERE name=?':'INSERT INTO settings(value,name) VALUES(?,?)';
    db()->prepare($sql)->execute([$value,$name]);
}
