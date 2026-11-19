<?php

const MIDIAS_MAX_ARQUIVOS = 5;
const MIDIAS_MAX_TAMANHO = 5242880;

function processarUploadsMidias(?array $arquivos, array &$erros): array
{
    if ($arquivos === null || !isset($arquivos['name'])) {
        return [];
    }

    $nomes = is_array($arquivos['name']) ? $arquivos['name'] : [$arquivos['name']];
    $temporarios = is_array($arquivos['tmp_name'] ?? null) ? $arquivos['tmp_name'] : [$arquivos['tmp_name'] ?? ''];
    $codigosErro = is_array($arquivos['error'] ?? null) ? $arquivos['error'] : [$arquivos['error'] ?? UPLOAD_ERR_NO_FILE];
    $tamanhos = is_array($arquivos['size'] ?? null) ? $arquivos['size'] : [$arquivos['size'] ?? 0];
    $selecionados = [];

    foreach ($nomes as $indice => $nome) {
        $codigoErro = (int) ($codigosErro[$indice] ?? UPLOAD_ERR_NO_FILE);
        if ($codigoErro === UPLOAD_ERR_NO_FILE) {
            continue;
        }

        $selecionados[] = [
            'nome' => is_string($nome) ? $nome : '',
            'temporario' => is_string($temporarios[$indice] ?? null) ? $temporarios[$indice] : '',
            'erro' => $codigoErro,
            'tamanho' => (int) ($tamanhos[$indice] ?? 0),
        ];
    }

    if (count($selecionados) > MIDIAS_MAX_ARQUIVOS) {
        $erros[] = 'Envie no máximo ' . MIDIAS_MAX_ARQUIVOS . ' arquivos por publicação.';

        return [];
    }

    $tiposPermitidos = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif',
        'image/webp' => 'webp',
        'application/pdf' => 'pdf',
    ];
    $tamanhoTotal = 0;
    foreach ($selecionados as $arquivo) {
        if ($arquivo['erro'] !== UPLOAD_ERR_OK) {
            $erros[] = 'Não foi possível receber o arquivo "' . basename($arquivo['nome']) . '".';
            continue;
        }
        if ($arquivo['tamanho'] < 1 || $arquivo['tamanho'] > MIDIAS_MAX_TAMANHO) {
            $erros[] = 'Cada arquivo deve ter no máximo 5 MB.';
            continue;
        }
        $tamanhoTotal += $arquivo['tamanho'];
    }

    if ($tamanhoTotal > MIDIAS_MAX_ARQUIVOS * MIDIAS_MAX_TAMANHO) {
        $erros[] = 'O tamanho total dos arquivos excede o limite permitido.';
    }
    if ($erros !== []) {
        return [];
    }

    $pastaUploads = dirname(__DIR__, 2) . '/public/assets/uploads';
    if (!is_dir($pastaUploads) && !mkdir($pastaUploads, 0755, true) && !is_dir($pastaUploads)) {
        $erros[] = 'Não foi possível preparar a pasta de mídias para receber arquivos.';

        return [];
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $midias = [];
    try {
        foreach ($selecionados as $arquivo) {
            if (!is_uploaded_file($arquivo['temporario'])) {
                $erros[] = 'Um arquivo enviado não pôde ser validado.';
                removerUploadsMidias($midias);

                return [];
            }

            $tipo = $finfo->file($arquivo['temporario']);
            if (!is_string($tipo) || !isset($tiposPermitidos[$tipo])) {
                $erros[] = 'Formato não permitido para "' . basename($arquivo['nome']) . '". Envie JPG, PNG, GIF, WEBP ou PDF.';
                removerUploadsMidias($midias);

                return [];
            }

            $nomeSeguro = preg_replace('/[^A-Za-z0-9._-]/', '_', basename($arquivo['nome']));
            $nomeSeguro = substr(is_string($nomeSeguro) ? $nomeSeguro : 'arquivo', 0, 240);
            $nomeArmazenado = bin2hex(random_bytes(16)) . '.' . $tiposPermitidos[$tipo];
            $caminhoFisico = $pastaUploads . '/' . $nomeArmazenado;

            if (!move_uploaded_file($arquivo['temporario'], $caminhoFisico)) {
                $erros[] = 'Não foi possível salvar "' . basename($arquivo['nome']) . '".';
                removerUploadsMidias($midias);

                return [];
            }

            $midias[] = [
                'nome_arquivo' => $nomeSeguro,
                'caminho' => '/portal-si/public/assets/uploads/' . $nomeArmazenado,
                'caminho_fisico' => $caminhoFisico,
                'tipo' => $tipo,
                'tamanho_bytes' => $arquivo['tamanho'],
            ];
        }
    } catch (Throwable $e) {
        removerUploadsMidias($midias);
        throw $e;
    }

    return $midias;
}

function removerUploadsMidias(array $midias): void
{
    foreach ($midias as $midia) {
        $caminho = $midia['caminho_fisico'] ?? '';
        if (is_string($caminho) && is_file($caminho)) {
            unlink($caminho);
        }
    }
}
