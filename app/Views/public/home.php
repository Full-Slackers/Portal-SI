<?php
require_once __DIR__ . '/../layout/header.php';

$itensPublicacao = $publicacoes ?? [];
$categorias = $categorias ?? [];
$eventos = $eventos ?? [];
$erro = $erro ?? '';
?>

<main class="site-main">
    <section class="site-hero">
        <div class="site-container hero-grid">
            <div class="hero-copy">
                <span class="eyebrow">PORTAL SI · ESUCRI</span>
                <h1>Inovação e Extensão Universitária em Sistemas de Informação</h1>
                <p>
                    Acompanhe projetos, publicações e ações de impacto social desenvolvidas pelo curso,
                    com foco em pesquisa, extensão e inovação aplicada à comunidade regional.
                </p>

                <div class="hero-actions">
                    <a class="btn btn-primary" href="#noticias-home">Ver notícias</a>
                    <a class="btn btn-secondary" href="#categorias">Explorar categorias</a>
                </div>
            </div>

            <article class="hero-feature" aria-label="Sobre o Portal SI">
                <span class="feature-tag">Faculdades ESUCRI</span>
                <h2>Conhecimento que transforma a comunidade.</h2>
                <p>
                    Um espaço para acompanhar as iniciativas, os projetos e as notícias
                    produzidas pelo curso de Sistemas de Informação.
                </p>
                <a class="feature-link" href="#noticias-home">Confira as notícias <span aria-hidden="true">→</span></a>
            </article>
        </div>
    </section>

    <section class="site-section" id="noticias-home">
        <div class="site-container section-head">
            <div>
                <span class="section-kicker">Atualizado pelo portal</span>
                <h2>Publicações recentes</h2>
                <p class="publicacoes-intro">Acompanhe notícias, projetos e iniciativas recentes do curso.</p>
            </div>
            <a class="section-link" href="/portal-si/public/publicacoes.php">Ver todas as publicações</a>
        </div>

        <div class="site-container card-grid">
            <?php if ($erro !== ''): ?>
                <div class="empty-state"><?= e($erro); ?></div>
            <?php elseif ($itensPublicacao === []): ?>
                <div class="empty-state">Ainda não há publicações disponíveis. Assim que um conteúdo for publicado, ele aparecerá aqui.</div>
            <?php else: ?>
                <?php foreach ($itensPublicacao as $item): ?>
                    <?php require __DIR__ . '/componentes/card_conteudo.php'; ?>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </section>

    <section class="site-section agenda-publica" id="agenda">
        <div class="site-container section-head">
            <div>
                <span class="section-kicker">Programe-se</span>
                <h2>Próximos eventos</h2>
                <p class="publicacoes-intro">Encontros e atividades divulgados pelo curso.</p>
            </div>
        </div>
        <div class="site-container agenda-grid">
            <?php if ($eventos === []): ?>
                <div class="empty-state">Ainda não há eventos futuros publicados. As novidades aparecerão aqui.</div>
            <?php else: ?>
                <?php foreach ($eventos as $evento): ?>
                    <article class="evento-publico-card">
                        <time datetime="<?= e($evento['data_inicio']); ?>">
                            <?= e(date('d/m/Y', strtotime($evento['data_inicio']))); ?>
                            <span><?= e(date('H:i', strtotime($evento['data_inicio']))); ?></span>
                        </time>
                        <div>
                            <?php if (!empty($evento['categoria'])): ?>
                                <span class="card-category"><?= e($evento['categoria']); ?></span>
                            <?php endif; ?>
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

    <section class="site-highlight" id="categorias">
        <div class="site-container section-head categoria-lista-head">
            <div>
                <span class="section-kicker">Explore o portal</span>
                <h2>Categorias</h2>
            </div>
        </div>
        <div class="site-container categoria-grid">
            <?php if ($erro !== ''): ?>
                <div class="empty-state"><?= e($erro); ?></div>
            <?php elseif ($categorias === []): ?>
                <div class="empty-state">As categorias estarão disponíveis assim que forem cadastradas no portal.</div>
            <?php else: ?>
                <?php foreach ($categorias as $categoria): ?>
                    <article class="categoria-card">
                        <span class="categoria-card-icone" aria-hidden="true"><?= htmlspecialchars(strtoupper(substr((string) $categoria['nome'], 0, 1)), ENT_QUOTES, 'UTF-8'); ?></span>
                        <h3><?= htmlspecialchars((string) $categoria['nome'], ENT_QUOTES, 'UTF-8'); ?></h3>
                        <p><?= htmlspecialchars((string) ($categoria['descricao'] ?? 'Conheça os conteúdos relacionados a esta categoria.'), ENT_QUOTES, 'UTF-8'); ?></p>
                        <span class="categoria-card-contagem"><?= (int) ($categoria['total_publicacoes'] ?? 0); ?> publicações</span>
                        <a href="/portal-si/public/categoria.php?slug=<?= urlencode((string) $categoria['slug']); ?>">Explorar categoria <span aria-hidden="true">→</span></a>
                    </article>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </section>

    <section class="home-apresentacao" aria-labelledby="home-apresentacao-titulo">
        <div class="site-container home-apresentacao-inner">
            <div class="home-apresentacao-intro">
                <span class="section-kicker">Mais que notícias</span>
                <h2 id="home-apresentacao-titulo">Conhecimento que se conecta com a vida real</h2>
                <p>
                    O Portal SI aproxima o curso, a tecnologia e a comunidade em um só lugar.
                    Explore ideias, acompanhe iniciativas e descubra diferentes formas de aprender e participar.
                </p>
            </div>
            <div class="home-apresentacao-itens">
                <article>
                    <span aria-hidden="true">01</span>
                    <h3>Descubra</h3>
                    <p>Encontre conteúdos sobre Sistemas de Informação, formação e inovação.</p>
                </article>
                <article>
                    <span aria-hidden="true">02</span>
                    <h3>Conecte</h3>
                    <p>Veja como conhecimento, pessoas e comunidade podem construir novas possibilidades.</p>
                </article>
                <article>
                    <span aria-hidden="true">03</span>
                    <h3>Participe</h3>
                    <p>Acompanhe projetos, ações e eventos e fique por dentro do que é compartilhado no portal.</p>
                </article>
            </div>
        </div>
    </section>

</main>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>
