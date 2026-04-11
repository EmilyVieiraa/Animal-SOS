<?php
declare(strict_types=1);

require_once APP_PATH . 'helpers/view_helpers.php';

$sel = (string)($status ?? '');

$page       = max(1, (int)($page ?? 1));
$totalPages = max(1, (int)($totalPages ?? 1));

// Gera URL de página com parâmetros de filtro

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

  <div class="container">
    <section class="catalog-grid">
      <?php foreach ($animais as $a): ?>
        <?php
          $variant = 'default';
          require __DIR__ . '/../partials/_animal_card.php';
        ?>
      <?php endforeach; ?>
    </section>
  </div>

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
