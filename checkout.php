<?php
require __DIR__.'/app.php';
app_config();
$error='';
$salesOpen=setting('sales_active','1')==='1';
if(!$salesOpen) http_response_code(503);

// Função robusta para validação matemática do CPF
function validaCPF($cpf) {
    $c = preg_replace('/\D/', '', $cpf);
    if (strlen($c) != 11 || preg_match("/^{$c[0]}{11}$/", $c)) return false;
    for ($s = 10, $n = 0, $i = 0; $s >= 2; $n += $c[$i++] * $s--);
    if ($c[9] != ((($n %= 11) < 2) ? 0 : 11 - $n)) return false;
    for ($s = 11, $n = 0, $i = 0; $s >= 2; $n += $c[$i++] * $s--);
    if ($c[10] != ((($n %= 11) < 2) ? 0 : 11 - $n)) return false;
    return true;
}

function old(string $key):string{return e($_POST[$key]??'');}

if($_SERVER['REQUEST_METHOD']==='POST' && $salesOpen){
    check_csrf();
    try {
        $name=trim($_POST['name']??'');
        $email=filter_var($_POST['email']??'',FILTER_VALIDATE_EMAIL);
        $cpf=preg_replace('/\D/','',$_POST['cpf']??'');
        $phone=preg_replace('/\D/','',$_POST['phone']??'');
        $postalCode=preg_replace('/\D/','',$_POST['postal_code']??'');
        $street=trim($_POST['street']??'');
        $district=trim($_POST['district']??'');
        $city=trim($_POST['city']??'');
        $state=strtoupper(trim($_POST['state']??''));
        $number=trim($_POST['address_number']??'');
        
        if(!$name || !$email || strlen($phone)<10) throw new Exception('Confira seus dados pessoais e tente novamente.');
        if(!validaCPF($cpf)) throw new Exception('O CPF informado é inválido. Verifique os números digitados.');
        if(strlen($postalCode)!==8 || !$street || !$district || !$city || strlen($state)!==2 || !$number) throw new Exception('Preencha o endereço completo, incluindo CEP, número, cidade e estado.');
        if(!in_array($_POST['method']??'',['pix','card'],true)) throw new Exception('Selecione uma forma de pagamento.');
        
        $upsell=!empty($_POST['upsell']); $amount=297+($upsell?39:0); $code=ticket_code();
        $stmt=db()->prepare('INSERT INTO orders(name,email,cpf,phone,street,district,postal_code,city,state,address_number,payment_method,status,amount,ticket_code,upsell,created_at) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
        $stmt->execute([$name,$email,$cpf,$phone,$street,$district,$postalCode,$city,$state,$number,$_POST['method'],'pending',$amount,$code,$upsell?1:0,date('Y-m-d H:i:s')]);
        header('Location: payment.php?id='.(int)db()->lastInsertId()); exit;
    } catch(Throwable $e){$error=$e->getMessage();}
}
?>
<!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1"><title>Inscrição — Rota da Prosperidade</title><link rel="stylesheet" href="assets/style.css">
<style>
  /* Trava de tamanho exclusiva para a logo do cabeçalho */
  .site-logo { max-height: 50px; max-width: 100%; width: auto; height: auto; object-fit: contain; display: block; }

  /* DESTAQUE DO UPSELL */
  .upsell { display: flex; align-items: center; padding: 24px 20px; background: #fdfdf5; border: 2px dashed #eab308; border-radius: 12px; margin: 35px 0 25px 0; cursor: pointer; position: relative; transition: all 0.3s ease; box-shadow: 0 4px 15px rgba(234, 179, 8, 0.1); }
  .upsell:hover { background: #fefce8; transform: translateY(-2px); }
  .upsell:has(input:checked) { background: #f0fdf4; border: 2px solid #22c55e; box-shadow: 0 4px 20px rgba(34, 197, 94, 0.15); }
  .upsell::before { content: 'OFERTA ESPECIAL'; position: absolute; top: -14px; left: 20px; background: #eab308; color: #fff; font-size: 0.75rem; font-weight: 800; padding: 6px 12px; border-radius: 20px; letter-spacing: 0.5px; text-transform: uppercase; box-shadow: 0 2px 8px rgba(234, 179, 8, 0.3); }
  .upsell:has(input:checked)::before { content: 'ADICIONADO!'; background: #22c55e; box-shadow: 0 2px 8px rgba(34, 197, 94, 0.3); }
  .upsell input[type="checkbox"] { width: 24px; height: 24px; margin-right: 15px; accent-color: #22c55e; cursor: pointer; flex-shrink: 0; }
  .upsell span { flex: 1; }
  .upsell span b { display: block; font-size: 1.05rem; color: #111827; margin-bottom: 4px; }
  .upsell span small { display: block; font-size: 0.85rem; color: #4b5563; line-height: 1.4; }
  .upsell strong { font-size: 1.25rem; color: #166534; margin-left: 15px; flex-shrink: 0; }
  
  @media (max-width: 600px) {
    .upsell { flex-direction: column; align-items: flex-start; padding-top: 30px; }
    .upsell input[type="checkbox"] { position: absolute; left: 20px; top: 32px; }
    .upsell span { padding-left: 35px; }
    .upsell strong { margin-left: 35px; margin-top: 10px; display: block; }
  }
</style>
</head>
<body class="checkout-page checkout-premium">
<header class="nav checkout-nav"><a href="index.php"><img class="site-logo" src="https://i.imgur.com/lZRM8gM.png" alt="Rota da Prosperidade"></a><small>● AMBIENTE SEGURO</small></header>
<main class="checkout-wrap">
<section class="checkout-panel"><span class="eyebrow">ÚLTIMO PASSO</span>
<?php if(!$salesOpen):?><div class="sales-closed"><h2>Inscrições temporariamente encerradas</h2><p>Entre em contato com a organização para verificar novas vagas.</p><a href="index.php">Voltar ao evento</a></div>
<?php else:?><h1>Garanta sua vaga</h1><p>Preencha seus dados para receber o ingresso digital.</p><?php if($error):?><div class="error"><?=e($error)?></div><?php endif;?>
<form method="post" class="checkout-form"><input type="hidden" name="csrf" value="<?=csrf()?>">
<h3>Seus dados</h3><label>Nome completo<input name="name" required autocomplete="name" value="<?=old('name')?>" placeholder="Como deseja ser chamado"></label><label>E-mail<input name="email" type="email" required autocomplete="email" value="<?=old('email')?>" placeholder="Onde receberá o ingresso"></label><div class="two"><label>CPF<input name="cpf" id="cpf_input" required inputmode="numeric" maxlength="14" value="<?=old('cpf')?>" placeholder="000.000.000-00"></label><label>WhatsApp<input name="phone" required inputmode="tel" value="<?=old('phone')?>" placeholder="(00) 00000-0000"></label></div>
<h3>Endereço completo</h3><div class="address-grid"><label class="cep">CEP<input name="postal_code" id="postal_code" required inputmode="numeric" maxlength="9" value="<?=old('postal_code')?>" placeholder="00000-000"></label><label class="street">Nome da rua<input name="street" id="street" required autocomplete="address-line1" value="<?=old('street')?>" placeholder="Rua, avenida ou travessa"></label><label class="number">Número<input name="address_number" required value="<?=old('address_number')?>" placeholder="123 ou S/N"></label><label class="district">Bairro<input name="district" id="district" required value="<?=old('district')?>" placeholder="Seu bairro"></label><label class="city">Cidade<input name="city" id="city" required value="<?=old('city')?>" placeholder="Sua cidade"></label><label class="state">Estado<input name="state" id="state" required maxlength="2" value="<?=old('state')?>" placeholder="UF"></label></div>
<label class="upsell"><input type="checkbox" name="upsell" value="1" <?=!empty($_POST['upsell'])?'checked':''?>><span><b>Adicionar e-book + aulas online Costura Bolso</b><small>Conteúdo prático para organizar finanças, prosperidade e rotina.</small></span><strong>+ R$ 39</strong></label>
<h3>Como deseja pagar?</h3><label class="pay"><input type="radio" name="method" value="pix" checked><b>PIX</b><span>Aprovação rápida</span></label><label class="pay"><input type="radio" name="method" value="card"><b>Cartão</b><span>Link de pagamento seguro</span></label><button class="button">Continuar para pagamento →</button></form><?php endif;?></section>
<aside class="summary"><span>SEU PEDIDO</span><h2>Rota da Prosperidade</h2><p>18 e 19 de setembro de 2026</p><hr><div><span>Ingresso individual</span><b>R$ 297,00</b></div><div class="secure-note">✓ Dados protegidos<br>✓ Ingresso digital com QR Code<br>✓ Confirmação por e-mail</div></aside>
</main>
<script>
// Máscara de CPF para facilitar a digitação
document.querySelector('#cpf_input')?.addEventListener('input', function(e) {
    let v = e.target.value.replace(/\D/g, '');
    if (v.length > 11) v = v.slice(0, 11);
    v = v.replace(/(\d{3})(\d)/, '$1.$2');
    v = v.replace(/(\d{3})(\d)/, '$1.$2');
    v = v.replace(/(\d{3})(\d{1,2})$/, '$1-$2');
    e.target.value = v;
});

// Autocompletar CEP
document.querySelector('#postal_code')?.addEventListener('blur',async e=>{const cep=e.target.value.replace(/\D/g,'');if(cep.length!==8)return;try{const r=await fetch('https://viacep.com.br/ws/'+cep+'/json/');const d=await r.json();if(!d.erro){street.value=d.logradouro;district.value=d.bairro;city.value=d.localidade;state.value=d.uf}}catch(_){}});
</script>
</body></html>