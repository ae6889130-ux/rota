<?php 
require __DIR__.'/app.php'; 
app_config();

$s=db()->prepare('SELECT * FROM orders WHERE id=?');
$s->execute([(int)($_GET['id']??0)]);
$o=$s->fetch();

if(!$o){
    http_response_code(404);
    exit('Pedido não encontrado');
}

// Verifica se o pagamento já foi aprovado e redireciona para o ingresso
if($o['status'] === 'paid') {
    header('Location: ticket.php?code=' . urlencode($o['ticket_code']));
    exit;
}

$pix=pix_payload(setting('pix_key'),(float)$o['amount'],setting('pix_name','SONIA ONUKI'),setting('pix_city','SAO PAULO'),'EV'.$o['id']);
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width">
    <title>Pagamento</title>
    <link rel="stylesheet" href="assets/style.css">
    <style>
        /* Trava de tamanho exclusiva para a logo do cabeçalho */
        .site-logo {
            max-height: 50px;
            max-width: 100%;
            width: auto;
            height: auto;
            object-fit: contain;
            display: block;
        }
    </style>
</head>
<body class="checkout-page">
    <header class="nav">
        <a class="brand" href="index.php"><img class="site-logo" src="https://i.imgur.com/lZRM8gM.png" alt="Rota da Prosperidade"></a>
    </header>
    <main class="payment-card">
        <span class="eyebrow">PEDIDO #<?=e((string)$o['id'])?></span>
        <h1>Finalize seu pagamento</h1>
        <p>Olá, <?=e(explode(' ',$o['name'])[0])?>. O valor total é <b><?=money((float)$o['amount'])?></b>.</p>
        
        <?php if($o['payment_method']==='pix'):?>
            <div class="pix-box">
                <div class="fake-qr">
                    <img alt="QR Code PIX" src="https://api.qrserver.com/v1/create-qr-code/?size=260x260&amp;data=<?=urlencode($pix)?>">
                </div>
                <label>Código PIX copia e cola
                    <textarea id="pix" readonly><?=e($pix)?></textarea>
                </label>
                <button class="button" onclick="navigator.clipboard.writeText(document.querySelector('#pix').value);this.textContent='Código copiado ✓'">Copiar código PIX</button>
                <small>Após pagar, aguarde a confirmação da organização.</small>
            </div>
        <?php else:?>
            <div class="card-link">
                <div>💳</div>
                <h2>Pagamento por cartão</h2>
                <p>Você será direcionado ao ambiente seguro da operadora.</p>
                <a class="button" rel="noopener" href="<?=e(setting('card_url'))?>">Pagar com cartão →</a>
            </div>
        <?php endif;?>
    </main>
</body>
</html>