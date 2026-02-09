<?php
declare(strict_types=1);

final class AuthController extends Controller
{
    private Usuario $usuarioModel;

    public function __construct()
    {
        $this->usuarioModel = new Usuario();
    }

    //Login do usuário -->
    public function login(): void
    {
        // Se já estiver logado, manda para a listagem
        if (!empty($_SESSION['usuario_id'])) {
            $this->redirect('/index.php?c=animal&a=listar');
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = trim((string)($_POST['email'] ?? ''));
            $senha = (string)($_POST['senha'] ?? '');

            // Validação básica
            if ($email === '' || $senha === '') {
                $_SESSION['flash_error'] = 'Informe e-mail e senha.';
                $_SESSION['open_modal'] = 'login';
                $this->redirect('/index.php?c=paginas&a=home');
                return;
            }

            // Busca usuário
            $usuario = $this->usuarioModel->encontrarPorEmail($email);

            // Verifica senha
            if (!$usuario || !password_verify($senha, $usuario['senha_hash'])) {
                $_SESSION['flash_error'] = 'E-mail ou senha inválidos.';
                $_SESSION['open_modal'] = 'login';
                $this->redirect('/index.php?c=paginas&a=home');
                return;
            }

            // LOGIN OK → padroniza sessão
            $_SESSION['usuario_id']   = $usuario['id'];
            $_SESSION['usuario_nome'] = $usuario['nome'];

            // (recomendado para permissões)
            if (!empty($usuario['tipo_usuario'])) {
                $_SESSION['tipo_usuario'] = $usuario['tipo_usuario'];
            }

            // Remove legado, se existir
            unset($_SESSION['user']);

            // =========================
            // PASSO 4: redirect de retorno
            // =========================
            $return = (string)($_POST['return'] ?? $_GET['return'] ?? ($_SESSION['return_to'] ?? ''));
            unset($_SESSION['return_to']);

            // Aceita return relativo interno (ex.: /index.php?...)
            // ou absoluto que comece com BASE_URL
            $okReturn = false;

            if ($return !== '') {
                if (str_starts_with($return, '/')) {
                    $okReturn = true; // relativo interno
                } elseif (str_starts_with($return, BASE_URL)) {
                    $okReturn = true; // absoluto interno
                }
            }

            if ($okReturn) {
                // Se vier absoluto com BASE_URL, converte para relativo
                // (evita redirect com URL duplicada dependendo do seu redirect())
                if (str_starts_with($return, BASE_URL)) {
                    $return = substr($return, strlen(BASE_URL));
                    if ($return === '') $return = '/';
                }

                $this->redirect($return);
                return;
            }

            // Fallback padrão (sua lógica atual)
            $this->redirect('/index.php?c=animal&a=listar');
            return;

        }

        // GET → volta para home e abre modal
        // (se vier return via GET, guarda na sessão para o POST não perder)
        $return = (string)($_GET['return'] ?? '');
        if ($return !== '' && str_starts_with($return, BASE_URL)) {
            $_SESSION['return_to'] = $return;
        }

        $return = (string)($_GET['return'] ?? '');
        if ($return !== '') {
            $okReturn = false;

            if (str_starts_with($return, '/')) {
                $okReturn = true;
            } elseif (str_starts_with($return, BASE_URL)) {
                $okReturn = true;
            }

            if ($okReturn) {
                $_SESSION['return_to'] = $return;
            }
        }

        $_SESSION['open_modal'] = 'login';
        $this->redirect('/index.php?c=paginas&a=home');
    }

    //Registro de novo usuário -->
    public function registro(): void
    {
        // Bloqueia a "tela" de cadastro: GET nunca renderiza view
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/index.php?c=paginas&a=home');
            return;
        }

        $dados = [
            'nome'         => trim((string)($_POST['nome'] ?? '')),
            'email'        => trim((string)($_POST['email'] ?? '')),
            'senha'        => (string)($_POST['senha'] ?? ''),
            'telefone'     => trim((string)($_POST['telefone'] ?? '')),
            'tipo_usuario' => (string)($_POST['tipo_usuario'] ?? 'Comum'),

            // Endereço
            'cep'          => trim((string)($_POST['cep'] ?? '')),
            'rua'          => trim((string)($_POST['rua'] ?? '')),
            'numero'       => trim((string)($_POST['numero'] ?? '')),
            'bairro'       => trim((string)($_POST['bairro'] ?? '')),
            'cidade'       => trim((string)($_POST['cidade'] ?? '')),
            'estado'       => trim((string)($_POST['estado'] ?? '')),

            'foto_perfil'  => null,
        ];

        // Helper: sempre retornar para HOME abrindo o modal de cadastro
        $erroCadastro = function (string $msg): void {
            $_SESSION['flash_registro_erro'] = $msg;
            $this->redirect('/index.php?c=paginas&a=home&open=cadastro');
        };

