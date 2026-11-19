<?php

require_once __DIR__ . '/../Models/Database.php';
require_once __DIR__ . '/../Models/Evento.php';

final class HomeController
{
    public function index(): array
    {
        $dados = [
            'categorias' => [],
            'publicacoes' => [],
            'eventos' => [],
            'erro' => '',
        ];

        try {
            $pdo = Database::connect();

            $categorias = $pdo->query(
                "SELECT cat.id, cat.nome, cat.slug, cat.descricao, cat.icone, cat.ordem,
                        COUNT(c.id) AS total_publicacoes
                 FROM categorias cat
                 LEFT JOIN conteudos c
                   ON c.categoria_id = cat.id AND c.status = 'publicado'
                 WHERE cat.ativa = TRUE AND cat.slug <> 'home'
                 GROUP BY cat.id
                 ORDER BY cat.ordem ASC, cat.nome ASC"
            );
            $dados['categorias'] = $categorias->fetchAll();

            $publicacoes = $pdo->query(
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
                 WHERE c.status = 'publicado'
                   AND cat.ativa = TRUE
                 ORDER BY COALESCE(c.publicado_em, c.criado_em) DESC, c.id DESC
                 LIMIT 6"
            );

            $dados['publicacoes'] = $publicacoes->fetchAll();
            $dados['eventos'] = (new Evento($pdo))->listarPublicados(null, 4);

        } catch (Throwable $e) {
            error_log('Erro ao carregar a home do portal: ' . $e->getMessage());
            $dados['erro'] = 'Não foi possível carregar as informações do portal no momento. Tente novamente mais tarde.';
        }

        return $dados;
    }
}
