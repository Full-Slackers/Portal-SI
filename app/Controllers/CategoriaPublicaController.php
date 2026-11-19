<?php

require_once __DIR__ . '/../Models/Database.php';
require_once __DIR__ . '/../Models/Evento.php';

final class CategoriaPublicaController
{
    private const ITENS_POR_PAGINA = 9;

    public function index(string $slug, int $pagina): array
    {
        $dados = [
            'categorias' => [],
            'categoria' => null,
            'apresentacao' => null,
            'publicacoes' => [],
            'eventos' => [],
            'pagina' => max(1, $pagina),
            'totalPaginas' => 1,
            'totalPublicacoes' => 0,
            'erro' => '',
        ];

        try {
            $pdo = Database::connect();

            $categorias = $pdo->query(
                'SELECT id, nome, slug, descricao, icone, ordem
                 FROM categorias
                 WHERE ativa = TRUE AND slug <> \'home\'
                 ORDER BY ordem ASC, nome ASC'
            );
            $dados['categorias'] = $categorias->fetchAll();

            $categoriaStmt = $pdo->prepare(
                'SELECT id, nome, slug, descricao, icone
                 FROM categorias
                 WHERE slug = :slug AND slug <> \'home\' AND ativa = TRUE
                 LIMIT 1'
            );
            $categoriaStmt->execute([':slug' => $slug]);
            $dados['categoria'] = $categoriaStmt->fetch() ?: null;

            if ($dados['categoria'] === null) {
                return $dados;
            }

            $dados['apresentacao'] = $this->apresentacaoDaCategoria(
                (string) $dados['categoria']['slug'],
                (string) $dados['categoria']['nome'],
                (string) ($dados['categoria']['descricao'] ?? '')
            );
            $dados['eventos'] = (new Evento($pdo))->listarPublicados(
                (int) $dados['categoria']['id'],
                4
            );

            $totalStmt = $pdo->prepare(
                "SELECT COUNT(*)
                 FROM conteudos
                 WHERE categoria_id = :categoria_id AND status = 'publicado'"
            );
            $totalStmt->execute([':categoria_id' => (int) $dados['categoria']['id']]);
            $dados['totalPublicacoes'] = (int) $totalStmt->fetchColumn();
            $dados['totalPaginas'] = max(
                1,
                (int) ceil($dados['totalPublicacoes'] / self::ITENS_POR_PAGINA)
            );
            $dados['pagina'] = min($dados['pagina'], $dados['totalPaginas']);
            $offset = ($dados['pagina'] - 1) * self::ITENS_POR_PAGINA;

            $publicacoesStmt = $pdo->prepare(
                "SELECT c.id, c.titulo, c.resumo, c.imagem_capa,
                        COALESCE(c.publicado_em, c.criado_em) AS publicado_em, c.destaque,
                        u.nome AS autor,
                        (SELECT STRING_AGG(t.nome, ', ' ORDER BY t.nome)
                         FROM conteudo_tags ct
                         INNER JOIN tags t ON t.id = ct.tag_id
                         WHERE ct.conteudo_id = c.id) AS tags
                 FROM conteudos c
                 INNER JOIN usuarios u ON u.id = c.autor_id
                 WHERE c.categoria_id = :categoria_id AND c.status = 'publicado'
                 ORDER BY c.destaque DESC, COALESCE(c.publicado_em, c.criado_em) DESC, c.id DESC
                 LIMIT :limite OFFSET :offset"
            );
            $publicacoesStmt->bindValue(':categoria_id', (int) $dados['categoria']['id'], PDO::PARAM_INT);
            $publicacoesStmt->bindValue(':limite', self::ITENS_POR_PAGINA, PDO::PARAM_INT);
            $publicacoesStmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $publicacoesStmt->execute();
            $dados['publicacoes'] = $publicacoesStmt->fetchAll();
        } catch (Throwable $e) {
            error_log('Erro ao carregar página de categoria: ' . $e->getMessage());
            $dados['erro'] = 'Não foi possível carregar esta categoria no momento. Tente novamente mais tarde.';
        }

        return $dados;
    }

