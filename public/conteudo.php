<?php

session_start();

require_once __DIR__ . '/../app/Controllers/PublicacaoController.php';
require_once __DIR__ . '/../app/helpers/seguranca.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$id = $id !== false && $id !== null ? $id : 0;
$paginaPublicacao = (new PublicacaoController())->exibir($id);

$publicacao = $paginaPublicacao['publicacao'];
$videoYoutubeId = $paginaPublicacao['videoYoutubeId'];
$tags = $paginaPublicacao['tags'];
$midias = $paginaPublicacao['midias'];
$comentarios = $paginaPublicacao['comentarios'];
$totalComentarios = $paginaPublicacao['totalComentarios'];
$relacionadas = $paginaPublicacao['relacionadas'];
$erros = $paginaPublicacao['erros'];
$mensagem = $paginaPublicacao['mensagem'];
$nomeComentario = $paginaPublicacao['nomeComentario'];
$textoComentario = $paginaPublicacao['textoComentario'];
$categorias = $paginaPublicacao['categorias'];
$categoria = $publicacao
    ? ['slug' => $publicacao['categoria_slug']]
    : null;
$tituloPagina = $publicacao ? $publicacao['titulo'] : 'Publicação não encontrada';

if ($publicacao === null && $erros === []) {
    http_response_code(404);
}

require __DIR__ . '/../app/Views/public/conteudo.php';
