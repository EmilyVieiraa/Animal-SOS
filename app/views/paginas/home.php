<?php
declare(strict_types=1);

$openModal = $_SESSION['open_modal'] ?? '';
?>

<?php if (!empty($_SESSION['flash_success'])): ?>
  <div class="alert alert-success">
    <?= htmlspecialchars($_SESSION['flash_success'], ENT_QUOTES, 'UTF-8') ?>
  </div>
<?php unset($_SESSION['flash_success']); endif; ?>

<!-- toggles só na HOME -->
<input type="checkbox" id="loginToggle" hidden <?= ($openModal === 'login') ? 'checked' : '' ?>>
<input type="checkbox" id="cadastroToggle" hidden <?= ($openModal === 'cadastro') ? 'checked' : '' ?>>

<?php if ($openModal): ?>
<script>
    // Remove qualquer hash antigo (#login ou #cadastro)
    // para não sobrescrever a abertura vinda da sessão PHP
    history.replaceState(null, '', location.pathname);
</script>
<?php endif; ?>

<section class="hero" id="home">
  <div class="container hero-layout">
    <div class="hero-image-container">
      <img src="https://images.unsplash.com/photo-1623387641168-d9803ddd3f35?q=80&w=1000&auto=format&fit=crop"
           alt="Cão e Gato juntos"
           class="hero-img">
    </div>

    <div class="hero-text">
      <h1><br><span>Ajude um animal de rua</span></h1>
      <p>
        Conecte-se com a causa animal. Cadastre animais em situação de rua e ajude a encontrar lares amorosos na nossa região.
      </p>
    </div>
  </div>
</section> 

<!-- MODAL LOGIN -->
<div class="login-overlay">
  <label for="loginToggle" class="overlay-close"></label>

  <div class="login-modal">
    <label for="loginToggle" class="close-btn">✕</label>

    <h2>Fazer Login</h2>
    <p>Entre com seu email e senha para acessar sua conta.</p>

    <?php if (!empty($_SESSION['flash_error'])): ?>
      <div class="alert alert-error">
        <?= htmlspecialchars($_SESSION['flash_error']) ?>
      </div>
      <?php unset($_SESSION['flash_error']); ?>
    <?php endif; ?>

    <form action="<?= BASE_URL ?>/index.php?c=auth&a=login" method="post">
      <div class="field">
        <label>Email</label>
        <input type="email" name="email" required>
      </div>

      <div class="field">
        <label>Senha</label>
        <input type="password" name="senha" required>
      </div>

    <div class="login-actions">
        <button type="submit" class="btn btn-primary">Entrar</button>
        <div class="forgot-password">
            <a href="<?= BASE_URL ?>/index.php?c=auth&a=esqueciSenha">Esqueceu a senha?</a>
        </div>
    </div>

    </form>
  </div>
</div>

