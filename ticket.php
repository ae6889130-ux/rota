<?php
require 'config.php';
if(!isset($_GET['hash'])) { die('Ingresso inválido.'); }

$stmt = $pdo->prepare("SELECT * FROM tickets WHERE hash = ?");
$stmt->execute([$_GET['hash']]);
$ticket = $stmt->fetch();

if(!$ticket) { die('Ingresso não encontrado.'); }
?>
<!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Ingresso | <?=htmlspecialchars($ticket['nome'], ENT_QUOTES, 'UTF-8')?></title><link rel="stylesheet" href="assets/style.css"></head><body class="system-page ticket-page"><main class="ticket-wrap"><div class="ticket-actions"><button class="primary-button" onclick="print()">Imprimir ou salvar em PDF</button></div><article class="event-ticket"><section class="ticket-main"><img src="assets/logo.svg" alt="Rota da Prosperidade"><h1>Rota da Prosperidade</h1><p>Imersão presencial com Dra. Sonia Onuki</p><div class="ticket-details"><div><small>Participante</small><b><?=htmlspecialchars($ticket['nome'], ENT_QUOTES, 'UTF-8')?></b></div><div><small>Data</small><b>18 e 19 de setembro de 2026</b></div><div><small>Ingresso</small><b><?=$ticket['upsell']?'Ingresso + bônus':'Ingresso individual'?></b></div><div><small>Status</small><b><?=$ticket['status']==='active'?'Ativo':'Inativo'?></b></div></div></section><aside class="ticket-code"><span class="system-kicker">Entrada individual</span><img src="https://api.qrserver.com/v1/create-qr-code/?size=220x220&amp;data=<?=urlencode($ticket['hash'])?>" alt="QR Code do ingresso"><b>Apresente na entrada</b><code><?=htmlspecialchars($ticket['hash'], ENT_QUOTES, 'UTF-8')?></code></aside></article></main></body></html>