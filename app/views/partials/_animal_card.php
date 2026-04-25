<?php
declare(strict_types=1);

/**
 * Partial: _animal_card.php
 * Espera:
 * - $dadosAnimal (array) dados do animal reportado
 * - $variacaoCard (opcional) 'default' | 'lg'
 */

$variacaoCard = (string)($variacaoCard ?? 'default');
if ($variacaoCard !== 'default' && $variacaoCard !== 'lg') {
  $variacaoCard = 'default';
}

$dadosAnimal = is_array($dadosAnimal ?? null) ? $dadosAnimal : [];

$animalId = (string)($dadosAnimal['id'] ?? '');
$fotoAnimal = (string)($dadosAnimal['foto'] ?? '');
$tituloAnimal = (string)($dadosAnimal['titulo'] ?? '');
$especieAnimal = (string)($dadosAnimal['especie'] ?? 'Animal');
$condicaoAnimal = (string)($dadosAnimal['condicao'] ?? '');
$descricaoAnimal = (string)($dadosAnimal['descricao'] ?? '');
$dataRegistro = formatDateTime((string)($dadosAnimal['data_criacao'] ?? ''));
$statusAnimal = (string)($dadosAnimal['status'] ?? '');
$nomeAutor = (string)($dadosAnimal['usuario_nome'] ?? 'Usuário');

$possuiId = ($animalId !== '');

$urlDetalhes = $possuiId
    ? BASE_URL . '/index.php?c=animal&a=detalhes&id=' . urlencode($animalId)
    : '';

$descricaoLimpa = trim((string)preg_replace('/\s+/', ' ', $descricaoAnimal));
$limiteDescricao = ($variacaoCard === 'lg') ? 220 : 110;
$descricaoCurta = mb_strimwidth($descricaoLimpa, 0, $limiteDescricao, '...', 'UTF-8');
$tituloExibicao = ($tituloAnimal !== '' ? $tituloAnimal : $especieAnimal);

$classeCard = 'denuncia-card' . ($variacaoCard === 'lg' ? ' denuncia-card--grande' : '');
?>

<article class="<?= h($classeCard) ?>">

  <?php if ($possuiId): ?>
    <a class="denuncia-card__midia" href="<?= h($urlDetalhes) ?>" aria-label="Ver detalhes da denúncia">
  <?php else: ?>
    <div class="denuncia-card__midia" aria-label="Denúncia sem ID (link indisponível)">
  <?php endif; ?>

    <?php if ($fotoAnimal !== ''): ?>
      <img src="<?= h(publicImgUrl($fotoAnimal)) ?>" alt="Foto do animal" loading="lazy">
    <?php else: ?>
      <div class="denuncia-card__placeholder">
        <span class="denuncia-card__placeholder-icone">🖼️</span>
        <span class="denuncia-card__placeholder-texto">Sem foto</span>
      </div>
    <?php endif; ?>

  <?php if ($possuiId): ?>
    </a>
  <?php else: ?>
    </div>
  <?php endif; ?>

  <div class="denuncia-card__corpo">
    <h3 class="denuncia-card__titulo">
      <?php if ($possuiId): ?>
        <a href="<?= h($urlDetalhes) ?>">
          <?= h($tituloExibicao) ?>
        </a>
      <?php else: ?>
        <?= h($tituloExibicao) ?>
      <?php endif; ?>
    </h3>

    <div class="denuncia-card__etiquetas">
      <?php if ($statusAnimal !== ''): ?>
        <span class="<?= h(statusBadgeClassUI($statusAnimal)) ?>">
          <?= h($statusAnimal) ?>
        </span>
      <?php endif; ?>

      <?php if ($condicaoAnimal !== ''): ?>
        <span class="<?= h(condicaoBadgeClassUI($condicaoAnimal)) ?>">
          <?= h($condicaoAnimal) ?>
        </span>
      <?php endif; ?>
    </div>

    <?php if ($descricaoLimpa !== ''): ?>
      <div class="denuncia-card__descricao"><?= h($descricaoCurta) ?></div>
    <?php else: ?>
      <div class="denuncia-card__descricao muted">Sem descrição.</div>
    <?php endif; ?>

    <p class="denuncia-card__registro">
      <?php if ($dataRegistro !== ''): ?>
        <span class="muted">Registrado em:</span> <?= h($dataRegistro) ?>
      <?php else: ?>
        <span class="muted">Registro sem data informada.</span>
      <?php endif; ?>
    </p>

    <div class="denuncia-card__rodape">
      <div class="denuncia-card__autor">
        <div class="denuncia-card__avatar"><?= h(initials($nomeAutor)) ?></div>
        <div class="denuncia-card__autor-nome"><?= h($nomeAutor) ?></div>
      </div>

      <?php if ($possuiId): ?>
        <a class="btn-ui btn-ui-ghost" href="<?= h($urlDetalhes) ?>">Ver detalhes</a>
      <?php else: ?>
        <span class="btn-ui btn-ui-ghost disabled" aria-disabled="true">Ver detalhes</span>
      <?php endif; ?>
    </div>
  </div>
</article>