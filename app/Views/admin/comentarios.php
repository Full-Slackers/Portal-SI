<?php
require_once __DIR__ . '/../../helpers/auth.php';
require_once __DIR__ . '/../../helpers/seguranca.php';
require_once __DIR__ . '/../../Controllers/ComentariosController.php';

require_perfil(['admin']);

$pagina = (new ComentariosController())->index();
$erros = $pagina['erros'];
$mensagem = $pagina['mensagem'];
$comentarios = $pagina['comentarios'];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Moderar comentários — Portal SI</title>
    <link rel="stylesheet" href="/portal-si/public/assets/css/estilo.css?v=admin-nav-ativo-20261110">
</head>
<body class="admin-page">
    <header class="admin-topo">
        <a class="marca" href="/portal-si/app/Views/admin/painel_adimin.php">
            <span class="tarja"></span>
            Painel administrativo
        </a>
        <nav class="admin-navegacao" aria-label="Navegação administrativa">
            <a href="/portal-si/app/Views/admin/painel_adimin.php">Publicações</a>
            <a href="/portal-si/app/Views/admin/eventos.php">Eventos</a>
            <a href="/portal-si/app/Views/admin/usuarios.php">Usuários</a>
            <a class="ativo" href="/portal-si/app/Views/admin/comentarios.php">Comentários</a>
            <a href="/portal-si/app/Views/admin/auditoria.php">Log</a>
            <a href="/portal-si/app/Views/admin/logout.php">Sair</a>
        </nav>
    </header>

    <main class="admin-home">
        <section class="admin-hero">
            <div class="admin-hero-texto">
                <span class="admin-eyebrow">PORTAL SI · MODERAÇÃO</span>
                <h1>Moderar comentários</h1>
                <p>Revise as contribuições nas publicações e remova conteúdo inadequado.</p>
            </div>
        </section>

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

        <section class="painel-secao admin-card">
            <div class="secao-cabecalho">
                <h2>Comentários recebidos</h2>
                <span><?= count($comentarios); ?> comentário(s)</span>
            </div>

            <?php if ($comentarios === []): ?>
                <p class="estado-vazio">Ainda não há comentários para moderar.</p>
            <?php else: ?>
                <div class="tabela-responsiva">
                    <table class="tabela-noticias">
                        <thead>
                            <tr>
                                <th scope="col">Comentário</th>
                                <th scope="col">Publicação</th>
                                <th scope="col">Enviado por</th>
                                <th scope="col">Data</th>
                                <th scope="col">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($comentarios as $comentario): ?>
                                <tr>
                                    <td class="comentario-moderacao-texto"><?= nl2br(e($comentario['comentario'])); ?></td>
                                    <td>
                                        <a href="/portal-si/public/conteudo.php?id=<?= (int) $comentario['conteudo_id']; ?>" target="_blank" rel="noopener noreferrer">
                                            <?= e($comentario['conteudo_titulo']); ?>
                                        </a>
                                    </td>
                                    <td><?= e($comentario['nome']); ?></td>
                                    <td><?= e(date('d/m/Y H:i', strtotime($comentario['criado_em']))); ?></td>
                                    <td>
                                        <form method="POST" action="/portal-si/app/Views/admin/comentarios.php">
                                            <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']); ?>">
                                            <input type="hidden" name="acao" value="excluir">
                                            <input type="hidden" name="comentario_id" value="<?= (int) $comentario['id']; ?>">
                                            <button type="submit" class="btn btn-pequeno btn-delete">Excluir</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>
    </main>
</body>
</html>
