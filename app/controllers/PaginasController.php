<?php
declare(strict_types=1);

final class PaginasController extends Controller
{
    private const VIEW_HOME = 'paginas/home';
    private const TITULO_HOME = 'Animal SOS';
    private const MODAL_LOGIN = 'login';
    private const MODAL_CADASTRO = 'cadastro';

    public function home(): void
    {
        $this->view(self::VIEW_HOME, $this->dadosHome());
    }

    private function dadosHome(): array
    {
        return [
            'title' => self::TITULO_HOME,
            'tituloPagina' => self::TITULO_HOME,
            'usaModalAutenticacao' => true,
            'modalAbertoAtual' => $this->consumirModalAbertoDaSessao(),
            'mensagemSucessoHome' => $this->consumirTextoSessao('flash_success'),
            'mensagemErroLogin' => $this->consumirTextoSessao('flash_error'),
            'mensagemErroCadastro' => $this->consumirTextoSessao('flash_registro_erro'),
            'mensagemSucessoCadastro' => $this->consumirTextoSessao('flash_registro_sucesso'),
        ];
    }

    private function consumirModalAbertoDaSessao(): string
    {
        if (!isset($_SESSION['open_modal'])) {
            return '';
        }

        $valorModalBruto = $_SESSION['open_modal'];
        unset($_SESSION['open_modal']);

        if (!is_string($valorModalBruto)) {
            return '';
        }

        $modalNormalizado = strtolower(trim($valorModalBruto));

        return match ($modalNormalizado) {
            self::MODAL_LOGIN, self::MODAL_CADASTRO => $modalNormalizado,
            default => '',
        };
    }

    private function consumirTextoSessao(string $chave): string
    {
        if (!isset($_SESSION[$chave])) {
            return '';
        }

        $valor = $_SESSION[$chave];
        unset($_SESSION[$chave]);

        return is_string($valor) ? $valor : '';
    }
}
