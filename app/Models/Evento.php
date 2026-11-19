<?php

final class Evento
{
    public function __construct(private PDO $pdo)
    {
    }

    public function listarCategorias(): array
    {
        return $this->pdo->query(
            'SELECT id, nome FROM categorias WHERE ativa = TRUE ORDER BY ordem ASC, nome ASC'
        )->fetchAll();
    }

    public function listarTodos(): array
    {
        return $this->pdo->query(
            'SELECT e.id, e.titulo, e.local, e.data_inicio, e.data_fim, e.status,
                    c.nome AS categoria
             FROM eventos e
             LEFT JOIN categorias c ON c.id = e.categoria_id
             ORDER BY e.data_inicio DESC, e.id DESC
             LIMIT 100'
        )->fetchAll();
    }

    public function buscarPorId(int $id): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, titulo, descricao, local, data_inicio, data_fim,
                    categoria_id, status
             FROM eventos
             WHERE id = :id'
        );
        $stmt->execute([':id' => $id]);
        $evento = $stmt->fetch();

        return $evento ?: null;
    }

    public function listarPublicados(?int $categoriaId = null, int $limite = 4): array
    {
        $sql = "SELECT e.id, e.titulo, e.descricao, e.local, e.data_inicio, e.data_fim,
                       c.nome AS categoria, c.slug AS categoria_slug
                FROM eventos e
                LEFT JOIN categorias c ON c.id = e.categoria_id
                WHERE e.status = 'publicado'
                  AND COALESCE(e.data_fim, e.data_inicio) >= CURRENT_TIMESTAMP";
        if ($categoriaId !== null) {
            $sql .= ' AND e.categoria_id = :categoria_id';
        }
        $sql .= ' ORDER BY e.data_inicio ASC, e.id ASC LIMIT :limite';

        $stmt = $this->pdo->prepare($sql);
        if ($categoriaId !== null) {
            $stmt->bindValue(':categoria_id', $categoriaId, PDO::PARAM_INT);
        }
        $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function criar(array $dados, int $autorId): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO eventos
                (titulo, descricao, local, data_inicio, data_fim, categoria_id, autor_id, status)
             VALUES
                (:titulo, :descricao, :local, :data_inicio, :data_fim, :categoria_id, :autor_id, :status)
             RETURNING id'
        );
        $this->salvar($stmt, $dados);
        $stmt->bindValue(':autor_id', $autorId, PDO::PARAM_INT);
        $stmt->execute();

        return (int) $stmt->fetchColumn();
    }

    public function atualizar(int $id, array $dados): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE eventos
             SET titulo = :titulo, descricao = :descricao, local = :local,
                 data_inicio = :data_inicio, data_fim = :data_fim,
                 categoria_id = :categoria_id, status = :status
             WHERE id = :id'
        );
        $this->salvar($stmt, $dados);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            return true;
        }

        return $this->buscarPorId($id) !== null;
    }

    public function excluir(int $id): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM eventos WHERE id = :id');
        $stmt->execute([':id' => $id]);

        return $stmt->rowCount() > 0;
    }

    private function salvar(PDOStatement $stmt, array $dados): void
    {
        $stmt->bindValue(':titulo', $dados['titulo'], PDO::PARAM_STR);
        $stmt->bindValue(':descricao', $dados['descricao'] !== '' ? $dados['descricao'] : null, $dados['descricao'] !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $stmt->bindValue(':local', $dados['local'] !== '' ? $dados['local'] : null, $dados['local'] !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $stmt->bindValue(':data_inicio', $dados['data_inicio'], PDO::PARAM_STR);
        $stmt->bindValue(':data_fim', $dados['data_fim'] !== '' ? $dados['data_fim'] : null, $dados['data_fim'] !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $stmt->bindValue(':categoria_id', $dados['categoria_id'] !== '' ? (int) $dados['categoria_id'] : null, $dados['categoria_id'] !== '' ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $stmt->bindValue(':status', $dados['status'], PDO::PARAM_STR);
    }
}