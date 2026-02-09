<?php
declare(strict_types=1);

/**
 * MapaController
 * - Tela “Mapa + filtros”
 * Nesta fase, pode começar como uma listagem filtrada (sem API de mapas).
 */
final class MapaController extends Controller
{
    private Animal $animalModel;

    public function __construct()
    {
        $this->animalModel = new Animal();
    }

    /**
     * GET /index.php?c=mapa&a=index
     * Filtros opcionais via querystring: status, cidade, especie (se você decidir)
     */
    public function index(): void
    {
        $this->requireAuth();

        $status = isset($_GET['status']) ? trim((string)$_GET['status']) : null;
        if ($status === '') $status = null;

        // Para começar simples: reutiliza listarTodos por status
        // Depois ampliamos para filtros por cidade/especie/raio, etc.
        $animais = $this->animalModel->listarTodos($status);

        $this->view('animais/mapa', [
            'animais' => $animais,
            'status'  => $status,
        ]);
    }
}
