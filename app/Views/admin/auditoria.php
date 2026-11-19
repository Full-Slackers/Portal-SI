<?php
require_once __DIR__ . '/../../helpers/auth.php';
require_once __DIR__ . '/../../helpers/seguranca.php';
require_once __DIR__ . '/../../Controllers/AuditoriaController.php';

require_perfil(['admin']);

$resultado = (new AuditoriaController())->index();
$registros = $resultado['registros'];
$erro = $resultado['erro'];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Log — Painel administrativo</title>
    <link rel="stylesheet" href="/portal-si/public/assets/css/estilo.css?v=admin-auditoria-20261110">
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
            <a href="/portal-si/app/Views/admin/comentarios.php">Comentários</a>
            <a class="ativo" href="/portal-si/app/Views/admin/auditoria.php">Log</a>
            <a href="/portal-si/app/Views/admin/logout.php">Sair</a>
        </nav>
    </header>

    <main class="admin-home">
        <section class="admin-hero">
            <div class="admin-hero-texto">
                <span class="admin-eyebrow">PORTAL SI · LOG</span>
                <h1>Log de atividades</h1>
                <p>Consulte as ações administrativas recentes. São exibidos até 200 registros.</p>
            </div>
        </section>

        <?php if ($erro !== ''): ?>
            <div class="alert alert-error" role="alert"><?= e($erro); ?></div>
        <?php else: ?>
            <section class="painel-secao admin-card">
                <div class="secao-cabecalho">
                    <h2>Ações recentes</h2>
                    <span><?= count($registros); ?> registro(s)</span>
                </div>
                <?php if ($registros === []): ?>
                    <p class="estado-vazio">Ainda não há ações registradas.</p>
                <?php else: ?>
                    <div class="tabela-responsiva">
                        <table class="tabela-noticias">
                            <thead>
                                <tr>
                                    <th scope="col">Data e hora</th>
                                    <th scope="col">Usuário</th>
                                    <th scope="col">Ação</th>
                                    <th scope="col">Registro</th>
                                    <th scope="col">Detalhes</th>
                                    <th scope="col">IP</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($registros as $registro): ?>
                                    <tr>
                                        <td><?= e(date('d/m/Y H:i:s', strtotime($registro['criado_em']))); ?></td>
                                        <td><?= e($registro['usuario_nome'] ?? 'Conta removida/sistema'); ?></td>
                                        <td><?= e($registro['acao']); ?></td>
                                        <td>
                                            <?= e($registro['tabela'] ?? '—'); ?>
                                            <?= $registro['registro_id'] !== null ? ' #' . (int) $registro['registro_id'] : ''; ?>
                                        </td>
                                        <td><code><?= e($registro['detalhes'] ?? ''); ?></code></td>
                                        <td><?= e($registro['ip_origem'] ?? '—'); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </section>
        <?php endif; ?>
    </main>
</body>
</html>
