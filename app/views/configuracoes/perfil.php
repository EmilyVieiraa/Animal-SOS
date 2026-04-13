<?php
declare(strict_types=1);
require_once APP_PATH . 'helpers/view_helpers.php';

$isOwnProfile = !empty($isOwnProfile);
$isPublico = !$isOwnProfile;
$modo = $isPublico ? 'publico' : 'meu';

/* ===== Campos base ===== */
$nome   = (string)($usuario['nome'] ?? 'Usuário');
$cidade = (string)($usuario['cidade'] ?? '');
$estado = (string)($usuario['estado'] ?? '');
$local  = trim($cidade . ($estado ? ' - ' . $estado : ''));
$inic = mb_strtoupper(mb_substr($nome, 0, 1));
$denunciasCount = (int)($denunciasCount ?? 0);
$mostrarEmail = !empty($usuario['mostrar_email']);
$mostrarWhats = !empty($usuario['mostrar_whatsapp']);

$email = trim((string)($usuario['email'] ?? ''));
$tel   = trim((string)($usuario['telefone'] ?? ''));

/* regra: se for meu perfil, pode mostrar; se for público, depende das flags */
$podeVerEmail = (!empty($isOwnProfile) || $mostrarEmail) && $email !== '';
$podeVerWhats = (!empty($isOwnProfile) || $mostrarWhats) && $tel !== '';

/* ===== Tipo de usuário + Endereço (ajuste conforme seu BD) ===== */
$tipoUsuario = (string)($usuario['tipo_usuario'] ?? 'Comum');

$rua    = trim((string)($usuario['rua'] ?? ''));
$numero = trim((string)($usuario['numero'] ?? ''));
$bairro = trim((string)($usuario['bairro'] ?? ''));

$fotoPerfil = (string)($usuario['foto_perfil'] ?? '');

$enderecoLinha1 = trim($rua . ($numero !== '' ? ', ' . $numero : ''));
$enderecoLinha2 = trim($bairro . ($cidade !== '' ? ' • ' . $cidade : '') . ($estado !== '' ? ' - ' . $estado : ''));
?>

<?php if ($isPublico): ?>

