<?php
require 'config.php';
if(!isset($_GET['hash'])) { die('Ingresso inválido.'); }

$stmt = $pdo->prepare("SELECT * FROM tickets WHERE hash = ?");
$stmt->execute([$_GET['hash']]);
$ticket = $stmt->fetch();

if(!$ticket) { die('Ingresso não encontrado.'); }
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Ingresso - <?= htmlspecialchars($ticket['nome']) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Cinzel:wght@700&family=Inter:wght@400;600&display=swap');
        body { background: #0f172a; display: flex; justify-content: center; align-items: center; min-height: 100vh; font-family: 'Inter', sans-serif; }
        .ticket-box {
            background: #fff;
            width: 800px;
            max-width: 95%;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 25px 50px -12px rgba(0,0,0,0.5);
            display: flex;
            position: relative;
        }
        .ticket-left { background: #022c22; color: #fff; padding: 40px; flex: 1; border-right: 2px dashed #0f172a; position: relative; }
        .ticket-left::after, .ticket-left::before { content: ''; position: absolute; right: -15px; width: 30px; height: 30px; background: #0f172a; border-radius: 50%; }
        .ticket-left::before { top: -15px; }
        .ticket-left::after { bottom: -15px; }
        .ticket-right { background: #f8fafc; padding: 40px; width: 250px; display: flex; flex-direction: column; align-items: center; justify-content: center; color: #0f172a;}
        
        @media print {
            body { background: white; align-items: flex-start; padding: 0; }
            .no-print { display: none !important; }
            .ticket-box { box-shadow: none; border: 1px solid #ccc; width: 100%; max-width: none; border-radius: 0;}
            .ticket-left::after, .ticket-left::before { display: none; }
            @page { margin: 10mm; }
        }
    </style>
</head>
<body>
    <div class="text-center w-full">
        <button onclick="window.print()" class="no-print bg-amber-500 text-slate-900 font-bold px-6 py-2 rounded mb-6 hover:bg-amber-600">🖨️ Baixar PDF / Imprimir</button>
        
        <div class="ticket-box mx-auto text-left">
            <div class="ticket-left">
                <img src="https://i.imgur.com/lZRM8gM.png" alt="Logo" class="h-12 mb-6 filter drop-shadow-md">
                <h1 class="text-3xl font-bold font-['Cinzel'] text-amber-400 mb-1">ROTA DA PROSPERIDADE</h1>
                <p class="text-emerald-300 font-semibold mb-8 uppercase tracking-widest text-xs">Imersão Presencial com Dra. Sonia Onuki</p>
                
                <div class="mb-4">
                    <p class="text-slate-400 text-xs uppercase">Participante</p>
                    <p class="text-2xl font-bold"><?= htmlspecialchars($ticket['nome']) ?></p>
                </div>
                
                <div class="flex gap-8 mb-6">
                    <div>
                        <p class="text-slate-400 text-xs uppercase">Data</p>
                        <p class="font-semibold">18 e 19 Set 2026</p>
                    </div>
                    <div>
                        <p class="text-slate-400 text-xs uppercase">Tipo</p>
                        <p class="font-semibold"><?= $ticket['upsell'] ? 'VIP + Consultoria' : 'Padrão' ?></p>
                    </div>
                </div>
            </div>
            
            <div class="ticket-right text-center">
                <p class="text-xs text-slate-500 uppercase font-bold mb-2">Entrada Exclusiva</p>
                <!-- QR Code via API -->
                <img src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=<?= $ticket['hash'] ?>" alt="QR Code" class="w-32 h-32 mb-4 border-4 border-white shadow-sm">
                <p class="text-[10px] text-slate-400 break-all w-full"><?= $ticket['hash'] ?></p>
                <p class="text-xs font-bold mt-2 text-emerald-700">STATUS: <?= strtoupper($ticket['status']) ?></p>
            </div>
        </div>
    </div>
</body>
</html>
