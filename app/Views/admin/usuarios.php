<?php
require_once __DIR__ . '/../../helpers/auth.php';
require_once __DIR__ . '/../../helpers/seguranca.php';
require_once __DIR__ . '/../../Controllers/UsuariosController.php';

require_perfil(['admin']);

$pagina = (new UsuariosController())->index();
$erros = $pagina['erros'];
$mensagem = $pagina['mensagem'];
$usuarios = $pagina['usuarios'];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Usuários — Portal SI</title>
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
            <a class="ativo" href="/portal-si/app/Views/admin/usuarios.php">Usuários</a>
            <a href="/portal-si/app/Views/admin/comentarios.php">Comentários</a>
            <a href="/portal-si/app/Views/admin/auditoria.php">Log</a>
            <a href="/portal-si/app/Views/admin/logout.php">Sair</a>
        </nav>
    </header>

    <main class="admin-home">
        <section class="admin-hero">
            <div class="admin-hero-texto">
                <span class="admin-eyebrow">PORTAL SI · ADMINISTRAÇÃO</span>
                <h1>Gerenciar usuários</h1>
                <p>Consulte as contas cadastradas e remova usuários quando necessário.</p>
            </div>
            <a class="btn btn-destaque" href="/portal-si/app/Views/admin/cadastro_usuario.php">+ Cadastrar usuário</a>
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
                <h2>Usuários cadastrados</h2>
                <span><?= count($usuarios); ?> usuário(s)</span>
            </div>

            <?php if ($usuarios === []): ?>
                <p class="estado-vazio">Nenhum usuário cadastrado.</p>
            <?php else: ?>
                <div class="tabela-responsiva">
                    <table class="tabela-noticias tabela-usuarios">
                        <thead>
                            <tr>
                                <th scope="col">Nome</th>
                                <th scope="col">E-mail</th>
                                <th scope="col">Perfil</th>
                                <th scope="col">Status</th>
                                <th scope="col">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($usuarios as $usuario): ?>
                                <tr>
                                    <td>
                                        <strong><?= e($usuario['nome']); ?></strong>
                                        <?php if ((int) $usuario['id'] === (int) $_SESSION['usuario_id']): ?>
                                            <span class="etiqueta-destaque">Você</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= e($usuario['email']); ?></td>
                                    <td><?= e(ucfirst($usuario['perfil'])); ?></td>
                                    <td><?= e(ucfirst($usuario['status'])); ?></td>
                                    <td>
                                        <?php if ((int) $usuario['id'] === (int) $_SESSION['usuario_id']): ?>
                                            <span class="texto-suave">Conta atual</span>
                                        <?php else: ?>
                                            <form method="POST" action="/portal-si/app/Views/admin/usuarios.php">
                                                <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']); ?>">
                                                <input type="hidden" name="acao" value="excluir">
                                                <input type="hidden" name="usuario_id" value="<?= (int) $usuario['id']; ?>">
                                                <button type="submit" class="btn btn-pequeno btn-delete">Excluir usuário</button>
                                            </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>

        <div class="painel-rodape">
            <a href="/portal-si/app/Views/admin/logout.php">Sair do sistema</a>
        </div>
    </main>
</body>
</html>
