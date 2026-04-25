<?php
declare(strict_types=1);

if (!defined('CSRF_CONTEXTO_COMENTARIO_ADICIONAR')) {
    define('CSRF_CONTEXTO_COMENTARIO_ADICIONAR', 'comentario_adicionar');
}

// ============================================================
// Escape HTML
// ============================================================

if (!function_exists('h')) {
    function h(mixed $valor): string
    {
        return htmlspecialchars((string)$valor, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('escaparHtml')) {
    function escaparHtml(mixed $valor): string
    {
        return h($valor);
    }
}

// ============================================================
// CSRF
// ============================================================

if (!function_exists('csrfToken')) {
    function csrfToken(string $contexto): string
    {
        if (!isset($_SESSION['_csrf_tokens']) || !is_array($_SESSION['_csrf_tokens'])) {
            $_SESSION['_csrf_tokens'] = [];
        }

        if (empty($_SESSION['_csrf_tokens'][$contexto]) || !is_string($_SESSION['_csrf_tokens'][$contexto])) {
            $_SESSION['_csrf_tokens'][$contexto] = bin2hex(random_bytes(32));
        }

        return $_SESSION['_csrf_tokens'][$contexto];
    }
}

if (!function_exists('csrfInput')) {
    function csrfInput(string $contexto): string
    {
        return '<input type="hidden" name="_csrf_token" value="' . h(csrfToken($contexto)) . '">';
    }
}

if (!function_exists('csrfValidarConsumo')) {
    /**
     * Valida token CSRF e consome o token de sessão do contexto.
     */
    function csrfValidarConsumo(string $contexto, ?string $tokenEnviado = null): bool
    {
        $tokenSessao = $_SESSION['_csrf_tokens'][$contexto] ?? null;
        $tokenEnviadoFinal = (string)($tokenEnviado ?? ($_POST['_csrf_token'] ?? ''));

        unset($_SESSION['_csrf_tokens'][$contexto]);

        return is_string($tokenSessao)
            && $tokenSessao !== ''
            && $tokenEnviadoFinal !== ''
            && hash_equals($tokenSessao, $tokenEnviadoFinal);
    }
}

if (!function_exists('tokenCsrf')) {
    function tokenCsrf(string $contexto): string
    {
        return csrfToken($contexto);
    }
}

if (!function_exists('inputCsrf')) {
    function inputCsrf(string $contexto): string
    {
        return csrfInput($contexto);
    }
}

// ============================================================
// Flash session
// ============================================================

if (!function_exists('flashConsultar')) {
    function flashConsultar(string $chave): ?string
    {
        if (!isset($_SESSION[$chave])) {
            return null;
        }

        $valor = (string)$_SESSION[$chave];
        return $valor !== '' ? $valor : null;
    }
}

if (!function_exists('flashConsumir')) {
    function flashConsumir(string $chave): ?string
    {
        $valor = flashConsultar($chave);
        unset($_SESSION[$chave]);
        return $valor;
    }
}

if (!function_exists('flashDefinir')) {
    function flashDefinir(string $chave, string $mensagem): void
    {
        $_SESSION[$chave] = $mensagem;
    }
}

if (!function_exists('flashConsumirTexto')) {
    /**
     * Compatível com contratos antigos que esperam string (vazia quando ausente).
     */
    function flashConsumirTexto(string $chave): string
    {
        $valor = flashConsumir($chave);
        return $valor ?? '';
    }
}

if (!function_exists('statusBadgeClassUI')) {
    // Classe de badge para status do animal (UI: tag/tag-*)
    function statusBadgeClassUI(string $s): string {
        $s = mb_strtolower(trim($s));
        if ($s === 'aguardando') return 'tag tag-warn';
        if ($s === 'em andamento') return 'tag tag-info';
        if ($s === 'resgatado')  return 'tag tag-ok';
        if ($s === 'adoção' || $s === 'adocao') return 'tag tag-info';
        if ($s === 'finalizado') return 'tag tag-ok';
        return 'tag';
    }
}

if (!function_exists('condicaoBadgeClassUI')) {
    // Classe de badge para condição do animal (UI: tag/tag-*)
    function condicaoBadgeClassUI(string $c): string {
        $c = mb_strtolower(trim($c));

        if ($c === 'ferido' || $c === 'muito debilitado') {
            return 'tag tag-warn';
        }

        if ($c === 'aparentemente saudável' || $c === 'aparentemente saudavel') {
            return 'tag tag-ok';
        }

        return 'tag tag-info';
    }
}

if (!function_exists('formatDateTime')) {
    function formatDateTime(string $value): string {
        $value = trim($value);
        if ($value === '') {
            return '';
        }

        try {
            $dt = new DateTime($value);
        } catch (Exception $e) {
            return $value;
        }

        $format = strpos($value, ' ') !== false ? 'd/m/Y H:i' : 'd/m/Y';
        return $dt->format($format);
    }
}

if (!function_exists('publicImgUrl')) {
    // Normaliza caminho da imagem para URL pública
    function publicImgUrl(string $path): string {
        $path = trim($path);
        if ($path === '') return '';

        // Se já for URL absoluta, retorna como está
        if (preg_match('~^https?://~i', $path)) return $path;

        // Remove barras duplicadas
        $path = ltrim($path, '/');

        // Garante BASE_URL + / + path
        return BASE_URL . '/' . $path;
    }
}

if (!function_exists('initials')) {
    // Gera iniciais para avatar
    function initials(string $name): string {
        $name = trim($name);
        if ($name === '') return 'US';
        $parts = preg_split('/\s+/', $name);
        $a = strtoupper(mb_substr($parts[0] ?? 'U', 0, 1));
        $b = strtoupper(mb_substr($parts[1] ?? ($parts[0] ?? 'S'), 0, 1));
        return $a . $b;
    }
}

if (!function_exists('pageUrl')) {
    function pageUrl(int $p, string $status): string {
        $q = [
            'c' => 'animal',
            'a' => 'listar',
            'p' => $p,
        ];
        if ($status !== '') $q['status'] = $status;
        return BASE_URL . '/index.php?' . http_build_query($q);
    }
}