        // Formata CEP
        if ($dados['cep'] !== '') {
            $digits = preg_replace('/\D+/', '', $dados['cep']);
            if (strlen($digits) === 8) {
                $dados['cep'] = substr($digits, 0, 5) . '-' . substr($digits, 5);
            }
        }

        // Validação de CEP
        if ($dados['cep'] !== '' && !preg_match('/^\d{5}-\d{3}$/', $dados['cep'])) {
            $erroCadastro('CEP inválido.');
            return;
        }

        // Validação de UF
        $ufsValidas = [
            'AC','AL','AP','AM','BA','CE','DF','ES','GO','MA','MT','MS',
            'MG','PA','PB','PR','PE','PI','RJ','RN','RS','RO','RR','SC',
            'SP','SE','TO'
        ];

        if ($dados['estado'] !== '' && !in_array($dados['estado'], $ufsValidas, true)) {
            $erroCadastro('Estado inválido.');
            return;
        }

        // Validação básica
        if ($dados['nome'] === '' || $dados['email'] === '' || $dados['senha'] === '') {
            $erroCadastro('Nome, e-mail e senha são obrigatórios.');
            return;
        }

        // Validação de senha (mínimo 8)
        $senha = trim((string)($_POST['senha'] ?? ''));
        if (mb_strlen($senha) < 8) {
            $erroCadastro('A senha deve ter no mínimo 8 caracteres.');
            return;
        }

        // Evita duplicidade de e-mail
        if ($this->usuarioModel->encontrarPorEmail($dados['email'])) {
            $erroCadastro('Este e-mail já está cadastrado. Use outro e-mail ou faça login.');
            return;
        }

        // ===== Upload da foto de perfil =====
        $dados['foto_perfil'] = null;

        if (isset($_FILES['foto_perfil']) && is_array($_FILES['foto_perfil'])) {
            $file = $_FILES['foto_perfil'];

            if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {

                if ($file['error'] !== UPLOAD_ERR_OK) {
                    $erroCadastro('Erro no upload da foto.');
                    return;
                }

                if ($file['size'] > 2 * 1024 * 1024) {
                    $erroCadastro('A foto deve ter no máximo 2MB.');
                    return;
                }

                $finfo = new finfo(FILEINFO_MIME_TYPE);
                $mime  = $finfo->file($file['tmp_name']);

                $extPermitidas = [
                    'image/jpeg' => 'jpg',
                    'image/png'  => 'png',
                    'image/webp' => 'webp',
                ];

                if (!isset($extPermitidas[$mime])) {
                    $erroCadastro('Formato inválido. Use JPG, PNG ou WEBP.');
                    return;
                }

                $uploadDir = dirname(__DIR__, 2) . '/public/uploads/usuarios';

                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0775, true);
                }

                if (!is_writable($uploadDir)) {
                    $erroCadastro('Pasta de upload sem permissão.');
                    return;
                }

                $nomeArquivo = 'user_' . bin2hex(random_bytes(8)) . '.' . $extPermitidas[$mime];
                $destinoFisico = $uploadDir . '/' . $nomeArquivo;

                if (!move_uploaded_file($file['tmp_name'], $destinoFisico)) {
                    $erroCadastro('Falha ao salvar a imagem.');
                    return;
                }

