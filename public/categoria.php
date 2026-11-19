<?php

session_start();

require_once __DIR__ . '/../app/Controllers/CategoriaPublicaController.php';
require_once __DIR__ . '/../app/helpers/seguranca.php';

$slug = $_GET['slug'] ?? '';
$slug = is_string($slug) ? trim($slug) : '';
$paginaInformada = filter_input(INPUT_GET, 'pagina', FILTER_VALIDATE_INT);
$pagina = $paginaInformada !== false && $paginaInformada !== null ? max(1, $paginaInformada) : 1;
$resultado = (new CategoriaPublicaController())->index($slug, $pagina);

$categorias = $resultado['categorias'];
$categoria = $resultado['categoria'];
$apresentacao = $resultado['apresentacao'];
$publicacoes = $resultado['publicacoes'];
$eventos = $resultado['eventos'];
$pagina = $resultado['pagina'];
$totalPaginas = $resultado['totalPaginas'];
$totalPublicacoes = $resultado['totalPublicacoes'];
$erro = $resultado['erro'];
$tituloPagina = $categoria ? $categoria['nome'] : 'Categoria não encontrada';

http_response_code($categoria || $erro !== '' ? 200 : 404);
require __DIR__ . '/../app/Views/public/categoria.php';
