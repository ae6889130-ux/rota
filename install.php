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
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Instalador - Rota da Prosperidade</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-900 text-white min-h-screen flex items-center justify-center">
    <div class="bg-slate-800 p-8 rounded-xl shadow-2xl w-full max-w-md border border-slate-700">
        <h1 class="text-2xl font-bold mb-6 text-amber-400 text-center">Instalador do Sistema</h1>
        <?php if(isset($msg)) echo "<div class='bg-emerald-600 p-3 rounded mb-4 text-center'>$msg</div>"; ?>
        <?php if(isset($error)) echo "<div class='bg-red-600 p-3 rounded mb-4'>$error</div>"; ?>
        <?php if(!isset($msg)): ?>
        <form method="POST" class="space-y-4">
            <div><label>Host DB</label><input type="text" name="host" value="localhost" class="w-full p-2 rounded bg-slate-700 text-white" required></div>
            <div><label>Nome do Banco</label><input type="text" name="db" value="rota_prosperidade" class="w-full p-2 rounded bg-slate-700 text-white" required></div>
            <div><label>Usuário DB</label><input type="text" name="user" value="root" class="w-full p-2 rounded bg-slate-700 text-white" required></div>
            <div><label>Senha DB</label><input type="password" name="pass" class="w-full p-2 rounded bg-slate-700 text-white"></div>
            <hr class="border-slate-600 my-4">
            <div><label>Usuário Admin</label><input type="text" name="admin_user" value="admin" class="w-full p-2 rounded bg-slate-700 text-white" required></div>
            <div><label>Senha Admin</label><input type="password" name="admin_pass" class="w-full p-2 rounded bg-slate-700 text-white" required></div>
            <button type="submit" class="w-full bg-amber-500 hover:bg-amber-600 text-slate-900 font-bold py-2 rounded mt-4">Instalar Sistema</button>
        </form>
        <?php endif; ?>
    </div>
</body>
</html>
