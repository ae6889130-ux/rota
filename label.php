<?php
require __DIR__.'/app.php';
app_config(); admin_required();
$stmt=db()->prepare('SELECT * FROM orders WHERE id=?');$stmt->execute([(int)($_GET['id']??0)]);$order=$stmt->fetch();
if(!$order){http_response_code(404);exit('Ingresso não encontrado.');}
?>
<!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width"><title>Etiqueta — <?=e($order['name'])?></title><link rel="stylesheet" href="assets/style.css"></head><body class="label-page"><main class="print-label"><header><img src="https://i.imgur.com/lZRM8gM.png" alt="Rota da Prosperidade"><?php if($order['ticket_label']):?><b><?=e($order['ticket_label'])?></b><?php endif;?></header><h1><?=e($order['name'])?></h1><p><?=e($order['street'])?>, <?=e($order['address_number'])?><br><?=e($order['district'])?> · <?=e($order['city'])?>/<?=e($order['state'])?><br>CEP <?=e($order['postal_code'])?></p><footer><code><?=e($order['ticket_code'])?></code><img src="https://api.qrserver.com/v1/create-qr-code/?size=160x160&amp;data=<?=urlencode($order['ticket_code'])?>" alt="QR Code"></footer></main><div class="label-actions"><button onclick="print()">Imprimir etiqueta</button><a href="admin.php?tab=tickets">Voltar</a></div></body></html>
