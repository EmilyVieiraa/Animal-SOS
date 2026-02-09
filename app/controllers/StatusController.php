<?php
declare(strict_types=1);

final class StatusController extends Controller
{
    private Status $statusModel;

    public function __construct()
    {
        $this->statusModel = new Status();
    }

    public function atualizar(): void
    {
        $this->requireLogin();

        $usuario = $_SESSION['usuario'];
        $novoStatus = $_POST['status'];
        $denunciaId = (int) $_POST['denuncia_id'];

        if (!$this->podeAlterarStatus($usuario['tipo_usuario'], $novoStatus)) {
            http_response_code(403);
            exit('Acesso negado');
        }

        $denunciaModel = new Denuncia();
        $denunciaModel->atualizarStatus(
            $denunciaId,
            $novoStatus,
            $usuario['id']
        );

        $this->redirect('/index.php?c=animal&a=detalhe&id=' . $denunciaId);
    }

    private function podeAlterarStatus(string $perfil, string $status): bool
    {
        $permissoes = [
            'admin' => ['aberto','em_analise','em_atendimento','resolvido','arquivado'],
            'moderator' => ['em_analise','arquivado'],
            'ong' => ['em_atendimento']
        ];

        return isset($permissoes[$perfil]) &&
               in_array($status, $permissoes[$perfil], true);
    }
}