                // Sugestão: salvar sem "public/" no banco (mais simples p/ URL)
                $dados['foto_perfil'] = 'uploads/usuarios/' . $nomeArquivo;
            }
        }
        // ===== Fim upload =====

        $ok = $this->usuarioModel->criar($dados);

        if ($ok) {
            $_SESSION['flash_registro_sucesso'] = 'Cadastro realizado com sucesso. Faça login.';
            $this->redirect('/index.php?c=paginas&a=home&open=login');
            return;
        }

        $erroCadastro('Erro ao cadastrar.');
    }

    //Logout do usuário -->
    public function logout(): void
    {
        // Remove dados do usuário
        unset(
            $_SESSION['usuario_id'],
            $_SESSION['usuario_nome'],
            $_SESSION['user']
        );

        // Proteção contra fixation
        session_regenerate_id(true);

        // Volta para home
        $this->redirect('/index.php?c=paginas&a=home');
    }

    //Processo de "esqueci minha senha" -->
    public function esqueciSenha(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = trim((string)($_POST['email'] ?? ''));

            // 1) Validação de formato
            if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $this->view('auth/esqueci_senha', [
                    'erro' => 'Informe um e-mail válido.',
                ]);
                return;
            }

            $usuarioModel = new Usuario();
            $u = $usuarioModel->encontrarPorEmail($email);

            // 2) Se NÃO existir no banco -> erro e para aqui (NÃO gera token, NÃO envia email)
            if (!$u) {
                $this->view('auth/esqueci_senha', [
                    'erro' => 'Email não cadastrado!',
                ]);
                return;
            }

            // 3) Se existe -> segue fluxo normal
            $okMsg = 'E-mail de redefinição enviado!';
            $debugLink = null;

            $resetModel = new PasswordReset();

            if (method_exists($resetModel, 'invalidarTokensDoUsuario')) {
                $resetModel->invalidarTokensDoUsuario((string)$u['id']);
            }

            $token = bin2hex(random_bytes(32));
            $tokenHash = hash('sha256', $token);
            $expiraEm = (new DateTime('+30 minutes'))->format('Y-m-d H:i:s');

            $resetModel->criar((string)$u['id'], $tokenHash, $expiraEm);

            // Enviar e-mail de redefinição
            $link = BASE_URL . '/index.php?c=auth&a=redefinirSenha&token=' . urlencode($token);

            $assunto = 'Redefinição de Senha - Animal S.O.S';

            $corpo = "
            <html>
            <head>
                <style>
                    body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                    .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                    .header { background-color: #4CAF50; color: white; padding: 20px; text-align: center; }
                    .content { padding: 20px; background-color: #f9f9f9; }
                    .button {
                        display: inline-block;
                        padding: 12px 24px;
                        background-color: #4CAF50;
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
                        <h2>Olá, " . htmlspecialchars((string)$u['nome']) . "!</h2>
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
            </html>
            ";

            // Tenta enviar o e-mail
            try {
                $emailEnviado = Mailer::send($email, (string)$u['nome'], $assunto, $corpo);

                if (!$emailEnviado) {
                    error_log('Falha ao enviar email de redefinição para: ' . $email);
                }
            } catch (Exception $e) {
                error_log('Exceção ao enviar email: ' . $e->getMessage());
                $emailEnviado = false;
            }

            // Link só para teste local
            if (defined('APP_ENV') && APP_ENV !== 'prod') {
                $debugLink = BASE_URL . '/index.php?c=auth&a=redefinirSenha&token=' . urlencode($token);
                if (!$emailEnviado) {
                    $okMsg = 'Token gerado! Use o link abaixo (email não enviado):';
                }
            }

            // 4) Render final de sucesso
            $this->view('auth/esqueci_senha', [
                'sucesso'    => $okMsg,
                'debug_link' => $debugLink,
            ]);
            return;
        }

        // GET: exibe a view de 'esqueci minha senha'
        $this->view('auth/esqueci_senha');
    }

    // Página para redefinir a senha via token -->
    public function redefinirSenha(): void
    {
        $token = trim((string)($_GET['token'] ?? ''));
        if ($token === '') {
            $this->view('auth/redefinir_senha', ['erro' => 'Token inválido.']);
            return;
        }

        $tokenHash = hash('sha256', $token);
        $resetModel = new PasswordReset();
        $reset = $resetModel->buscarValidoPorHash($tokenHash);

        if (!$reset) {
            $this->view('auth/redefinir_senha', ['erro' => 'Token inválido ou expirado.']);
            return;
        }

        $this->view('auth/redefinir_senha', ['token' => $token]);
    }

    // Salva a nova senha após validação do token -->
    public function salvarNovaSenha(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo 'Método não permitido.';
            exit;
        }

        $token  = trim((string)($_POST['token'] ?? ''));
        $senha  = (string)($_POST['senha'] ?? '');
        $senha2 = (string)($_POST['senha_confirmacao'] ?? '');

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

        if (strlen($senha) < 6) {
            $this->view('auth/redefinir_senha', [
                'erro'  => 'A senha deve ter no mínimo 6 caracteres.',
                'token' => $token,
            ]);
            return;
        }

        $tokenHash = hash('sha256', $token);
        $resetModel = new PasswordReset();
        $reset = $resetModel->buscarValidoPorHash($tokenHash);

        if (!$reset) {
            $this->view('auth/redefinir_senha', ['erro' => 'Token inválido ou expirado.']);
            return;
        }

        $usuarioId = (string)$reset['usuario_id'];

        // Atualiza senha sem exigir senha atual (seu método novo no Usuario)
        $usuarioModel = new Usuario();
        $ok = $usuarioModel->redefinirSenhaSemSenhaAtual($usuarioId, $senha);

        if (!$ok) {
            $this->view('auth/redefinir_senha', [
                'erro'  => 'Falha ao atualizar a senha. Tente novamente.',
                'token' => $token,
            ]);
            return;
        }

        // Marca token como usado
        $resetModel->marcarComoUsado((string)$reset['id']);

        // Força logout (para não entrar direto na conta)
        unset($_SESSION['usuario_id'], $_SESSION['usuario_nome'], $_SESSION['tipo_usuario']);

        // (Opcional, recomendado) evita session fixation
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }

        // Flash message para aparecer após redirect
        $_SESSION['flash_sucesso'] = 'Senha alterada com sucesso! Faça login';

        // Redireciona para login
        $this->redirect('/index.php?c=auth&a=login');
        return;

    }

}
