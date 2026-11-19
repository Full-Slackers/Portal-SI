<?php
require_once __DIR__ . '/../layout/header.php';

if (!isset($publicacao)) {
    $publicacao = null;
}
if (!isset($videoYoutubeId)) {
    $videoYoutubeId = null;
}
if (!isset($totalComentarios)) {
    $totalComentarios = 0;
}
if (!isset($mensagem)) {
    $mensagem = '';
}
if (!isset($erros)) {
    $erros = [];
}
if (!isset($comentarios)) {
    $comentarios = [];
}
if (!isset($nomeComentario)) {
    $nomeComentario = '';
}
if (!isset($textoComentario)) {
    $textoComentario = '';
}
if (!isset($relacionadas)) {
    $relacionadas = [];
}
if (!isset($tags)) {
    $tags = [];
}
if (!isset($midias)) {
    $midias = [];
}
?>

<main class="site-main">
    <?php if ($publicacao === null): ?>
        <section class="site-section">
            <div class="site-container">
                <div class="empty-state">
                    <?= $erros !== [] ? e($erros[0]) : 'Esta publicação não foi encontrada ou não está disponível.'; ?>
                </div>
                <p class="categoria-voltar"><a class="section-link" href="/portal-si/public/index.php">Voltar para a home</a></p>
            </div>
        </section>
    <?php else: ?>
        <?php $dataPublicacao = $publicacao['publicado_em'] ?: 'now'; ?>
        <article class="publicacao-page">
            <header class="publicacao-hero">
                <div class="site-container publicacao-hero-inner">
                    <a class="categoria-voltar-link" href="<?= $publicacao['categoria_slug'] === 'home' ? '/portal-si/public/index.php' : '/portal-si/public/categoria.php?slug=' . urlencode($publicacao['categoria_slug']); ?>">
                        ← <?= $publicacao['categoria_slug'] === 'home' ? 'Início' : e($publicacao['categoria']); ?>
                    </a>
                    <span class="eyebrow">PUBLICAÇÃO<?= $publicacao['categoria_slug'] !== 'home' ? ' · ' . e($publicacao['categoria']) : ''; ?></span>
                    <h1><?= e($publicacao['titulo']); ?></h1>
                    <?php if (!empty($publicacao['resumo'])): ?>
                        <p class="publicacao-resumo"><?= e($publicacao['resumo']); ?></p>
                    <?php endif; ?>
                    <div class="publicacao-meta">
                        <span>Por <?= e($publicacao['autor']); ?></span>
                        <time datetime="<?= e($dataPublicacao); ?>"><?= e(date('d/m/Y', strtotime($dataPublicacao))); ?></time>
                        <a href="#comentarios"><?= $totalComentarios; ?> comentário(s)</a>
                    </div>
                </div>
            </header>

            <div class="site-container publicacao-corpo">
                <?php if (!empty($publicacao['imagem_capa'])): ?>
                    <img class="publicacao-capa" src="<?= e($publicacao['imagem_capa']); ?>" alt="Imagem de capa: <?= e($publicacao['titulo']); ?>">
                <?php endif; ?>

                <?php if ($videoYoutubeId !== null): ?>
                    <div class="publicacao-video">
                        <iframe
                            src="https://www.youtube-nocookie.com/embed/<?= e($videoYoutubeId); ?>"
                            title="Vídeo relacionado: <?= e($publicacao['titulo']); ?>"
                            loading="lazy"
                            referrerpolicy="strict-origin-when-cross-origin"
                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                            allowfullscreen
                        ></iframe>
                    </div>
                <?php endif; ?>

                <?php if ($tags !== []): ?>
                    <section class="publicacao-tags" aria-label="Tags da publicação">
                        <strong>Temas:</strong>
                        <ul>
                            <?php foreach ($tags as $tag): ?>
                                <li><?= e($tag['nome']); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </section>
                <?php endif; ?>

                <div class="publicacao-texto"><?= nl2br(e($publicacao['corpo'] ?? '')); ?></div>

                <?php if ($midias !== []): ?>
                    <section class="publicacao-midias" aria-labelledby="publicacao-midias-titulo">
                        <h2 id="publicacao-midias-titulo">Materiais relacionados</h2>
                        <div class="publicacao-midias-grid">
                            <?php foreach ($midias as $midia): ?>
                                <?php if (str_starts_with((string) $midia['tipo'], 'image/')): ?>
                                    <a class="publicacao-midia-imagem" href="<?= e($midia['caminho']); ?>" target="_blank" rel="noopener noreferrer">
                                        <img src="<?= e($midia['caminho']); ?>" alt="<?= e($midia['nome_arquivo']); ?>" loading="lazy">
                                        <span><?= e($midia['nome_arquivo']); ?></span>
                                    </a>
                                <?php else: ?>
                                    <a class="publicacao-midia-arquivo" href="<?= e($midia['caminho']); ?>" target="_blank" rel="noopener noreferrer">
                                        <span aria-hidden="true">PDF</span>
                                        <span><?= e($midia['nome_arquivo']); ?></span>
                                        <?php if (!empty($midia['tamanho_kb'])): ?>
                                            <small><?= (int) $midia['tamanho_kb']; ?> KB</small>
                                        <?php endif; ?>
                                    </a>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    </section>
                <?php endif; ?>

                <div class="publicacao-acao">
                    <span>O que você achou desta publicação?</span>
                    <a class="btn btn-primary" href="#comentarios">Deixe seu comentário</a>
                </div>
            </div>
        </article>

        <section class="site-section publicacao-interacao" id="comentarios">
            <div class="site-container interacao-grid">
                <div class="comentarios-lista">
                    <span class="section-kicker">Conversa aberta</span>
                    <h2>Comentários <span class="comentarios-contagem">(<?= $totalComentarios; ?>)</span></h2>

                    <?php if ($mensagem !== ''): ?>
                        <div class="alert alert-success"><?= e($mensagem); ?></div>
                    <?php endif; ?>

                    <?php if ($erros !== []): ?>
                        <div class="alert alert-error">
                            <ul>
                                <?php foreach ($erros as $erro): ?>
                                    <li><?= e($erro); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <?php if ($comentarios === []): ?>
                        <div class="empty-state">Seja a primeira pessoa a comentar. Sua opinião pode inspirar a próxima conversa.</div>
                    <?php else: ?>
                        <?php foreach ($comentarios as $comentario): ?>
                            <article class="comentario-card">
                                <div class="comentario-cabecalho">
                                    <span class="comentario-avatar" aria-hidden="true"><?= e(strtoupper(mb_substr($comentario['nome'], 0, 1))); ?></span>
                                    <div>
                                        <strong><?= e($comentario['nome']); ?></strong>
                                        <time datetime="<?= e($comentario['criado_em']); ?>"><?= e(date('d/m/Y \à\s H:i', strtotime($comentario['criado_em']))); ?></time>
                                    </div>
                                </div>
                                <p><?= nl2br(e($comentario['comentario'])); ?></p>
                            </article>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <aside class="comentario-form-card">
                    <span class="section-kicker">Participe</span>
                    <h2>Deixe sua contribuição</h2>
                    <p>Compartilhe uma ideia, dúvida ou experiência relacionada a esta publicação.</p>
                    <form method="POST" action="/portal-si/public/conteudo.php?id=<?= (int) $publicacao['id']; ?>#comentarios">
                        <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']); ?>">
                        <div class="campo">
                            <label for="nome">Seu nome</label>
                            <input type="text" id="nome" name="nome" maxlength="120" value="<?= e($nomeComentario); ?>" required>
                        </div>
                        <div class="campo">
                            <label for="comentario">Comentário</label>
                            <textarea id="comentario" name="comentario" rows="5" maxlength="2000" required><?= e($textoComentario); ?></textarea>
                        </div>
                        <button type="submit" class="btn-destaque">Publicar comentário</button>
                    </form>
                </aside>
            </div>
        </section>

        <?php if ($relacionadas !== []): ?>
            <section class="site-section publicacoes-relacionadas">
                <div class="site-container section-head">
                    <div>
                        <span class="section-kicker">Continue explorando</span>
                        <h2>Mais de <?= e($publicacao['categoria']); ?></h2>
                    </div>
                    <?php if ($publicacao['categoria_slug'] !== 'home'): ?>
                        <a class="section-link" href="/portal-si/public/categoria.php?slug=<?= urlencode($publicacao['categoria_slug']); ?>">Ver categoria</a>
                    <?php else: ?>
                        <a class="section-link" href="/portal-si/public/index.php#publicacoes">Voltar às publicações</a>
                    <?php endif; ?>
                </div>
                <div class="site-container card-grid">
                    <?php foreach ($relacionadas as $relacionada): ?>
                        <article class="content-card">
                            <h3><?= e($relacionada['titulo']); ?></h3>
                            <p><?= e($relacionada['resumo'] ?? 'Leia mais sobre este tema.'); ?></p>
                            <div class="card-meta">
                                <a href="/portal-si/public/conteudo.php?id=<?= (int) $relacionada['id']; ?>">Ler publicação</a>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>
    <?php endif; ?>
</main>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>
