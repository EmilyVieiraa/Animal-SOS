<?php
declare(strict_types=1);
function h($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
?>

<div class="forgot-page">

  <main class="forgot-container">
    <h1>Esqueci minha senha</h1>

    <?php if (!empty($erro)): ?>
      <p class="msg msg-error"><?= h($erro) ?></p>
    <?php endif; ?>

    <?php if (!empty($sucesso)): ?>
      <p class="msg msg-success"><?= h($sucesso) ?></p>

      <?php if (!empty($debug_link)): ?>
        <p class="debug-link">
          <strong>Link (teste local):</strong>
          <a href="<?= h($debug_link) ?>"><?= h($debug_link) ?></a>
        </p>
      <?php endif; ?>

      <p class="forgot-actions">
        <a href="<?= BASE_URL ?>/login">Voltar para login</a>
      </p>

    <?php else: ?>

      <p class="forgot-subtitle">Informe seu e-mail para receber um link de redefinição.</p>

      <form method="post" class="forgot-form">
        <label for="email">E-mail</label>
        <input id="email" type="email" name="email" required>
        <button type="submit" class="btn btn-primary">Enviar</button>
      </form>

      <p class="forgot-actions">
        <a href="<?= BASE_URL ?>/login">Voltar para login</a>
      </p>

    <?php endif; ?>
  </main>

</div>
