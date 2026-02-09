<?php
declare(strict_types=1);

/**
 * UsuarioController
 * - Perfil (visualizar/editar)
 * - Alterar senha
 */
final class UsuarioController extends Controller
{
    private Usuario $usuarioModel;

    public function __construct()
    {
        $this->usuarioModel = new Usuario();
    }

    /**
     * GET  -> exibe perfil
     * POST -> atualiza dados do perfil
     *
     * URL: /index.php?c=usuario&a=perfil
     */
    public function perfil(): void
    {
        $id = (string)($_GET['id'] ?? '');
        if ($id === '') {
            http_response_code(400);
            exit('ID do usuário não informado.');
        }

        $usuario = $this->usuarioModel->encontrarPorId($id);
        if (!$usuario) {
            http_response_code(404);
            exit('Usuário não encontrado.');
        }

        $this->view('configuracoes/perfil', [
            'usuario' => $usuario,
            'isOwnProfile' => (!empty($_SESSION['usuario_id']) && $_SESSION['usuario_id'] === $id),
        ]);
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

        $usuario = $this->usuarioModel->encontrarPorId($id);
        if (!$usuario) {
            http_response_code(404);
            exit('Usuário não encontrado.');
        }

        $this->view('usuario/perfil', [
            'usuario' => $usuario,
            'isOwnProfile' => true,
        ]);
    }

}
