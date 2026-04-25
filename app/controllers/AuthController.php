<?php
declare(strict_types=1);

require_once APP_PATH . 'helpers/view_helpers.php';

final class AuthController extends Controller
{
    private const CSRF_CONTEXTO_LOGIN = 'auth_login';
    private const CSRF_CONTEXTO_REGISTRO = 'auth_registro';
    private const CSRF_CONTEXTO_ESQUECI_SENHA = 'auth_esqueci_senha';
    private const CSRF_CONTEXTO_REDEFINIR_SENHA = 'auth_redefinir_senha';
    private const TIPOS_USUARIO_PERMITIDOS = ['Comum', 'ONG', 'Autoridade'];

    private Usuario $usuarioModel;

    public function __construct()
    {
        $this->usuarioModel = new Usuario();
    }

    // ============================================================
    // Login
    // ============================================================

    public function login(): void
    {
        if (!empty($_SESSION['usuario_id'])) {
            $this->redirect('/index.php?c=animal&a=listar');
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->processarPostLogin();
            return;
        }

        $this->processarGetLogin();
    }

    private function processarPostLogin(): void
    {
        if (!$this->validarCsrf(self::CSRF_CONTEXTO_LOGIN)) {
            $this->falharLogin('Sua sessão expirou. Tente novamente.');
            return;
        }

        $email = trim((string)($_POST['email'] ?? ''));
        $senha = (string)($_POST['senha'] ?? '');

        if ($email === '' || $senha === '') {
            $this->falharLogin('Informe e-mail e senha.');
            return;
        }

        $usuario = $this->usuarioModel->encontrarPorEmail($email);

        if (!$usuario || !password_verify($senha, $usuario['senha_hash'])) {
            $this->falharLogin('E-mail ou senha inválidos.');
            return;
        }

        // Renova o ID de sessão para prevenir session fixation no login.
        // TODO (controller): avaliar impacto em sessões paralelas antes de produção.
        session_regenerate_id(true);

        $_SESSION['usuario_id']   = $usuario['id'];
        $_SESSION['usuario_nome'] = $usuario['nome'];

        if (!empty($usuario['tipo_usuario'])) {
            $_SESSION['tipo_usuario'] = $usuario['tipo_usuario'];
        }

        // Remove chave de sessão legada, se existir.
        unset($_SESSION['user']);

        $this->redirect($this->resolverRetorno());
    }

    private function processarGetLogin(): void
    {
        $retorno = (string)($_GET['return'] ?? '');

        if ($retorno !== '' && $this->retornoEhValido($retorno)) {
            $_SESSION['return_to'] = $retorno;
        }

        $_SESSION['open_modal'] = 'login';
        $this->redirect('/index.php?c=paginas&a=home');
    }

    /**
     * Grava flash de erro e redireciona para home com o modal de login aberto.
        * Contrato com PaginasController: chave 'flash_error' + 'open_modal' = 'login'.
     */
    private function falharLogin(string $mensagem): void
    {
        flashDefinir('flash_error', $mensagem);
        $_SESSION['open_modal']  = 'login';
        $this->redirect('/index.php?c=paginas&a=home');
    }

    /**
     * Resolve para onde redirecionar após login bem-sucedido.
     * Aceita retorno via POST, GET ou sessão desde que seja URL interna.
     */
    private function resolverRetorno(): string
    {
        $retorno = (string)($_POST['return'] ?? $_GET['return'] ?? ($_SESSION['return_to'] ?? ''));
        unset($_SESSION['return_to']);

        if ($retorno !== '' && $this->retornoEhValido($retorno)) {
            if (str_starts_with($retorno, BASE_URL)) {
                $retorno = substr($retorno, strlen(BASE_URL)) ?: '/';
            }
            return $retorno;
        }

        return '/index.php?c=animal&a=listar';
    }

