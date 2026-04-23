<?php
declare(strict_types=1);

require_once APP_PATH . 'helpers/view_helpers.php';
?>

<div class="auth-recuperacao-pagina">

  <main class="auth-recuperacao-container">
    <h1>Esqueci minha senha</h1>

    <?php if (!empty($erro)): ?>
      <p class="auth-mensagem auth-mensagem--erro"><?= h($erro) ?></p>
    <?php endif; ?>

    <?php if (!empty($sucesso)): ?>
      <p class="auth-mensagem auth-mensagem--sucesso"><?= h($sucesso) ?></p>

      <?php if (!empty($debug_link)): ?>
        <p class="auth-recuperacao-link-debug">
          <a href="<?= h((string)$debug_link) ?>"><?= h((string)$debug_link) ?></a>
        </p>
      <?php endif; ?>

      <p class="auth-recuperacao-acoes">
        <a href="<?= BASE_URL ?>/login">Voltar para login</a>
      </p>

    <?php else: ?>

      <p class="auth-recuperacao-subtitulo">Informe seu e-mail para receber um link de redefinição.</p>

      <form method="post" class="auth-recuperacao-formulario">
        <?= csrfInput('auth_esqueci_senha') ?>
        <label for="email">E-mail</label>
        <input id="email" type="email" name="email" required>
        <button type="submit" class="btn btn-primary">Enviar</button>
      </form>

      <p class="auth-recuperacao-acoes">
        <a href="<?= BASE_URL ?>/login">Voltar para login</a>
      </p>

    <?php endif; ?>
  </main>

</div>
