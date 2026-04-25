<?php
declare(strict_types=1);

require_once APP_PATH . 'helpers/view_helpers.php';

$usaModalAutenticacao = (bool)($usaModalAutenticacao ?? false);

if (!$usaModalAutenticacao) {
    return;
}

$modalAbertoAtual = is_string($modalAbertoAtual ?? null) ? $modalAbertoAtual : '';

$mensagemSucessoHome = is_string($mensagemSucessoHome ?? null) ? $mensagemSucessoHome : '';
$mensagemErroLogin = is_string($mensagemErroLogin ?? null) ? $mensagemErroLogin : '';
$mensagemErroCadastro = is_string($mensagemErroCadastro ?? null) ? $mensagemErroCadastro : '';
$mensagemSucessoCadastro = is_string($mensagemSucessoCadastro ?? null) ? $mensagemSucessoCadastro : '';
?>

<?php if ($mensagemSucessoHome !== ''): ?>
  <div class="alert alert-success">
    <?= h($mensagemSucessoHome) ?>
  </div>
<?php endif; ?>

<input type="checkbox" id="loginToggle" hidden <?= ($modalAbertoAtual === 'login') ? 'checked' : '' ?>>
<input type="checkbox" id="cadastroToggle" hidden <?= ($modalAbertoAtual === 'cadastro') ? 'checked' : '' ?>>

<?php if ($modalAbertoAtual !== ''): ?>
<script>
    history.replaceState(null, '', location.pathname);
</script>
<?php endif; ?>

<div class="auth-login-sobreposicao">
  <label for="loginToggle" class="auth-login-fechar-fundo"></label>

  <div class="auth-login-modal">
    <label for="loginToggle" class="auth-login-fechar">✕</label>

    <h2>Fazer Login</h2>
    <p>Entre com seu email e senha para acessar sua conta.</p>

    <?php if ($mensagemErroLogin !== ''): ?>
      <div class="alert alert-error">
        <?= h($mensagemErroLogin) ?>
      </div>
    <?php endif; ?>

    <form action="<?= BASE_URL ?>/index.php?c=auth&a=login" method="post">
      <?= csrfInput('auth_login') ?>

      <div class="auth-login-campo">
        <label>Email</label>
        <input type="email" name="email" required>
      </div>

      <div class="auth-login-campo">
        <label>Senha</label>
        <input type="password" name="senha" required>
      </div>

      <div class="auth-login-acoes">
        <button type="submit" class="btn btn-primary">Entrar</button>
        <div class="auth-login-recuperacao">
            <a href="<?= BASE_URL ?>/index.php?c=auth&a=esqueciSenha">Esqueceu a senha?</a>
        </div>
      </div>
    </form>
  </div>
</div>

