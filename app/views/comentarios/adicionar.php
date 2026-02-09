<?php
declare(strict_types=1);
function h($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
?>

<h1>Adicionar comentário</h1>

<?php if (!empty($erro)): ?>
  <p style="color:red;"><?= h($erro) ?></p>
<?php endif; ?>

<form method="post" action="<?= BASE_URL ?>/index.php?c=comentario&a=adicionar">
  <input type="hidden" name="animal_id" value="<?= h($animal_id ?? '') ?>">

  <label>Mensagem</label><br>
  <textarea name="mensagem" rows="4" required style="width:100%;"></textarea>

  <br><br>
  <button type="submit">Publicar</button>
</form>

<p style="margin-top:12px;">
  <a href="<?= BASE_URL ?>/index.php?c=animal&a=detalhes&id=<?= h($animal_id ?? '') ?>">Voltar</a>
</p>
