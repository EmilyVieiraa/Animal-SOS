<?php
declare(strict_types=1);

/**
 * Partial: _animal_card.php
 * Espera:
 * - $a (array) dados da denúncia/animal
 * - $variant (opcional) 'default' | 'lg'
 */

$variant = (string)($variant ?? 'default');

$id        = (string)($a['id'] ?? '');
$foto      = (string)($a['foto'] ?? '');
$titulo    = (string)($a['titulo'] ?? '');
$especie   = (string)($a['especie'] ?? 'Animal');
$condicao  = (string)($a['condicao'] ?? '');
$descricao = (string)($a['descricao'] ?? '');
$dataHora  = (string)($a['data_hora'] ?? '');
$st        = (string)($a['status'] ?? '');
$autorNome = (string)($a['usuario_nome'] ?? $a['nome'] ?? 'Usuário');

$hasId  = ($id !== '');

// ✅ Ajuste aqui se a sua action for "detalhe" e não "detalhes"
$detAction = 'detalhes';

$detUrl = $hasId
  ? (BASE_URL . '/index.php?c=animal&a=' . $detAction . '&id=' . urlencode($id))
  : '';

$descLimpa = trim(preg_replace('/\s+/', ' ', $descricao));
$limit = ($variant === 'lg') ? 220 : 110;
$descCurta = mb_strimwidth($descLimpa, 0, $limit, '...', 'UTF-8');

$cardClass = 'item-card' . ($variant === 'lg' ? ' item-card-lg' : '');
?>

<article class="<?= h($cardClass) ?>">

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