<section class="perfil-publico">

  <div class="perfil-hero">
    <div class="container perfil-hero-inner">
      <a class="perfil-back" href="javascript:history.back()">← Voltar</a>
    </div>
  </div>

  <div class="container perfil-wrap">

    <article class="perfil-card-main">

      <div class="perfil-avatar" aria-label="Avatar do usuário">
        <?php if ($fotoPerfil && file_exists(PUBLIC_PATH . '/' . $fotoPerfil)): ?>
          <img src="<?= h(BASE_URL) ?>/<?= h($fotoPerfil) ?>" alt="<?= h($nome) ?>" class="perfil-avatar-img">
        <?php else: ?>
          <span><?= h($inic) ?></span>
        <?php endif; ?>
      </div>

      <div class="perfil-main">

        <!-- Nome -->
        <div class="perfil-top">
          <h1 class="perfil-name"><?= h($nome) ?></h1>
        </div>

        <!-- Contador -->
        <div class="perfil-followline">
          <span class="perfil-count">
            <strong><?= $denunciasCount ?></strong> Denúncias publicadas
          </span>
        </div>

        <!-- Contato (sempre no mesmo layout; valor muda) -->
        <div class="perfil-contato">
          <h3>Sobre</h3>

          <ul class="perfil-contato-list">
            <li>
              <span class="c-ico">✉️</span>
              <span class="c-label">Email:</span>
              <span class="c-value">
                <?php if ($podeVerEmail): ?>
                  <?= h($email) ?>
                <?php else: ?>
                  <span class="contato-bloqueado">Não disponível publicamente</span>
                <?php endif; ?>
              </span>
            </li>

            <li>
              <span class="c-ico">📞</span>
              <span class="c-label">WhatsApp:</span>
              <span class="c-value">
                <?php if ($podeVerWhats): ?>
                  <?= h($tel) ?>
                <?php else: ?>
                  <span class="contato-bloqueado">Não disponível publicamente</span>
                <?php endif; ?>
              </span>
            </li>
          </ul>
        </div>

        <!-- Tipo de usuário + Endereço (mesmo layout do print) -->
        <div class="perfil-tipo">

          <ul class="perfil-contato-list">
            <li>
              <span class="c-ico">🏷️</span>
              <span class="c-label">Tipo de usuário:</span>
              <span class="c-value"><?= h($tipoUsuario) ?></span>
            </li>

            <li>
              <span class="c-ico">📍</span>
              <span class="c-label">Endereço:</span>
              <span class="c-value">
                <?php if ($enderecoLinha1 !== ''): ?>
                  <?= h($enderecoLinha1) ?>
                  <?php if ($enderecoLinha2 !== ''): ?>
                    <br><span class="contato-sub"><?= h($enderecoLinha2) ?></span>
                  <?php endif; ?>
                <?php elseif ($enderecoLinha2 !== ''): ?>
                  <?= h($enderecoLinha2) ?>
                <?php else: ?>
                  <span class="contato-bloqueado">Não informado</span>
                <?php endif; ?>
              </span>
            </li>
          </ul>
        </div>

      </div><!-- /perfil-main -->

    </article>

    <section class="perfil-denuncias-card">

      <div class="perfil-denuncias-head">
        <h2>Denúncias deste usuário</h2>
        <p class="muted">
          <?= !empty($denuncias) ? 'Veja as denúncias publicadas por ' . h($nome) . '.' : 'Nenhuma denúncia publicada até o momento.' ?>
        </p>
      </div>

      <?php if (!empty($denuncias)): ?>
        <div class="perfil-denuncias-grid">

          <?php foreach ($denuncias as $d): ?>
            <?php
              /**
               * Normaliza o array da denúncia ($d) para o formato esperado pelo partial ($a)
               * Campos esperados pelo partial:
               * id, foto, titulo, especie, condicao, descricao, data_hora, status, usuario_nome
               */

              $dadosDenuncia = [];

              // id
              $dadosDenuncia['id'] = (string)($d['id'] ?? '');

              // foto (vários nomes possíveis no seu array)
              $dadosDenuncia['foto'] = (string)($d['foto'] ?? $d['imagem_url'] ?? $d['imagem'] ?? $d['caminho_imagem'] ?? '');

              // título
              $dadosDenuncia['titulo'] = (string)($d['titulo'] ?? 'Denúncia');

              // especie (no perfil você tinha "tipo", então caímos nele se não existir "especie")
              $dadosDenuncia['especie'] = (string)($d['especie'] ?? $d['tipo'] ?? 'Animal');

              // condição (se existir)
              $dadosDenuncia['condicao'] = (string)($d['condicao'] ?? '');

              // descrição
              $dadosDenuncia['descricao'] = (string)($d['descricao'] ?? '');

              // data/hora (ajuste conforme seu retorno real)
              $dadosDenuncia['data_hora'] = (string)($d['data_hora'] ?? $d['data_cadastro'] ?? $d['criado_em'] ?? '');

              // status
              $dadosDenuncia['status'] = (string)($d['status'] ?? '');

              // nome do autor (no perfil, o autor é o próprio dono do perfil)
              $dadosDenuncia['usuario_nome'] = $nome;

              // Variante grande no perfil
              $variacaoCard = 'lg';

              // Renderiza o card reutilizável
              require APP_PATH . 'views/partials/_animal_card.php';
            ?>
          <?php endforeach; ?>

        </div>
      <?php endif; ?>

    </section>

  </div><!-- /container -->
</section>

<!--- meu perfil ------------------------------------------------------------------------------------------------------------------------------------->
<?php else: ?>

