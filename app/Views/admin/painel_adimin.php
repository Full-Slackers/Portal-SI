<?php
require_once __DIR__ . '/../../helpers/auth.php';
require_once __DIR__ . '/../../helpers/seguranca.php';
require_once __DIR__ . '/../../Controllers/ConteudoController.php';

require_perfil(['admin']);

$painel = (new ConteudoController())->index();
$erros = $painel['erros'];
$mensagem = $painel['mensagem'];
$tipoMensagem = $painel['tipoMensagem'];
$noticia = $painel['noticia'];
$categorias = $painel['categorias'];
$noticias = $painel['noticias'];
$pagina = $painel['pagina'];
$totalPaginas = $painel['totalPaginas'];
$totalNoticias = $painel['totalNoticias'];
$estatisticas = $painel['estatisticas'];
$rotulosStatus = [
    'rascunho' => 'Rascunho',
    'revisao' => 'Em revisão',
    'publicado' => 'Publicado',
    'arquivado' => 'Arquivado',
];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel Administrativo — Portal SI</title>
    <link rel="stylesheet" href="/portal-si/public/assets/css/estilo.css?v=admin-nav-ativo-20261110">
</head>
<body class="admin-page">
    <header class="admin-topo">
        <a class="marca" href="/portal-si/app/Views/admin/painel_adimin.php">
            <span class="tarja"></span>
            Painel administrativo
        </a>
        <nav class="admin-navegacao" aria-label="Navegação administrativa">
            <a class="ativo" href="/portal-si/app/Views/admin/painel_adimin.php">Publicações</a>
            <a href="/portal-si/app/Views/admin/eventos.php">Eventos</a>
            <a href="/portal-si/app/Views/admin/usuarios.php">Usuários</a>
            <a href="/portal-si/app/Views/admin/comentarios.php">Comentários</a>
            <a href="/portal-si/app/Views/admin/auditoria.php">Log</a>
            <a href="/portal-si/app/Views/admin/logout.php">Sair</a>
        </nav>
    </header>

    <main class="admin-home">
        <section class="admin-hero">
            <div class="admin-hero-texto">
                <span class="admin-eyebrow">PORTAL SI · ADMINISTRAÇÃO</span>
                <h1>Painel Administrativo</h1>
                <p>Olá, <?= e($_SESSION['usuario_nome'] ?? 'Administrador'); ?>. Gerencie as notícias e acompanhe o conteúdo do portal.</p>
            </div>
            <a class="btn btn-destaque" href="/portal-si/app/Views/admin/painel_adimin.php?novo=1">+ Nova notícia</a>
        </section>

        <?php if ($mensagem !== ''): ?>
            <div class="alert alert-<?= e($tipoMensagem); ?>"><?= e($mensagem); ?></div>
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

        <section class="admin-resumo" aria-label="Resumo das notícias">
            <article class="resumo-card">
                <span>Total de notícias</span>
                <strong><?= $estatisticas['total']; ?></strong>
                <small>Todos os registros do portal</small>
            </article>
            <article class="resumo-card">
                <span>Publicadas</span>
                <strong><?= $estatisticas['publicados']; ?></strong>
                <small>Visíveis para os leitores</small>
            </article>
            <article class="resumo-card">
                <span>Em andamento</span>
                <strong><?= $estatisticas['pendentes']; ?></strong>
                <small>Rascunhos e revisões</small>
            </article>
        </section>

        <?php if ($painel['mostrarFormulario']): ?>
            <section class="painel-secao admin-card" id="formulario-noticia">
                <div class="secao-cabecalho">
                    <h2><?= $noticia['id'] > 0 ? 'Editar notícia' : 'Cadastrar notícia'; ?></h2>
                </div>
                <?php if ($categorias === []): ?>
                    <div class="alert alert-error">Nenhuma categoria encontrada. Cadastre categorias no banco para habilitar o cadastro de notícias.</div>
                <?php else: ?>
                    <form method="POST" enctype="multipart/form-data" action="/portal-si/app/Views/admin/painel_adimin.php<?= $noticia['id'] > 0 ? '?editar=' . (int) $noticia['id'] : ''; ?>">
                        <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']); ?>">
                        <input type="hidden" name="acao" value="<?= $noticia['id'] > 0 ? 'atualizar' : 'criar'; ?>">
                        <?php if ($noticia['id'] > 0): ?>
                            <input type="hidden" name="id" value="<?= (int) $noticia['id']; ?>">
                        <?php endif; ?>

                        <div class="campo">
                            <label for="titulo">Título:</label>
                            <input type="text" id="titulo" name="titulo" maxlength="200" value="<?= e($noticia['titulo']); ?>" required>
                        </div>

                        <div class="campo">
                            <label for="categoria_id">Categoria:</label>
                            <select id="categoria_id" name="categoria_id" required>
                                <option value="">Selecione uma categoria</option>
                                <?php foreach ($categorias as $categoria): ?>
                                    <option value="<?= (int) $categoria['id']; ?>" <?= (int) $noticia['categoria_id'] === (int) $categoria['id'] ? 'selected' : ''; ?>>
                                        <?= e($categoria['nome']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="campo">
                            <label for="resumo">Resumo:</label>
                            <textarea id="resumo" name="resumo" rows="3" required><?= e($noticia['resumo']); ?></textarea>
                        </div>

                        <div class="campo">
                            <label for="corpo">Conteúdo:</label>
                            <textarea id="corpo" name="corpo" rows="8" required><?= e($noticia['corpo']); ?></textarea>
                        </div>

                        <div class="campo">
                            <label for="imagem_capa">Imagem de capa (opcional):</label>
                            <input type="text" id="imagem_capa" name="imagem_capa" maxlength="255" value="<?= e($noticia['imagem_capa'] ?? ''); ?>" placeholder="URL HTTPS ou caminho local, por exemplo: /portal-si/public/assets/images/capa.jpg">
                            <small class="texto-suave">Use uma imagem já disponível no site; este campo não envia arquivos.</small>
                        </div>

                        <div class="campo">
                            <label for="link_youtube">Vídeo do YouTube (opcional):</label>
                            <input type="url" id="link_youtube" name="link_youtube" maxlength="255" value="<?= e($noticia['link_youtube'] ?? ''); ?>" placeholder="https://www.youtube.com/watch?v=..." inputmode="url">
                        </div>

                        <div class="campo">
                            <label for="tags">Tags (opcional):</label>
                            <input type="text" id="tags" name="tags" maxlength="700" value="<?= e($noticia['tags'] ?? ''); ?>" placeholder="Tecnologia, Extensão, Comunidade">
                            <small class="texto-suave">Separe as tags por vírgula. Até 10 tags; as novas são cadastradas automaticamente.</small>
                        </div>

                        <div class="campo">
                            <label for="midias">Arquivos da publicação (opcional):</label>
                            <input type="file" id="midias" name="midias[]" accept="image/jpeg,image/png,image/gif,image/webp,application/pdf" multiple>
                            <small class="texto-suave">Até 5 imagens ou PDFs, com no máximo 5 MB por arquivo. Os arquivos adicionados serão exibidos no detalhe público.</small>
                        </div>
                        <?php if (!empty($painel['midiasExistentes'])): ?>
                            <div class="campo">
                                <span class="texto-suave">Arquivos já associados:</span>
                                <ul class="lista-midias-admin">
                                    <?php foreach ($painel['midiasExistentes'] as $midia): ?>
                                        <li><a href="<?= e($midia['caminho']); ?>" target="_blank" rel="noopener noreferrer"><?= e($midia['nome_arquivo']); ?></a></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>

                        <div class="campo">
                            <label for="status">Status:</label>
                            <select id="status" name="status" required>
                                <?php foreach ($rotulosStatus as $valorStatus => $rotuloStatus): ?>
                                    <option value="<?= e($valorStatus); ?>" <?= $noticia['status'] === $valorStatus ? 'selected' : ''; ?>>
                                        <?= e($rotuloStatus); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <label class="campo-destaque">
                            <input type="checkbox" name="destaque" value="1" <?= $noticia['destaque'] ? 'checked' : ''; ?>>
                            Marcar como notícia em destaque
                        </label>

                        <div class="acoes painel-acoes">
                            <button type="submit" class="btn-destaque">Salvar notícia</button>
                            <a class="btn btn-secundario" href="/portal-si/app/Views/admin/painel_adimin.php">Cancelar</a>
                        </div>
                    </form>
                <?php endif; ?>
            </section>
        <?php endif; ?>

        <section class="painel-secao admin-card">
            <div class="secao-cabecalho">
                <h2>Notícias cadastradas</h2>
                <span><?= $totalNoticias; ?> registro(s)</span>
            </div>

            <?php if ($noticias === []): ?>
                <p class="estado-vazio">Nenhuma notícia cadastrada. Use “Nova notícia” para criar a primeira.</p>
            <?php else: ?>
                <div class="tabela-responsiva">
                    <table class="tabela-noticias">
                        <thead>
                            <tr>
                                <th scope="col">Título</th>
                                <th scope="col">Categoria</th>
                                <th scope="col">Status</th>
                                <th scope="col">Criada em</th>
                                <th scope="col">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($noticias as $item): ?>
                                <tr>
                                    <td>
                                        <strong><?= e($item['titulo']); ?></strong>
                                        <?php if ($item['destaque']): ?><span class="etiqueta-destaque">Destaque</span><?php endif; ?>
                                    </td>
                                    <td><?= e($item['categoria']); ?></td>
                                    <td><span class="etiqueta-status status-<?= e($item['status']); ?>"><?= e($rotulosStatus[$item['status']] ?? $item['status']); ?></span></td>
                                    <td><?= e(date('d/m/Y', strtotime($item['criado_em']))); ?></td>
                                    <td>
                                        <div class="acoes-linha">
                                            <a class="btn btn-pequeno" href="/portal-si/app/Views/admin/painel_adimin.php?editar=<?= (int) $item['id']; ?>">Editar</a>
                                            <form method="POST" action="/portal-si/app/Views/admin/painel_adimin.php">
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

                <?php if ($totalPaginas > 1): ?>
                    <nav class="paginacao" aria-label="Paginação das notícias">
                        <?php if ($pagina > 1): ?>
                            <a class="btn btn-secundario" href="/portal-si/app/Views/admin/painel_adimin.php?pagina=<?= $pagina - 1; ?>">Anterior</a>
                        <?php endif; ?>
                        <span>Página <?= $pagina; ?> de <?= $totalPaginas; ?></span>
                        <?php if ($pagina < $totalPaginas): ?>
                            <a class="btn btn-secundario" href="/portal-si/app/Views/admin/painel_adimin.php?pagina=<?= $pagina + 1; ?>">Próxima</a>
                        <?php endif; ?>
                    </nav>
                <?php endif; ?>
            <?php endif; ?>
        </section>

    </main>
</body>
</html>
