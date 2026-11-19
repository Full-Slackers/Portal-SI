<?php

require_once __DIR__ . '/../Models/Database.php';
require_once __DIR__ . '/../Models/Auditoria.php';

final class AuditoriaController
{
    public function index(): array
    {
        try {
            return [
                'registros' => (new Auditoria(Database::connect()))->listarRecentes(),
                'erro' => '',
            ];
        } catch (PDOException $e) {
            error_log('Falha ao consultar registros de auditoria: ' . $e->getMessage());

            return [
                'registros' => [],
                'erro' => 'Não foi possível carregar os registros de auditoria. Verifique a conexão e tente novamente.',
            ];
        }
    }
}
