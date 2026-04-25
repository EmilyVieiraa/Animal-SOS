<?php
declare(strict_types=1);

require_once APP_PATH . 'helpers/view_helpers.php';

// Dados do animal reportado
$animalId = (string)($animal['id'] ?? '');
$tituloAnimal = (string)($animal['titulo'] ?? '');
$descricaoAnimal = (string)($animal['descricao'] ?? '');
$especieAnimal = (string)($animal['especie'] ?? '—');
$condicaoAnimal = (string)($animal['condicao'] ?? '—');
$corAnimal = (string)($animal['cor'] ?? '—');
$localizacaoAnimal = (string)($animal['localizacao'] ?? '—');
$statusAnimal = (string)($animal['status'] ?? 'Aguardando');
$dataRegistro = formatDateTime((string)($animal['data_criacao'] ?? ''));
$nomeAutor = (string)($animal['usuario_nome'] ?? '—');
$autorId = (string)($animal['usuario_id'] ?? '');

// Sessão e mensagens
$usuarioLogado    = !empty($_SESSION['usuario_id']);
$mensagemSucesso  = (string)($mensagemSucesso ?? '');
$mensagemErro     = (string)($mensagemErro ?? '');
$mensagemInfo     = (string)($mensagemInfo ?? '');

// Dados preparados no controller
$podeAtualizarStatus = (bool)($podeAlterarStatus ?? false);
$statusDisponiveisParaAtualizacao = is_array($statusDisponiveis ?? null) ? $statusDisponiveis : [];
$imagemPlaceholderTela = (string)($imagemPlaceholder ?? (BASE_URL . '/assets/img/placeholder-animal.jpg'));

// URL de retorno para login
$urlRetorno = '/index.php?c=animal&a=detalhes&id=' . urlencode($animalId);

// Dados de comentários (contrato vindo do controller/model)
$listaComentarios = is_array($comentarios ?? null) ? $comentarios : [];

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

    <?php if ($mensagemInfo !== ''): ?>
      <div class="flash flash--info">
        <?= h($mensagemInfo) ?>
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
          <?= h($tituloAnimal !== '' ? $tituloAnimal : 'Animal sem título') ?>
        </h2>

        <?php if ($descricaoAnimal !== ''): ?>
          <p class="denuncia-detalhe__descricao"><?= nl2br(h($descricaoAnimal)) ?></p>
        <?php else: ?>
          <p class="denuncia-detalhe__descricao denuncia-detalhe__descricao--suave">Sem descrição.</p>
        <?php endif; ?>

        <div class="denuncia-detalhe__metadados denuncia-detalhe__metadados--triplo">
          <div class="denuncia-detalhe__metadado-item">
            <span class="denuncia-detalhe__metadado-rotulo">Status</span>
            <span class="denuncia-detalhe__metadado-valor"><?= h($statusAnimal) ?></span>
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
              <span class="denuncia-detalhe__info-valor"><?= h($localizacaoAnimal) ?></span>
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
              <input type="hidden" name="animal_id" value="<?= h($animalId) ?>">

                <?= csrfInput(CSRF_CONTEXTO_ANIMAL_ATUALIZAR_STATUS) ?>

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
                      <?= (string)$statusOpcao === $statusAnimal ? 'selected' : '' ?>
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

          <?php if (!empty($listaComentarios)): ?>
            <div class="denuncia-detalhe__lista">
              <?php foreach ($listaComentarios as $comentarioItem): ?>
                <div class="denuncia-detalhe__item">
                  <div class="denuncia-detalhe__item-topo">
                    <strong><?= h($comentarioItem['usuario_nome'] ?? 'Usuário') ?></strong>
                    <span class="denuncia-detalhe__item-data">
                      <?= h(formatDateTime((string)($comentarioItem['data_hora'] ?? ''))) ?>
                    </span>
                  </div>

                  <div class="denuncia-detalhe__item-corpo">
                    <?= nl2br(h($comentarioItem['mensagem'] ?? '')) ?>
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
                <?= csrfInput(CSRF_CONTEXTO_COMENTARIO_ADICIONAR) ?>
                <input type="hidden" name="animal_id" value="<?= h($animalId) ?>">

                <label for="mensagem-comentario-detalhes">Mensagem</label>
                <textarea
                  id="mensagem-comentario-detalhes"
                  name="mensagem"
                  rows="3"
                  maxlength="1000"
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