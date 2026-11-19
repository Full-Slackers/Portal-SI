<?php

function caminhoImagemValido(string $caminho): bool
{
    if ($caminho === '') {
        return true;
    }

    if (strlen($caminho) > 255) {
        return false;
    }

    if (filter_var($caminho, FILTER_VALIDATE_URL)) {
        $partes = parse_url($caminho);

        return is_array($partes)
            && strtolower((string) ($partes['scheme'] ?? '')) === 'https';
    }

    return strpos($caminho, '//') !== 0
        && strpos(rawurldecode($caminho), '..') === false
        && preg_match('~^(?:/|public/|assets/)[A-Za-z0-9._/%-]+$~', $caminho) === 1;
}

function obterIdYoutube(string $url): ?string
{
    if ($url === '' || strlen($url) > 255) {
        return null;
    }

    $partes = parse_url($url);
    if (!is_array($partes) || !in_array(strtolower((string) ($partes['scheme'] ?? '')), ['http', 'https'], true)) {
        return null;
    }

    $host = strtolower((string) ($partes['host'] ?? ''));
    $caminho = (string) ($partes['path'] ?? '');
    $id = null;

    if (in_array($host, ['youtu.be', 'www.youtu.be'], true)) {
        $id = trim($caminho, '/');
    } elseif (in_array($host, ['youtube.com', 'www.youtube.com', 'm.youtube.com', 'youtube-nocookie.com', 'www.youtube-nocookie.com'], true)) {
        if ($caminho === '/watch') {
            parse_str((string) ($partes['query'] ?? ''), $query);
            $id = is_string($query['v'] ?? null) ? $query['v'] : null;
        } elseif (preg_match('~^/(?:embed|shorts|live)/([^/]+)~', $caminho, $correspondencia) === 1) {
            $id = $correspondencia[1];
        }
    }

    return is_string($id) && preg_match('/^[A-Za-z0-9_-]{11}$/', $id) === 1
        ? $id
        : null;
}
