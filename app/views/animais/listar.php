<?php
declare(strict_types=1);

require_once APP_PATH . 'helpers/view_helpers.php';

$filtroStatus = (string)($status ?? '');
$paginaAtual = max(1, (int)($page ?? 1));
$totalPaginas = max(1, (int)($totalPages ?? 1));
$listaDenuncias = is_array($denuncias ?? null) ? $denuncias : [];

$possuiDenuncias = !empty($listaDenuncias);
$deveExibirPaginacao = $totalPaginas > 1;

$janelaPaginacao = 2;
$paginaInicial = max(1, $paginaAtual - $janelaPaginacao);
$paginaFinal = min($totalPaginas, $paginaAtual + $janelaPaginacao);

$primeiraPagina = 1;
$paginaAnterior = max(1, $paginaAtual - 1);
$proximaPagina = min($totalPaginas, $paginaAtual + 1);

$urlPrimeiraPagina = pageUrl($primeiraPagina, $filtroStatus);
$urlPaginaAnterior = pageUrl($paginaAnterior, $filtroStatus);
$urlProximaPagina = pageUrl($proximaPagina, $filtroStatus);
$urlUltimaPagina = pageUrl($totalPaginas, $filtroStatus);
?>

<section class="denuncia-listagem__cabecalho">
  <h1 class="denuncia-listagem__titulo">Denúncias de Animais</h1>
  <p class="denuncia-listagem__subtitulo">Veja relatos enviados pela comunidade e acompanhe atualizações.</p>
</section>

<?php if (!$possuiDenuncias): ?>
  <div class="denuncia-listagem__vazio">
    <h2>Nenhuma denúncia encontrada.</h2>
  </div>
<?php else: ?>
  <div class="container">
    <section class="denuncia-listagem__grade">
      <?php foreach ($listaDenuncias as $denuncia):
        $dadosDenuncia = $denuncia;
        $variacaoCard = 'default';
        require __DIR__ . '/../partials/_animal_card.php';
      endforeach; ?>
    </section>
  </div>

  <?php if ($deveExibirPaginacao): ?>
    <nav class="denuncia-paginacao" aria-label="Paginação">
      <a class="denuncia-paginacao__link <?= $paginaAtual <= 1 ? 'denuncia-paginacao__link--desabilitada' : '' ?>"
         href="<?= $paginaAtual <= 1 ? '#' : h($urlPrimeiraPagina) ?>">Primeira</a>

      <a class="denuncia-paginacao__link <?= $paginaAtual <= 1 ? 'denuncia-paginacao__link--desabilitada' : '' ?>"
         href="<?= $paginaAtual <= 1 ? '#' : h($urlPaginaAnterior) ?>">Anterior</a>

      <?php if ($paginaInicial > 1): ?>
        <a class="denuncia-paginacao__pagina" href="<?= h($urlPrimeiraPagina) ?>">1</a>
        <?php if ($paginaInicial > 2): ?>
          <span class="denuncia-paginacao__reticencias">…</span>
        <?php endif; ?>
      <?php endif; ?>

      <?php for ($numeroPagina = $paginaInicial; $numeroPagina <= $paginaFinal; $numeroPagina++): ?>
        <a class="denuncia-paginacao__pagina <?= $numeroPagina === $paginaAtual ? 'denuncia-paginacao__pagina--ativa' : '' ?>"
           href="<?= h(pageUrl($numeroPagina, $filtroStatus)) ?>"><?= (int)$numeroPagina ?></a>
      <?php endfor; ?>

      <?php if ($paginaFinal < $totalPaginas): ?>
        <?php if ($paginaFinal < $totalPaginas - 1): ?>
          <span class="denuncia-paginacao__reticencias">…</span>
        <?php endif; ?>
        <a class="denuncia-paginacao__pagina" href="<?= h($urlUltimaPagina) ?>"><?= (int)$totalPaginas ?></a>
      <?php endif; ?>

      <a class="denuncia-paginacao__link <?= $paginaAtual >= $totalPaginas ? 'denuncia-paginacao__link--desabilitada' : '' ?>"
         href="<?= $paginaAtual >= $totalPaginas ? '#' : h($urlProximaPagina) ?>">Próxima</a>

      <a class="denuncia-paginacao__link <?= $paginaAtual >= $totalPaginas ? 'denuncia-paginacao__link--desabilitada' : '' ?>"
         href="<?= $paginaAtual >= $totalPaginas ? '#' : h($urlUltimaPagina) ?>">Última</a>
    </nav>
  <?php endif; ?>
<?php endif; ?>