<section class="perfil-publico perfil-meu">

  <div class="perfil-hero">
    <div class="container perfil-hero-inner">
      <a class="perfil-back" href="<?= h(BASE_URL) ?>/index.php?c=paginas&a=home">← Voltar</a>
    </div>
  </div>

  <div class="container perfil-wrap">

    <?php if (!empty($erro)): ?>
      <div class="perfil-alert perfil-alert--error"><?= h($erro) ?></div>
    <?php endif; ?>

    <?php if (!empty($sucesso)): ?>
      <div class="perfil-alert perfil-alert--success"><?= h($sucesso) ?></div>
    <?php endif; ?>

    <form method="post" class="perfil-form">
      <article class="perfil-card-main">

        <div class="perfil-avatar" aria-label="Avatar do usuário">
          <?php if ($fotoPerfil && file_exists(PUBLIC_PATH . '/' . $fotoPerfil)): ?>
            <img src="<?= h(BASE_URL) ?>/<?= h($fotoPerfil) ?>" alt="<?= h($nome) ?>" class="perfil-avatar-img">
          <?php else: ?>
            <span><?= h($inic) ?></span>
          <?php endif; ?>
        </div>

        <div class="perfil-main">

          <!-- Nome (editável) -->
          <div class="perfil-top">

              <div class="perfil-name-edit">
                <!-- Nome (visual) -->
                <h1 class="perfil-name" id="nomeDisplay"><?= h($usuario['nome'] ?? '') ?></h1>

                <!-- Lápis (à direita do nome) -->
                <button type="button" class="perfil-pen" onclick="toggleNomeEdit()" aria-label="Editar nome">
                  <svg viewBox="0 0 24 24" class="icon-pen">
                    <path d="M3 17.25V21h3.75L17.8 9.94l-3.75-3.75L3 17.25zM20.7 7.04a1 1 0 0 0 0-1.41l-2.34-2.34a1 1 0 0 0-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/>
                  </svg>
                </button>

                <!-- Input (aparece ao editar) -->
                <input
                  id="nomeInput"
                  class="perfil-name-input"
                  type="text"
                  name="nome"
                  value="<?= h($usuario['nome'] ?? '') ?>"
                  style="display:none;"
                  required
                >
              </div>
          </div>

          <!-- Contador -->
          <div class="perfil-followline">
            <span class="perfil-count">
              <strong><?= $denunciasCount ?></strong> Denúncias publicadas
            </span>
          </div>

          <!-- Dados (Tipo de usuário, Email, Privacidade, WhatsApp e Endereço) -->
          <div class="perfil-contato">
            <h3>Informações</h3>

            <ul class="perfil-contato-list">
              <!-- 1. Tipo de usuário -->
              <li>
                <span class="c-ico">🏷️</span>
                <span class="c-label">Tipo de usuário:</span>
                <span class="c-value"><?= h($tipoUsuario) ?></span>
              </li>

              <!-- 2. Email -->
              <li>
                <span class="c-ico">✉️</span>
                <span class="c-label">Email:</span>
                <span class="c-value">
                  <?= h($email !== '' ? $email : 'Não informado') ?>
                </span>
              </li>

              <!-- 3. Privacidade -->
              <li class="perfil-priv">
                <span class="c-ico">🔒</span>
                <span class="c-label">Privacidade:</span>
                <span class="c-value">
                  <label class="perfil-check">
                    <input type="checkbox" name="mostrar_email" value="1"
                      <?= !empty($usuario['mostrar_email']) ? 'checked' : '' ?>>
                    Mostrar meu email no perfil público
                  </label>

                  <label class="perfil-check">
                    <input type="checkbox" name="mostrar_whatsapp" value="1"
                      <?= !empty($usuario['mostrar_whatsapp']) ? 'checked' : '' ?>>
                    Mostrar meu WhatsApp no perfil público
                  </label>
                </span>
              </li>

              <!-- 4. WhatsApp -->
              <li>
                <span class="c-ico">📞</span>
                <span class="c-label">WhatsApp:</span>
                <span class="c-value">
                  <input
                    class="perfil-input"
                    type="text"
                    name="telefone"
                    value="<?= h($usuario['telefone'] ?? '') ?>"
                    placeholder="(xx) xxxxx-xxxx"
                  >
                </span>
              </li>

              <!-- 5. Endereço -->
              <li>
                <span class="c-ico">📍</span>
                <span class="c-label">Endereço:</span>
                <span class="c-value">
                  <div class="perfil-endereco-form">
                    <!-- Linha 1: Rua, Número, Bairro e Cidade -->
                    <div class="endereco-row">
                      <div class="endereco-field">
                        <label for="rua">Rua:</label>
                        <input
                          id="rua"
                          class="perfil-input"
                          type="text"
                          name="rua"
                          value="<?= h($usuario['rua'] ?? '') ?>"
                          placeholder="Rua"
                        >
                      </div>
                      <div class="endereco-field endereco-field--small">
                        <label for="numero">Número:</label>
                        <input
                          id="numero"
                          class="perfil-input"
                          type="text"
                          name="numero"
                          value="<?= h($usuario['numero'] ?? '') ?>"
                          placeholder="Nº"
                        >
                      </div>
                      <div class="endereco-field">
                        <label for="bairro">Bairro:</label>
                        <input
                          id="bairro"
                          class="perfil-input"
                          type="text"
                          name="bairro"
                          value="<?= h($usuario['bairro'] ?? '') ?>"
                          placeholder="Bairro"
                        >
                      </div>
                      <div class="endereco-field">
                        <label for="cidade">Cidade:</label>
                        <input
                          id="cidade"
                          class="perfil-input"
                          type="text"
                          name="cidade"
                          value="<?= h($usuario['cidade'] ?? '') ?>"
                          placeholder="Cidade"
                        >
                      </div>
                    </div>

                    <!-- Linha 2: Estado e CEP -->
                    <div class="endereco-row">
                      <div class="endereco-field endereco-field--small">
                        <label for="estado">Estado:</label>
                        <input
                          id="estado"
                          class="perfil-input"
                          type="text"
                          name="estado"
                          value="<?= h($usuario['estado'] ?? '') ?>"
                          placeholder="UF"
                          maxlength="2"
                        >
                      </div>
                      <div class="endereco-field">
                        <label for="cep">CEP:</label>
                        <input
                          id="cep"
                          class="perfil-input"
                          type="text"
                          name="cep"
                          value="<?= h($usuario['cep'] ?? '') ?>"
                          placeholder="00000-000"
                        >
                      </div>
                    </div>
                  </div>
                </span>
              </li>
            </ul>
          </div>

          <!-- UM ÚNICO botão salvar -->
          <button type="submit" class="btn-perfil" style="margin-top: 16px;">Salvar</button>

        </div><!-- /perfil-main -->

      </article>
    </form>

    <!-- Denúncias (igual ao público, reaproveita o mesmo bloco que você já tem) -->
    <section class="perfil-denuncias-card">

      <div class="perfil-denuncias-head">
        <h2>Minhas denúncias</h2>
        <p class="muted">
          <?= !empty($denuncias) ? 'Acompanhe as denúncias publicadas por você.' : 'Você ainda não publicou denúncias.' ?>
        </p>
      </div>

      <?php if (!empty($denuncias)): ?>
        <div class="perfil-denuncias-grid">
          <?php foreach ($denuncias as $d): ?>
            <?php
              $dadosDenuncia = [];
              $dadosDenuncia['id'] = (string)($d['id'] ?? '');
              $dadosDenuncia['foto'] = (string)($d['foto'] ?? $d['imagem_url'] ?? $d['imagem'] ?? $d['caminho_imagem'] ?? '');
              $dadosDenuncia['titulo'] = (string)($d['titulo'] ?? 'Denúncia');
              $dadosDenuncia['especie'] = (string)($d['especie'] ?? $d['tipo'] ?? 'Animal');
              $dadosDenuncia['condicao'] = (string)($d['condicao'] ?? '');
              $dadosDenuncia['descricao'] = (string)($d['descricao'] ?? '');
              $dadosDenuncia['data_hora'] = (string)($d['data_hora'] ?? $d['data_cadastro'] ?? $d['criado_em'] ?? '');
              $dadosDenuncia['status'] = (string)($d['status'] ?? '');
              $dadosDenuncia['usuario_nome'] = $nome;

              $variacaoCard = 'default'; // mantém igual ao listar (pode trocar se quiser)
              require APP_PATH . 'views/partials/_animal_card.php';
            ?>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

    </section>

  </div><!-- /container -->
</section>

<script>
function toggleNomeEdit(){
  const display = document.getElementById('nomeDisplay');
  const input   = document.getElementById('nomeInput');
  if (!display || !input) return;

  const editing = input.style.display !== 'none';

  if (!editing) {
    display.style.display = 'none';
    input.style.display = 'block';
    input.focus();
    input.setSelectionRange(input.value.length, input.value.length);
  } else {
    // volta a mostrar o nome (sem salvar ainda)
    display.style.display = 'block';
    input.style.display = 'none';
  }
}
</script>

<script>
  document.addEventListener('input', (e) => {
    if (e.target && e.target.id === 'nomeInput') {
      const display = document.getElementById('nomeDisplay');
      if (display) display.textContent = e.target.value;
    }
  });
</script>

<?php endif; ?>