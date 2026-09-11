<?php
declare(strict_types=1);
session_start();
if (!is_file(__DIR__ . '/config.php')) { header('Location: install.php'); exit; }
require __DIR__ . '/config.php';

$_SESSION['csrf'] ??= bin2hex(random_bytes(24));
$error = '';
function checkout_old(string $key): string { return htmlspecialchars((string)($_POST[$key] ?? ''), ENT_QUOTES, 'UTF-8'); }
function valid_cpf(string $cpf): bool {
    if (strlen($cpf) !== 11 || preg_match('/^(\d)\1{10}$/', $cpf)) return false;
    for ($digit = 9; $digit < 11; $digit++) {
        $sum = 0;
        for ($i = 0; $i < $digit; $i++) $sum += (int)$cpf[$i] * (($digit + 1) - $i);
        $check = (10 * $sum) % 11;
        if ($check === 10) $check = 0;
        if ((int)$cpf[$digit] !== $check) return false;
    }
    return true;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim((string)($_POST['nome'] ?? ''));
    $email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
    $cpf = preg_replace('/\D+/', '', (string)($_POST['cpf'] ?? ''));
    $payment = (string)($_POST['payment_method'] ?? '');
    $bonus = isset($_POST['bonus']) ? 1 : 0;
    if (!hash_equals($_SESSION['csrf'], (string)($_POST['csrf'] ?? ''))) $error = 'Sua sessão expirou. Atualize a página e tente novamente.';
    elseif (mb_strlen($name) < 3 || !$email) $error = 'Informe seu nome completo e um e-mail válido.';
    elseif (!valid_cpf($cpf)) $error = 'Informe um CPF válido para continuar.';
    elseif (!in_array($payment, ['pix', 'card'], true)) $error = 'Escolha uma forma de pagamento.';
    else {
        $hash = bin2hex(random_bytes(24));
        $stmt = $pdo->prepare('INSERT INTO tickets (hash, nome, email, cpf, payment_method, upsell) VALUES (?, ?, ?, ?, ?, ?)');
        $stmt->execute([$hash, $name, $email, $cpf, $payment, $bonus]);
        header('Location: ticket.php?hash=' . urlencode($hash)); exit;
    }
}
?>
<!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex"><title>Inscrição segura | Rota da Prosperidade</title><link rel="stylesheet" href="assets/style.css"></head>
<body class="system-page checkout-page">
<header class="checkout-header"><a href="index.php"><img src="https://i.imgur.com/lZRM8gM.png" alt="Rota da Prosperidade"></a><span><i></i> Ambiente seguro</span></header>
<main class="checkout-shell">
<section class="checkout-panel">
<a class="back-link" href="index.php">← Voltar para o evento</a><span class="system-kicker">Inscrição presencial</span><h1>Você está a um passo da sua nova rota.</h1><p class="checkout-intro">Preencha seus dados abaixo. Seu ingresso com QR Code será gerado ao concluir.</p>
<?php if ($error): ?><div class="alert error" role="alert"><?=htmlspecialchars($error, ENT_QUOTES, 'UTF-8')?></div><?php endif; ?>
<form method="post" class="checkout-form"><input type="hidden" name="csrf" value="<?=htmlspecialchars($_SESSION['csrf'], ENT_QUOTES, 'UTF-8')?>">
<div class="form-section"><span>01</span><div><h2>Seus dados</h2><p>Informações de quem participará da imersão.</p></div></div>
<label>Nome completo<input name="nome" required autocomplete="name" value="<?=checkout_old('nome')?>" placeholder="Como está no documento"></label>
<div class="form-row"><label>E-mail<input name="email" type="email" required autocomplete="email" value="<?=checkout_old('email')?>" placeholder="voce@email.com"></label><label>CPF<input name="cpf" id="cpf" required inputmode="numeric" maxlength="14" value="<?=checkout_old('cpf')?>" placeholder="000.000.000-00"></label></div>
<div class="form-section"><span>02</span><div><h2>Forma de pagamento</h2><p>Escolha como deseja continuar.</p></div></div>
<div class="payment-grid"><label class="payment-option"><input type="radio" name="payment_method" value="pix" checked><span class="payment-icon">◇</span><span><b>PIX</b><small>Aprovação rápida</small></span></label><label class="payment-option"><input type="radio" name="payment_method" value="card"><span class="payment-icon">▭</span><span><b>Cartão</b><small>Pagamento seguro</small></span></label></div>
<label class="bonus"><input type="checkbox" name="bonus" value="1"><span><b>Sim, quero adicionar o bônus especial</b><small>Experiência complementar exclusiva para esta turma.</small></span><strong>+ R$ 1,00</strong></label>
<button class="primary-button checkout-submit" type="submit">Concluir inscrição <span>→</span></button><p class="privacy-note">Ao continuar, você concorda com o uso dos dados para processar sua inscrição e emitir seu ingresso.</p>
</form></section>
<aside class="order-summary"><span class="system-kicker">Resumo da inscrição</span><div class="summary-art"><small>IMERSÃO PRESENCIAL</small><strong>Rota da<br>Prosperidade</strong><span>18 e 19 · SET · 2026</span></div><div class="summary-line"><span>Ingresso individual</span><b>R$ 397,00</b></div><div class="summary-line bonus-line"><span>Bônus opcional</span><b id="bonus-price">—</b></div><div class="summary-total"><span>Total</span><strong id="total-price">R$ 397,00</strong></div><ul><li>✓ 02 dias de imersão</li><li>✓ Garrafinha + apostila</li><li>✓ Ingresso digital com QR Code</li></ul></aside>
</main><script>
const cpf=document.querySelector('#cpf');cpf?.addEventListener('input',()=>{let v=cpf.value.replace(/\D/g,'').slice(0,11);cpf.value=v.replace(/(\d{3})(\d)/,'$1.$2').replace(/(\d{3})(\d)/,'$1.$2').replace(/(\d{3})(\d{1,2})$/,'$1-$2')});
const bonus=document.querySelector('[name="bonus"]');bonus?.addEventListener('change',()=>{document.querySelector('#bonus-price').textContent=bonus.checked?'R$ 1,00':'—';document.querySelector('#total-price').textContent=bonus.checked?'R$ 398,00':'R$ 397,00'});
</script></body></html>