<!-- MODAL CADASTRO -->
<div id="modalCadastro" class="modal-overlay">
  <div class="modal-container">

    <div class="modal-header">
      <div>
        <h2>Crie sua Conta</h2>
        <!-- Mensagens de erro/sucesso via sessão -->
        <?php if (!empty($_SESSION['flash_registro_erro'])): ?>
          <div class="alert alert-error">
            <?= htmlspecialchars($_SESSION['flash_registro_erro']) ?>
          </div>
          <?php unset($_SESSION['flash_registro_erro']); ?>
        <?php endif; ?>

        <?php if (!empty($_SESSION['flash_registro_sucesso'])): ?>
          <div class="alert alert-success">
            <?= htmlspecialchars($_SESSION['flash_registro_sucesso']) ?>
          </div>
          <?php unset($_SESSION['flash_registro_sucesso']); ?>
        <?php endif; ?>

        <p class="modal-subtitle">Preencha seus dados para começar.</p>
      </div>
      <label for="cadastroToggle" class="close-modal" aria-label="Fechar">&times;</label>
    </div>

    <form class="modal-form"
          method="POST"
          action="<?= BASE_URL ?>/index.php?c=auth&a=registro"
          enctype="multipart/form-data">

      <div class="upload-section centered">
        <div class="upload-wrapper">
          <label class="upload-label">Foto de Perfil</label>

          <label class="upload-box circle" for="foto_perfil">
            <img id="imagePreview" alt="" aria-hidden="true">
            <span id="imagePreviewText">Foto</span>
          </label>

          <input type="file" id="foto_perfil" name="foto_perfil" accept="image/*" class="file-input-hidden">
        </div>
      </div>

      <div class="form-group">
        <label for="cad_nome">Nome Completo <span style="color:red;">*</span></label>
        <input type="text" id="cad_nome" name="nome" placeholder="Seu nome" required maxlength="255">
      </div>

      <div class="form-group">
        <label for="cad_email">E-mail <span style="color:red;">*</span></label>
        <input type="email" id="cad_email" name="email" placeholder="seu@email.com" required maxlength="255">
      </div>

      <div class="form-group">
        <label for="cad_telefone">Telefone / WhatsApp</label>
        <input type="tel" id="cad_telefone" name="telefone" placeholder="(XX) 99999-9999" maxlength="20">
      </div>

      <div class="form-group">
        <label for="cad_cep">CEP</label>
        <input type="text" id="cad_cep" name="cep" placeholder="00000-000" maxlength="9" inputmode="numeric">
        <p class="helper-text" id="cepHelp" style="display:none;"></p>
      </div>

      <div class="form-group">
        <label for="cad_rua">Rua</label>
        <input type="text" id="cad_rua" name="rua" placeholder="Nome da rua" maxlength="255">
      </div>

      <div class="form-group">
        <label for="cad_numero">Número</label>
        <input type="text" id="cad_numero" name="numero" placeholder="Número" maxlength="20">
      </div>

      <div class="form-group">
        <label for="cad_bairro">Bairro</label>
        <input type="text" id="cad_bairro" name="bairro" placeholder="Bairro" maxlength="100">
      </div>

      <div class="form-group">
        <label for="cad_cidade">Cidade</label>
        <input type="text" id="cad_cidade" name="cidade" placeholder="Cidade" maxlength="100">
      </div>

      <div class="form-group">
        <label for="cad_estado">Estado</label>
        <div class="select-wrapper">
          <select id="cad_estado" name="estado">
            <option value="">Selecione o estado</option>
            <option value="AC">Acre</option>
            <option value="AL">Alagoas</option>
            <option value="AP">Amapá</option>
            <option value="AM">Amazonas</option>
            <option value="BA">Bahia</option>
            <option value="CE">Ceará</option>
            <option value="DF">Distrito Federal</option>
            <option value="ES">Espírito Santo</option>
            <option value="GO">Goiás</option>
            <option value="MA">Maranhão</option>
            <option value="MT">Mato Grosso</option>
            <option value="MS">Mato Grosso do Sul</option>
            <option value="MG">Minas Gerais</option>
            <option value="PA">Pará</option>
            <option value="PB">Paraíba</option>
            <option value="PR">Paraná</option>
            <option value="PE">Pernambuco</option>
            <option value="PI">Piauí</option>
            <option value="RJ">Rio de Janeiro</option>
            <option value="RN">Rio Grande do Norte</option>
            <option value="RS">Rio Grande do Sul</option>
            <option value="RO">Rondônia</option>
            <option value="RR">Roraima</option>
            <option value="SC">Santa Catarina</option>
            <option value="SP">São Paulo</option>
            <option value="SE">Sergipe</option>
            <option value="TO">Tocantins</option>
          </select>
        </div>
      </div>

      <div class="form-group">
        <label for="tipo_usuario">Eu sou:</label>
        <div class="select-wrapper">
          <select id="tipo_usuario" name="tipo_usuario" required>
            <option value="Comum" selected>Usuário Comum (Adotante/Apoiador)</option>
            <option value="ONG">ONG / Protetor</option>
            <option value="Autoridade">Autoridade / Órgão Público</option>
          </select>
        </div>
      </div>

      <div class="form-group">
        <label for="cad_senha">Senha <span style="color:red;">*</span></label>
        <input type="password" id="cad_senha" name="senha" placeholder="Crie uma senha segura" required>
        <p class="helper-text">Sua senha será criptografada.</p>
      </div>

      <div class="modal-footer">
        <label for="cadastroToggle" class="btn btn-secondary">Cancelar</label>
        <button type="submit" class="btn btn-primary btn-submit">Cadastrar</button>
      </div>
    </form>
  </div>
</div>

<!-- SCRIPTS DO MODAL DE CADASTRO -->
<script>
(function () {
  const input = document.getElementById('foto_perfil');
  const img = document.getElementById('imagePreview');
  const text = document.getElementById('imagePreviewText');
  const box = document.querySelector('#modalCadastro .upload-box.circle');
  if (!input || !img || !text || !box) return;

  input.addEventListener('change', function () {
    const file = this.files && this.files[0];
    if (!file) return;

    if (!file.type.startsWith('image/')) {
      alert('Selecione uma imagem válida.');
      this.value = '';
      return;
    }

    const url = URL.createObjectURL(file);
    img.src = url;

    box.classList.add('has-image');
    text.style.display = 'none';
    img.style.display = 'block';
  });
})();
</script>