    /**
     * Verifica se a URL de retorno é segura (relativa interna ou absoluta com BASE_URL).
     */
    private function retornoEhValido(string $retorno): bool
    {
        $retorno = trim($retorno);
        if ($retorno === '') {
            return false;
        }

        // Bloqueia quebras de linha no header Location e barras invertidas ambíguas.
        if (strpbrk($retorno, "\r\n") !== false || str_contains($retorno, '\\')) {
            return false;
        }

        // Somente caminhos internos absolutos (sem esquema relativo "//host").
        if (str_starts_with($retorno, '/')) {
            return !str_starts_with($retorno, '//');
        }

        // Aceita URL absoluta somente quando for exatamente do próprio BASE_URL.
        if (!str_starts_with($retorno, BASE_URL)) {
            return false;
        }

        $basePartes = parse_url(BASE_URL);
        $retPartes  = parse_url($retorno);

        if (!is_array($basePartes) || !is_array($retPartes)) {
            return false;
        }

        $mesmoEsquema = ($retPartes['scheme'] ?? null) === ($basePartes['scheme'] ?? null);
        $mesmoHost    = ($retPartes['host'] ?? null) === ($basePartes['host'] ?? null);
        $mesmaPorta   = ($retPartes['port'] ?? null) === ($basePartes['port'] ?? null);

        if (!$mesmoEsquema || !$mesmoHost || !$mesmaPorta) {
            return false;
        }

        $basePath    = rtrim((string)($basePartes['path'] ?? ''), '/');
        $retornoPath = (string)($retPartes['path'] ?? '');

        if ($basePath === '') {
            return true;
        }

        if ($retornoPath === $basePath) {
            return true;
        }

        return str_starts_with($retornoPath, $basePath . '/');
    }

    // ============================================================
    // Registro
    // ============================================================

    public function registro(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/index.php?c=paginas&a=home');
            return;
        }

        $dados = $this->montarDadosRegistro();

        // Closure de erro: grava flash e redireciona para home com modal de cadastro aberto.
        $erroCadastro = function (string $mensagem): void {
            flashDefinir('flash_registro_erro', $mensagem);
            $_SESSION['open_modal'] = 'cadastro';
            $this->redirect('/index.php?c=paginas&a=home');
        };

        if (!$this->validarCsrf(self::CSRF_CONTEXTO_REGISTRO)) {
            $erroCadastro('Sua sessão expirou. Envie o cadastro novamente.');
            return;
        }

        if (!$this->validarDadosRegistro($dados, $erroCadastro)) {
            return;
        }

        $caminhoFoto = $this->processarUploadFotoPerfil($erroCadastro);
        if ($caminhoFoto === false) {
            return; // $erroCadastro já foi chamado internamente.
        }
        $dados['foto_perfil'] = $caminhoFoto;

        $cadastroCriado = $this->usuarioModel->criar($dados);

        if ($cadastroCriado) {
            flashDefinir('flash_registro_sucesso', 'Cadastro realizado com sucesso. Faça login.');
            $_SESSION['open_modal'] = 'login';
            $this->redirect('/index.php?c=paginas&a=home');
            return;
        }