<div id="modalCadastro" class="auth-cadastro-sobreposicao">
  <div class="auth-cadastro-modal">

    <div class="auth-cadastro-cabecalho">
      <div>
        <h2>Crie sua Conta</h2>
        <?php if ($mensagemErroCadastro !== ''): ?>
          <div class="alert alert-error">
            <?= h($mensagemErroCadastro) ?>
          </div>
        <?php endif; ?>

        <?php if ($mensagemSucessoCadastro !== ''): ?>
          <div class="alert alert-success">
            <?= h($mensagemSucessoCadastro) ?>
          </div>
        <?php endif; ?>

        <p class="auth-cadastro-subtitulo">Preencha seus dados para começar.</p>
      </div>
      <label for="cadastroToggle" class="auth-cadastro-fechar" aria-label="Fechar">&times;</label>
    </div>

    <form class="auth-cadastro-formulario"
          method="POST"
          action="<?= BASE_URL ?>/index.php?c=auth&a=registro"
          enctype="multipart/form-data">
      <?= csrfInput('auth_registro') ?>

      <div class="auth-cadastro-upload auth-cadastro-upload--centralizado">
        <div class="auth-cadastro-upload-area">
          <label class="auth-cadastro-upload-rotulo">Foto de Perfil</label>

          <label class="auth-cadastro-upload-caixa auth-cadastro-upload-caixa--circular" for="foto_perfil">
            <img id="imagePreview" alt="" aria-hidden="true">
            <span id="imagePreviewText">Foto</span>
          </label>

          <input type="file" id="foto_perfil" name="foto_perfil" accept="image/*" class="auth-cadastro-arquivo-oculto">
        </div>
      </div>

      <div class="auth-cadastro-grupo">
        <label for="cad_nome">Nome Completo <span style="color:red;">*</span></label>
        <input type="text" id="cad_nome" name="nome" placeholder="Seu nome" required maxlength="255">
      </div>

      <div class="auth-cadastro-grupo">
        <label for="cad_email">E-mail <span style="color:red;">*</span></label>
        <input type="email" id="cad_email" name="email" placeholder="seu@email.com" required maxlength="255">
      </div>

      <div class="auth-cadastro-grupo">
        <label for="cad_telefone">Telefone / WhatsApp</label>
        <input type="tel" id="cad_telefone" name="telefone" placeholder="(XX) 99999-9999" maxlength="20">
      </div>

      <div class="auth-cadastro-grupo">
        <label for="cad_cep">CEP</label>
        <input type="text" id="cad_cep" name="cep" placeholder="00000-000" maxlength="9" inputmode="numeric">
        <p class="auth-cadastro-ajuda" id="cepHelp" style="display:none;"></p>
      </div>

      <div class="auth-cadastro-grupo">
        <label for="cad_rua">Rua</label>
        <input type="text" id="cad_rua" name="rua" placeholder="Nome da rua" maxlength="255">
      </div>

      <div class="auth-cadastro-grupo">
        <label for="cad_numero">Número</label>
        <input type="text" id="cad_numero" name="numero" placeholder="Número" maxlength="20">
      </div>

      <div class="auth-cadastro-grupo">
        <label for="cad_bairro">Bairro</label>
        <input type="text" id="cad_bairro" name="bairro" placeholder="Bairro" maxlength="100">
      </div>

      <div class="auth-cadastro-grupo">
        <label for="cad_cidade">Cidade</label>
        <input type="text" id="cad_cidade" name="cidade" placeholder="Cidade" maxlength="100">
      </div>

      <div class="auth-cadastro-grupo">
        <label for="cad_estado">Estado</label>
        <div class="auth-cadastro-select">
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

      <div class="auth-cadastro-grupo">
        <label for="tipo_usuario">Eu sou:</label>
        <div class="auth-cadastro-select">
          <select id="tipo_usuario" name="tipo_usuario" required>
            <option value="Comum" selected>Usuário Comum (Adotante/Apoiador)</option>
            <option value="ONG">ONG / Protetor</option>
            <option value="Autoridade">Autoridade / Órgão Público</option>
          </select>
        </div>
      </div>

      <div class="auth-cadastro-grupo">
        <label for="cad_senha">Senha <span style="color:red;">*</span></label>
        <input type="password" id="cad_senha" name="senha" placeholder="Crie uma senha segura" required>
        <p class="auth-cadastro-ajuda">Sua senha será criptografada.</p>
      </div>

      <div class="auth-cadastro-rodape">
        <label for="cadastroToggle" class="btn btn-secondary">Cancelar</label>
        <button type="submit" class="btn btn-primary btn-submit">Cadastrar</button>
      </div>
    </form>
  </div>
</div>

<script>
(function () {
  const input = document.getElementById('foto_perfil');
  const img = document.getElementById('imagePreview');
  const text = document.getElementById('imagePreviewText');
  const box = document.querySelector('#modalCadastro .auth-cadastro-upload-caixa--circular');
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

    box.classList.add('auth-cadastro-upload-caixa--com-imagem');
    text.style.display = 'none';
    img.style.display = 'block';
  });
})();
</script>

