<?php
session_start();
require 'config.php';
if(!isset($_SESSION['admin_logged'])) { die('Acesso negado. Logue no admin primeiro.'); }
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Scanner QR - Rota da Prosperidade</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/html5-qrcode"></script>
</head>
<body class="bg-slate-900 text-white h-screen flex flex-col items-center py-10">
    <h1 class="text-2xl font-bold mb-4 text-emerald-400">Scanner de Ingressos</h1>
    
    <div id="reader" class="w-full max-w-md bg-white text-black rounded shadow-lg overflow-hidden border-4 border-slate-700"></div>
    
    <div id="result" class="mt-6 text-xl font-bold p-4 text-center rounded hidden w-full max-w-md"></div>
    <a href="admin.php" class="mt-8 text-slate-400 underline">Voltar ao Painel</a>

    <script>
        const html5QrcodeScanner = new Html5QrcodeScanner("reader", { fps: 10, qrbox: {width: 250, height: 250} }, false);
        
        let isProcessing = false;

        function onScanSuccess(decodedText, decodedResult) {
            if(isProcessing) return;
            isProcessing = true;
            
            fetch('api_checkin.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'hash=' + encodeURIComponent(decodedText)
            })
            .then(res => res.json())
            .then(data => {
                const resDiv = document.getElementById('result');
                resDiv.classList.remove('hidden', 'bg-green-500', 'bg-red-500', 'bg-orange-500');
                
                if(data.status === 'success') {
                    resDiv.classList.add('bg-green-500');
                    resDiv.innerHTML = '✅ CHECK-IN LIBERADO<br><span class="text-sm font-normal">' + data.nome + '</span>';
                } else if(data.status === 'already') {
                    resDiv.classList.add('bg-orange-500');
                    resDiv.innerHTML = '⚠️ JÁ FEZ CHECK-IN<br><span class="text-sm font-normal">' + data.nome + '</span>';
                } else {
                    resDiv.classList.add('bg-red-500');
                    resDiv.innerHTML = '❌ INGRESSO INVÁLIDO OU INATIVO';
                }
                
                setTimeout(() => { isProcessing = false; resDiv.classList.add('hidden'); }, 3000);
            });
        }

        html5QrcodeScanner.render(onScanSuccess);
    </script>
</body>
</html>
