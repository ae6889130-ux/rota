<?php
session_start();
require 'config.php';

// Auth Check
if(!isset($_SESSION['admin_logged'])) {
    if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['login'])) {
        $stmt = $pdo->prepare("SELECT * FROM admin WHERE username = ?");
        $stmt->execute([$_POST['username']]);
        $admin = $stmt->fetch();
        if($admin && password_verify($_POST['password'], $admin['password'])) {
            $_SESSION['admin_logged'] = true;
            header("Location: admin.php"); exit;
        } else {
            $error = "Login inválido.";
        }
    }
}

// Action Handlers
if(isset($_SESSION['admin_logged']) && isset($_GET['action'])) {
    if($_GET['action'] == 'logout') { session_destroy(); header("Location: admin.php"); exit; }
    if($_GET['action'] == 'toggle_status') {
        $pdo->prepare("UPDATE tickets SET status = IF(status='active', 'inactive', 'active') WHERE id = ?")->execute([$_GET['id']]);
        header("Location: admin.php"); exit;
    }
    if($_GET['action'] == 'checkin') {
        $pdo->prepare("UPDATE tickets SET checked_in = 1, checkin_time = NOW() WHERE id = ?")->execute([$_GET['id']]);
        header("Location: admin.php"); exit;
    }
}

// Manual Add
if(isset($_SESSION['admin_logged']) && $_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_ticket'])) {
    $hash = md5(uniqid($_POST['cpf'], true));
    $stmt = $pdo->prepare("INSERT INTO tickets (hash, nome, email, cpf, payment_method) VALUES (?, ?, ?, ?, 'manual')");
    $stmt->execute([$hash, $_POST['nome'], $_POST['email'], $_POST['cpf']]);
    header("Location: admin.php?msg=Ingresso+Adicionado"); exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Admin - Rota da Prosperidade</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-100 text-slate-800">
    <?php if(!isset($_SESSION['admin_logged'])): ?>
    <!-- Login Page -->
    <div class="min-h-screen flex items-center justify-center bg-slate-900">
        <form method="POST" class="bg-white p-8 rounded shadow-lg w-96">
            <h2 class="text-2xl font-bold mb-4 text-center">Login Administrativo</h2>
            <?php if(isset($error)) echo "<p class='text-red-500 mb-2'>$error</p>"; ?>
            <input type="hidden" name="login" value="1">
            <input type="text" name="username" placeholder="Usuário" class="w-full p-2 border rounded mb-4" required>
            <input type="password" name="password" placeholder="Senha" class="w-full p-2 border rounded mb-4" required>
            <button class="w-full bg-emerald-600 text-white p-2 rounded">Entrar</button>
        </form>
    </div>
    <?php else: ?>
    <!-- Dashboard -->
    <div class="flex h-screen overflow-hidden">
        <!-- Sidebar -->
        <div class="w-64 bg-slate-900 text-white flex flex-col">
            <div class="p-4 font-bold text-xl border-b border-slate-700 text-amber-400">Admin Panel</div>
            <nav class="flex-1 p-4 space-y-2">
                <a href="admin.php" class="block p-2 bg-slate-800 rounded">Ingressos</a>
                <a href="scanner.php" target="_blank" class="block p-2 hover:bg-slate-800 rounded">Scanner QR Code</a>
            </nav>
            <a href="admin.php?action=logout" class="p-4 bg-red-600 text-center hover:bg-red-700">Sair</a>
        </div>
        
        <!-- Main Content -->
        <div class="flex-1 flex flex-col h-screen overflow-y-auto p-8">
            <div class="flex justify-between items-center mb-8">
                <h1 class="text-3xl font-bold text-slate-800">Gestão de Ingressos</h1>
                <button onclick="document.getElementById('modal-add').classList.remove('hidden')" class="bg-emerald-600 text-white px-4 py-2 rounded shadow hover:bg-emerald-700">+ Adicionar Manual</button>
            </div>
            
            <?php if(isset($_GET['msg'])) echo "<div class='bg-green-200 text-green-800 p-3 rounded mb-4'>".$_GET['msg']."</div>"; ?>
            
            <!-- Table -->
            <div class="bg-white rounded shadow overflow-hidden">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-200 text-slate-600 text-sm uppercase">
                            <th class="p-3">ID / Hash</th>
                            <th class="p-3">Nome / CPF</th>
                            <th class="p-3">Status</th>
                            <th class="p-3">Check-in</th>
                            <th class="p-3">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="text-sm">
                        <?php
                        $tickets = $pdo->query("SELECT * FROM tickets ORDER BY id DESC")->fetchAll();
                        foreach($tickets as $t):
                        ?>
                        <tr class="border-b hover:bg-slate-50">
                            <td class="p-3 font-mono text-xs"><?= substr($t['hash'], 0, 8) ?>...</td>
                            <td class="p-3"><strong><?= htmlspecialchars($t['nome']) ?></strong><br><span class="text-slate-500"><?= htmlspecialchars($t['cpf']) ?></span></td>
                            <td class="p-3">
                                <span class="px-2 py-1 rounded text-xs text-white <?= $t['status']=='active'?'bg-green-500':'bg-red-500' ?>"><?= strtoupper($t['status']) ?></span>
                            </td>
                            <td class="p-3">
                                <?php if($t['checked_in']): ?>
                                    <span class="text-green-600 font-bold">✓ <?= date('H:i d/m', strtotime($t['checkin_time'])) ?></span>
                                <?php else: ?>
                                    <span class="text-slate-400">Pendente</span>
                                <?php endif; ?>
                            </td>
                            <td class="p-3 space-x-2">
                                <a href="ticket.php?hash=<?= $t['hash'] ?>" target="_blank" class="text-blue-500 hover:underline">Ver PDF</a>
                                <a href="admin.php?action=toggle_status&id=<?= $t['id'] ?>" class="text-orange-500 hover:underline"><?= $t['status']=='active'?'Inativar':'Ativar' ?></a>
                                <?php if(!$t['checked_in'] && $t['status']=='active'): ?>
                                    <a href="admin.php?action=checkin&id=<?= $t['id'] ?>" class="bg-blue-100 text-blue-700 px-2 py-1 rounded text-xs hover:bg-blue-200">Forçar Checkin</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <!-- Modal Add Manual -->
    <div id="modal-add" class="fixed inset-0 bg-black/50 hidden flex items-center justify-center">
        <form method="POST" class="bg-white p-6 rounded shadow-lg w-96 relative">
            <button type="button" onclick="document.getElementById('modal-add').classList.add('hidden')" class="absolute top-2 right-2 text-slate-500 text-xl">&times;</button>
            <h3 class="font-bold text-lg mb-4">Adicionar Ingresso</h3>
            <input type="hidden" name="add_ticket" value="1">
            <input type="text" name="nome" placeholder="Nome Completo" required class="w-full p-2 border rounded mb-3">
            <input type="email" name="email" placeholder="E-mail" required class="w-full p-2 border rounded mb-3">
            <input type="text" name="cpf" placeholder="CPF" required class="w-full p-2 border rounded mb-4">
            <button class="w-full bg-emerald-600 text-white p-2 rounded">Gerar Ingresso</button>
        </form>
    </div>
    <?php endif; ?>
</body>
</html>
