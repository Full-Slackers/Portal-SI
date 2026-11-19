<?php
require_once __DIR__ . '/../../helpers/auth.php';
require_once __DIR__ . '/../../helpers/seguranca.php';
require_once __DIR__ . '/../../Controllers/EventoController.php';

require_perfil(['admin']);

$painel = (new EventoController())->index();
$categorias = $painel['categorias'];
$eventos = $painel['eventos'];
$evento = $painel['evento'];
$erros = $painel['erros'];
$mensagem = $painel['mensagem'];
$rotulosStatus = [
    'rascunho' => 'Rascunho',
    'publicado' => 'Publicado',
    'cancelado' => 'Cancelado',
];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Eventos — Portal SI</title>
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
            <a class="ativo" href="/portal-si/app/Views/admin/eventos.php">Eventos</a>
            <a href="/portal-si/app/Views/admin/usuarios.php">Usuários</a>
            <a href="/portal-si/app/Views/admin/comentarios.php">Comentários</a>
            <a href="/portal-si/app/Views/admin/auditoria.php">Log</a>
            <a href="/portal-si/app/Views/admin/logout.php">Sair</a>
        </nav>
    </header>

    <main class="admin-home">
        <section class="admin-hero">
            <div class="admin-hero-texto">
                <span class="admin-eyebrow">PORTAL SI · AGENDA</span>
                <h1>Gerenciar eventos</h1>
                <p>Cadastre e atualize eventos. Os eventos publicados e futuros aparecem na área pública.</p>
            </div>
            <a class="btn btn-destaque" href="/portal-si/app/Views/admin/eventos.php?novo=1">+ Novo evento</a>
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

        <?php if ($painel['mostrarFormulario']): ?>
            <section class="painel-secao admin-card">
                <div class="secao-cabecalho">
                    <h2><?= $evento['id'] > 0 ? 'Editar evento' : 'Cadastrar evento'; ?></h2>
                </div>
                <form method="POST" action="/portal-si/app/Views/admin/eventos.php<?= $evento['id'] > 0 ? '?editar=' . (int) $evento['id'] : ''; ?>">
                    <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']); ?>">
                    <input type="hidden" name="acao" value="<?= $evento['id'] > 0 ? 'atualizar' : 'criar'; ?>">
                    <?php if ($evento['id'] > 0): ?>
                        <input type="hidden" name="id" value="<?= (int) $evento['id']; ?>">
                    <?php endif; ?>

                    <div class="campo">
                        <label for="titulo">Título</label>
                        <input type="text" id="titulo" name="titulo" maxlength="200" value="<?= e($evento['titulo']); ?>" required>
                    </div>
                    <div class="campo">
                        <label for="descricao">Descrição</label>
                        <textarea id="descricao" name="descricao" rows="4" maxlength="10000"><?= e($evento['descricao']); ?></textarea>
                    </div>
                    <div class="campo">
                        <label for="local">Local</label>
                        <input type="text" id="local" name="local" maxlength="160" value="<?= e($evento['local']); ?>">
                    </div>
                    <div class="admin-form-grid">
                        <div class="campo">
                            <label for="data_inicio">Início</label>
                            <input type="datetime-local" id="data_inicio" name="data_inicio" value="<?= e($evento['data_inicio']); ?>" required>
                        </div>
                        <div class="campo">
                            <label for="data_fim">Término (opcional)</label>
                            <input type="datetime-local" id="data_fim" name="data_fim" value="<?= e($evento['data_fim']); ?>">
                        </div>
                    </div>
                    <div class="admin-form-grid">
                        <div class="campo">
                            <label for="categoria_id">Categoria</label>
                            <select id="categoria_id" name="categoria_id">
                                <option value="">Sem categoria</option>
                                <?php foreach ($categorias as $categoria): ?>
                                    <option value="<?= (int) $categoria['id']; ?>" <?= (int) $evento['categoria_id'] === (int) $categoria['id'] ? 'selected' : ''; ?>>
                                        <?= e($categoria['nome']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="campo">
                            <label for="status">Status</label>
                            <select id="status" name="status" required>
                                <?php foreach ($rotulosStatus as $valor => $rotulo): ?>
                                    <option value="<?= e($valor); ?>" <?= $evento['status'] === $valor ? 'selected' : ''; ?>>
                                        <?= e($rotulo); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="acoes painel-acoes">
                        <button type="submit" class="btn-destaque">Salvar evento</button>
                        <a class="btn btn-secundario" href="/portal-si/app/Views/admin/eventos.php">Cancelar</a>
                    </div>
                </form>
            </section>
        <?php endif; ?>

        <section class="painel-secao admin-card">
            <div class="secao-cabecalho">
                <h2>Eventos cadastrados</h2>
                <span><?= count($eventos); ?> evento(s)</span>
            </div>
            <?php if ($eventos === []): ?>
                <p class="estado-vazio">Ainda não há eventos cadastrados.</p>
            <?php else: ?>
                <div class="tabela-responsiva">
                    <table class="tabela-noticias">
                        <thead>
                            <tr>
                                <th scope="col">Evento</th>
                                <th scope="col">Categoria</th>
                                <th scope="col">Data</th>
                                <th scope="col">Status</th>
                                <th scope="col">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($eventos as $item): ?>
                                <tr>
                                    <td>
                                        <strong><?= e($item['titulo']); ?></strong>
                                        <?php if (!empty($item['local'])): ?><br><span class="texto-suave"><?= e($item['local']); ?></span><?php endif; ?>
                                    </td>
                                    <td><?= e($item['categoria'] ?? 'Sem categoria'); ?></td>
                                    <td><?= e(date('d/m/Y H:i', strtotime($item['data_inicio']))); ?></td>
                                    <td><span class="etiqueta-status status-<?= e($item['status']); ?>"><?= e($rotulosStatus[$item['status']] ?? $item['status']); ?></span></td>
                                    <td>
                                        <div class="acoes-linha">
                                            <a class="btn btn-pequeno" href="/portal-si/app/Views/admin/eventos.php?editar=<?= (int) $item['id']; ?>">Editar</a>
                                            <form method="POST" action="/portal-si/app/Views/admin/eventos.php">
                                                <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']); ?>">
                                                <input type="hidden" name="acao" value="excluir">
                                                <input type="hidden" name="id" value="<?= (int) $item['id']; ?>">
                                                <button type="submit" class="btn btn-pequeno btn-delete">Excluir</button>
                                            </form>
                                        </div>
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
