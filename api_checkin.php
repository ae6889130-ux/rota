<?php
session_start();
require 'config.php';

if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['hash'])) {
    $hash = $_POST['hash'];
    $stmt = $pdo->prepare("SELECT * FROM tickets WHERE hash = ?");
    $stmt->execute([$hash]);
    $ticket = $stmt->fetch();
    
    if(!$ticket || $ticket['status'] == 'inactive') {
        echo json_encode(['status' => 'invalid']);
        exit;
    }
    
    if($ticket['checked_in']) {
        echo json_encode(['status' => 'already', 'nome' => $ticket['nome']]);
        exit;
    }
    
    $pdo->prepare("UPDATE tickets SET checked_in = 1, checkin_time = NOW() WHERE id = ?")->execute([$ticket['id']]);
    echo json_encode(['status' => 'success', 'nome' => $ticket['nome']]);
    exit;
}
