<?php
declare(strict_types=1);
session_start();
header('Content-Type: application/json; charset=UTF-8');
if (empty($_SESSION['admin_logged']) || $_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(403); echo json_encode(['status'=>'forbidden']); exit; }
require __DIR__.'/config.php';
$hash=trim((string)($_POST['hash']??''));
if ($hash==='') { http_response_code(422); echo json_encode(['status'=>'invalid']); exit; }
$stmt=$pdo->prepare('SELECT * FROM tickets WHERE hash=?');$stmt->execute([$hash]);$ticket=$stmt->fetch();
if(!$ticket||$ticket['status']==='inactive'){echo json_encode(['status'=>'invalid']);exit;}
if($ticket['checked_in']){echo json_encode(['status'=>'already','nome'=>$ticket['nome']]);exit;}
$pdo->prepare('UPDATE tickets SET checked_in=1,checkin_time=NOW() WHERE id=?')->execute([$ticket['id']]);
echo json_encode(['status'=>'success','nome'=>$ticket['nome']]);
