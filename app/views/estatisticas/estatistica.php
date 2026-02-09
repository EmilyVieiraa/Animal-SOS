<?php
declare(strict_types=1);
function h($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

$dados = $dados ?? [];

// Caso a view traga colunas diferentes, não quebra: mostramos o que existir.
$total = $dados['total_denuncias'] ?? ($dados['total'] ?? null);
$aguardando = $dados['aguardando'] ?? null;
$resgatado  = $dados['resgatado'] ?? null;
$adocao     = $dados['adocao'] ?? ($dados['adoção'] ?? $dados['adoção'] ?? null);
?>

<h1>Dashboard</h1>

<div class="grid">
  <?php if ($total !== null): ?>
    <div class="card">
      <div class="label">Total de denúncias</div>
      <div class="value"><?= h($total) ?></div>
    </div>
  <?php endif; ?>

  <?php if ($aguardando !== null): ?>
    <div class="card">
      <div class="label">Aguardando</div>
      <div class="value"><?= h($aguardando) ?></div>
    </div>
  <?php endif; ?>

  <?php if ($resgatado !== null): ?>
    <div class="card">
      <div class="label">Resgatado</div>
      <div class="value"><?= h($resgatado) ?></div>
    </div>
  <?php endif; ?>

  <?php if ($adocao !== null): ?>
    <div class="card">
      <div class="label">Adoção</div>
      <div class="value"><?= h($adocao) ?></div>
    </div>
  <?php endif; ?>
</div>

<h2 style="margin-top:18px;">Detalhes (bruto)</h2>
<p style="color:#666;margin-top:6px;">
  Útil para validar rapidamente se a view do banco está retornando todas as métricas esperadas.
</p>

<pre style="background:#f7f7f7;padding:12px;border-radius:10px;overflow:auto;"><?= h(print_r($dados, true)) ?></pre>

<p style="margin-top:14px;">
  <a href="<?= BASE_URL ?>/index.php?c=paginas&a=home">Voltar</a>
</p>

<style>
  .grid{
    display:grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap:12px;
    margin-top: 12px;
  }
  .card{
    border:1px solid #e7e7e7;
    border-radius:12px;
    padding:14px;
    background:#fff;
  }
  .label{ color:#666; font-size:14px; }
  .value{ font-size:28px; font-weight:700; margin-top:6px; }
</style>
