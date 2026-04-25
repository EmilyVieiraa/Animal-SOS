<?php
declare(strict_types=1);

require_once APP_PATH . 'helpers/view_helpers.php';

final class UsuarioController extends Controller
{
    private const CSRF_CONTEXTO_PERFIL = 'usuario_perfil';
    private Usuario $usuarioModel;
    private Animal $animalModel;

    public function __construct()
    {
        $this->usuarioModel = new Usuario();
        $this->animalModel = new Animal();
    }

    private function obterIdUsuarioLogado(): string
    {
        return (string)($_SESSION['usuario_id'] ?? '');
    }

    /**
     * Valida token CSRF e consome o token de sessão.
     */
    private function validarCsrf(string $contexto): bool
    {
        return csrfValidarConsumo($contexto);
    }

    /**
     * Monta payload de atualização de perfil a partir do POST.
     * Endereço é incluído quando o fluxo solicitar explicitamente.
     */
    private function montarDadosAtualizacaoPerfil(bool $incluirEndereco): array
    {
        $nome = trim((string)($_POST['nome'] ?? ''));
        $telefone = trim((string)($_POST['telefone'] ?? ''));

        $dados = [
            'nome' => $nome,
            'telefone' => ($telefone !== '' ? $telefone : null),
            'mostrar_email' => isset($_POST['mostrar_email']) ? 1 : 0,
            'mostrar_whatsapp' => isset($_POST['mostrar_whatsapp']) ? 1 : 0,
        ];

        if ($incluirEndereco) {
            $rua = trim((string)($_POST['rua'] ?? ''));
            $numero = trim((string)($_POST['numero'] ?? ''));
            $bairro = trim((string)($_POST['bairro'] ?? ''));
            $cidade = trim((string)($_POST['cidade'] ?? ''));
            $estado = trim((string)($_POST['estado'] ?? ''));
            $cep = trim((string)($_POST['cep'] ?? ''));

            $dados['rua'] = ($rua !== '' ? $rua : null);
            $dados['numero'] = ($numero !== '' ? $numero : null);
            $dados['bairro'] = ($bairro !== '' ? $bairro : null);
            $dados['cidade'] = ($cidade !== '' ? $cidade : null);
            $dados['estado'] = ($estado !== '' ? $estado : null);
            $dados['cep'] = ($cep !== '' ? $cep : null);
        }

        return $dados;
    }

    /**
     * Valida e persiste a atualização de perfil.
     * Em caso de falha, chama $erroCallback com a mensagem e retorna false.
     * Em caso de sucesso, retorna true (o chamador trata o redirect/flash).
     */
    private function atualizarPerfilUsuario(string $usuarioId, bool $incluirEndereco, callable $erroCallback): bool
    {
        $dados = $this->montarDadosAtualizacaoPerfil($incluirEndereco);
        $nome = (string)($dados['nome'] ?? '');

        if ($nome === '') {
            $erroCallback('Nome é obrigatório.');
            return false;
        }

        $atualizou = $this->usuarioModel->atualizarPerfil($usuarioId, $dados);

        if (!$atualizou) {
            $erroCallback('Não foi possível atualizar o perfil.');
            return false;
        }

        return true;
    }

    private function carregarDadosPaginaPerfil(string $usuarioId): array
    {
        $usuario = $this->usuarioModel->encontrarPorId($usuarioId);
        if (!$usuario) {
            http_response_code(404);
            exit('Usuário não encontrado.');
        }

        $denunciasCount = $this->animalModel->contarPorAutor($usuarioId);
        $denuncias = $this->animalModel->listarPorAutor($usuarioId);

        return [
            'usuario' => $usuario,
            'denunciasCount' => $denunciasCount,
            'denuncias' => $denuncias,
        ];
    }

    /**
     * Renderiza a view de perfil consumindo flash messages e injetando o token CSRF.
     */
    private function renderizarPerfil(string $usuarioId, bool $isOwnProfile): void
    {
        $dadosPerfil = $this->carregarDadosPaginaPerfil($usuarioId);

        $this->view('configuracoes/perfil', [
            'usuario'       => $dadosPerfil['usuario'],
            'isOwnProfile'  => $isOwnProfile,
            'denunciasCount' => $dadosPerfil['denunciasCount'],
            'denuncias'     => $dadosPerfil['denuncias'],
            'erro'          => flashConsumir('flash_error_perfil'),
            'sucesso'       => flashConsumir('flash_success_perfil'),
            '_csrf_token'   => csrfToken(self::CSRF_CONTEXTO_PERFIL),
        ]);
    }

    public function meuPerfil(): void
    {
        $usuarioId = $this->obterIdUsuarioLogado();
        if ($usuarioId === '') {
            $this->redirect('/index.php?c=auth&a=login');
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!$this->validarCsrf(self::CSRF_CONTEXTO_PERFIL)) {
                flashDefinir('flash_error_perfil', 'Sua sessão expirou. Tente novamente.');
                $this->redirect('/index.php?c=usuario&a=meuPerfil');
                return;
            }

            $erroCallback = function (string $mensagem): void {
                flashDefinir('flash_error_perfil', $mensagem);
                $this->redirect('/index.php?c=usuario&a=meuPerfil');
            };

            if (!$this->atualizarPerfilUsuario($usuarioId, true, $erroCallback)) {
                return;
            }

            flashDefinir('flash_success_perfil', 'Perfil atualizado com sucesso.');
            $this->redirect('/index.php?c=usuario&a=meuPerfil');
            return;
        }

        $this->renderizarPerfil($usuarioId, true);
    }

    public function perfil(): void
    {
        $usuarioId = (string)($_GET['id'] ?? '');
        if ($usuarioId === '') {
            http_response_code(400);
            exit('ID do usuário não informado.');
        }

        $usuarioLogadoId = $this->obterIdUsuarioLogado();
        $isOwnProfile = ($usuarioLogadoId !== '' && $usuarioLogadoId === $usuarioId);

        if ($isOwnProfile && ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
            if (!$this->validarCsrf(self::CSRF_CONTEXTO_PERFIL)) {
                flashDefinir('flash_error_perfil', 'Sua sessão expirou. Tente novamente.');
                $this->redirect('/index.php?c=usuario&a=perfil&id=' . urlencode($usuarioId));
                return;
            }

            $erroCallback = function (string $mensagem) use ($usuarioId): void {
                flashDefinir('flash_error_perfil', $mensagem);
                $this->redirect('/index.php?c=usuario&a=perfil&id=' . urlencode($usuarioId));
            };

            if (!$this->atualizarPerfilUsuario($usuarioId, true, $erroCallback)) {
                return;
            }

            flashDefinir('flash_success_perfil', 'Perfil atualizado com sucesso.');
            $this->redirect('/index.php?c=usuario&a=perfil&id=' . urlencode($usuarioId));
            return;
        }

        $this->renderizarPerfil($usuarioId, $isOwnProfile);
    }

}