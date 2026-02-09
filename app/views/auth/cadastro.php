<h1>Cadastro</h1>

<?php if (!empty($erro)): ?>
    <p style="color:red;"><?= htmlspecialchars($erro) ?></p>
<?php endif; ?>

    <div class="register-modal">
    <h2>Criar conta</h2>

    <?php if (!empty($_SESSION['flash_registro_erro'])): ?>
        <div class="alert alert-error">
        <?= htmlspecialchars($_SESSION['flash_registro_erro']) ?>
        </div>
        <?php unset($_SESSION['flash_registro_erro']); ?>
    <?php endif; ?>

    <?php if (!empty($_SESSION['flash_registro_sucesso'])): ?>
        <div class="alert alert-success">
        <?= htmlspecialchars($_SESSION['flash_registro_sucesso']) ?>
        </div>
        <?php unset($_SESSION['flash_registro_sucesso']); ?>
    <?php endif; ?>

    <form method="post" action="/animalSOS/public/index.php?c=auth&a=registro">
        ...
    </form>
    </div>

<form method="post">
    <label>Nome</label><br>
    <input type="text" name="nome" required><br><br>

    <label>E-mail</label><br>
    <input type="email" name="email" required><br><br>

    <label>Senha</label><br>
    <input
    type="password"
    name="senha"
    minlength="8"
    required
    autocomplete="new-password"
    /><br><br>

    <label>Telefone</label><br>
    <input type="text" name="telefone"><br><br>

    <label>Tipo de usuário</label><br>
    <select name="tipo_usuario">
        <option value="Comum">Comum</option>
        <option value="Voluntário">Voluntário</option>
        <option value="Admin">Admin</option>
    </select><br><br>

    <button type="submit">Cadastrar</button>
</form>

<p>
    <a href="<?= BASE_URL ?>/index.php?c=auth&a=login">Voltar para login</a>
</p>
