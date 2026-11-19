<?php

session_start();

require_once __DIR__ . '/../app/Controllers/HomeController.php';
require_once __DIR__ . '/../app/helpers/seguranca.php';

try {
    $home = (new HomeController())->index();
    $categorias = $home['categorias'];
    $publicacoes = $home['publicacoes'];
    $eventos = $home['eventos'];
    $erro = $home['erro'];
    $tituloPagina = 'Home';
    require_once __DIR__ . '/../app/Views/public/home.php';
} catch (Throwable $e) {
    error_log('Falha ao renderizar a home do portal: ' . $e->getMessage());
    $categorias = [];
    $publicacoes = [];
    $eventos = [];
    $erro = 'Não foi possível carregar as informações do portal no momento. Tente novamente mais tarde.';
    $tituloPagina = 'Home';
    require_once __DIR__ . '/../app/Views/public/home.php';
}
