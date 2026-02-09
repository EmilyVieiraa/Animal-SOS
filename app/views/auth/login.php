
<?php
function h($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
?>

<h1>Login</h1>

<?php if (!empty($sucesso)): ?>
  <p style="color: green; font-weight: bold;">
    <?= htmlspecialchars($sucesso, ENT_QUOTES, 'UTF-8') ?>
  </p>
<?php endif; ?>

<?php if (!empty($erro)): ?>
  <p style="color: red; font-weight: bold;"><?= h($erro) ?></p>
<?php endif; ?>

<?php if (!empty($erro)): ?>
  <p style="color:red;"><?= h($erro) ?></p>
<?php endif; ?>

<form method="post" action="">
  <label>E-mail</label><br>
  <input type="email" name="email" required><br><br>

  <label>Senha</label><br>
  <input type="password" name="senha" required><br><br>

  <button type="submit">Entrar</button>
</form>

<p style="margin-top:10px;">
  <a href="<?= BASE_URL ?>/cadastro">Cadastrar</a>
  &nbsp;|&nbsp;
  <a href="<?= BASE_URL ?>/esqueci">Esqueci a senha</a>
</p>
