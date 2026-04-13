<?php
declare(strict_types=1);

require_once APP_PATH . 'helpers/view_helpers.php';

// Dados da denúncia
$denunciaId = (string)($denuncia['id'] ?? '');
$tituloDenuncia = (string)($denuncia['titulo'] ?? '');
$descricaoDenuncia = (string)($denuncia['descricao'] ?? '');
$especieAnimal = (string)($denuncia['especie'] ?? '—');
$condicaoAnimal = (string)($denuncia['condicao'] ?? '—');
$corAnimal = (string)($denuncia['cor'] ?? '—');
$localizacaoDenuncia = (string)($denuncia['localizacao'] ?? '—');
$statusDenuncia = (string)($denuncia['status'] ?? 'Aguardando');
$dataRegistro = formatDateTime((string)($denuncia['data_hora'] ?? ''));
$nomeAutor = (string)($denuncia['usuario_nome'] ?? '—');
$autorId = (string)($denuncia['criado_por'] ?? '');

// Sessão e mensagens
$usuarioLogado = !empty($_SESSION['usuario_id']);
$mensagemSucesso = (string)($_SESSION['flash_success'] ?? '');
$mensagemErro = (string)($_SESSION['flash_error'] ?? '');
unset($_SESSION['flash_success'], $_SESSION['flash_error']);

// Dados preparados no controller
$podeAtualizarStatus = (bool)($podeAlterarStatus ?? false);
$statusDisponiveisParaAtualizacao = is_array($statusDisponiveis ?? null) ? $statusDisponiveis : [];
$imagemPlaceholderTela = (string)($imagemPlaceholder ?? (BASE_URL . '/assets/img/placeholder-animal.jpg'));

// URL de retorno para login
$urlRetorno = '/index.php?c=animal&a=detalhes&id=' . urlencode($denunciaId);

// Imagem principal
$imagemPrincipal = '';
if (!empty($imagens) && is_array($imagens)) {
    foreach ($imagens as $imagem) {
        $caminhoImagem = (string)($imagem['caminho'] ?? '');
        if ($caminhoImagem !== '') {
            $imagemPrincipal = $caminhoImagem;
            break;
        }
    }
}

$urlImagemPrincipal = $imagemPrincipal !== ''
    ? publicImgUrl($imagemPrincipal)
    : $imagemPlaceholderTela;

$mostrarFormularioStatus = $usuarioLogado
    && $podeAtualizarStatus
    && !empty($statusDisponiveisParaAtualizacao);
?>

