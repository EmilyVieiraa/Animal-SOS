<?php
declare(strict_types=1);
final class UsuarioController extends Controller
{
    private Usuario $usuarioModel;
    private Animal $animalModel;

    public function __construct()
    {
        $this->usuarioModel = new Usuario();
        $this->animalModel = new Animal();
    }

        
    /**
    * GET  -> exibe form de senha
    * POST -> altera senha
    *
    * URL: /index.php?c=usuario&a=senha
    */
        public function senha(): void
        {
            $this->requireAuth();

            $usuarioId = (string)($_SESSION['usuario_id'] ?? '');

            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $senhaAtual = (string)($_POST['senha_atual'] ?? '');
                $novaSenha  = (string)($_POST['nova_senha'] ?? '');
                $confirmar  = (string)($_POST['confirmar_senha'] ?? '');

                if ($novaSenha === '' || strlen($novaSenha) < 6) {
                    $this->view('configuracoes/senha', [
                        'erro' => 'A nova senha deve ter no mínimo 6 caracteres.',
                    ]);
                    return;
                }

                if ($novaSenha !== $confirmar) {
                    $this->view('configuracoes/senha', [
                        'erro' => 'Nova senha e confirmação não coincidem.',
                    ]);
                    return;
                }

                $ok = $this->usuarioModel->alterarSenha($usuarioId, $senhaAtual, $novaSenha);

                if (!$ok) {
                    $this->view('configuracoes/senha', [
                        'erro' => 'Senha atual incorreta.',
                    ]);
                    return;
                }

                $this->view('configuracoes/senha', [
                    'sucesso' => 'Senha alterada com sucesso.',
                ]);
                return;
            }

            $this->view('configuracoes/senha');
        }

        public function meuPerfil(): void
        {
            if (empty($_SESSION['usuario_id'])) {
                $this->redirect('/index.php?c=auth&a=login');
                return;
            }

            $id = (string)$_SESSION['usuario_id'];

            $erro = null;
            $sucesso = null;

            // ✅ Se vier POST, salva os dados
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $nome     = trim((string)($_POST['nome'] ?? ''));
                $telefone = trim((string)($_POST['telefone'] ?? ''));
                $rua      = trim((string)($_POST['rua'] ?? ''));
                $numero   = trim((string)($_POST['numero'] ?? ''));
                $bairro   = trim((string)($_POST['bairro'] ?? ''));
                $cidade   = trim((string)($_POST['cidade'] ?? ''));
                $estado   = trim((string)($_POST['estado'] ?? ''));
                $cep      = trim((string)($_POST['cep'] ?? ''));

                $dados = [
                    'nome' => $nome,
                    'telefone' => ($telefone !== '' ? $telefone : null),
                    'rua' => ($rua !== '' ? $rua : null),
                    'numero' => ($numero !== '' ? $numero : null),
                    'bairro' => ($bairro !== '' ? $bairro : null),
                    'cidade' => ($cidade !== '' ? $cidade : null),
                    'estado' => ($estado !== '' ? $estado : null),
                    'cep' => ($cep !== '' ? $cep : null),
                    'mostrar_email' => isset($_POST['mostrar_email']) ? 1 : 0,
                    'mostrar_whatsapp' => isset($_POST['mostrar_whatsapp']) ? 1 : 0,
                ];

                if ($nome === '') {
                    $erro = 'Nome é obrigatório.';
                } else {
                    $ok = $this->usuarioModel->atualizarPerfil($id, $dados);
                    if ($ok) {
                        $sucesso = 'Perfil atualizado com sucesso.';
                    } else {
                        $erro = 'Não foi possível atualizar o perfil.';
                    }
                }
            }

            $usuario = $this->usuarioModel->encontrarPorId($id);
            if (!$usuario) {
                http_response_code(404);
                exit('Usuário não encontrado.');
            }

            $denunciasCount = $this->animalModel->contarPorAutor($id);
            $denuncias      = $this->animalModel->listarPorAutor($id);

            $this->view('configuracoes/perfil', [
                'usuario' => $usuario,
                'isOwnProfile' => true,
                'denunciasCount' => $denunciasCount,
                'denuncias' => $denuncias,
                'modo' => 'meu',
                'erro' => $erro,
                'sucesso' => $sucesso,
            ]);
        }

    public function perfil(): void
    {
        $id = (string)($_GET['id'] ?? '');
        if ($id === '') {
            http_response_code(400);
            exit('ID do usuário não informado.');
        }

        $isOwn = (!empty($_SESSION['usuario_id']) && (string)$_SESSION['usuario_id'] === (string)$id);

        // ✅ Se for meu perfil e vier POST, salva preferências + dados
        if ($isOwn && ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
            $nome     = trim((string)($_POST['nome'] ?? ''));
            $telefone = trim((string)($_POST['telefone'] ?? ''));

            $dados = [
                'nome' => $nome,
                'telefone' => ($telefone !== '' ? $telefone : null),

                // ✅ checkboxes: se não vier no POST, vira 0
                'mostrar_email' => isset($_POST['mostrar_email']) ? 1 : 0,
                'mostrar_whatsapp' => isset($_POST['mostrar_whatsapp']) ? 1 : 0,
            ];

            if ($nome === '') {
                $erro = 'Nome é obrigatório.';
            } else {
                $ok = $this->usuarioModel->atualizarPerfil($id, $dados);
                if ($ok) {
                    $sucesso = 'Perfil atualizado com sucesso.';
                } else {
                    $erro = 'Não foi possível atualizar o perfil.';
                }
            }
        }

        $usuario = $this->usuarioModel->encontrarPorId($id);
        if (!$usuario) {
            http_response_code(404);
            exit('Usuário não encontrado.');
        }

        $denunciasCount = $this->animalModel->contarPorAutor($id);
        $denuncias      = $this->animalModel->listarPorAutor($id);

        $this->view('configuracoes/perfil', [
            'usuario' => $usuario,
            'isOwnProfile' => $isOwn,
            'denunciasCount' => $denunciasCount,
            'denuncias' => $denuncias,
            'modo' => $isOwn ? 'meu' : 'publico',

            // ✅ mensagens (se existirem)
            'erro' => $erro ?? null,
            'sucesso' => $sucesso ?? null,
        ]);
    }

}