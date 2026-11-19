<?php
session_start();
require_once __DIR__ . '/../../helpers/auditoria.php';

$erro = '';
$mensagemSucesso = '';
$dados = [
    'email' => ''
];

if (isset($_GET['erro']) && is_string($_GET['erro'])) {
    $mensagensErro = [
        'restrito' => 'Entre no sistema para acessar essa página.',
        'permissao_negada' => 'Seu perfil não tem permissão para acessar essa página.',
        'expirado' => 'Sua sessão expirou. Entre novamente.',
    ];
    $erro = $mensagensErro[$_GET['erro']] ?? '';
}

if (isset($_GET['msg']) && $_GET['msg'] === 'logout_sucesso') {
    $mensagemSucesso = 'Você saiu do sistema com sucesso.';
}

$host = 'localhost';
$dbname = 'portal_si';
$username = 'postgres';
$password = 'postgres';
$port = 5432;

try {
    $pdo = new PDO(
        "pgsql:host={$host};port={$port};dbname={$dbname};user={$username};password={$password}",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (PDOException $e) {
    $erro = 'Não foi possível conectar ao sistema no momento. Tente novamente mais tarde.';
    $pdo = null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $pdo) {
    $dados['email'] = trim(strtolower($_POST['email'] ?? ''));
    $senha = $_POST['senha'] ?? '';

    if ($dados['email'] === '' || $senha === '') {
        $erro = 'E-mail e senha são obrigatórios.';
    } else {
        try {
            $sql = 'SELECT id, nome, email, senha_hash, perfil, status
                    FROM usuarios
                    WHERE email = :email
                      AND perfil = :perfil
                      AND status = :status
                    LIMIT 1';
            $stmt = $pdo->prepare($sql);
            $stmt->bindValue(':email', $dados['email'], PDO::PARAM_STR);
            $stmt->bindValue(':perfil', 'admin', PDO::PARAM_STR);
            $stmt->bindValue(':status', 'ativo', PDO::PARAM_STR);
            $stmt->execute();
            $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($usuario && password_verify($senha, $usuario['senha_hash'])) {
                session_regenerate_id(true);

                $_SESSION['usuario_id'] = (int) $usuario['id'];
                $_SESSION['usuario_nome'] = $usuario['nome'];
                $_SESSION['usuario_email'] = $usuario['email'];
                $_SESSION['usuario_perfil'] = $usuario['perfil'];
                $_SESSION['ultimo_acesso'] = time();
                registrar_auditoria($pdo, 'login_sucesso', 'usuarios', (int) $usuario['id']);

                header('Location: /portal-si/app/Views/admin/painel_adimin.php');
                exit;
            }

            $erro = 'Acesso permitido apenas para usuários administradores ativos.';
        } catch (PDOException $e) {
            $erro = 'Erro ao tentar autenticar. Tente novamente mais tarde.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Autenticação — Portal SI</title>
    <link rel="stylesheet" href="/portal-si/public/assets/css/estilo.css">
</head>
<body>
    <div class="topo">
        <div class="marca">
            <span class="tarja"></span>
            Painel administrativo
        </div>
    </div>

    <div class="container">
        <h1>Acesso ao Painel Administrativo</h1>

        <?php if ($mensagemSucesso !== ''): ?>
            <div class="alert alert-success"><?= htmlspecialchars($mensagemSucesso, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>

        <?php if ($erro !== ''): ?>
            <div class="alert alert-error"><?= htmlspecialchars($erro, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>

        <form method="POST" action="/portal-si/app/Views/admin/login.php">
            <div class="campo">
                <label for="email">E-mail:</label>
                <input type="email" id="email" name="email" value="<?= htmlspecialchars($dados['email'], ENT_QUOTES, 'UTF-8'); ?>" required>
            </div>

            <div class="campo">
                <label for="senha">Senha:</label>
                <input type="password" id="senha" name="senha" required>
            </div>

            <button type="submit">Entrar no Sistema</button>
        </form>

        <div class="acoes">
            <a class="btn btn-secundario" href="/portal-si/public/index.php">Voltar para Home</a>
        </div>

    </div>
</body>
</html>
