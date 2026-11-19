<?php
require_once __DIR__ . '/../../helpers/auth.php';
require_once __DIR__ . '/../../helpers/seguranca.php';
require_once __DIR__ . '/../../Controllers/UsuariosController.php';

require_perfil(['admin']);

$formulario = (new UsuariosController())->criar();
$erros = $formulario['erros'];
$usuario = $formulario['usuario'];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastrar usuário — Portal SI</title>
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
                <h1>Cadastrar usuário</h1>
                <p>Crie uma conta para acessar o Portal SI.</p>
            </div>
        </section>

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
            <form method="POST" action="/portal-si/app/Views/admin/cadastro_usuario.php">
                <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']); ?>">

                <div class="campo">
                    <label for="nome">Nome completo:</label>
                    <input type="text" id="nome" name="nome" maxlength="120" value="<?= e($usuario['nome']); ?>" required>
                </div>

                <div class="campo">
                    <label for="email">E-mail institucional:</label>
                    <input type="email" id="email" name="email" maxlength="160" value="<?= e($usuario['email']); ?>" required>
                </div>

                <div class="campo">
                    <label for="perfil">Perfil:</label>
                    <select id="perfil" name="perfil" required>
                        <option value="admin" <?= $usuario['perfil'] === 'admin' ? 'selected' : ''; ?>>Administrador</option>
                        <option value="editor" <?= $usuario['perfil'] === 'editor' ? 'selected' : ''; ?>>Editor</option>
                        <option value="aluno" <?= $usuario['perfil'] === 'aluno' ? 'selected' : ''; ?>>Aluno</option>
                    </select>
                </div>

                <div class="campo">
                    <label for="senha">Senha (mínimo 8 caracteres):</label>
                    <input type="password" id="senha" name="senha" minlength="8" required>
                </div>

                <div class="campo">
                    <label for="confirmar_senha">Confirme a senha:</label>
                    <input type="password" id="confirmar_senha" name="confirmar_senha" minlength="8" required>
                </div>

                <div class="acoes painel-acoes">
                    <button type="submit" class="btn-destaque">Cadastrar usuário</button>
                    <a class="btn btn-secundario" href="/portal-si/app/Views/admin/usuarios.php">Cancelar</a>
                </div>
            </form>
        </section>
    </main>
</body>
</html>
