<?php
declare(strict_types=1);

require_once APP_PATH . 'helpers/view_helpers.php';
?>

<div class="auth-acesso-pagina">
  <main class="auth-acesso-cartao auth-acesso-cartao--login">
    <h1 class="auth-acesso-titulo">Login</h1>
    <p class="auth-acesso-subtitulo">Entre com seu e-mail e senha para acessar sua conta.</p>

    <?php if (!empty($sucesso)): ?>
      <p class="auth-mensagem auth-mensagem--sucesso">
        <?= htmlspecialchars($sucesso, ENT_QUOTES, 'UTF-8') ?>
      </p>
    <?php endif; ?>

    <?php if (!empty($erro)): ?>
      <p class="auth-mensagem auth-mensagem--erro"><?= h($erro) ?></p>
    <?php endif; ?>

    <form method="post" action="<?= BASE_URL ?>/index.php?c=auth&a=login" class="auth-acesso-formulario">
      <?= csrfInput('auth_login') ?>
      <div class="auth-acesso-campo">
        <label for="login_email">E-mail</label>
        <input id="login_email" type="email" name="email" required>
      </div>

      <div class="auth-acesso-campo">
        <label for="login_senha">Senha</label>
        <input id="login_senha" type="password" name="senha" required>
      </div>

      <button type="submit" class="btn btn-primary">Entrar</button>
    </form>

    <p class="auth-acesso-links">
      <a href="<?= BASE_URL ?>/index.php?c=paginas&a=home#cadastro">Cadastrar</a>
      <span aria-hidden="true">|</span>
      <a href="<?= BASE_URL ?>/esqueci">Esqueci a senha</a>
    </p>
  </main>
</div>