        $erroCadastro('Erro ao cadastrar.');
    }

    private function montarDadosRegistro(): array
    {
        return [
            'nome'         => trim((string)($_POST['nome'] ?? '')),
            'email'        => trim((string)($_POST['email'] ?? '')),
            'senha'        => (string)($_POST['senha'] ?? ''),
            'telefone'     => trim((string)($_POST['telefone'] ?? '')),
            'tipo_usuario' => (string)($_POST['tipo_usuario'] ?? 'Comum'),
            'cep'          => trim((string)($_POST['cep'] ?? '')),
            'rua'          => trim((string)($_POST['rua'] ?? '')),
            'numero'       => trim((string)($_POST['numero'] ?? '')),
            'bairro'       => trim((string)($_POST['bairro'] ?? '')),
            'cidade'       => trim((string)($_POST['cidade'] ?? '')),
            'estado'       => trim((string)($_POST['estado'] ?? '')),
            'foto_perfil'  => null,
        ];
    }

    private function validarDadosRegistro(array &$dados, callable $erroCadastro): bool
    {
        // Formata CEP para o padrão 00000-000.
        if ($dados['cep'] !== '') {
            $digitos = preg_replace('/\D+/', '', $dados['cep']);
            if (strlen($digitos) === 8) {
                $dados['cep'] = substr($digitos, 0, 5) . '-' . substr($digitos, 5);
            }
        }

        if ($dados['cep'] !== '' && !preg_match('/^\d{5}-\d{3}$/', $dados['cep'])) {
            $erroCadastro('CEP inválido.');
            return false;
        }

        $ufsValidas = [
            'AC', 'AL', 'AP', 'AM', 'BA', 'CE', 'DF', 'ES', 'GO', 'MA', 'MT', 'MS',
            'MG', 'PA', 'PB', 'PR', 'PE', 'PI', 'RJ', 'RN', 'RS', 'RO', 'RR', 'SC',
            'SP', 'SE', 'TO',
        ];

        if ($dados['estado'] !== '' && !in_array($dados['estado'], $ufsValidas, true)) {
            $erroCadastro('Estado inválido.');
            return false;
        }

        if ($dados['nome'] === '' || $dados['email'] === '' || $dados['senha'] === '') {
            $erroCadastro('Nome, e-mail e senha são obrigatórios.');
            return false;
        }

        if (mb_strlen($dados['senha']) < 8) {
            $erroCadastro('A senha deve ter no mínimo 8 caracteres.');
            return false;
        }

        if (!in_array($dados['tipo_usuario'], self::TIPOS_USUARIO_PERMITIDOS, true)) {
            $erroCadastro('Tipo de usuário inválido.');
            return false;
        }

        if ($this->usuarioModel->encontrarPorEmail($dados['email'])) {
            $erroCadastro('Este e-mail já está cadastrado. Use outro e-mail ou faça login.');
            return false;
        }

        return true;
    }

    /**
     * Processa o upload da foto de perfil durante o cadastro.
     *
     * @param callable $erroCadastro  Closure que exibe mensagem de erro e redireciona.
     * @return string|null|false      Caminho relativo salvo no banco, null se sem foto,
     *                                ou false se ocorreu erro (já tratado pelo closure).
     */
    private function processarUploadFotoPerfil(callable $erroCadastro): string|null|false
    {
        if (!isset($_FILES['foto_perfil']) || !is_array($_FILES['foto_perfil'])) {
            return null;
        }

        $arquivo = $_FILES['foto_perfil'];

        if (($arquivo['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return null;
        }

        if ($arquivo['error'] !== UPLOAD_ERR_OK) {
            $erroCadastro('Erro no upload da foto.');
            return false;
        }

        if ($arquivo['size'] > 2 * 1024 * 1024) {
            $erroCadastro('A foto deve ter no máximo 2MB.');
            return false;
        }

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime  = $finfo->file($arquivo['tmp_name']);

        $extensoesPermitidas = [
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/webp' => 'webp',
        ];

        if (!isset($extensoesPermitidas[$mime])) {
            $erroCadastro('Formato inválido. Use JPG, PNG ou WEBP.');
            return false;
        }

        $diretorioUpload = dirname(__DIR__, 2) . '/public/uploads/usuarios';

        if (!is_dir($diretorioUpload)
            && !mkdir($diretorioUpload, 0775, true)
            && !is_dir($diretorioUpload)
        ) {
            $erroCadastro('Pasta de upload sem permissão.');
            return false;
        }

        if (!is_writable($diretorioUpload)) {
            $erroCadastro('Pasta de upload sem permissão.');
            return false;
        }

        $nomeArquivo   = 'user_' . bin2hex(random_bytes(8)) . '.' . $extensoesPermitidas[$mime];
        $destinoFisico = $diretorioUpload . '/' . $nomeArquivo;

        if (!move_uploaded_file($arquivo['tmp_name'], $destinoFisico)) {
            $erroCadastro('Falha ao salvar a imagem.');
            return false;
        }

        return 'uploads/usuarios/' . $nomeArquivo;
    }

    // ============================================================
    // Logout
    // ============================================================

    public function logout(): void
    {
        unset(
            $_SESSION['usuario_id'],
            $_SESSION['usuario_nome'],
            $_SESSION['tipo_usuario'],
            $_SESSION['user']           // chave legada; mantida por compatibilidade
        );

        session_regenerate_id(true);

        $this->redirect('/index.php?c=paginas&a=home');
    }

    // ============================================================
    // Recuperação de senha
    // ============================================================

    public function esqueciSenha(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->view('auth/esqueci_senha');
            return;
        }

        if (!$this->validarCsrf(self::CSRF_CONTEXTO_ESQUECI_SENHA)) {
            $this->view('auth/esqueci_senha', ['erro' => 'Sua sessão expirou. Tente novamente.']);
            return;
        }

        $email = trim((string)($_POST['email'] ?? ''));

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->view('auth/esqueci_senha', ['erro' => 'Informe um e-mail válido.']);
            return;
        }

        $usuario = $this->usuarioModel->encontrarPorEmail($email);

        if (!$usuario) {
            $this->renderizarRespostaRecuperacaoSenha();
            return;
        }

        $resetModel = new PasswordReset();
        $resetModel->invalidarTokensDoUsuario((string)$usuario['id']);

        $token     = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $token);
        $expiraEm  = (new DateTime('+30 minutes'))->format('Y-m-d H:i:s');

        $resetModel->criar((string)$usuario['id'], $tokenHash, $expiraEm);

        $linkRedefinicao = BASE_URL . '/index.php?c=auth&a=redefinirSenha&token=' . urlencode($token);
        $corpoEmail      = $this->construirCorpoEmailRedefinicao((string)$usuario['nome'], $linkRedefinicao);

        $emailEnviado = false;
        try {
            $emailEnviado = Mailer::send(
                $email,
                (string)$usuario['nome'],
                'Redefinição de Senha - Animal S.O.S',
                $corpoEmail
            );

            if (!$emailEnviado) {
                error_log('Falha ao enviar e-mail de redefinição para: ' . $email);
            }
        } catch (Exception $excecao) {
            error_log('Exceção ao enviar e-mail: ' . $excecao->getMessage());
        }

        if (defined('APP_ENV') && APP_ENV !== 'prod' && !$emailEnviado) {
            error_log('Link local de redefinição gerado para ' . $email . ': ' . $linkRedefinicao);
        }

        $this->renderizarRespostaRecuperacaoSenha();
    }

    private function renderizarRespostaRecuperacaoSenha(): void
    {
        $this->view('auth/esqueci_senha', [
            'sucesso' => 'Se o e-mail estiver cadastrado, você receberá instruções para redefinir a senha.',
        ]);
    }

    /**
     * Monta o HTML do e-mail de redefinição de senha.
     * Cor da marca (#7a4f32) substituiu o verde genérico (#4CAF50) do template anterior.
     */
    private function construirCorpoEmailRedefinicao(string $nomeUsuario, string $link): string
    {
        $nomeSafe = htmlspecialchars($nomeUsuario, ENT_QUOTES, 'UTF-8');

        return "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background-color: #7a4f32; color: white; padding: 20px; text-align: center; }
                .content { padding: 20px; background-color: #f9f9f9; }
                .button {
                    display: inline-block;
                    padding: 12px 24px;
                    background-color: #7a4f32;
                    color: white;
                    text-decoration: none;
                    border-radius: 5px;
                    margin: 20px 0;
                }
                .footer { padding: 20px; text-align: center; font-size: 12px; color: #666; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>Animal S.O.S</h1>
                </div>
                <div class='content'>
                    <h2>Olá, {$nomeSafe}!</h2>
                    <p>Você solicitou a redefinição de senha da sua conta no Animal S.O.S.</p>
                    <p>Clique no botão abaixo para criar uma nova senha:</p>
                    <p style='text-align: center;'>
                        <a href='{$link}' class='button'>Redefinir Senha</a>
                    </p>
                    <p>Ou copie e cole o link abaixo no seu navegador:</p>
                    <p style='word-break: break-all;'>{$link}</p>
                    <p><strong>Este link é válido por 30 minutos.</strong></p>
                    <p>Se você não solicitou esta redefinição, ignore este e-mail.</p>
                </div>
                <div class='footer'>
                    <p>Animal S.O.S - Sistema de Registro de Animais em Situação de Risco</p>
                    <p>Este é um e-mail automático, não responda.</p>
                </div>
            </div>
        </body>
        </html>";
    }

    // ============================================================
    // Redefinição via token
    // ============================================================

    public function redefinirSenha(): void
    {
        $token = trim((string)($_GET['token'] ?? ''));

        if ($token === '') {
            $this->view('auth/redefinir_senha', ['erro' => 'Token inválido.']);
            return;
        }

        $resetModel = new PasswordReset();
        $reset      = $resetModel->buscarValidoPorHash(hash('sha256', $token));

        if (!$reset) {
            $this->view('auth/redefinir_senha', ['erro' => 'Token inválido ou expirado.']);
            return;
        }

        $this->view('auth/redefinir_senha', ['token' => $token]);
    }

    public function salvarNovaSenha(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->responderMetodoNaoPermitido();
        }

        $token  = trim((string)($_POST['token'] ?? ''));
        $senha  = (string)($_POST['senha'] ?? '');
        $senha2 = (string)($_POST['senha_confirmacao'] ?? '');

        if (!$this->validarCsrf(self::CSRF_CONTEXTO_REDEFINIR_SENHA)) {
            $this->view('auth/redefinir_senha', [
                'erro'  => 'Sua sessão expirou. Tente redefinir a senha novamente.',
                'token' => $token,
            ]);
            return;
        }

        if ($token === '' || $senha === '' || $senha2 === '') {
            $this->view('auth/redefinir_senha', [
                'erro'  => 'Preencha todos os campos.',
                'token' => $token,
            ]);
            return;
        }

        if ($senha !== $senha2) {
            $this->view('auth/redefinir_senha', [
                'erro'  => 'As senhas não coincidem.',
                'token' => $token,
            ]);
            return;
        }

        if (strlen($senha) < 8) {
            $this->view('auth/redefinir_senha', [
                'erro'  => 'A senha deve ter no mínimo 8 caracteres.',
                'token' => $token,
            ]);
            return;
        }

        $resetModel = new PasswordReset();
        $reset      = $resetModel->buscarValidoPorHash(hash('sha256', $token));

        if (!$reset) {
            $this->view('auth/redefinir_senha', ['erro' => 'Token inválido ou expirado.']);
            return;
        }

        $senhaAtualizada = $this->usuarioModel->redefinirSenhaSemSenhaAtual(
            (string)$reset['usuario_id'],
            $senha
        );

        if (!$senhaAtualizada) {
            $this->view('auth/redefinir_senha', [
                'erro'  => 'Falha ao atualizar a senha. Tente novamente.',
                'token' => $token,
            ]);
            return;
        }

        $resetModel->marcarComoUsado((string)$reset['id']);

        // Encerra qualquer sessão aberta e renova o ID para prevenir fixation.
        unset(
            $_SESSION['usuario_id'],
            $_SESSION['usuario_nome'],
            $_SESSION['tipo_usuario'],
            $_SESSION['user']
        );
        session_regenerate_id(true);

        // Chave 'flash_success' alinhada com o que home.php já lê.
        // (corrige divergência anterior: controller gravava 'flash_sucesso')
        flashDefinir('flash_success', 'Senha alterada com sucesso! Faça login.');

        $this->redirect('/index.php?c=auth&a=login');
    }

    /**
     * Mantém o contrato atual para métodos inválidos neste fluxo (HTTP 405 + mensagem simples).
     */
    private function responderMetodoNaoPermitido(): void
    {
        http_response_code(405);
        echo 'Método não permitido.';
        exit;
    }

    private function validarCsrf(string $contexto): bool
    {
        return csrfValidarConsumo($contexto);
    }
}
