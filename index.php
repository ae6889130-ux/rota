<?php
if(!file_exists('config.php')) { header('Location: install.php'); exit; }
require 'config.php';

if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'checkout') {
    $nome = $_POST['nome'];
    $email = $_POST['email'];
    $cpf = $_POST['cpf'];
    $payment = $_POST['payment_method'];
    $upsell = isset($_POST['upsell']) ? 1 : 0;
    $hash = md5(uniqid($cpf, true));
    
    $stmt = $pdo->prepare("INSERT INTO tickets (hash, nome, email, cpf, payment_method, upsell) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$hash, $nome, $email, $cpf, $payment, $upsell]);
    
    header("Location: ticket.php?hash=$hash");
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rota da Prosperidade - Dra. Sonia Onuki</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .hero-bg { background: radial-gradient(circle at top, #11281e 0%, #0f172a 100%); }
        .glass-panel { background: rgba(30, 41, 59, 0.7); backdrop-filter: blur(10px); }
    </style>
</head>
<body class="bg-slate-900 text-slate-300 font-sans antialiased">
    <!-- Header -->
    <header class="py-6 px-4 md:px-12 flex justify-between items-center sticky top-0 bg-slate-900/90 backdrop-blur z-50 border-b border-slate-800">
        <img src="https://i.imgur.com/lZRM8gM.png" alt="Logo Rota da Prosperidade" class="h-10">
        <a href="#checkout" class="bg-amber-400 hover:bg-amber-500 text-slate-900 px-6 py-2 rounded-full font-bold transition">Garantir Vaga</a>
    </header>

    <!-- Hero Section -->
    <section class="hero-bg py-20 px-4">
        <div class="max-w-6xl mx-auto grid md:grid-cols-2 gap-12 items-center">
            <div>
                <span class="text-amber-400 font-semibold tracking-widest text-sm uppercase">Imersão Presencial • 18 e 19 Set 2026</span>
                <h1 class="text-4xl md:text-6xl font-bold text-white mt-4 mb-6 leading-tight">
                    Alinhe Identidade, Propósito e Finanças aos <span class="text-emerald-400">Planos de Deus</span>
                </h1>
                <p class="text-lg text-slate-400 mb-8">
                    Um planner que te levará a caminhar com Deus. O mapa para orientar sua nova jornada de prosperidade em 2027.
                </p>
                <a href="#checkout" class="inline-block bg-emerald-500 hover:bg-emerald-600 text-white text-xl font-bold px-8 py-4 rounded-lg shadow-lg shadow-emerald-500/30 transition">
                    Quero Transformar Meu 2027
                </a>
            </div>
            <div class="relative">
                <img src="https://i.imgur.com/RXu3kSY.jpeg" alt="Dra. Sonia Onuki" class="rounded-2xl shadow-2xl border border-slate-700 relative z-10 w-full object-cover h-[500px]">
                <div class="absolute inset-0 bg-emerald-500 blur-[100px] opacity-20 -z-10 rounded-full"></div>
            </div>
        </div>
    </section>

    <!-- O Que Você Vai Aprender -->
    <section class="py-20 px-4 bg-slate-950">
        <div class="max-w-4xl mx-auto">
            <h2 class="text-3xl font-bold text-white text-center mb-12">O Que Você Vai Aprender e Fazer?</h2>
            <div class="grid md:grid-cols-2 gap-6">
                <div class="glass-panel p-6 rounded-xl border border-slate-800">
                    <h3 class="text-amber-400 font-bold mb-2">01. Mapeamento 2026</h3>
                    <p class="text-sm">Mapeamento do seu ano de 2026, celebrar resultados e corrigir a rota.</p>
                </div>
                <div class="glass-panel p-6 rounded-xl border border-slate-800">
                    <h3 class="text-amber-400 font-bold mb-2">02. Propósito de Deus</h3>
                    <p class="text-sm">Sua prosperidade intimamente ligada ao seu propósito.</p>
                </div>
                <div class="glass-panel p-6 rounded-xl border border-slate-800">
                    <h3 class="text-amber-400 font-bold mb-2">03. Obras Consumadas</h3>
                    <p class="text-sm">Entender sua dimensão Corpo, Alma e Espírito para usufruir na totalidade.</p>
                </div>
                <div class="glass-panel p-6 rounded-xl border border-slate-800">
                    <h3 class="text-amber-400 font-bold mb-2">04. Business as Mission (BAM)</h3>
                    <p class="text-sm">Projetar planejamento nos 4 pilares: econômico, social, ambiental e espiritual.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Checkout Integrado -->
    <section id="checkout" class="py-20 px-4">
        <div class="max-w-3xl mx-auto glass-panel p-8 rounded-2xl border border-slate-700 shadow-2xl">
            <div class="text-center mb-8">
                <h2 class="text-3xl font-bold text-white mb-2">Finalize sua Inscrição</h2>
                <p>Ingresso Principal: R$ 297,00</p>
            </div>

            <form method="POST" class="space-y-6">
                <input type="hidden" name="action" value="checkout">
                
                <div class="grid md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm mb-1 text-slate-400">Nome Completo</label>
                        <input type="text" name="nome" required class="w-full bg-slate-800 border border-slate-600 rounded p-3 text-white focus:border-amber-400 outline-none">
                    </div>
                    <div>
                        <label class="block text-sm mb-1 text-slate-400">E-mail</label>
                        <input type="email" name="email" required class="w-full bg-slate-800 border border-slate-600 rounded p-3 text-white focus:border-amber-400 outline-none">
                    </div>
                </div>
                <div>
                    <label class="block text-sm mb-1 text-slate-400">CPF</label>
                    <input type="text" name="cpf" required class="w-full bg-slate-800 border border-slate-600 rounded p-3 text-white focus:border-amber-400 outline-none">
                </div>

                <!-- Order Bump / Upsell -->
                <div class="bg-emerald-900/30 border border-emerald-500/50 rounded-lg p-4 flex gap-4 items-start">
                    <input type="checkbox" name="upsell" id="upsell" class="mt-1 w-5 h-5 accent-emerald-500">
                    <label for="upsell" class="cursor-pointer">
                        <span class="block text-amber-400 font-bold">Sim, quero adicionar a Consultoria Pós-Evento (+ 60x R$ 39,00)</span>
                        <span class="text-sm text-slate-300">Acompanhamento exclusivo para aplicar o planner na prática em 2027.</span>
                    </label>
                </div>

                <!-- Pagamento -->
                <div>
                    <label class="block text-sm mb-2 text-slate-400">Método de Pagamento</label>
                    <div class="grid grid-cols-2 gap-4">
                        <label class="border border-slate-600 rounded p-4 flex items-center gap-2 cursor-pointer hover:border-amber-400 has-[:checked]:border-amber-400 has-[:checked]:bg-amber-400/10">
                            <input type="radio" name="payment_method" value="pix" checked class="accent-amber-400"> Pix (Aprovação Imediata)
                        </label>
                        <label class="border border-slate-600 rounded p-4 flex items-center gap-2 cursor-pointer hover:border-amber-400 has-[:checked]:border-amber-400 has-[:checked]:bg-amber-400/10">
                            <input type="radio" name="payment_method" value="card" class="accent-amber-400"> Cartão de Crédito
                        </label>
                    </div>
                </div>

                <button type="submit" class="w-full bg-emerald-500 hover:bg-emerald-600 text-white font-bold py-4 rounded-lg text-lg transition shadow-lg">
                    Concluir Inscrição
                </button>
            </form>
        </div>
    </section>
</body>
</html>
