<?php

final class Auditoria
{
    public function __construct(private PDO $pdo)
    {
    }

    public function listarRecentes(int $limite = 200): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT a.id, a.usuario_id, a.acao, a.tabela, a.registro_id,
                    a.detalhes, a.ip_origem, a.criado_em, u.nome AS usuario_nome
             FROM auditoria a
             LEFT JOIN usuarios u ON u.id = a.usuario_id
             ORDER BY a.criado_em DESC, a.id DESC
             LIMIT :limite'
        );
        $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }
}
