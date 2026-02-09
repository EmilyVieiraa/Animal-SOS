<?php
declare(strict_types=1);
function h($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
?>

<h1>Perfil</h1>

<?php if (!empty($erro)): ?>
  <p style="color:red;"><?= h($erro) ?></p>
<?php endif; ?>

<?php if (!empty($sucesso)): ?>
  <p style="color:green;"><?= h($sucesso) ?></p>
<?php endif; ?>

<form method="post">
  <label>Nome</label><br>
  <input type="text" name="nome" value="<?= h($usuario['nome'] ?? '') ?>" required>
  <br><br>

  <label>Telefone</label><br>
  <input type="text" name="telefone" value="<?= h($usuario['telefone'] ?? '') ?>">
  <br><br>

  <button type="submit">Salvar</button>
</form>

<p style="margin-top:12px;">
  <a href="<?= BASE_URL ?>/index.php?c=paginas&a=home">Voltar</a>
</p>
