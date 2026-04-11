<?php
declare(strict_types=1);

if (!function_exists('h')) {
    function h($v): string
    {
        return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('statusBadgeClassUI')) {
    // Classe de badge para status do animal (UI: tag/tag-*)
    function statusBadgeClassUI(string $s): string {
        $s = mb_strtolower(trim($s));
        if ($s === 'aguardando') return 'tag tag-warn';
        if ($s === 'resgatado')  return 'tag tag-ok';
        if ($s === 'adoção' || $s === 'adocao') return 'tag tag-info';
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
// Gera iniciais para avatar
function initials(string $name): string {
    $name = trim($name);
    if ($name === '') return 'US';
    $parts = preg_split('/\s+/', $name);
    $a = strtoupper(mb_substr($parts[0] ?? 'U', 0, 1));
    $b = strtoupper(mb_substr($parts[1] ?? ($parts[0] ?? 'S'), 0, 1));
    return $a . $b;
}

function pageUrl(int $p, string $status): string {
    $q = [
        'c' => 'animal',
        'a' => 'listar',
        'p' => $p,
    ];
    if ($status !== '') $q['status'] = $status;
    return BASE_URL . '/index.php?' . http_build_query($q);
}