<?php
declare(strict_types=1);
require_once APP_PATH . 'helpers/view_helpers.php';

/**
 * Variáveis seguras (uma única vez)
 */
$titulo    = (string)($animal['titulo'] ?? '');
$descricao = (string)($animal['descricao'] ?? '');

$especie   = (string)($animal['especie'] ?? '—');
$condicao  = (string)($animal['condicao'] ?? '—');
$cor       = (string)($animal['cor'] ?? '—');
$local     = (string)($animal['localizacao'] ?? '—');

$status    = (string)($animal['status'] ?? 'Aguardando');
$dataHora  = (string)($animal['data_hora'] ?? '');
$autorNome = (string)($animal['usuario_nome'] ?? '—');
$autorId   = (string)($animal['usuario_id'] ?? '');

/**
 * Sessão (padronizada conforme seu AuthController)
 */
$isLogged    = !empty($_SESSION['usuario_id']);
$tipoUsuario = (string)($_SESSION['tipo_usuario'] ?? 'Comum');

/**
 * Permissões de status (UI)
 */
$canUpdateStatus = in_array($tipoUsuario, ['ONG', 'Autoridade', 'Admin'], true);

$statusOptions = [];
if ($tipoUsuario === 'ONG') {
  $statusOptions = ['Adoção', 'Resgatado'];
} elseif (in_array($tipoUsuario, ['Autoridade', 'Admin'], true)) {
  $statusOptions = ['Aguardando', 'Adoção', 'Resgatado'];
}

/**
 * Imagem principal: primeira da galeria, senão placeholder
 */
$imgPrincipal = null;
if (!empty($imagens) && !empty($imagens[0]['caminho'])) {
  $imgPrincipal = (string)$imagens[0]['caminho'];
}
$placeholder = BASE_URL . '/assets/img/placeholder-animal.jpg';

// URL de retorno para esta denúncia (usada no login)
$returnTo = '/index.php?c=animal&a=detalhes&id=' . urlencode((string)($animal['id'] ?? ''));
?>

