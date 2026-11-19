<?php

class Validacao
{
	public function __construct(private PDO $pdo)
	{
	}

	public function buscarPorEmail(string $email): ?array
	{
		$sql = "SELECT id, nome, email, senha_hash, perfil, status
				FROM usuarios
				WHERE email = :email AND status = 'ativo' LIMIT 1";

		$stmt = $this->pdo->prepare($sql);
		$stmt->bindValue(':email', $email, PDO::PARAM_STR);
		$stmt->execute();

		$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

		return $usuario ?: null;
	}
}



