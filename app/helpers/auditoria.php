<?php

function registrar_auditoria(
    PDO $pdo,
    string $acao,
    ?string $tabela = null,
    ?int $registroId = null,
    array $detalhes = []
): bool {
    $usuarioId = isset($_SESSION['usuario_id']) ? (int) $_SESSION['usuario_id'] : null;
    $ip = $_SERVER['REMOTE_ADDR'] ?? null;
    if (!is_string($ip) || filter_var($ip, FILTER_VALIDATE_IP) === false) {
        $ip = null;
    }

    $metadados = [
        'ator_id' => $usuarioId,
        'ator_nome' => is_string($_SESSION['usuario_nome'] ?? null) ? $_SESSION['usuario_nome'] : null,
    ] + $detalhes;
    $detalhesJson = json_encode($metadados, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($detalhesJson === false) {
        error_log('Falha ao serializar os detalhes de uma ação de auditoria.');

        return false;
    }

    try {
        $stmt = $pdo->prepare(
            'INSERT INTO auditoria (usuario_id, acao, tabela, registro_id, detalhes, ip_origem)
             VALUES (:usuario_id, :acao, :tabela, :registro_id, :detalhes, :ip_origem)'
        );
        $stmt->bindValue(':usuario_id', $usuarioId, $usuarioId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->bindValue(':acao', $acao, PDO::PARAM_STR);
        $stmt->bindValue(':tabela', $tabela, $tabela === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindValue(':registro_id', $registroId, $registroId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->bindValue(':detalhes', $detalhesJson, PDO::PARAM_STR);
        $stmt->bindValue(':ip_origem', $ip, $ip === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->execute();

        return true;
    } catch (PDOException $e) {
        error_log('Falha ao gravar ação de auditoria "' . $acao . '": ' . $e->getMessage());

        return false;
    }
}
