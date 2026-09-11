<?php
session_start();
if(file_exists('config.php')) {
    die("O sistema já está instalado. Remova ou renomeie install.php para segurança.");
}

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $host = $_POST['host'];
    $db = $_POST['db'];
    $user = $_POST['user'];
    $pass = $_POST['pass'];
    $admin_user = $_POST['admin_user'];
    $admin_pass = password_hash($_POST['admin_pass'], PASSWORD_DEFAULT);

    try {
        $pdo = new PDO("mysql:host=$host", $user, $pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `$db`");
        $pdo->exec("USE `$db`");
        
        $sql = "
        CREATE TABLE IF NOT EXISTS admin (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) NOT NULL,
            password VARCHAR(255) NOT NULL
        );
        CREATE TABLE IF NOT EXISTS tickets (
            id INT AUTO_INCREMENT PRIMARY KEY,
            hash VARCHAR(64) UNIQUE NOT NULL,
            nome VARCHAR(100) NOT NULL,
            email VARCHAR(100) NOT NULL,
            cpf VARCHAR(20) NOT NULL,
            upsell BOOLEAN DEFAULT FALSE,
            payment_method VARCHAR(20),
            status ENUM('active', 'inactive') DEFAULT 'active',
            checked_in BOOLEAN DEFAULT FALSE,
            checkin_time DATETIME NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        );
        INSERT INTO admin (username, password) VALUES ('$admin_user', '$admin_pass');
        ";
        $pdo->exec($sql);

        $config_content = "<?php\n"
                        . "define('DB_HOST', '$host');\n"
                        . "define('DB_USER', '$user');\n"
                        . "define('DB_PASS', '$pass');\n"
                        . "define('DB_NAME', '$db');\n"
                        . "try {\n"
                        . "    \$pdo = new PDO('mysql:host='.DB_HOST.';dbname='.DB_NAME, DB_USER, DB_PASS);\n"
                        . "    \$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);\n"
                        . "} catch(PDOException \$e) { die('DB Connection failed'); }\n";
        
        file_put_contents('config.php', $config_content);
        
        $msg = "Instalação concluída com sucesso! <a href='admin.php' class='underline'>Acesse o Painel</a>";
    } catch(PDOException $e) {
        $error = "Erro: " . $e->getMessage();
    }
}
?>
<!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Instalação | Rota da Prosperidade</title><link rel="stylesheet" href="assets/style.css"></head><body class="system-page auth-page"><main class="install-card"><span class="system-kicker">Configuração inicial</span><h1>Prepare o sistema do evento.</h1><p>Conecte o banco de dados e crie o primeiro acesso administrativo.</p><?php if(isset($msg)):?><div class="alert success"><?=$msg?></div><?php endif?><?php if(isset($error)):?><div class="alert error"><?=htmlspecialchars($error,ENT_QUOTES,'UTF-8')?></div><?php endif?><?php if(!isset($msg)):?><form method="post" class="install-form"><h2>Banco de dados</h2><label>Servidor<input name="host" value="localhost" required></label><label>Nome do banco<input name="db" value="rota_prosperidade" required></label><label>Usuário<input name="user" value="root" required></label><label>Senha<input type="password" name="pass"></label><h2>Acesso administrativo</h2><label>Usuário do painel<input name="admin_user" value="admin" required></label><label>Senha do painel<input type="password" name="admin_pass" required minlength="8"></label><button class="primary-button">Instalar e acessar →</button></form><?php endif?></main></body></html>