<!-- Abre modal conforme hash na URL -->
<script>
    (function(){
    function openFromHash(){
      const h = (location.hash || '').toLowerCase();
      const login = document.getElementById('loginToggle');
      const cad = document.getElementById('cadastroToggle');
      if (!login || !cad) return;

      // ⚠️ Só altera se houver hash
      if (!h) return;

      login.checked = false;
      cad.checked = false;

      if (h === '#login') login.checked = true;
      if (h === '#cadastro') cad.checked = true;
    }

    window.addEventListener('load', openFromHash);
    window.addEventListener('hashchange', openFromHash);
    })();
</script>

<!-- Script para controlar os modais via sessão / URL -->
<script>
  (function () {
  const loginToggle = document.getElementById('loginToggle');
  const cadastroToggle = document.getElementById('cadastroToggle');

  function openLogin(e){
    if (e) e.preventDefault();
    if (!loginToggle) return;
    if (cadastroToggle) cadastroToggle.checked = false;
    loginToggle.checked = true;
  }

  function openCadastro(e){
    if (e) e.preventDefault();
    if (!cadastroToggle) return;
    if (loginToggle) loginToggle.checked = false;
    cadastroToggle.checked = true;
  }

  // Abre instantaneamente ao clicar nos links do header (#login / #cadastro)
  document.querySelectorAll('a[href$="#login"]').forEach(a => a.addEventListener('click', openLogin));
  document.querySelectorAll('a[href$="#cadastro"]').forEach(a => a.addEventListener('click', openCadastro));

  // Se carregar com hash, abre também
  const h = (location.hash || '').toLowerCase();
  if (h === '#login') openLogin();
  if (h === '#cadastro') openCadastro();

  // =========================
  // NOVO: abrir por querystring
  // Ex.: ?open=login  ou ?open=cadastro (ou ?open=registro)
  // =========================
  const params = new URLSearchParams(window.location.search);
  const open = (params.get('open') || '').toLowerCase();

  if (open === 'login') openLogin();
  if (open === 'cadastro' || open === 'registro') openCadastro();

})();
</script>

<!-- Script para buscar endereço via CEP -->
<script>
(function () {
  const cepInput   = document.getElementById('cad_cep');
  const ruaInput   = document.getElementById('cad_rua');
  const numInput   = document.getElementById('cad_numero');
  const cidadeInput= document.getElementById('cad_cidade');
  const estadoSel  = document.getElementById('cad_estado');
  const bairroInput= document.getElementById('cad_bairro'); // opcional
  const cepHelp    = document.getElementById('cepHelp');

  if (!cepInput || !ruaInput || !cidadeInput || !estadoSel) return;

  function setHelp(msg, isError){
    if (!cepHelp) return;
    cepHelp.style.display = msg ? 'block' : 'none';
    cepHelp.textContent = msg || '';
    cepHelp.className = 'helper-text' + (isError ? ' helper-error' : '');
  }

  function onlyDigits(s){ return (s || '').replace(/\D/g, ''); }

  function fillAddress(data){
    // ViaCEP: logradouro, bairro, localidade, uf
    ruaInput.value = data.logradouro || '';
    if (bairroInput) bairroInput.value = data.bairro || '';
    cidadeInput.value = data.localidade || '';
    estadoSel.value = data.uf || '';

    // Coloca foco no número (normalmente é o que falta)
    if (numInput) numInput.focus();
  }

  async function fetchCep(cep){
    const url = `https://viacep.com.br/ws/${cep}/json/`;
    const res = await fetch(url, { method: 'GET' });
    if (!res.ok) throw new Error('Falha ao consultar CEP.');
    return await res.json();
  }

  // Máscara simples: 00000-000
  cepInput.addEventListener('input', function(){
    let v = onlyDigits(cepInput.value).slice(0, 8);
    if (v.length > 5) v = v.slice(0,5) + '-' + v.slice(5);
    cepInput.value = v;
    setHelp('', false);
  });

  // Consulta ao sair do campo (blur) ou quando completar 8 dígitos
  let lastCep = '';
  async function tryLookup(){
    const cep = onlyDigits(cepInput.value);

    if (cep.length === 0) { setHelp('', false); return; }
    if (cep.length !== 8) { setHelp('CEP incompleto.', true); return; }
    if (cep === lastCep) return;

    lastCep = cep;
    setHelp('Consultando CEP...', false);

    try{
      const data = await fetchCep(cep);

      if (data.erro) {
        setHelp('CEP não encontrado.', true);
        return;
      }

      fillAddress(data);
      setHelp('Endereço preenchido automaticamente.', false);

    } catch (e){
      setHelp('Não foi possível consultar o CEP no momento.', true);
    }
  }

  cepInput.addEventListener('blur', tryLookup);

  // Opcional: consultar automaticamente ao completar 9 chars (00000-000)
  cepInput.addEventListener('keyup', function(){
    if (onlyDigits(cepInput.value).length === 8) {
      tryLookup();
    }
  });
})();
</script>

<?php unset($_SESSION['open_modal']); ?>