<?php

class Usuario
{
	private PDO $pdo;

	public function __construct(PDO $pdo)
	{
		$this->pdo = $pdo;
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

	public function listarTodos(): array
	{
		$stmt = $this->pdo->query(
			'SELECT id, nome, email, perfil, status
			 FROM usuarios
			 ORDER BY nome ASC, id ASC'
		);

		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	public function criar(string $nome, string $email, string $senhaHash, string $perfil): int
	{
		$stmt = $this->pdo->prepare(
			'INSERT INTO usuarios (nome, email, senha_hash, perfil)
			 VALUES (:nome, :email, :senha_hash, :perfil)
			 RETURNING id'
		);
		$stmt->execute([
			':nome' => $nome,
			':email' => $email,
			':senha_hash' => $senhaHash,
			':perfil' => $perfil,
		]);

		return (int) $stmt->fetchColumn();
	}

	public function buscarPorId(int $id): ?array
	{
		$stmt = $this->pdo->prepare(
			'SELECT id, nome, perfil, status
			 FROM usuarios
			 WHERE id = :id'
		);
		$stmt->bindValue(':id', $id, PDO::PARAM_INT);
		$stmt->execute();
		$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

		return $usuario ?: null;
	}

	public function contarAdministradoresAtivos(): int
	{
		$stmt = $this->pdo->query(
			"SELECT COUNT(*) FROM usuarios
			 WHERE perfil = 'admin' AND status = 'ativo'"
		);

		return (int) $stmt->fetchColumn();
	}

	public function excluir(int $id): bool
	{
		$stmt = $this->pdo->prepare('DELETE FROM usuarios WHERE id = :id');
		$stmt->bindValue(':id', $id, PDO::PARAM_INT);
		$stmt->execute();

		return $stmt->rowCount() > 0;
	}
}

$_SESSION['csrf_token'] = $_SESSION['csrf_token'] ?? bin2hex(random_bytes(32));
function validar_csrf(string $token): bool
{
 return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

?>