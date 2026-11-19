<?php

require_once __DIR__ . '/../Models/Database.php';

final class PublicacoesController
{
    private const ITENS_POR_PAGINA = 9;

    public function index(int $pagina): array
    {
        $dados = [
            'categorias' => [],
            'publicacoes' => [],
            'pagina' => max(1, $pagina),
            'totalPaginas' => 1,
            'erro' => '',
        ];

        try {
            $pdo = Database::connect();

            $categorias = $pdo->query(
                'SELECT id, nome, slug, descricao, icone, ordem
                 FROM categorias
                 WHERE ativa = TRUE
                 ORDER BY ordem ASC, nome ASC'
            );
            $dados['categorias'] = $categorias->fetchAll();

            $total = (int) $pdo->query(
                "SELECT COUNT(*)
                 FROM conteudos c
                 INNER JOIN categorias cat ON cat.id = c.categoria_id
                 WHERE c.status = 'publicado' AND cat.ativa = TRUE"
            )->fetchColumn();

            $dados['totalPaginas'] = max(1, (int) ceil($total / self::ITENS_POR_PAGINA));
            $dados['pagina'] = min($dados['pagina'], $dados['totalPaginas']);
            $offset = ($dados['pagina'] - 1) * self::ITENS_POR_PAGINA;

            $stmt = $pdo->prepare(
                "SELECT c.id, c.titulo, c.slug, c.resumo, c.imagem_capa,
                        COALESCE(c.publicado_em, c.criado_em) AS publicado_em,
                        c.destaque, cat.nome AS categoria,
                        cat.slug AS categoria_slug, u.nome AS autor,
                        (SELECT STRING_AGG(t.nome, ', ' ORDER BY t.nome)
                         FROM conteudo_tags ct
                         INNER JOIN tags t ON t.id = ct.tag_id
                         WHERE ct.conteudo_id = c.id) AS tags
                 FROM conteudos c
                 INNER JOIN categorias cat ON cat.id = c.categoria_id
                 INNER JOIN usuarios u ON u.id = c.autor_id
                 WHERE c.status = 'publicado' AND cat.ativa = TRUE
                 ORDER BY c.destaque DESC, COALESCE(c.publicado_em, c.criado_em) DESC, c.id DESC
                 LIMIT :limite OFFSET :offset"
            );
            $stmt->bindValue(':limite', self::ITENS_POR_PAGINA, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            $dados['publicacoes'] = $stmt->fetchAll();
        } catch (Throwable $e) {
            error_log('Erro ao carregar todas as publicações: ' . $e->getMessage());
            $dados['erro'] = 'Não foi possível carregar as publicações no momento. Tente novamente mais tarde.';
        }

        return $dados;
    }
}
