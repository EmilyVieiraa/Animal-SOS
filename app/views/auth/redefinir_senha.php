<?php
declare(strict_types=1);

require_once APP_PATH . 'helpers/view_helpers.php';
?>

<div class="auth-recuperacao-pagina">

  <main class="auth-recuperacao-container">
    <h1>Redefinir Senha</h1>

    <?php if (!empty($erro)): ?>
      <p class="auth-mensagem auth-mensagem--erro"><?= h($erro) ?></p>
    <?php endif; ?>

    <?php if (!empty($token)): ?>
      <p class="auth-recuperacao-subtitulo">Defina uma nova senha para sua conta.</p>

      <form method="post" action="<?= BASE_URL ?>/index.php?c=auth&a=salvarNovaSenha" class="auth-recuperacao-formulario">
        <?= csrfInput('auth_redefinir_senha') ?>
        <input type="hidden" name="token" value="<?= h($token) ?>">

        <div class="auth-recuperacao-campo">
          <label for="senha">Nova Senha</label>
          <input id="senha" type="password" name="senha" minlength="8" required>
        </div>

        <div class="auth-recuperacao-campo">
          <label for="senha_confirmacao">Confirmar Senha</label>
          <input id="senha_confirmacao" type="password" name="senha_confirmacao" minlength="8" required>
        </div>

        <button type="submit" class="btn btn-primary">Salvar Nova Senha</button>
      </form>
    <?php else: ?>
      <p class="auth-mensagem auth-mensagem--erro">
        Token inválido ou expirado.
        <a href="<?= BASE_URL ?>/index.php?c=auth&a=esqueciSenha">Solicitar novo link</a>
      </p>
    <?php endif; ?>

    <p class="auth-recuperacao-acoes">
      <a href="<?= BASE_URL ?>/index.php?c=auth&a=login">Voltar para login</a>
    </p>
  </main>

</div>
