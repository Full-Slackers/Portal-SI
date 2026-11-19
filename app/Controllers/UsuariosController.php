<?php

require_once __DIR__ . '/../Models/Database.php';
require_once __DIR__ . '/../Models/Usuario.php';
require_once __DIR__ . '/../helpers/auditoria.php';

final class UsuariosController
{
    public function criar(): array
    {
        $dados = [
            'erros' => [],
            'usuario' => [
                'nome' => '',
                'email' => '',
                'perfil' => 'aluno',
            ],
        ];

        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return $dados;
        }

        $nome = $_POST['nome'] ?? '';
        $email = $_POST['email'] ?? '';
        $perfil = $_POST['perfil'] ?? '';
        $senha = $_POST['senha'] ?? '';
        $confirmarSenha = $_POST['confirmar_senha'] ?? '';
        $token = $_POST['csrf_token'] ?? '';

        $dados['usuario'] = [
            'nome' => is_string($nome) ? trim($nome) : '',
            'email' => is_string($email) ? strtolower(trim($email)) : '',
            'perfil' => is_string($perfil) ? $perfil : '',
        ];

        if (!is_string($token) || !hash_equals($_SESSION['csrf_token'], $token)) {
            $dados['erros'][] = 'Sua sessão expirou ou o formulário é inválido. Atualize a página e tente novamente.';
            return $dados;
        }

        if ($dados['usuario']['nome'] === '' || strlen($dados['usuario']['nome']) > 120) {
            $dados['erros'][] = 'Informe um nome com até 120 caracteres.';
        }

        if (
            $dados['usuario']['email'] === ''
            || strlen($dados['usuario']['email']) > 160
            || !filter_var($dados['usuario']['email'], FILTER_VALIDATE_EMAIL)
        ) {
            $dados['erros'][] = 'Informe um e-mail válido com até 160 caracteres.';
        }

        if (!in_array($dados['usuario']['perfil'], ['admin', 'editor', 'aluno'], true)) {
            $dados['erros'][] = 'Selecione um perfil válido.';
        }

        if (!is_string($senha) || strlen($senha) < 8) {
            $dados['erros'][] = 'A senha deve ter ao menos 8 caracteres.';
        }

        if (!is_string($confirmarSenha) || $senha !== $confirmarSenha) {
            $dados['erros'][] = 'As senhas informadas não coincidem.';
        }

        if ($dados['erros'] !== []) {
            return $dados;
        }

        try {
            $pdo = Database::connect();
            $usuarios = new Usuario($pdo);
            $usuarioId = $usuarios->criar(
                $dados['usuario']['nome'],
                $dados['usuario']['email'],
                password_hash($senha, PASSWORD_DEFAULT),
                $dados['usuario']['perfil']
            );
            registrar_auditoria($pdo, 'usuario_criado', 'usuarios', $usuarioId, [
                'perfil' => $dados['usuario']['perfil'],
            ]);

            header('Location: /portal-si/app/Views/admin/usuarios.php?cadastrado=1');
            exit;
        } catch (PDOException $e) {
            if ($e->getCode() === '23505') {
                $dados['erros'][] = 'O e-mail informado já está cadastrado no sistema.';
            } else {
                error_log('Falha ao criar usuário pelo painel: ' . $e->getMessage());
                $dados['erros'][] = 'Não foi possível cadastrar o usuário. Tente novamente mais tarde.';
            }
        } catch (Throwable $e) {
            error_log('Falha inesperada ao criar usuário pelo painel: ' . $e->getMessage());
            $dados['erros'][] = 'Ocorreu um erro inesperado ao cadastrar o usuário.';
        }

        return $dados;
    }

    public function index(): array
    {
        $dados = [
            'erros' => [],
            'mensagem' => '',
            'usuarios' => [],
        ];

        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        try {
            $pdo = Database::connect();
            $usuarios = new Usuario($pdo);

            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $token = $_POST['csrf_token'] ?? '';
                $acao = is_string($_POST['acao'] ?? null) ? $_POST['acao'] : '';
                if (!is_string($token) || !hash_equals($_SESSION['csrf_token'], $token)) {
                    $dados['erros'][] = 'Sua sessão expirou ou o formulário é inválido. Atualize a página e tente novamente.';
                } elseif ($acao !== 'excluir') {
                    $dados['erros'][] = 'Ação inválida.';
                } else {
                    $usuarioId = filter_input(INPUT_POST, 'usuario_id', FILTER_VALIDATE_INT) ?: 0;
                    if ($usuarioId < 1) {
                        $dados['erros'][] = 'Usuário inválido para exclusão.';
                    } elseif ($usuarioId === (int) $_SESSION['usuario_id']) {
                        $dados['erros'][] = 'Não é possível excluir o usuário que está conectado.';
                    } else {
                        $usuarioAlvo = $usuarios->buscarPorId($usuarioId);
                        if (!$usuarioAlvo) {
                            $dados['erros'][] = 'O usuário não foi encontrado.';
                        } elseif (
                            $usuarioAlvo['perfil'] === 'admin'
                            && $usuarioAlvo['status'] === 'ativo'
                            && $usuarios->contarAdministradoresAtivos() <= 1
                        ) {
                            $dados['erros'][] = 'Não é possível excluir o último administrador ativo do sistema.';
                        } else {
                            try {
                                if (!$usuarios->excluir($usuarioId)) {
                                    $dados['erros'][] = 'O usuário não foi encontrado.';
                                } else {
                                    registrar_auditoria($pdo, 'usuario_excluido', 'usuarios', $usuarioId);
                                    header('Location: /portal-si/app/Views/admin/usuarios.php?excluido=1');
                                    exit;
                                }
                            } catch (PDOException $e) {
                                if ($e->getCode() === '23503') {
                                    $dados['erros'][] = 'Não é possível excluir este usuário porque ele possui notícias ou eventos vinculados.';
                                } else {
                                    throw $e;
                                }
                            }
                        }
                    }
                }
            } elseif (isset($_GET['excluido'])) {
                $dados['mensagem'] = 'Usuário excluído com sucesso.';
            } elseif (isset($_GET['cadastrado'])) {
                $dados['mensagem'] = 'Usuário cadastrado com sucesso.';
            }

            $dados['usuarios'] = $usuarios->listarTodos();
        } catch (PDOException $e) {
            error_log('Falha ao gerenciar usuários: ' . $e->getMessage());
            $dados['mensagem'] = 'Não foi possível carregar os usuários. Verifique a conexão e tente novamente.';
        } catch (Throwable $e) {
            error_log('Falha inesperada ao gerenciar usuários: ' . $e->getMessage());
            $dados['mensagem'] = 'Ocorreu um erro inesperado ao carregar os usuários.';
        }

        return $dados;
    }
}
