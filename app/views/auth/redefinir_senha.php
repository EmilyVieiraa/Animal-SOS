<?php
declare(strict_types=1);
function h($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
?>

<h1>Redefinir senha</h1>

<?php if (!empty($erro)): ?>
  <p style="color:red;"><?= h($erro) ?></p>
<?php endif; ?>

<?php if (!empty($token)): ?>
  <form method="post" action="<?= BASE_URL ?>/index.php?c=auth&a=salvarNovaSenha">
    <input type="hidden" name="token" value="<?= h($token) ?>">

    <label>Nova senha</label><br>
    <input type="password" name="senha" required><br><br>

    <label>Confirmar nova senha</label><br>
    <input type="password" name="senha_confirmacao" required><br><br>

    <button type="submit">Salvar</button>
  </form>
<?php endif; ?>

<p style="margin-top:12px;">
  <a href="<?= BASE_URL ?>/login">Voltar ao login</a>
</p>
