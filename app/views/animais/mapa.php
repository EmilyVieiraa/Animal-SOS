<?php
declare(strict_types=1);
function h($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
?>

<h1>Mapa</h1>

<p>Use os filtros abaixo e clique em uma denúncia para abrir a localização.</p>

<form method="get" action="<?= BASE_URL ?>/index.php" style="margin: 12px 0;">
  <input type="hidden" name="c" value="mapa">
  <input type="hidden" name="a" value="index">

  <label for="status">Status:</label>
  <?php $sel = (string)($status ?? ''); ?>
  <select name="status" id="status">
    <option value="" <?= $sel === '' ? 'selected' : '' ?>>Todos</option>
    <option value="Aguardando" <?= $sel === 'Aguardando' ? 'selected' : '' ?>>Aguardando</option>
    <option value="Em andamento" <?= $sel === 'Em andamento' ? 'selected' : '' ?>>Em andamento</option>
    <option value="Resgatado" <?= $sel === 'Resgatado' ? 'selected' : '' ?>>Resgatado</option>
    <option value="Adoção" <?= $sel === 'Adoção' ? 'selected' : '' ?>>Adoção</option>
    <option value="Finalizado" <?= $sel === 'Finalizado' ? 'selected' : '' ?>>Finalizado</option>
  </select>

  <button type="submit">Aplicar</button>

  <?php if ($sel !== ''): ?>
    <a href="<?= BASE_URL ?>/index.php?c=mapa&a=index" style="margin-left:8px;">Limpar</a>
  <?php endif; ?>
</form>

<div style="border:1px solid #ddd;border-radius:12px;padding:12px;margin:12px 0;background:#fafafa;">
  <strong>Mapa (placeholder)</strong>
  <p style="margin:6px 0;color:#666;">
    Nesta versão, a abertura é feita no Google Maps ao clicar em “Abrir no mapa”.
    Se você quiser, depois integramos Leaflet/OpenStreetMap aqui.
  </p>
</div>

<hr>

<h2>Denúncias</h2>

<?php if (empty($animais)): ?>
  <p>Nenhuma denúncia encontrada.</p>
<?php else: ?>
  <ul style="padding-left:18px;">
    <?php foreach ($animais as $a): ?>
      <?php
        $id  = (string)($a['id'] ?? '');
        $mapsUrl = null;

        if ($lat !== null && $lng !== null && $lat !== '' && $lng !== '') {
          $mapsUrl = 'https://www.google.com/maps?q=' . rawurlencode($lat . ',' . $lng);
        }

        $detUrl = BASE_URL . '/index.php?c=animal&a=detalhes&id=' . urlencode($id);
      ?>
      <li style="margin:10px 0;">
        <div><strong><?= h($a['especie'] ?? '') ?></strong> — <?= h($a['status'] ?? '') ?></div>
        <div style="color:#666; font-size:14px;"><?= h($a['localizacao'] ?? '') ?></div>
        <div style="margin-top:6px;">
          <a href="<?= h($detUrl) ?>">Ver detalhes</a>
          <?php if ($mapsUrl): ?>
            | <a href="<?= h($mapsUrl) ?>" target="_blank">Abrir no mapa</a>
          <?php endif; ?>
        </div>
      </li>
    <?php endforeach; ?>
  </ul>
<?php endif; ?>