    private function apresentacaoDaCategoria(string $slug, string $nome, string $descricao): array
    {
        $apresentacoes = [
            'sobre-o-curso' => [
                'titulo' => 'Tecnologia, conhecimento e novas possibilidades',
                'texto' => 'Conheça melhor o universo de Sistemas de Informação e os diferentes caminhos que conectam tecnologia, pessoas e organizações. Esta área reúne conteúdos para quem quer entender a formação e suas possibilidades.',
                'destaques' => [
                    ['titulo' => 'Formação em tecnologia', 'texto' => 'Uma visão ampla sobre conhecimentos e competências que fazem parte da área de Sistemas de Informação.'],
                    ['titulo' => 'Aprendizado conectado', 'texto' => 'Conteúdos que aproximam conceitos, ferramentas e situações do cotidiano profissional.'],
                    ['titulo' => 'Descubra o curso', 'texto' => 'Um ponto de partida para explorar informações, novidades e materiais relacionados ao curso.'],
                ],
            ],
            'acoes-sociais' => [
                'titulo' => 'Tecnologia também pode aproximar e transformar',
                'texto' => 'As ações sociais mostram como o conhecimento pode dialogar com as necessidades das pessoas e da comunidade. Explore ideias, experiências e iniciativas que inspiram participação e colaboração.',
                'destaques' => [
                    ['titulo' => 'Olhar para a comunidade', 'texto' => 'Conheça temas e iniciativas que valorizam a escuta, a participação e o contexto local.'],
                    ['titulo' => 'Colaboração na prática', 'texto' => 'A troca entre diferentes pessoas e saberes pode abrir caminhos para soluções mais inclusivas.'],
                    ['titulo' => 'Impacto que inspira', 'texto' => 'Acompanhe reflexões e experiências que incentivam o uso responsável da tecnologia.'],
                ],
            ],
            'atividades-complementares' => [
                'titulo' => 'Aprender também acontece fora da sala de aula',
                'texto' => 'Palestras, oficinas, cursos e outras experiências podem ampliar a formação acadêmica. Use este espaço para acompanhar orientações e descobrir oportunidades de aprendizado complementar.',
                'destaques' => [
                    ['titulo' => 'Novas experiências', 'texto' => 'Explore diferentes formatos de atividade e encontre assuntos que complementem sua jornada.'],
                    ['titulo' => 'Aprendizado contínuo', 'texto' => 'Cada experiência pode contribuir para desenvolver conhecimentos, autonomia e novas perspectivas.'],
                    ['titulo' => 'Organize sua jornada', 'texto' => 'Consulte as publicações da categoria para acompanhar informações e orientações compartilhadas.'],
                ],
            ],
            'projetos-extensao' => [
                'titulo' => 'Ideias que saem do papel e encontram a comunidade',
                'texto' => 'A extensão aproxima a aprendizagem acadêmica de diferentes realidades. Nesta categoria, você pode conhecer propostas, processos e aprendizados que conectam Sistemas de Informação a desafios do mundo ao redor.',
                'destaques' => [
                    ['titulo' => 'Problemas reais, novas ideias', 'texto' => 'Projetos começam com perguntas e oportunidades de contribuir de forma relevante.'],
                    ['titulo' => 'Construção colaborativa', 'texto' => 'A parceria entre estudantes, docentes e comunidade enriquece o processo de aprendizagem.'],
                    ['titulo' => 'Compartilhe aprendizados', 'texto' => 'Acompanhe experiências e resultados publicados para inspirar novas iniciativas.'],
                ],
            ],
            'eventos' => [
                'titulo' => 'Encontros para aprender, trocar e participar',
                'texto' => 'Eventos são oportunidades para conhecer ideias, conversar sobre tecnologia e ampliar conexões. Acompanhe as publicações desta categoria para descobrir novidades e informações compartilhadas pelo portal.',
                'destaques' => [
                    ['titulo' => 'Conhecimento em movimento', 'texto' => 'Palestras, encontros e atividades aproximam diferentes perspectivas sobre tecnologia.'],
                    ['titulo' => 'Troca de experiências', 'texto' => 'Conversas e conexões ajudam a transformar curiosidade em novas ideias.'],
                    ['titulo' => 'Fique por dentro', 'texto' => 'Consulte as publicações e acompanhe os eventos divulgados nesta categoria.'],
                ],
            ],
        ];

        return $apresentacoes[$slug] ?? [
            'titulo' => 'Explore mais sobre ' . $nome,
            'texto' => $descricao !== ''
                ? $descricao
                : 'Um espaço para reunir informações, ideias e conteúdos relacionados a ' . $nome . '.',
            'destaques' => [
                ['titulo' => 'Conheça', 'texto' => 'Explore informações e conteúdos relacionados a esta categoria.'],
                ['titulo' => 'Acompanhe', 'texto' => 'Novas publicações ajudam a manter este espaço atualizado.'],
                ['titulo' => 'Participe', 'texto' => 'Compartilhe ideias e acompanhe as iniciativas divulgadas pelo portal.'],
            ],
        ];
    }
}
