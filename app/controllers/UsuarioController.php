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

    private function obterIdUsuarioLogado(): string
    {
        return (string)($_SESSION['usuario_id'] ?? '');
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
     * Processa atualização de perfil e devolve mensagens para a view.
     * Retorno: ['erro' => ?string, 'sucesso' => ?string].
     */
    private function processarAtualizacaoPerfil(string $usuarioId, bool $incluirEndereco): array
    {
        $dados = $this->montarDadosAtualizacaoPerfil($incluirEndereco);
        $nome = (string)($dados['nome'] ?? '');

        if ($nome === '') {
            return [
                'erro' => 'Nome é obrigatório.',
                'sucesso' => null,
            ];
        }

        $atualizou = $this->usuarioModel->atualizarPerfil($usuarioId, $dados);

        if ($atualizou) {
            return [
                'erro' => null,
                'sucesso' => 'Perfil atualizado com sucesso.',
            ];
        }

        return [
            'erro' => 'Não foi possível atualizar o perfil.',
            'sucesso' => null,
        ];
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

    private function renderizarPerfil(string $usuarioId, bool $isOwnProfile, ?string $erro, ?string $sucesso): void
    {
        $dadosPerfil = $this->carregarDadosPaginaPerfil($usuarioId);

        $this->view('configuracoes/perfil', [
            'usuario' => $dadosPerfil['usuario'],
            'isOwnProfile' => $isOwnProfile,
            'denunciasCount' => $dadosPerfil['denunciasCount'],
            'denuncias' => $dadosPerfil['denuncias'],
            'erro' => $erro,
            'sucesso' => $sucesso,
        ]);
    }

        public function meuPerfil(): void
        {
            $usuarioId = $this->obterIdUsuarioLogado();
            if ($usuarioId === '') {
                $this->redirect('/index.php?c=auth&a=login');
                return;
            }

            $erro = null;
            $sucesso = null;

            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $resultadoAtualizacao = $this->processarAtualizacaoPerfil($usuarioId, true);
                $erro = $resultadoAtualizacao['erro'];
                $sucesso = $resultadoAtualizacao['sucesso'];
            }

            $this->renderizarPerfil($usuarioId, true, $erro, $sucesso);
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

        $erro = null;
        $sucesso = null;

        if ($isOwnProfile && ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
            $resultadoAtualizacao = $this->processarAtualizacaoPerfil($usuarioId, true);
            $erro = $resultadoAtualizacao['erro'];
            $sucesso = $resultadoAtualizacao['sucesso'];
        }

        $this->renderizarPerfil($usuarioId, $isOwnProfile, $erro, $sucesso);
    }

}