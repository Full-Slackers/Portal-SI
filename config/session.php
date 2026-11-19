<?php

function iniciar_sessao_segura(): void
{
 if (session_status() === PHP_SESSION_NONE)
 {
 session_set_cookie_params(['lifetime' => 0, // Expira ao fechar o navegador
 'path' => '/',
 'domain' => '',
 'secure' => false, // true em ambientes sob HTTPS/produção
 'httponly' => true, // Inibe acesso JavaScript ao cookie
 'samesite' => 'Lax' // Proteção contra ataques CSRF
 ]);
 session_start();
 }
}

function validar_tempo_inatividade(int $limiteSegundos = 1800): void
{
 if (isset($_SESSION['ultimo_acesso']) && (time() - $_SESSION['ultimo_acesso'] > $limiteSegundos))
 { session_unset();
 session_destroy();
 header('Location: /portal-si/app/Views/admin/login.php?erro=expirado');
 exit;
 }
 $_SESSION['ultimo_acesso'] = time();
}

?>