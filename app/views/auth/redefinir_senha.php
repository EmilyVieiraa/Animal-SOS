<?php
declare(strict_types=1);
function h($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
?>

<div class="forgot-page">

  <main class="forgot-container">
    <h1>Redefinir Senha</h1>

    <?php if (!empty($erro)): ?>
      <p class="msg msg-error"><?= h($erro) ?></p>
    <?php endif; ?>

    <?php if (!empty($token)): ?>
      <p class="forgot-subtitle">Defina uma nova senha para sua conta.</p>

      <form method="post" action="<?= BASE_URL ?>/index.php?c=auth&a=salvarNovaSenha" class="forgot-form">
        <input type="hidden" name="token" value="<?= h($token) ?>">

        <div>
          <label for="senha">Nova Senha</label><br>
          <input id="senha" type="password" name="senha" minlength="8" required>
        </div>

        <div>
          <label for="senha_confirmacao">Confirmar Senha</label><br>
          <input id="senha_confirmacao" type="password" name="senha_confirmacao" minlength="8" required>
        </div>

        <button type="submit" class="btn btn-primary">Salvar Nova Senha</button>
      </form>
    <?php else: ?>
      <p class="msg msg-error">Token inválido ou expirado. <a href="<?= BASE_URL ?>/index.php?c=auth&a=esqueciSenha">Solicitar novo link</a></p>
    <?php endif; ?>

    <p class="forgot-actions">
      <a href="<?= BASE_URL ?>/index.php?c=auth&a=login">Voltar para login</a>
    </p>
  </main>

</div>
