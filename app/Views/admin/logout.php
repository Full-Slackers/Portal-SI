<?php

require_once __DIR__ . '/../../helpers/auth.php';
require_once __DIR__ . '/../../helpers/seguranca.php';
require_once __DIR__ . '/../../helpers/auditoria.php';
require_once __DIR__ . '/../../Models/Database.php';

require_login();

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$erro = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    $confirmacao = $_POST['confirmar'] ?? '';

    if (!is_string($token) || !hash_equals($_SESSION['csrf_token'], $token)) {
        $erro = 'Sua sessão expirou. Atualize a página e tente novamente.';
    } elseif ($confirmacao === 'nao') {
        header('Location: /portal-si/app/Views/admin/painel_adimin.php');
        exit;
    } elseif ($confirmacao === 'sim') {
        try {
            registrar_auditoria(Database::connect(), 'logout', 'usuarios', (int) $_SESSION['usuario_id']);
        } catch (PDOException $e) {
            error_log('Falha ao conectar para registrar logout na auditoria: ' . $e->getMessage());
        }

        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }

        session_destroy();
        header('Location: /portal-si/app/Views/admin/login.php?msg=logout_sucesso');
        exit;
    } else {
        $erro = 'Escolha se deseja sair do painel.';
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirmar saída — Painel administrativo</title>
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
        </nav>
    </header>

    <main class="admin-home">
        <section class="painel-secao admin-card" aria-labelledby="confirmar-saida-titulo">
            <div class="secao-cabecalho">
                <h1 id="confirmar-saida-titulo">Deseja realmente sair do painel administrativo?</h1>
            </div>

            <?php if ($erro !== ''): ?>
                <div class="alert alert-error" role="alert"><?= e($erro); ?></div>
            <?php endif; ?>

            <form method="POST" action="/portal-si/app/Views/admin/logout.php">
                <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']); ?>">
                <div class="acoes painel-acoes">
                    <button class="btn btn-destaque" type="submit" name="confirmar" value="sim">Sim, sair</button>
                    <button class="btn btn-secundario" type="submit" name="confirmar" value="nao">Não, voltar ao painel</button>
                </div>
            </form>
        </section>
    </main>
</body>
</html>
