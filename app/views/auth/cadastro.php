<?php
declare(strict_types=1);

require_once APP_PATH . 'helpers/view_helpers.php';

$flashRegistroErro = flashConsumir('flash_registro_erro');
$flashRegistroSucesso = flashConsumir('flash_registro_sucesso');
?>

<div class="auth-acesso-pagina">
    <main class="auth-acesso-cartao auth-acesso-cartao--cadastro">
        <h1 class="auth-acesso-titulo">Cadastro</h1>
        <p class="auth-acesso-subtitulo">Preencha os dados abaixo para criar sua conta.</p>

        <?php if (!empty($erro)): ?>
            <p class="auth-mensagem auth-mensagem--erro"><?= h($erro) ?></p>
        <?php endif; ?>

        <?php if (!empty($flashRegistroErro)): ?>
            <div class="alert alert-error">
                <?= h($flashRegistroErro) ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($flashRegistroSucesso)): ?>
            <div class="alert alert-success">
                <?= h($flashRegistroSucesso) ?>
            </div>
        <?php endif; ?>

        <form method="post" action="<?= BASE_URL ?>/index.php?c=auth&a=registro" enctype="multipart/form-data" class="auth-acesso-formulario auth-acesso-formulario--cadastro">
            <?= csrfInput('auth_registro') ?>
            <div class="auth-acesso-campo">
                <label for="cadastro_nome">Nome <span style="color:red;">*</span></label>
                <input id="cadastro_nome" type="text" name="nome" required>
            </div>

            <div class="auth-acesso-campo">
                <label for="cadastro_email">E-mail <span style="color:red;">*</span></label>
                <input id="cadastro_email" type="email" name="email" required>
            </div>

            <div class="auth-acesso-campo">
                <label for="cadastro_senha">Senha <span style="color:red;">*</span></label>
                <input
                id="cadastro_senha"
                type="password"
                name="senha"
                minlength="8"
                required
                autocomplete="new-password"
                >
            </div>

            <div class="auth-acesso-campo">
                <label for="cadastro_telefone">Telefone</label>
                <input id="cadastro_telefone" type="text" name="telefone">
            </div>

            <div class="auth-acesso-campo">
                <label for="cadastro_tipo_usuario">Tipo de usuário</label>
                <select id="cadastro_tipo_usuario" name="tipo_usuario">
                    <option value="Comum">Comum</option>
                    <option value="ONG">ONG</option>
                    <option value="Autoridade">Autoridade</option>
                </select>
            </div>

            <button type="submit" class="btn btn-primary">Cadastrar</button>
        </form>

        <p class="auth-acesso-links">
            <a href="<?= BASE_URL ?>/index.php?c=auth&a=login">Voltar para login</a>
        </p>
    </main>
</div>
