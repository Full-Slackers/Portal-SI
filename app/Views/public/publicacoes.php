<?php
require_once __DIR__ . '/../layout/header.php';
$pagina = isset($pagina) ? max(1, (int) $pagina) : max(1, (int) ($_GET['pagina'] ?? 1));
$publicacoes = $publicacoes ?? [];
$erro = $erro ?? '';
$totalPaginas = $totalPaginas ?? 1;
?>

<main class="site-main">
    <section class="site-section">
        <div class="site-container section-head">
            <div>
                <span class="section-kicker">Portal acadêmico</span>
                <h1 class="publicacoes-titulo">Todas as publicações</h1>
                <p class="publicacoes-intro">Notícias, projetos e ações do curso de Sistemas de Informação.</p>
            </div>
            <a href="/portal-si/public/index.php#publicacoes" class="section-link">Voltar para a home</a>
        </div>

        <div class="site-container card-grid">
            <?php if ($erro !== ''): ?>
                <div class="empty-state"><?= e($erro); ?></div>
            <?php elseif ($publicacoes === []): ?>
                <div class="empty-state">Nenhuma publicação publicada no momento.</div>
            <?php else: ?>
                <?php foreach ($publicacoes as $item): ?>
                    <?php $rotuloCategoria = $item['categoria']; ?>
                    <?php require __DIR__ . '/componentes/card_conteudo.php'; ?>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <?php if ($erro === '' && $totalPaginas > 1): ?>
            <nav class="paginacao publicacoes-paginacao" aria-label="Paginação das publicações">
                <?php if ($pagina > 1): ?>
                    <a class="btn btn-secundario" href="?pagina=<?= $pagina - 1; ?>">Anterior</a>
                <?php endif; ?>
                <span>Página <?= $pagina; ?> de <?= $totalPaginas; ?></span>
                <?php if ($pagina < $totalPaginas): ?>
                    <a class="btn btn-secundario" href="?pagina=<?= $pagina + 1; ?>">Próxima</a>
                <?php endif; ?>
            </nav>
        <?php endif; ?>
    </section>
</main>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>
