<?php

final class Conteudo
{
    public function __construct(private PDO $pdo)
    {
    }

    public function getCategorias(): array
    {
        $stmt = $this->pdo->query('SELECT id, nome FROM categorias ORDER BY ordem ASC, nome ASC');

        return $stmt->fetchAll();
    }

    public function countAll(): int
    {
        $stmt = $this->pdo->query('SELECT COUNT(*) FROM conteudos');

        return (int) $stmt->fetchColumn();
    }

    public function getDashboardStats(): array
    {
        $stmt = $this->pdo->query(
            "SELECT
                COUNT(*) AS total,
                COUNT(*) FILTER (WHERE status = 'publicado') AS publicados,
                COUNT(*) FILTER (WHERE status IN ('rascunho', 'revisao')) AS pendentes
             FROM conteudos"
        );
        $estatisticas = $stmt->fetch();

        return [
            'total' => (int) $estatisticas['total'],
            'publicados' => (int) $estatisticas['publicados'],
            'pendentes' => (int) $estatisticas['pendentes'],
        ];
    }

    public function getAll(int $limite, int $offset): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT c.id, c.titulo, c.status, c.destaque, c.criado_em, cat.nome AS categoria
             FROM conteudos c
             JOIN categorias cat ON cat.id = c.categoria_id
             ORDER BY c.criado_em DESC, c.id DESC
             LIMIT :limite OFFSET :offset'
        );
        $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function getById(int $id): ?array
    {
        $stmt = $this->pdo->prepare(
            "SELECT c.id, c.titulo, c.categoria_id, c.resumo, c.corpo,
                    c.imagem_capa, c.link_youtube, c.status, c.destaque,
                    COALESCE((
                        SELECT STRING_AGG(t.nome, ', ' ORDER BY t.nome)
                        FROM conteudo_tags ct
                        INNER JOIN tags t ON t.id = ct.tag_id
                        WHERE ct.conteudo_id = c.id
                    ), '') AS tags
             FROM conteudos c
             WHERE c.id = :id"
        );
        $stmt->execute([':id' => $id]);
        $conteudo = $stmt->fetch();

        return $conteudo ?: null;
    }

    public function listarMidias(int $conteudoId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, nome_arquivo, caminho, tipo, tamanho_kb
             FROM midias
             WHERE conteudo_id = :conteudo_id
             ORDER BY criado_em ASC, id ASC'
        );
        $stmt->execute([':conteudo_id' => $conteudoId]);

        return $stmt->fetchAll();
    }

    public function create(array $dados, int $autorId, array $midias = []): int
    {
        $this->pdo->beginTransaction();

        try {
            $stmt = $this->pdo->prepare(
                'INSERT INTO conteudos
                    (titulo, slug, resumo, corpo, imagem_capa, link_youtube,
                     categoria_id, autor_id, status, destaque, publicado_em)
                 VALUES
                    (:titulo, :slug, :resumo, :corpo, :imagem_capa, :link_youtube,
                     :categoria_id, :autor_id, :status, :destaque,
                     CASE WHEN :publicado THEN NOW() ELSE NULL END)
                 RETURNING id'
            );
            $this->bindDadosConteudo($stmt, $dados);
            $stmt->bindValue(':autor_id', $autorId, PDO::PARAM_INT);
            $stmt->execute();
            $id = (int) $stmt->fetchColumn();

            $this->salvarTags($id, $dados['tags'] ?? '');
            $this->salvarMidias($id, $autorId, $midias);
            $this->pdo->commit();

            return $id;
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw $e;
        }
    }

    public function update(int $id, array $dados, int $autorId, array $midias = []): bool
    {
        $this->pdo->beginTransaction();

        try {
            $stmt = $this->pdo->prepare(
                'UPDATE conteudos
                 SET titulo = :titulo, slug = :slug, resumo = :resumo, corpo = :corpo,
                     imagem_capa = :imagem_capa, link_youtube = :link_youtube,
                     categoria_id = :categoria_id, status = :status, destaque = :destaque,
                     publicado_em = CASE
                         WHEN :publicado THEN COALESCE(publicado_em, NOW())
                         ELSE NULL
                     END
                 WHERE id = :id'
            );
            $this->bindDadosConteudo($stmt, $dados, $id);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();

            if ($stmt->rowCount() === 0 && $this->getById($id) === null) {
                $this->pdo->rollBack();

                return false;
            }

            $this->salvarTags($id, $dados['tags'] ?? '');
            $this->salvarMidias($id, $autorId, $midias);
            $this->pdo->commit();

            return true;
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw $e;
        }
    }

    public function delete(int $id): bool
    {
        $midias = $this->listarMidias($id);
        $stmt = $this->pdo->prepare('DELETE FROM conteudos WHERE id = :id');
        $stmt->execute([':id' => $id]);

        if ($stmt->rowCount() === 0) {
            return false;
        }

        foreach ($midias as $midia) {
            $caminho = (string) ($midia['caminho'] ?? '');
            $nome = basename($caminho);
            if (
                !str_starts_with($caminho, '/portal-si/public/assets/uploads/')
                || !preg_match('/\A[a-f0-9]{32}\.(?:jpg|png|gif|webp|pdf)\z/i', $nome)
            ) {
                continue;
            }

            $arquivo = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'public'
                . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'uploads'
                . DIRECTORY_SEPARATOR . $nome;
            if (is_file($arquivo) && !unlink($arquivo)) {
                error_log('Não foi possível remover mídia órfã da publicação ' . $id . '.');
            }
        }

        return true;
    }

    private function bindDadosConteudo(PDOStatement $stmt, array $dados, int $ignorarId = 0): void
    {
        $stmt->bindValue(':titulo', $dados['titulo'], PDO::PARAM_STR);
        $stmt->bindValue(':slug', $this->gerarSlugUnico($dados['titulo'], $ignorarId), PDO::PARAM_STR);
        $stmt->bindValue(':resumo', $dados['resumo'], PDO::PARAM_STR);
        $stmt->bindValue(':corpo', $dados['corpo'], PDO::PARAM_STR);
        $imagemCapa = $dados['imagem_capa'] !== '' ? $dados['imagem_capa'] : null;
        $linkYoutube = $dados['link_youtube'] !== '' ? $dados['link_youtube'] : null;
        $stmt->bindValue(':imagem_capa', $imagemCapa, $imagemCapa === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindValue(':link_youtube', $linkYoutube, $linkYoutube === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindValue(':categoria_id', (int) $dados['categoria_id'], PDO::PARAM_INT);
        $stmt->bindValue(':status', $dados['status'], PDO::PARAM_STR);
        $stmt->bindValue(':destaque', $dados['destaque'], PDO::PARAM_BOOL);
        $stmt->bindValue(':publicado', $dados['status'] === 'publicado', PDO::PARAM_BOOL);
    }

    private function salvarTags(int $conteudoId, string $listaTags): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM conteudo_tags WHERE conteudo_id = :conteudo_id');
        $stmt->execute([':conteudo_id' => $conteudoId]);

        $nomes = [];
        foreach (explode(',', $listaTags) as $nome) {
            $nome = trim($nome);
            if ($nome === '') {
                continue;
            }
            $chave = mb_strtolower($nome);
            if (isset($nomes[$chave])) {
                continue;
            }
            $nomes[$chave] = $nome;
        }

        foreach ($nomes as $nome) {
            $stmt = $this->pdo->prepare('SELECT id FROM tags WHERE LOWER(nome) = LOWER(:nome) LIMIT 1');
            $stmt->execute([':nome' => $nome]);
            $tagId = $stmt->fetchColumn();

            if ($tagId === false) {
                $slugBase = $this->criarSlug($nome);
                $slug = $slugBase;
                $sufixo = 2;
                while (true) {
                    $stmt = $this->pdo->prepare('SELECT 1 FROM tags WHERE slug = :slug LIMIT 1');
                    $stmt->execute([':slug' => $slug]);
                    if ($stmt->fetchColumn() === false) {
                        break;
                    }
                    $slug = substr($slugBase, 0, 66) . '-' . $sufixo++;
                }

                $stmt = $this->pdo->prepare('INSERT INTO tags (nome, slug) VALUES (:nome, :slug) RETURNING id');
                $stmt->execute([':nome' => $nome, ':slug' => $slug]);
                $tagId = $stmt->fetchColumn();
            }

            $stmt = $this->pdo->prepare(
                'INSERT INTO conteudo_tags (conteudo_id, tag_id) VALUES (:conteudo_id, :tag_id)'
            );
            $stmt->execute([':conteudo_id' => $conteudoId, ':tag_id' => (int) $tagId]);
        }
    }

    private function salvarMidias(int $conteudoId, int $autorId, array $midias): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO midias (conteudo_id, nome_arquivo, caminho, tipo, tamanho_kb, enviado_por)
             VALUES (:conteudo_id, :nome_arquivo, :caminho, :tipo, :tamanho_kb, :enviado_por)'
        );

        foreach ($midias as $midia) {
            $stmt->execute([
                ':conteudo_id' => $conteudoId,
                ':nome_arquivo' => $midia['nome_arquivo'],
                ':caminho' => $midia['caminho'],
                ':tipo' => $midia['tipo'],
                ':tamanho_kb' => max(1, (int) ceil($midia['tamanho_bytes'] / 1024)),
                ':enviado_por' => $autorId,
            ]);
        }
    }

    private function criarSlug(string $texto): string
    {
        $ascii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $texto);
        $base = strtolower(trim((string) preg_replace('/[^a-zA-Z0-9]+/', '-', $ascii === false ? $texto : $ascii), '-'));

        return substr($base === '' ? 'tag' : $base, 0, 70);
    }

    private function gerarSlugUnico(string $titulo, int $ignorarId = 0): string
    {
        $texto = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $titulo);
        if ($texto === false) {
            $texto = $titulo;
        }

        $base = strtolower(trim((string) preg_replace('/[^a-zA-Z0-9]+/', '-', $texto), '-'));
        if ($base === '') {
            $base = 'noticia';
        }
        $base = substr($base, 0, 200);

        $slug = $base;
        $sufixo = 2;
        do {
            $sql = 'SELECT id FROM conteudos WHERE slug = :slug';
            $parametros = [':slug' => $slug];
            if ($ignorarId > 0) {
                $sql .= ' AND id <> :id';
                $parametros[':id'] = $ignorarId;
            }
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($parametros);
            if (!$stmt->fetch()) {
                return $slug;
            }

            $slug = $base . '-' . $sufixo;
            $sufixo++;
        } while (true);
    }
}