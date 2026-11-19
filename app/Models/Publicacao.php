<?php

final class Publicacao
{
    public function __construct(private PDO $pdo)
    {
    }

    public function listarCategoriasAtivas(): array
    {
        $stmt = $this->pdo->query(
            'SELECT id, nome, slug
             FROM categorias
             WHERE ativa = TRUE AND slug <> \'home\'
             ORDER BY ordem ASC, nome ASC'
        );

        return $stmt->fetchAll();
    }

    public function buscarPublicada(int $id): ?array
    {
        $stmt = $this->pdo->prepare(
            "SELECT c.id, c.titulo, c.resumo, c.corpo, c.imagem_capa, c.link_youtube,
                    COALESCE(c.publicado_em, c.criado_em) AS publicado_em,
                    c.categoria_id, cat.nome AS categoria,
                    cat.slug AS categoria_slug, u.nome AS autor
             FROM conteudos c
             INNER JOIN categorias cat ON cat.id = c.categoria_id
             INNER JOIN usuarios u ON u.id = c.autor_id
             WHERE c.id = :id AND c.status = 'publicado' AND cat.ativa = TRUE
             LIMIT 1"
        );
        $stmt->execute([':id' => $id]);
        $publicacao = $stmt->fetch();

        return $publicacao ?: null;
    }

    public function listarTags(int $conteudoId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT t.id, t.nome, t.slug
             FROM tags t
             INNER JOIN conteudo_tags ct ON ct.tag_id = t.id
             WHERE ct.conteudo_id = :conteudo_id
             ORDER BY t.nome ASC'
        );
        $stmt->execute([':conteudo_id' => $conteudoId]);

        return $stmt->fetchAll();
    }

    public function listarMidias(int $conteudoId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT nome_arquivo, caminho, tipo, tamanho_kb
             FROM midias
             WHERE conteudo_id = :conteudo_id
             ORDER BY criado_em ASC, id ASC'
        );
        $stmt->execute([':conteudo_id' => $conteudoId]);

        return $stmt->fetchAll();
    }

    public function listarComentarios(int $conteudoId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT nome, comentario, criado_em
             FROM comentarios
             WHERE conteudo_id = :conteudo_id
             ORDER BY criado_em DESC, id DESC
             LIMIT 50'
        );
        $stmt->execute([':conteudo_id' => $conteudoId]);

        return $stmt->fetchAll();
    }

    public function contarComentarios(int $conteudoId): int
    {
        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*) FROM comentarios WHERE conteudo_id = :conteudo_id'
        );
        $stmt->execute([':conteudo_id' => $conteudoId]);

        return (int) $stmt->fetchColumn();
    }

    public function criarComentario(int $conteudoId, string $nome, string $comentario): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO comentarios (conteudo_id, nome, comentario)
             VALUES (:conteudo_id, :nome, :comentario)'
        );
        $stmt->execute([
            ':conteudo_id' => $conteudoId,
            ':nome' => $nome,
            ':comentario' => $comentario,
        ]);
    }

    public function listarRelacionadas(int $categoriaId, int $ignorarId): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT id, titulo, resumo
             FROM conteudos
             WHERE categoria_id = :categoria_id
               AND id <> :id
               AND status = 'publicado'
             ORDER BY destaque DESC, publicado_em DESC, id DESC
             LIMIT 3"
        );
        $stmt->execute([
            ':categoria_id' => $categoriaId,
            ':id' => $ignorarId,
        ]);

        return $stmt->fetchAll();
    }
}
