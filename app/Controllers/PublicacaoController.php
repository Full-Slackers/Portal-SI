<?php

require_once __DIR__ . '/../Models/Database.php';
require_once __DIR__ . '/../Models/Publicacao.php';
require_once __DIR__ . '/../helpers/midia.php';

final class PublicacaoController
{
    public function exibir(int $id): array
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        $dados = [
            'publicacao' => null,
            'categorias' => [],
            'videoYoutubeId' => null,
            'tags' => [],
            'midias' => [],
            'comentarios' => [],
            'totalComentarios' => 0,
            'relacionadas' => [],
            'erros' => [],
            'nomeComentario' => '',
            'textoComentario' => '',
            'mensagem' => isset($_GET['comentario']) && $_GET['comentario'] === 'enviado'
                ? 'Seu comentário foi publicado. Obrigado por participar!'
                : '',
        ];

        try {
            $publicacaoModel = new Publicacao(Database::connect());
            $dados['categorias'] = $publicacaoModel->listarCategoriasAtivas();
            $dados['publicacao'] = $publicacaoModel->buscarPublicada($id);

            if ($dados['publicacao'] === null) {
                return $dados;
            }
            $dados['videoYoutubeId'] = obterIdYoutube((string) ($dados['publicacao']['link_youtube'] ?? ''));
            $dados['tags'] = $publicacaoModel->listarTags($id);
            $dados['midias'] = $publicacaoModel->listarMidias($id);

            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $nome = $_POST['nome'] ?? '';
                $comentario = $_POST['comentario'] ?? '';
                $token = $_POST['csrf_token'] ?? '';
                $dados['nomeComentario'] = is_string($nome) ? trim($nome) : '';
                $dados['textoComentario'] = is_string($comentario) ? trim($comentario) : '';

                if (
                    !is_string($token)
                    || !hash_equals($_SESSION['csrf_token'], $token)
                ) {
                    $dados['erros'][] = 'Sua sessão expirou. Atualize a página e tente novamente.';
                }

                if ($dados['nomeComentario'] === '' || mb_strlen($dados['nomeComentario']) > 120) {
                    $dados['erros'][] = 'Informe seu nome (até 120 caracteres).';
                }

                if ($dados['textoComentario'] === '' || mb_strlen($dados['textoComentario']) > 2000) {
                    $dados['erros'][] = 'Escreva um comentário com até 2.000 caracteres.';
                }

                if ($dados['erros'] === []) {
                    $publicacaoModel->criarComentario(
                        $id,
                        $dados['nomeComentario'],
                        $dados['textoComentario']
                    );

                    header('Location: /portal-si/public/conteudo.php?id=' . $id . '&comentario=enviado#comentarios');
                    exit;
                }
            }

            $dados['comentarios'] = $publicacaoModel->listarComentarios($id);
            $dados['totalComentarios'] = $publicacaoModel->contarComentarios($id);
            $dados['relacionadas'] = $publicacaoModel->listarRelacionadas(
                (int) $dados['publicacao']['categoria_id'],
                $id
            );
        } catch (PDOException $e) {
            error_log('Falha ao carregar ou comentar publicação: ' . $e->getMessage());
            $dados['erros'][] = 'Não foi possível carregar esta publicação agora. Tente novamente mais tarde.';
        }

        return $dados;
    }
}
