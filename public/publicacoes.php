<?php

session_start();

require_once __DIR__ . '/../app/Controllers/PublicacoesController.php';
require_once __DIR__ . '/../app/helpers/seguranca.php';

$paginaInformada = filter_input(INPUT_GET, 'pagina', FILTER_VALIDATE_INT);
$pagina = $paginaInformada !== false && $paginaInformada !== null ? max(1, $paginaInformada) : 1;
$listagem = (new PublicacoesController())->index($pagina);

$categorias = $listagem['categorias'];
$publicacoes = $listagem['publicacoes'];
$pagina = $listagem['pagina'];
$totalPaginas = $listagem['totalPaginas'];
$erro = $listagem['erro'];
$tituloPagina = 'Publicações';

require __DIR__ . '/../app/Views/public/publicacoes.php';
