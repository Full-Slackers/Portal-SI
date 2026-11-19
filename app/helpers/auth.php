<?php

// Garante que a sessao esteja disponivel antes de consultar os dados do usuario.
if (session_status() === PHP_SESSION_NONE) {
	session_start();
}

/**
 * Verifica se existe uma sessao valida de usuario autenticado.
 */
function is_logged_in(): bool
{
	return isset($_SESSION['usuario_id']) && !empty($_SESSION['usuario_id']);
}

/**
 * Bloqueia o acesso a scripts restritos e redireciona para o login.
 */
function require_login(): void
{
	if (!is_logged_in()) {
		header('Location: /portal-si/app/Views/admin/login.php?erro=restrito');
		exit;
	}
}

/**
 * Garante que o usuario tenha um dos perfis permitidos.
 *
 * @param array<int, string> $perfisPermitidos
 */
function require_perfil(array $perfisPermitidos): void
{
	require_login();

	if (!in_array($_SESSION['usuario_perfil'] ?? null, $perfisPermitidos, true)) {
		header('Location: /portal-si/app/Views/admin/login.php?erro=permissao_negada');
		exit;
	}
}
