<?php
$usuarioLogado = !empty($_SESSION['usuario_id']);
$categoriasMenu = $categorias ?? [];
$slugCategoriaAtual = isset($categoria['slug']) && is_string($categoria['slug'])
    ? $categoria['slug']
    : '';
$paginaInicial = basename((string) ($_SERVER['SCRIPT_NAME'] ?? '')) === 'index.php';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($tituloPagina) ? htmlspecialchars($tituloPagina, ENT_QUOTES, 'UTF-8') . ' | Portal SI' : 'Portal SI — ESUCRI'; ?></title>
    <meta name="description" content="Portal SI da ESUCRI com notícias, projetos e conteúdos acadêmicos de Sistemas de Informação.">
    <link rel="stylesheet" href="/portal-si/public/assets/css/estilo.css?v=nav-inicio-20261110">
</head>
<body class="site-home">
    <header class="site-topo">
        <div class="site-container topo-inner">
            <a class="marca" href="/portal-si/public/index.php" aria-label="Portal SI início">
                <img class="marca-logo" src="/portal-si/public/assets/images/esucri-topo.png" alt="Faculdades ESUCRI">
                <span class="marca-nome">Portal SI</span>
            </a>

            <nav class="site-nav" aria-label="Navegação principal">
                <a href="/portal-si/public/index.php" <?= $paginaInicial ? 'aria-current="page"' : ''; ?>>Início</a>
                <?php foreach ($categoriasMenu as $categoriaMenu): ?>
                    <a
                        href="/portal-si/public/categoria.php?slug=<?= urlencode((string) ($categoriaMenu['slug'] ?? '')); ?>"
                        <?= ($categoriaMenu['slug'] ?? '') === $slugCategoriaAtual ? 'aria-current="page"' : ''; ?>
                    ><?= htmlspecialchars((string) ($categoriaMenu['nome'] ?? 'Categoria'), ENT_QUOTES, 'UTF-8'); ?></a>
                <?php endforeach; ?>
                <?php if ($usuarioLogado): ?>
                    <a href="/portal-si/app/Views/admin/painel_adimin.php">Painel</a>
                    <a href="/portal-si/app/Views/admin/logout.php" class="btn-acesso btn-acesso-sair">Sair</a>
                <?php else: ?>
                    <a href="/portal-si/app/Views/admin/login.php" class="btn-acesso">Acessar Painel</a>
                <?php endif; ?>
            </nav>
        </div>
    </header>
