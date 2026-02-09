<?php
declare(strict_types=1);

require_once APP_PATH . 'helpers/view_helpers.php';

$sel = (string)($status ?? '');

$page       = max(1, (int)($page ?? 1));
$totalPages = max(1, (int)($totalPages ?? 1));

// Gera URL de página com parâmetros de filtro
function pageUrl(int $p, string $status): string {
    $q = [
        'c' => 'animal',
        'a' => 'listar',
        'p' => $p,
    ];
    if ($status !== '') $q['status'] = $status;
    return BASE_URL . '/index.php?' . http_build_query($q);
}

// Normaliza caminho da imagem para URL pública
function publicImgUrl(string $path): string {
    $path = trim($path);
    if ($path === '') return '';

    // Se já for URL absoluta, retorna como está
    if (preg_match('~^https?://~i', $path)) return $path;

    // Remove barras duplicadas
    $path = ltrim($path, '/');

    // Garante BASE_URL + / + path
    return BASE_URL . '/' . $path;
}
?>

<section class="catalog-hero">
  <h1 class="catalog-title">Denúncias de Animais</h1>
  <p class="catalog-subtitle">Veja relatos enviados pela comunidade e acompanhe atualizações.</p>
</section>

<?php if (empty($animais)): ?>
  <div class="empty-state">
    <h2>Nenhuma denúncia encontrada.</h2>
  </div>
<?php else: ?>

  <section class="catalog-grid">
    <?php foreach ($animais as $a): ?>
      <?php
        $id        = (string)($a['id'] ?? '');
        $foto      = (string)($a['foto'] ?? '');
        $titulo    = (string)($a['titulo'] ?? '');
        $especie   = (string)($a['especie'] ?? 'Animal');
        $condicao  = (string)($a['condicao'] ?? '');
        $descricao = (string)($a['descricao'] ?? '');
        $dataHora  = (string)($a['data_hora'] ?? '');
        $st        = (string)($a['status'] ?? '');
        $autorNome = (string)($a['usuario_nome'] ?? $a['nome'] ?? 'Usuário');

        // Se não tem ID, não dá para ir para detalhes
        $hasId  = ($id !== '');
        $detUrl = $hasId
          ? (BASE_URL . '/index.php?c=animal&a=detalhes&id=' . urlencode($id))
          : '';
      ?>

      <article class="item-card">

        <?php if ($hasId): ?>
          <a class="item-media" href="<?= h($detUrl) ?>" aria-label="Ver detalhes">
        <?php else: ?>
          <div class="item-media" aria-label="Denúncia sem ID (link indisponível)">
        <?php endif; ?>

            <?php if ($foto !== ''): ?>
              <img src="<?= h(publicImgUrl($foto)) ?>" alt="Foto do animal" loading="lazy">
            <?php else: ?>
              <div class="item-placeholder">
                <span class="ph-icon">🖼️</span>
                <span class="ph-text">Sem foto</span>
              </div>
            <?php endif; ?>

        <?php if ($hasId): ?>
          </a>
        <?php else: ?>
          </div>
        <?php endif; ?>

        <div class="item-body">
          <h3 class="item-title">
            <?php if ($hasId): ?>
              <a href="<?= h($detUrl) ?>"><?= h($titulo !== '' ? $titulo : $especie) ?></a>
            <?php else: ?>
              <?= h($titulo !== '' ? $titulo : $especie) ?>
            <?php endif; ?>
          </h3>

          <div class="item-tags">
            <?php if ($st !== ''): ?>
              <span class="<?= h(statusBadgeClassUI($st)) ?>"><?= h($st) ?></span>
            <?php endif; ?>
            <?php if ($condicao !== ''): ?>
              <span class="<?= h(condicaoBadgeClassUI($condicao)) ?>"><?= h($condicao) ?></span>
            <?php endif; ?>
          </div>

          <?php
            $descLimpa = trim(preg_replace('/\s+/', ' ', $descricao));
            $descCurta = mb_strimwidth($descLimpa, 0, 110, '...', 'UTF-8');
          ?>
          <?php if ($descLimpa !== ''): ?>
            <div class="item-description"><?= h($descCurta) ?></div>
          <?php else: ?>
            <div class="item-description muted">Sem descrição.</div>
          <?php endif; ?>

          <p class="item-desc">
            <?php if ($dataHora !== ''): ?>
              <span class="muted">Registrado em:</span> <?= h($dataHora) ?>
            <?php else: ?>
              <span class="muted">Registro sem data informada.</span>
            <?php endif; ?>
          </p>

          <div class="item-footer">
            <div class="item-author">
              <div class="avatar"><?= h(initials($autorNome)) ?></div>
              <div class="author-name"><?= h($autorNome) ?></div>
            </div>

            <?php if ($hasId): ?>
              <a class="btn-ui btn-ui-ghost" href="<?= h($detUrl) ?>">Ver detalhes</a>
            <?php else: ?>
              <span class="btn-ui btn-ui-ghost disabled" aria-disabled="true">Ver detalhes</span>
            <?php endif; ?>
          </div>
        </div>
      </article>
    <?php endforeach; ?>
  </section>

  <?php
  $window = 2;
  $start = max(1, $page - $window);
  $end   = min($totalPages, $page + $window);
  ?>

  <?php if ($totalPages > 1): ?>
    <nav class="pagination-ui" aria-label="Paginação">
      <a class="pg-link <?= $page <= 1 ? 'disabled' : '' ?>"
         href="<?= $page <= 1 ? '#' : h(pageUrl(1, $sel)) ?>">First</a>

      <a class="pg-link <?= $page <= 1 ? 'disabled' : '' ?>"
         href="<?= $page <= 1 ? '#' : h(pageUrl($page - 1, $sel)) ?>">Prev</a>

      <?php if ($start > 1): ?>
        <a class="pg-page" href="<?= h(pageUrl(1, $sel)) ?>">1</a>
        <?php if ($start > 2): ?><span class="pg-ellipsis">…</span><?php endif; ?>
      <?php endif; ?>

      <?php for ($p = $start; $p <= $end; $p++): ?>
        <a class="pg-page <?= $p === $page ? 'active' : '' ?>"
           href="<?= h(pageUrl($p, $sel)) ?>"><?= (int)$p ?></a>
      <?php endfor; ?>

      <?php if ($end < $totalPages): ?>
        <?php if ($end < $totalPages - 1): ?><span class="pg-ellipsis">…</span><?php endif; ?>
        <a class="pg-page" href="<?= h(pageUrl($totalPages, $sel)) ?>"><?= (int)$totalPages ?></a>
      <?php endif; ?>

      <a class="pg-link <?= $page >= $totalPages ? 'disabled' : '' ?>"
         href="<?= $page >= $totalPages ? '#' : h(pageUrl($page + 1, $sel)) ?>">Next</a>

      <a class="pg-link <?= $page >= $totalPages ? 'disabled' : '' ?>"
         href="<?= $page >= $totalPages ? '#' : h(pageUrl($totalPages, $sel)) ?>">Last</a>
    </nav>
  <?php endif; ?>

<?php endif; ?>
