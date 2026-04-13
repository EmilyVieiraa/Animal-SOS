<?php
$logado = !empty($_SESSION['usuario_id']);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($title ?? 'Animal SOS', ENT_QUOTES, 'UTF-8') ?></title>

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
      <a href="<?= BASE_URL ?>/index.php?c=paginas&a=home">Início</a>
      <a href="<?= BASE_URL ?>/index.php?c=animal&a=listar">Animais Reportados</a>
      <a href="<?= BASE_URL ?>/index.php?c=animal&a=reportar">Reportar Animal</a>
    </nav>

    <div class="header-actions">
      <div class="auth-buttons">
        <?php if ($logado): ?>

          <a class="profile-chip" href="<?= BASE_URL ?>/index.php?c=usuario&a=meuPerfil">
            <span class="profile-text">Meu Perfil</span>
          </a>

          <a class="profile-chip profile-chip--primary"
            href="<?= BASE_URL ?>/index.php?c=auth&a=logout">
            <span class="profile-text">Sair</span>
          </a>
        <?php else: ?>
          <a class="btn btn-login" href="<?= BASE_URL ?>/index.php?c=paginas&a=home#login">Login</a>
          <a class="btn btn-primary" href="<?= BASE_URL ?>/index.php?c=paginas&a=home#cadastro">Cadastrar</a>
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

<main>