<?php
declare(strict_types=1);

require_once APP_PATH . 'helpers/view_helpers.php';

final class ComentarioController extends Controller
{
    private const TAMANHO_MAXIMO_MENSAGEM = 1000;
    private const CSRF_CONTEXTO_ADICIONAR = CSRF_CONTEXTO_COMENTARIO_ADICIONAR;

    private Comentario $comentarioModel;

    public function __construct()
    {
        $this->comentarioModel = new Comentario();
    }

    public function adicionar(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            exit('Método não permitido.');
        }

        $animalId = trim((string)($_POST['animal_id'] ?? ''));
        $urlRetorno = $this->urlRetornoDetalhes($animalId);

        // precisa estar logado
        if (empty($_SESSION['usuario_id'])) {
            flashDefinir('flash_error', 'Você precisa estar logado para comentar.');
            $this->redirect('/index.php?c=auth&a=login&return=' . urlencode($urlRetorno));
            return;
        }

        if (!$this->validarCsrf(self::CSRF_CONTEXTO_ADICIONAR)) {
            flashDefinir('flash_error', 'Token de segurança inválido ou expirado. Tente novamente.');
            $this->redirect($urlRetorno);
            return;
        }

        $usuarioId = (string)$_SESSION['usuario_id'];
        $mensagem  = trim((string)($_POST['mensagem'] ?? ''));

        $erroValidacao = $this->validarDadosComentario($animalId, $mensagem);
        if ($erroValidacao !== null) {
            flashDefinir('flash_error', $erroValidacao);
            $this->redirect($urlRetorno);
            return;
        }

        try {
            $comentarioCriado = $this->comentarioModel->criar([
                'animal_id'  => $animalId,
                'usuario_id' => $usuarioId,
                'mensagem'   => $mensagem,
            ]);
        } catch (Throwable $erro) {
            flashDefinir('flash_error', 'Não foi possível publicar o comentário. Tente novamente.');
            $this->redirect($urlRetorno);
            return;
        }

        if (!$comentarioCriado) {
            flashDefinir('flash_error', 'Não foi possível publicar o comentário. Tente novamente.');
            $this->redirect($urlRetorno);
            return;
        }

        flashDefinir('flash_success', 'Comentário publicado com sucesso.');
        $this->redirect($urlRetorno);
    }

    private function validarDadosComentario(string $animalId, string $mensagem): ?string
    {
        if ($animalId === '') {
            return 'Denúncia inválida para adicionar comentário.';
        }

        if ($mensagem === '') {
            return 'Escreva uma mensagem para publicar o comentário.';
        }

        if (mb_strlen($mensagem) > self::TAMANHO_MAXIMO_MENSAGEM) {
            return 'Comentário muito longo. Use até ' . self::TAMANHO_MAXIMO_MENSAGEM . ' caracteres.';
        }

        return null;
    }

    private function urlRetornoDetalhes(string $animalId): string
    {
        if ($animalId === '') {
            return '/index.php?c=animal&a=listar';
        }

        return '/index.php?c=animal&a=detalhes&id=' . urlencode($animalId);
    }

    private function validarCsrf(string $contexto): bool
    {
        return csrfValidarConsumo($contexto);
    }

}
