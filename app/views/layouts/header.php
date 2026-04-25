<?php
// Contrato de sessão: $_SESSION['usuario_id'] é definido por AuthController no login.
$usuarioLogado = !empty($_SESSION['usuario_id']);

// Contrato de título: Controller::normalizarDadosRenderizacao() garante que
// $tituloPagina está sempre definido antes de chegar aqui.
$tituloDocumento = (string)($tituloPagina ?? 'Animal SOS');

// URLs de navegação globais (contrato atual: query string).
$urlHome              = BASE_URL . '/index.php?c=paginas&a=home';
$urlAnimaisReportados = BASE_URL . '/index.php?c=animal&a=listar';
$urlReportarAnimal    = BASE_URL . '/index.php?c=animal&a=reportar';
$urlMeuPerfil         = BASE_URL . '/index.php?c=usuario&a=meuPerfil';
$urlLogout            = BASE_URL . '/index.php?c=auth&a=logout';

// Legado compatível: modais de login/cadastro ficam na home e são abertos por âncora.
$urlLoginHome   = $urlHome . '#login';
$urlCadastroHome = $urlHome . '#cadastro';

// Contrato de modal: apenas a view da home passa usaModalAutenticacao = true.
// Para qualquer outra view, o include do partial é suprimido.
$usaModalAutenticacao = (bool)($usaModalAutenticacao ?? false);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($tituloDocumento, ENT_QUOTES, 'UTF-8') ?></title>

  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css?v=4">

  <link rel="icon" href="<?= BASE_URL ?>/assets/img/img_logo.png" type="image/png">

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display&family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
</head>
<body>

<header class="main-header">
  <div class="container header-content">

    <div class="logo">
      <img src="<?= BASE_URL ?>/assets/img/img_logo.png" class="logo-icon" alt="Ícone Animal SOS">      <span class="logo-text">Animal SOS</span>
    </div>

    <!-- Toggle do menu responsivo (sem JS) -->
    <input type="checkbox" id="menuToggle" hidden>

    <!-- Mantém as ROTAS exatamente como você já usava -->
    <nav class="navbar" aria-label="Navegação principal">
      <a href="<?= $urlHome ?>">Início</a>
      <a href="<?= $urlAnimaisReportados ?>">Animais Reportados</a>
      <a href="<?= $urlReportarAnimal ?>">Reportar Animal</a>
    </nav>

    <div class="header-actions">
      <div class="auth-buttons">
        <?php if ($usuarioLogado): ?>

          <a class="profile-chip" href="<?= $urlMeuPerfil ?>">
            <span class="profile-text">Meu Perfil</span>
          </a>

          <a class="profile-chip profile-chip--primary"
            href="<?= $urlLogout ?>">
            <span class="profile-text">Sair</span>
          </a>
        <?php else: ?>
          <a class="btn btn-login" href="<?= $urlLoginHome ?>">Login</a>
          <a class="btn btn-primary" href="<?= $urlCadastroHome ?>">Cadastrar</a>
        <?php endif; ?>

      </div>

      <!-- Botão hamburger -->
      <label for="menuToggle" class="menu-btn" aria-label="Abrir/fechar menu">
        <span></span>
        <span></span>
        <span></span>
      </label>
    </div>
    
  </div>
</header>

<?php if ($usaModalAutenticacao): ?>
<?php require APP_PATH . 'views/partials/_modais_autenticacao.php'; ?>
<?php endif; ?>

<main>