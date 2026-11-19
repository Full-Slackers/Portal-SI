<?php
$item = isset($item) && is_array($item) ? $item : [];
$rotulo = $rotuloCategoria ?? ($item['categoria'] ?? 'Publicação');
$dataPublicacao = $item['publicado_em'] ?? '';
$dataTimestamp = $dataPublicacao !== '' ? strtotime((string) $dataPublicacao) : false;
?>
<article class="content-card">
    <?php if (!empty($item['imagem_capa'])): ?>
        <a class="content-card-imagem-link" href="/portal-si/public/conteudo.php?id=<?= (int) $item['id']; ?>">
            <img class="content-card-imagem" src="<?= e($item['imagem_capa']); ?>" alt="Imagem de capa: <?= e($item['titulo']); ?>" loading="lazy">
        </a>
    <?php endif; ?>

    <div class="card-topline">
        <span class="card-category"><?= e($rotulo); ?></span>
        <?php if (!empty($item['destaque'])): ?>
            <span class="card-pill">Destaque</span>
        <?php endif; ?>
    </div>

    <h3><?= e($item['titulo']); ?></h3>
    <p><?= e($item['resumo'] ?? 'Confira esta publicação e conheça mais detalhes.'); ?></p>

    <?php if (!empty($item['tags'])): ?>
        <ul class="content-card-tags" aria-label="Tags da publicação">
            <?php foreach (explode(', ', $item['tags']) as $tag): ?>
                <li><?= e($tag); ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <div class="card-meta">
        <?php if ($dataTimestamp !== false): ?>
            <time datetime="<?= e((string) $dataPublicacao); ?>"><?= e(date('d/m/Y', $dataTimestamp)); ?></time>
        <?php else: ?>
            <span>Publicação recente</span>
        <?php endif; ?>
        <?php if (!empty($item['autor'])): ?>
            <span><?= e($item['autor']); ?></span>
        <?php endif; ?>
        <a href="/portal-si/public/conteudo.php?id=<?= (int) $item['id']; ?>">Ler mais</a>
    </div>
</article>
