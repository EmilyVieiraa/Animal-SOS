<?php
declare(strict_types=1);
require_once APP_PATH . 'helpers/view_helpers.php';

$isOwnProfile = !empty($isOwnProfile);
$isPublicProfile = !$isOwnProfile;

/* ===== Campos base ===== */
$nomeUsuario = (string)($usuario['nome'] ?? 'Usuário');
$cidade = (string)($usuario['cidade'] ?? '');
$estado = (string)($usuario['estado'] ?? '');
$inicialNomeUsuario = mb_strtoupper(mb_substr($nomeUsuario, 0, 1));
$denunciasCount = (int)($denunciasCount ?? 0);
$mostrarEmail = !empty($usuario['mostrar_email']);
$mostrarWhats = !empty($usuario['mostrar_whatsapp']);

$email = trim((string)($usuario['email'] ?? ''));
$telefone = trim((string)($usuario['telefone'] ?? ''));

/* regra: se for meu perfil, pode mostrar; se for público, depende das flags */
$podeVerEmail = ($isOwnProfile || $mostrarEmail) && $email !== '';
$podeVerWhats = ($isOwnProfile || $mostrarWhats) && $telefone !== '';

/* ===== Tipo de usuário + Endereço (ajuste conforme seu BD) ===== */
$tipoUsuario = (string)($usuario['tipo_usuario'] ?? 'Comum');

$rua    = trim((string)($usuario['rua'] ?? ''));
$numero = trim((string)($usuario['numero'] ?? ''));
$bairro = trim((string)($usuario['bairro'] ?? ''));

$fotoPerfil = (string)($usuario['foto_perfil'] ?? '');
$listaDenuncias = is_array($denuncias ?? null) ? $denuncias : [];

$enderecoLinha1 = trim($rua . ($numero !== '' ? ', ' . $numero : ''));
$enderecoLinha2 = trim($bairro . ($cidade !== '' ? ' • ' . $cidade : '') . ($estado !== '' ? ' - ' . $estado : ''));

$normalizarDadosDenuncia = static function (array $denuncia, string $nomeAutor): array {
  return [
    'id' => (string)($denuncia['id'] ?? ''),
    'foto' => (string)($denuncia['foto'] ?? ''),
    'titulo' => (string)($denuncia['titulo'] ?? 'Denúncia'),
    'especie' => (string)($denuncia['especie'] ?? 'Animal'),
    'condicao' => (string)($denuncia['condicao'] ?? ''),
    'descricao' => (string)($denuncia['descricao'] ?? ''),
    'data_hora' => (string)($denuncia['data_hora'] ?? ''),
    'status' => (string)($denuncia['status'] ?? ''),
    'usuario_nome' => $nomeAutor,
  ];
};

$renderizarHeroPerfil = static function (string $urlVoltar): void {
  ?>
  <div class="perfil-hero">
    <div class="container perfil-hero-inner">
      <a class="perfil-back" href="<?= h($urlVoltar) ?>">← Voltar</a>
    </div>
  </div>
  <?php
};

$renderizarAvatarPerfil = static function (string $fotoPerfil, string $nomeUsuario, string $inicialNomeUsuario): void {
  ?>
  <div class="perfil-avatar" aria-label="Avatar do usuário">
    <?php if ($fotoPerfil && file_exists(PUBLIC_PATH . '/' . $fotoPerfil)): ?>
      <img src="<?= h(BASE_URL) ?>/<?= h($fotoPerfil) ?>" alt="<?= h($nomeUsuario) ?>" class="perfil-avatar-img">
    <?php else: ?>
      <span><?= h($inicialNomeUsuario) ?></span>
    <?php endif; ?>
  </div>
  <?php
};

$renderizarContadorDenuncias = static function (int $denunciasCount): void {
  ?>
  <div class="perfil-followline">
    <span class="perfil-count">
      <strong><?= $denunciasCount ?></strong> Denúncias publicadas
    </span>
  </div>
  <?php
};

$renderizarSecaoDenuncias = static function (
  array $listaDenuncias,
  string $titulo,
  string $textoComItens,
  string $textoSemItens,
  string $nomeUsuario,
  callable $normalizarDadosDenuncia,
  string $variacaoCard
): void {
  ?>
  <section class="perfil-denuncias-card">

    <div class="perfil-denuncias-head">
      <h2><?= h($titulo) ?></h2>
      <p class="muted">
        <?= !empty($listaDenuncias) ? h($textoComItens) : h($textoSemItens) ?>
      </p>
    </div>

    <?php if (!empty($listaDenuncias)): ?>
      <div class="perfil-denuncias-grid">
        <?php foreach ($listaDenuncias as $denuncia): ?>
          <?php
            $dadosDenuncia = $normalizarDadosDenuncia($denuncia, $nomeUsuario);
            require APP_PATH . 'views/partials/_animal_card.php';
          ?>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

  </section>
  <?php
};
?>

<?php if ($isPublicProfile): ?>

<section class="perfil-publico">

  <?php $renderizarHeroPerfil('javascript:history.back()'); ?>

  <div class="container perfil-wrap">

    <article class="perfil-card-main">

      <?php $renderizarAvatarPerfil($fotoPerfil, $nomeUsuario, $inicialNomeUsuario); ?>

      <div class="perfil-main">

        <!-- Nome -->
        <div class="perfil-top">
          <h1 class="perfil-name"><?= h($nomeUsuario) ?></h1>
        </div>

        <?php $renderizarContadorDenuncias($denunciasCount); ?>

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
                  <?= h($telefone) ?>
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

    <?php
      $variacaoCard = 'lg';
      $renderizarSecaoDenuncias(
        $listaDenuncias,
        'Denúncias deste usuário',
        'Veja as denúncias publicadas por ' . $nomeUsuario . '.',
        'Nenhuma denúncia publicada até o momento.',
        $nomeUsuario,
        $normalizarDadosDenuncia,
        $variacaoCard
      );
    ?>

  </div><!-- /container -->
</section>

<!--- meu perfil ------------------------------------------------------------------------------------------------------------------------------------->
<?php else: ?>

<section class="perfil-publico perfil-meu">

  <?php $renderizarHeroPerfil(BASE_URL . '/index.php?c=paginas&a=home'); ?>

  <div class="container perfil-wrap">

    <?php if (!empty($erro)): ?>
      <div class="perfil-alert perfil-alert--error"><?= h($erro) ?></div>
    <?php endif; ?>

    <?php if (!empty($sucesso)): ?>
      <div class="perfil-alert perfil-alert--success"><?= h($sucesso) ?></div>
    <?php endif; ?>

    <form method="post" class="perfil-form">
      <article class="perfil-card-main">

        <?php $renderizarAvatarPerfil($fotoPerfil, $nomeUsuario, $inicialNomeUsuario); ?>

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

          <?php $renderizarContadorDenuncias($denunciasCount); ?>

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
    <?php
      $variacaoCard = 'default';
      $renderizarSecaoDenuncias(
        $listaDenuncias,
        'Minhas denúncias',
        'Acompanhe as denúncias publicadas por você.',
        'Você ainda não publicou denúncias.',
        $nomeUsuario,
        $normalizarDadosDenuncia,
        $variacaoCard
      );
    ?>

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