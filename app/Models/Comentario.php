<?php

final class Comentario
{
    public function __construct(private PDO $pdo)
    {
    }

    public function listarTodos(): array
    {
        $stmt = $this->pdo->query(
            'SELECT cm.id, cm.nome, cm.comentario, cm.criado_em,
                    c.id AS conteudo_id, c.titulo AS conteudo_titulo
             FROM comentarios cm
             INNER JOIN conteudos c ON c.id = cm.conteudo_id
             ORDER BY cm.criado_em DESC, cm.id DESC'
        );

        return $stmt->fetchAll();
    }

    public function excluir(int $id): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM comentarios WHERE id = :id');
        $stmt->execute([':id' => $id]);

        return $stmt->rowCount() > 0;
    }
}
