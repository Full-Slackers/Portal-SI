<?php

require_once __DIR__ . '/../Models/Database.php';
require_once __DIR__ . '/../Models/Comentario.php';
require_once __DIR__ . '/../helpers/auditoria.php';

final class ComentariosController
{
    public function index(): array
    {
        $dados = [
            'erros' => [],
            'mensagem' => isset($_GET['excluido']) ? 'Comentário excluído com sucesso.' : '',
            'comentarios' => [],
        ];

        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        try {
            $pdo = Database::connect();
            $comentarios = new Comentario($pdo);

            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $token = $_POST['csrf_token'] ?? '';
                $acao = $_POST['acao'] ?? '';
                $id = filter_input(INPUT_POST, 'comentario_id', FILTER_VALIDATE_INT);

                if (!is_string($token) || !hash_equals($_SESSION['csrf_token'], $token)) {
                    $dados['erros'][] = 'Sua sessão expirou ou o formulário é inválido. Atualize a página e tente novamente.';
                } elseif ($acao !== 'excluir' || $id === false || $id === null || $id < 1) {
                    $dados['erros'][] = 'Solicitação de exclusão inválida.';
                } elseif (!$comentarios->excluir($id)) {
                    $dados['erros'][] = 'O comentário não foi encontrado ou já foi excluído.';
                } else {
                    registrar_auditoria($pdo, 'comentario_excluido', 'comentarios', $id);
                    header('Location: /portal-si/app/Views/admin/comentarios.php?excluido=1');
                    exit;
                }
            }

            $dados['comentarios'] = $comentarios->listarTodos();
        } catch (Throwable $e) {
            error_log('Falha ao moderar comentários: ' . $e->getMessage());
            $dados['erros'][] = 'Não foi possível carregar ou moderar os comentários. Tente novamente mais tarde.';
        }

        return $dados;
    }
}
