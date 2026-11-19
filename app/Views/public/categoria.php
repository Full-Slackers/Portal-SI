<?php
$categoria = $categoria ?? null;
$publicacoes = $publicacoes ?? [];
$eventos = $eventos ?? [];
$erro = $erro ?? '';
$totalPaginas = $totalPaginas ?? 1;
$pagina = $pagina ?? 1;
$totalPublicacoes = $totalPublicacoes ?? 0;
require_once __DIR__ . '/../layout/header.php';
?>

<main class="site-main">
    <?php if ($categoria === null): ?>
        <section class="site-section">
            <div class="site-container">
                <div class="empty-state">
                    <?= $erro !== '' ? e($erro) : 'A categoria solicitada não foi encontrada.'; ?>
                </div>
                <p class="categoria-voltar"><a class="section-link" href="/portal-si/public/index.php#categorias">Voltar às categorias</a></p>
            </div>
        </section>
    <?php else: ?>
        <section class="categoria-hero">
            <div class="site-container categoria-hero-inner">
                <a class="categoria-voltar-link" href="/portal-si/public/index.php#categorias">← Todas as categorias</a>
                <span class="eyebrow">PORTAL SI · CATEGORIA</span>
                <h1><?= e($categoria['nome']); ?></h1>
                <p><?= e($categoria['descricao'] ?: 'Acompanhe as informações, iniciativas e publicações relacionadas a esta categoria.'); ?></p>
                <div class="categoria-resumo">
                    <span><?= $totalPublicacoes; ?> publicação(ões) publicadas</span>
                    <?php if (($_SESSION['usuario_perfil'] ?? '') === 'admin'): ?>
                        <a class="btn btn-primary" href="/portal-si/app/Views/admin/painel_adimin.php?novo=1&amp;categoria=<?= (int) $categoria['id']; ?>">Criar publicação nesta categoria</a>
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <?php $apresentacao = $apresentacao ?? [
            'titulo' => 'Sobre esta categoria',
            'texto' => 'Acompanhe as informações, iniciativas e publicações relacionadas a esta categoria.',
            'destaques' => [],
        ]; ?>
        <section class="site-section categoria-apresentacao" aria-labelledby="categoria-apresentacao-titulo">
            <div class="site-container">
                <div class="categoria-apresentacao-intro">
                    <span class="section-kicker">Mais sobre <?= e($categoria['nome']); ?></span>
                    <h2 id="categoria-apresentacao-titulo"><?= e($apresentacao['titulo'] ?? 'Sobre esta categoria'); ?></h2>
                    <p><?= e($apresentacao['texto'] ?? 'Acompanhe as informações, iniciativas e publicações relacionadas a esta categoria.'); ?></p>
                </div>
                <?php if (!empty($apresentacao['destaques'])): ?>
                    <div class="categoria-apresentacao-grid">
                        <?php foreach ($apresentacao['destaques'] as $destaque): ?>
                            <article class="categoria-apresentacao-card">
                                <span class="categoria-apresentacao-marca" aria-hidden="true"></span>
                                <h3><?= e($destaque['titulo'] ?? 'Destaque'); ?></h3>
                                <p><?= e($destaque['texto'] ?? ''); ?></p>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </section>

        <section class="site-section agenda-publica" id="agenda">
            <div class="site-container section-head">
                <div>
                    <span class="section-kicker">Programe-se</span>
                    <h2>Próximos eventos</h2>
                    <p class="publicacoes-intro">Eventos publicados relacionados a <?= e($categoria['nome']); ?>.</p>
                </div>
            </div>
            <div class="site-container agenda-grid">
                <?php if ($eventos === []): ?>
                    <div class="empty-state">Ainda não há eventos futuros nesta categoria.</div>
                <?php else: ?>
                    <?php foreach ($eventos as $evento): ?>
                        <article class="evento-publico-card">
                            <time datetime="<?= e($evento['data_inicio']); ?>">
                                <?= e(date('d/m/Y', strtotime($evento['data_inicio']))); ?>
                                <span><?= e(date('H:i', strtotime($evento['data_inicio']))); ?></span>
                            </time>
                            <div>
                                <span class="card-category"><?= e($categoria['nome']); ?></span>
                                <h3><?= e($evento['titulo']); ?></h3>
                                <?php if (!empty($evento['descricao'])): ?>
                                    <p><?= e($evento['descricao']); ?></p>
                                <?php endif; ?>
                                <?php if (!empty($evento['local'])): ?>
                                    <span class="evento-local">Local: <?= e($evento['local']); ?></span>
                                <?php endif; ?>
                            </div>
                        </article>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>

        <section class="site-section">
            <div class="site-container section-head">
                <div>
                    <span class="section-kicker">Conteúdo por categoria</span>
                    <h2>Publicações de <?= e($categoria['nome']); ?></h2>
                </div>
            </div>

            <?php if ($erro !== ''): ?>
                <div class="site-container empty-state"><?= e($erro); ?></div>
            <?php else: ?>
                <div class="site-container card-grid">
                    <?php if ($publicacoes === []): ?>
                        <div class="empty-state">Ainda não há publicações nesta categoria. Novos conteúdos aparecerão aqui quando forem publicados.</div>
                    <?php else: ?>
                        <?php foreach ($publicacoes as $item): ?>
                            <?php $rotuloCategoria = $categoria['nome']; ?>
                            <?php require __DIR__ . '/componentes/card_conteudo.php'; ?>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <?php if ($totalPaginas > 1): ?>
                    <nav class="paginacao publicacoes-paginacao" aria-label="Paginação das publicações">
                        <?php if ($pagina > 1): ?>
                            <a class="btn btn-secundario" href="?slug=<?= urlencode($categoria['slug']); ?>&amp;pagina=<?= $pagina - 1; ?>">Anterior</a>
                        <?php endif; ?>
                        <span>Página <?= $pagina; ?> de <?= $totalPaginas; ?></span>
                        <?php if ($pagina < $totalPaginas): ?>
                            <a class="btn btn-secundario" href="?slug=<?= urlencode($categoria['slug']); ?>&amp;pagina=<?= $pagina + 1; ?>">Próxima</a>
                        <?php endif; ?>
                    </nav>
                <?php endif; ?>
            <?php endif; ?>
        </section>
    <?php endif; ?>
</main>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>