<section class="den-detail">
  <div class="den-detail__container">

    <a class="den-detail__back" href="<?= BASE_URL ?>/index.php?c=animal&a=listar">← Voltar</a>

    <?php if (!empty($_SESSION['flash_success'])): ?>
      <div class="flash flash--success">
        <?= h((string)$_SESSION['flash_success']) ?>
      </div>
      <?php unset($_SESSION['flash_success']); ?>
    <?php endif; ?>

    <?php if (!empty($_SESSION['flash_error'])): ?>
      <div class="flash flash--error">
        <?= h((string)$_SESSION['flash_error']) ?>
      </div>
      <?php unset($_SESSION['flash_error']); ?>
    <?php endif; ?>

    <div class="den-hero">

      <!-- Coluna esquerda: imagem -->
      <div class="den-hero__media">
        <div class="den-image">
          <img
            src="<?= h($imgPrincipal ?: $placeholder) ?>"
            alt="Foto da denúncia"
            loading="lazy"
            onerror="this.src='<?= h($placeholder) ?>'"
          >
        </div>

        <?php if (!empty($imagens) && count($imagens) > 1): ?>
          <div class="den-thumbs" aria-label="Galeria de imagens">
            <?php foreach ($imagens as $idx => $img): ?>
              <?php
                $src = (string)($img['caminho'] ?? '');
                if ($src === '') continue;
              ?>
              <button
                type="button"
                class="den-thumb"
                data-src="<?= h($src) ?>"
                aria-label="Ver imagem <?= (int)($idx + 1) ?>"
              >
                <img src="<?= h($src) ?>" alt="Miniatura <?= (int)($idx + 1) ?>" loading="lazy">
              </button>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>

      <!-- Coluna direita: informações -->
      <div class="den-hero__info">

        <!-- TÍTULO DA DENÚNCIA -->
        <h2 class="den-title"><?= h($titulo !== '' ? $titulo : 'Denúncia sem título') ?></h2>

        <!-- DESCRIÇÃO -->
        <?php if ($descricao !== ''): ?>
          <p class="den-desc"><?= nl2br(h($descricao)) ?></p>
        <?php else: ?>
          <p class="den-desc den-desc--muted">Sem descrição.</p>
        <?php endif; ?>

        <!-- STATUS + REGISTRADO EM + AUTOR -->
        <div class="den-meta den-meta--row den-meta--triple">
          <div class="den-meta__item">
            <span class="den-meta__label">Status</span>
            <span class="den-meta__value"><?= h($status) ?></span>
          </div>

          <div class="den-meta__item">
            <span class="den-meta__label">Registrado em</span>
            <span class="den-meta__value"><?= h($dataHora !== '' ? $dataHora : '—') ?></span>
          </div>

          <div class="den-meta__item">
            <span class="den-meta__label">Autor</span>

            <?php if ($autorId !== ''): ?>
              <a
                href="<?= BASE_URL ?>/index.php?c=usuario&a=perfil&id=<?= h($autorId) ?>"
                class="den-meta__value den-meta__link"
              >
                <?= h($autorNome) ?>
              </a>
            <?php else: ?>
              <span class="den-meta__value">—</span>
            <?php endif; ?>
          </div>

        </div>

        <hr class="den-sep">

        <!-- Card: Informações -->
        <div class="den-card">
          <div class="den-card__title">Informações do animal</div>

          <div class="den-info-grid">
            <div class="den-info-row">
              <span class="den-info-k">Espécie</span>
              <span class="den-info-v"><?= h($especie) ?></span>
            </div>

            <div class="den-info-row">
              <span class="den-info-k">Condição</span>
              <span class="den-info-v"><?= h($condicao) ?></span>
            </div>

            <div class="den-info-row">
              <span class="den-info-k">Cor</span>
              <span class="den-info-v"><?= h($cor) ?></span>
            </div>

            <div class="den-info-row den-info-row--full">
              <span class="den-info-k">Local</span>
              <span class="den-info-v"><?= h($local) ?></span>
            </div>
          </div>
        </div>

        <!-- Ações -->
        <div class="den-actions">
          <?php if ($isLogged && $canUpdateStatus): ?>
            <form
              method="post"
              action="<?= BASE_URL ?>/index.php?c=animal&a=atualizarStatus"
              class="den-statusform"
            >
              <input type="hidden" name="animal_id" value="<?= h($animal['id'] ?? '') ?>">

              <label class="den-statusform__label" for="status-select">Alterar status</label>

              <div class="den-statusform__row">
                <select id="status-select" name="status" class="den-statusform__select" required>
                  <?php foreach ($statusOptions as $opt): ?>
                    <option value="<?= h($opt) ?>" <?= $opt === $status ? 'selected' : '' ?>>
                      <?= h($opt) ?>
                    </option>
                  <?php endforeach; ?>
                </select>

                <button type="submit" class="btn-primary">Salvar</button>
              </div>
            </form>
          <?php endif; ?>
        </div>

      </div>
    </div>

    <!-- Seção inferior -->
    <div class="den-bottom">
      <h2 class="den-bottom__title">Atividade na denúncia</h2>

      <div class="den-bottom__grid">
        <!-- Histórico -->
        <div class="den-block">
          <div class="den-block__head">
            <h3 class="den-block__title">Histórico de status</h3>
          </div>

          <?php if (!empty($historico)): ?>
            <div class="den-list">
              <?php foreach ($historico as $hItem): ?>
                <div class="den-item">
                  <div class="den-item__top">
                    <strong><?= h($hItem['novo_status'] ?? '') ?></strong>

                    <?php if (!empty($hItem['status_anterior'])): ?>
                      <span class="den-item__date">de <?= h($hItem['status_anterior']) ?></span>
                    <?php endif; ?>
                  </div>

                  <div class="den-item__body den-item__body--muted">
                    Por <?= h($hItem['atualizado_por_nome'] ?? '') ?>
                    em <?= h($hItem['data_hora'] ?? '') ?>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          <?php else: ?>
            <p class="den-empty">Sem alterações de status registradas.</p>
          <?php endif; ?>
        </div>

        <!-- Comentários -->
        <div class="den-block">
          <div class="den-block__head">
            <h3 class="den-block__title">Comentários</h3>
          </div>

          <?php if (!empty($comentarios)): ?>
            <div class="den-list">
              <?php foreach ($comentarios as $c): ?>
                <div class="den-item">
                  <div class="den-item__top">
                    <strong><?= h($c['usuario_nome'] ?? 'Usuário') ?></strong>
                    <span class="den-item__date"><?= h($c['data_hora'] ?? '') ?></span>
                  </div>
                  <div class="den-item__body">
                    <?= nl2br(h($c['mensagem'] ?? '')) ?>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          <?php else: ?>
            <p class="den-empty">Nenhum comentário ainda.</p>
          <?php endif; ?>

          <div class="den-block__foot">

            <?php if (!$isLogged): ?>
              <h4 class="den-subtitle">
                <a
                  href="<?= BASE_URL ?>/index.php?c=auth&a=login&return=<?= urlencode($returnTo) ?>"
                  class="den-addlink"
                >
                  Adicionar comentário
                </a>
              </h4>
            <?php endif; ?>

            <?php if ($isLogged): ?>
              <form
                id="form-comentario"
                method="post"
                action="<?= BASE_URL ?>/index.php?c=comentario&a=adicionar"
                class="den-form"
              >
                <input type="hidden" name="animal_id" value="<?= h($animal['id'] ?? '') ?>">

                <textarea
                  name="mensagem"
                  rows="3"
                  required
                  placeholder="Publicar comentário"></textarea>

                <div class="den-form__actions">
                  <button type="submit" class="btn-primary">Publicar</button>
                </div>
              </form>
            <?php endif; ?>

          </div>

        </div>
      </div>
    </div>

  </div>
</section>

<script>
  // Troca da imagem principal ao clicar nas miniaturas
  (function(){
    const mainImg = document.querySelector('.den-image img');
    const thumbs = document.querySelectorAll('.den-thumb');
    if (!mainImg || !thumbs.length) return;

    thumbs.forEach(btn => {
      btn.addEventListener('click', () => {
        const src = btn.getAttribute('data-src');
        if (!src) return;
        mainImg.src = src;
      });
    });
  })();
</script>