<script>
(function () {
  const alternadorLogin = document.getElementById('loginToggle');
  const alternadorCadastro = document.getElementById('cadastroToggle');

  if (!alternadorLogin || !alternadorCadastro) {
    return;
  }

  function fecharModais() {
    alternadorLogin.checked = false;
    alternadorCadastro.checked = false;
  }

  function abrirLogin(evento) {
    if (evento) evento.preventDefault();
    fecharModais();
    alternadorLogin.checked = true;
  }

  function abrirCadastro(evento) {
    if (evento) evento.preventDefault();
    fecharModais();
    alternadorCadastro.checked = true;
  }

  function aplicarHashAtual() {
    const hashAtual = (location.hash || '').toLowerCase();

    if (hashAtual === '#login') {
      abrirLogin();
    }

    if (hashAtual === '#cadastro') {
      abrirCadastro();
    }
  }

  document.querySelectorAll('a[href$="#login"]').forEach(function (linkLogin) {
    linkLogin.addEventListener('click', abrirLogin);
  });

  document.querySelectorAll('a[href$="#cadastro"]').forEach(function (linkCadastro) {
    linkCadastro.addEventListener('click', abrirCadastro);
  });

  window.addEventListener('load', aplicarHashAtual);
  window.addEventListener('hashchange', aplicarHashAtual);

  aplicarHashAtual();
})();
</script>

<script>
(function () {
  const cepInput = document.getElementById('cad_cep');
  const ruaInput = document.getElementById('cad_rua');
  const numInput = document.getElementById('cad_numero');
  const cidadeInput = document.getElementById('cad_cidade');
  const estadoSel = document.getElementById('cad_estado');
  const bairroInput = document.getElementById('cad_bairro');
  const cepHelp = document.getElementById('cepHelp');

  if (!cepInput || !ruaInput || !cidadeInput || !estadoSel) return;

  function setHelp(msg, isError) {
    if (!cepHelp) return;
    cepHelp.style.display = msg ? 'block' : 'none';
    cepHelp.textContent = msg || '';
    cepHelp.className = 'auth-cadastro-ajuda' + (isError ? ' auth-cadastro-ajuda--erro' : '');
  }

  function onlyDigits(s) {
    return (s || '').replace(/\D/g, '');
  }

  function fillAddress(data) {
    ruaInput.value = data.logradouro || '';
    if (bairroInput) bairroInput.value = data.bairro || '';
    cidadeInput.value = data.localidade || '';
    estadoSel.value = data.uf || '';

    if (numInput) numInput.focus();
  }

  async function fetchCep(cep) {
    const url = `https://viacep.com.br/ws/${cep}/json/`;
    const res = await fetch(url, { method: 'GET' });
    if (!res.ok) throw new Error('Falha ao consultar CEP.');
    return await res.json();
  }

  cepInput.addEventListener('input', function () {
    let v = onlyDigits(cepInput.value).slice(0, 8);
    if (v.length > 5) v = v.slice(0, 5) + '-' + v.slice(5);
    cepInput.value = v;
    setHelp('', false);
  });

  let lastCep = '';
  async function tryLookup() {
    const cep = onlyDigits(cepInput.value);

    if (cep.length === 0) {
      setHelp('', false);
      return;
    }

    if (cep.length !== 8) {
      setHelp('CEP incompleto.', true);
      return;
    }

    if (cep === lastCep) return;

    lastCep = cep;
    setHelp('Consultando CEP...', false);

    try {
      const data = await fetchCep(cep);

      if (data.erro) {
        setHelp('CEP não encontrado.', true);
        return;
      }

      fillAddress(data);
      setHelp('Endereço preenchido automaticamente.', false);
    } catch (e) {
      setHelp('Não foi possível consultar o CEP no momento.', true);
    }
  }

  cepInput.addEventListener('blur', tryLookup);

  cepInput.addEventListener('keyup', function () {
    if (onlyDigits(cepInput.value).length === 8) {
      tryLookup();
    }
  });
})();
</script>
