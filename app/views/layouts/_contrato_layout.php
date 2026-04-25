<?php
declare(strict_types=1);

if (!function_exists('normalizarContratoLayoutGlobal')) {
    /**
     * Normaliza o contrato de layout para consumo seguro em header/footer.
     *
     * Mantém fallback legado para preservar compatibilidade quando o contrato
     * não for fornecido por algum fluxo antigo.
     */
    function normalizarContratoLayoutGlobal(mixed $contrato): array
    {
        $dados = is_array($contrato) ? $contrato : [];

        $navegacao = is_array($dados['navegacao'] ?? null) ? $dados['navegacao'] : [];
        $modal = is_array($dados['modal'] ?? null) ? $dados['modal'] : [];
        $sessao = is_array($dados['sessao'] ?? null) ? $dados['sessao'] : [];

        $urlHome = (string)($navegacao['urlHome'] ?? (BASE_URL . '/index.php?c=paginas&a=home'));

        return [
            'versao' => (int)($dados['versao'] ?? 1),
            'tituloDocumento' => (string)($dados['tituloDocumento'] ?? 'Animal SOS'),
            'navegacao' => [
                'urlHome' => $urlHome,
                'urlAnimaisReportados' => (string)($navegacao['urlAnimaisReportados'] ?? (BASE_URL . '/index.php?c=animal&a=listar')),
                'urlReportarAnimal' => (string)($navegacao['urlReportarAnimal'] ?? (BASE_URL . '/index.php?c=animal&a=reportar')),
                'urlMeuPerfil' => (string)($navegacao['urlMeuPerfil'] ?? (BASE_URL . '/index.php?c=usuario&a=meuPerfil')),
                'urlLogout' => (string)($navegacao['urlLogout'] ?? (BASE_URL . '/index.php?c=auth&a=logout')),
                'urlLoginHome' => (string)($navegacao['urlLoginHome'] ?? ($urlHome . '#login')),
                'urlCadastroHome' => (string)($navegacao['urlCadastroHome'] ?? ($urlHome . '#cadastro')),
            ],
            'modal' => [
                'usaModalAutenticacao' => (bool)($modal['usaModalAutenticacao'] ?? false),
                'modalAbertoAtual' => is_string($modal['modalAbertoAtual'] ?? null) ? (string)$modal['modalAbertoAtual'] : '',
            ],
            'sessao' => [
                'usuarioLogado' => (bool)($sessao['usuarioLogado'] ?? !empty($_SESSION['usuario_id'])),
            ],
        ];
    }
}