<section class="denuncia-detalhe">
  <div class="denuncia-detalhe__container">

    <a class="denuncia-detalhe__voltar" href="<?= BASE_URL ?>/index.php?c=animal&a=listar">← Voltar</a>

    <?php if ($mensagemSucesso !== ''): ?>
      <div class="flash flash--success">
        <?= h($mensagemSucesso) ?>
      </div>
    <?php endif; ?>

    <?php if ($mensagemErro !== ''): ?>
      <div class="flash flash--error">
        <?= h($mensagemErro) ?>
      </div>
    <?php endif; ?>

    <div class="denuncia-detalhe__hero">

      <!-- Coluna esquerda -->
      <div class="denuncia-detalhe__hero-midia">
        <div class="denuncia-detalhe__imagem-principal">
          <img
            src="<?= h($urlImagemPrincipal) ?>"
            alt="Foto da denúncia"
            loading="lazy"
            onerror="this.src='<?= h($imagemPlaceholderTela) ?>'"
          >
        </div>

        <?php if (!empty($imagens) && count($imagens) > 1): ?>
          <div class="denuncia-detalhe__miniaturas" aria-label="Galeria de imagens">
            <?php foreach ($imagens as $idx => $img):
              $src = (string)($img['caminho'] ?? '');
              if ($src === '') {
                  continue;
              }
              $urlImagem = publicImgUrl($src);
            ?>
              <button
                type="button"
                class="denuncia-detalhe__miniatura"
                data-src="<?= h($urlImagem) ?>"
                aria-label="Ver imagem <?= (int)($idx + 1) ?>"
              >
                <img
                  src="<?= h($urlImagem) ?>"
                  alt="Miniatura <?= (int)($idx + 1) ?>"
                  loading="lazy"
                >
              </button>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>

      <!-- Coluna direita -->
      <div class="denuncia-detalhe__hero-conteudo">

        <h2 class="denuncia-detalhe__titulo">
          <?= h($tituloDenuncia !== '' ? $tituloDenuncia : 'Denúncia sem título') ?>
        </h2>

        <?php if ($descricaoDenuncia !== ''): ?>
          <p class="denuncia-detalhe__descricao"><?= nl2br(h($descricaoDenuncia)) ?></p>
        <?php else: ?>
          <p class="denuncia-detalhe__descricao denuncia-detalhe__descricao--suave">Sem descrição.</p>
        <?php endif; ?>

        <div class="denuncia-detalhe__metadados denuncia-detalhe__metadados--triplo">
          <div class="denuncia-detalhe__metadado-item">
            <span class="denuncia-detalhe__metadado-rotulo">Status</span>
            <span class="denuncia-detalhe__metadado-valor"><?= h($statusDenuncia) ?></span>
          </div>

          <div class="denuncia-detalhe__metadado-item">
            <span class="denuncia-detalhe__metadado-rotulo">Registrado em</span>
            <span class="denuncia-detalhe__metadado-valor"><?= h($dataRegistro !== '' ? $dataRegistro : '—') ?></span>
          </div>

          <div class="denuncia-detalhe__metadado-item">
            <span class="denuncia-detalhe__metadado-rotulo">Autor</span>

            <?php if ($autorId !== ''): ?>
              <a
                href="<?= BASE_URL ?>/index.php?c=usuario&a=perfil&id=<?= h($autorId) ?>"
                class="denuncia-detalhe__metadado-valor denuncia-detalhe__metadado-link"
                title="<?= h($nomeAutor) ?>"
              >
                <?= h($nomeAutor) ?>
              </a>
            <?php else: ?>
              <span class="denuncia-detalhe__metadado-valor">—</span>
            <?php endif; ?>
          </div>
        </div>

        <hr class="denuncia-detalhe__separador">

        <div class="denuncia-detalhe__card-informacoes">
          <div class="denuncia-detalhe__card-titulo">Informações do animal</div>

          <div class="denuncia-detalhe__info-grade">
            <div class="denuncia-detalhe__info-linha">
              <span class="denuncia-detalhe__info-chave">Espécie</span>
              <span class="denuncia-detalhe__info-valor"><?= h($especieAnimal) ?></span>
            </div>

            <div class="denuncia-detalhe__info-linha">
              <span class="denuncia-detalhe__info-chave">Condição</span>
              <span class="denuncia-detalhe__info-valor"><?= h($condicaoAnimal) ?></span>
            </div>

            <div class="denuncia-detalhe__info-linha">
              <span class="denuncia-detalhe__info-chave">Cor</span>
              <span class="denuncia-detalhe__info-valor"><?= h($corAnimal) ?></span>
            </div>

            <div class="denuncia-detalhe__info-linha denuncia-detalhe__info-linha--completa">
              <span class="denuncia-detalhe__info-chave">Local</span>
              <span class="denuncia-detalhe__info-valor"><?= h($localizacaoDenuncia) ?></span>
            </div>
          </div>
        </div>

        <div class="denuncia-detalhe__acoes">
          <?php if ($mostrarFormularioStatus): ?>
            <form
              method="post"
              action="<?= BASE_URL ?>/index.php?c=animal&a=atualizarStatus"
              class="denuncia-detalhe__form-status"
            >
              <input type="hidden" name="animal_id" value="<?= h($denunciaId) ?>">

              <label class="denuncia-detalhe__form-status-rotulo" for="status-select">Alterar status</label>

              <div class="denuncia-detalhe__form-status-linha">
                <select
                  id="status-select"
                  name="status"
                  class="denuncia-detalhe__form-status-selecao"
                  required
                >
                  <?php foreach ($statusDisponiveisParaAtualizacao as $statusOpcao): ?>
                    <option
                      value="<?= h((string)$statusOpcao) ?>"
                      <?= (string)$statusOpcao === $statusDenuncia ? 'selected' : '' ?>
                    >
                      <?= h((string)$statusOpcao) ?>
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

    <div class="denuncia-detalhe__atividade">
      <h2 class="denuncia-detalhe__atividade-titulo">Atividade na denúncia</h2>

      <div class="denuncia-detalhe__atividade-grade">

        <!-- Histórico -->
        <div class="denuncia-detalhe__bloco">
          <div class="denuncia-detalhe__bloco-cabecalho">
            <h3 class="denuncia-detalhe__bloco-titulo">Histórico de status</h3>
          </div>

          <?php if (!empty($historico)): ?>
            <div class="denuncia-detalhe__lista">
              <?php foreach ($historico as $itemHistorico): ?>
                <div class="denuncia-detalhe__item">
                  <div class="denuncia-detalhe__item-topo">
                    <strong><?= h($itemHistorico['novo_status'] ?? '') ?></strong>

                    <?php if (!empty($itemHistorico['status_anterior'])): ?>
                      <span class="denuncia-detalhe__item-data">
                        de <?= h($itemHistorico['status_anterior']) ?>
                      </span>
                    <?php endif; ?>
                  </div>

                  <div class="denuncia-detalhe__item-corpo denuncia-detalhe__item-corpo--suave">
                    Por <?= h($itemHistorico['atualizado_por_nome'] ?? '') ?>
                    em <?= h(formatDateTime((string)($itemHistorico['data_hora'] ?? ''))) ?>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          <?php else: ?>
            <p class="denuncia-detalhe__vazio">Sem alterações de status registradas.</p>
          <?php endif; ?>
        </div>

        <!-- Comentários -->
        <div class="denuncia-detalhe__bloco">
          <div class="denuncia-detalhe__bloco-cabecalho">
            <h3 class="denuncia-detalhe__bloco-titulo">Comentários</h3>
          </div>

          <?php if (!empty($comentarios)): ?>
            <div class="denuncia-detalhe__lista">
              <?php foreach ($comentarios as $comentario): ?>
                <div class="denuncia-detalhe__item">
                  <div class="denuncia-detalhe__item-topo">
                    <strong><?= h($comentario['usuario_nome'] ?? 'Usuário') ?></strong>
                    <span class="denuncia-detalhe__item-data">
                      <?= h(formatDateTime((string)($comentario['data_hora'] ?? ''))) ?>
                    </span>
                  </div>

                  <div class="denuncia-detalhe__item-corpo">
                    <?= nl2br(h($comentario['mensagem'] ?? '')) ?>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          <?php else: ?>
            <p class="denuncia-detalhe__vazio">Nenhum comentário ainda.</p>
          <?php endif; ?>

          <div class="denuncia-detalhe__bloco-rodape">
            <?php if (!$usuarioLogado): ?>
              <h4 class="denuncia-detalhe__subtitulo">
                <a
                  href="<?= BASE_URL ?>/index.php?c=auth&a=login&return=<?= urlencode($urlRetorno) ?>"
                  class="denuncia-detalhe__link-adicionar"
                >
                  Adicionar comentário
                </a>
              </h4>
            <?php endif; ?>

            <?php if ($usuarioLogado): ?>
              <form
                id="form-comentario"
                method="post"
                action="<?= BASE_URL ?>/index.php?c=comentario&a=adicionar"
                class="denuncia-detalhe__form-comentario"
              >
                <input type="hidden" name="animal_id" value="<?= h($denunciaId) ?>">

                <textarea
                  name="mensagem"
                  rows="3"
                  required
                  placeholder="Publicar comentário"
                ></textarea>

                <div class="denuncia-detalhe__form-comentario-acoes">
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
  (function () {
    const imagemPrincipal = document.querySelector('.denuncia-detalhe__imagem-principal img');
    const miniaturas = document.querySelectorAll('.denuncia-detalhe__miniatura');

    if (!imagemPrincipal || !miniaturas.length) return;

    miniaturas.forEach((miniatura) => {
      miniatura.addEventListener('click', () => {
        const novaFonte = miniatura.getAttribute('data-src');
        if (!novaFonte) return;
        imagemPrincipal.src = novaFonte;
      });
    });
  })();
</script>