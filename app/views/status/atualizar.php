<?php
function h($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
?>

<h1>Atualizar status</h1>

<?php if (!empty($erro)): ?>
  <p style="color:red;"><?= h($erro) ?></p>
<?php endif; ?>

<form method="post">
  <input type="hidden" name="animal_id" value="<?= h($animal_id ?? '') ?>">

  <label>Novo status</label><br>
  <select name="novo_status" required>
    <option value="">Selecione</option>
    <option value="Aguardando">Aguardando</option>
    <option value="Em andamento">Em andamento</option>
    <option value="Resgatado">Resgatado</option>
    <option value="Adoção">Adoção</option>
    <option value="Finalizado">Finalizado</option>
  </select>

  <br><br>

  <button type="submit">Salvar status</button>
</form>

<p>
  <a href="<?= BASE_URL ?>/index.php?c=animal&a=detalhes&id=<?= h($animal_id ?? '') ?>">Voltar</a>
</p>
