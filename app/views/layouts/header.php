<?php
require_once APP_PATH . 'views/layouts/_contrato_layout.php';

// Contrato global normalizado para header/footer.
$layoutGlobal = normalizarContratoLayoutGlobal($contratoLayoutGlobal ?? []);

// Contrato de título: manter compatibilidade com $tituloPagina legado.
$tituloDocumento = (string)($layoutGlobal['tituloDocumento'] ?? ($tituloPagina ?? 'Animal SOS'));

$navegacaoGlobal = $layoutGlobal['navegacao'];
$usuarioLogado = (bool)$layoutGlobal['sessao']['usuarioLogado'];
$usaModalAutenticacao = (bool)$layoutGlobal['modal']['usaModalAutenticacao'];

$urlHome = (string)$navegacaoGlobal['urlHome'];
$urlAnimaisReportados = (string)$navegacaoGlobal['urlAnimaisReportados'];
$urlReportarAnimal = (string)$navegacaoGlobal['urlReportarAnimal'];
$urlMeuPerfil = (string)$navegacaoGlobal['urlMeuPerfil'];
$urlLogout = (string)$navegacaoGlobal['urlLogout'];
$urlLoginHome = (string)$navegacaoGlobal['urlLoginHome'];
$urlCadastroHome = (string)$navegacaoGlobal['urlCadastroHome'];
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