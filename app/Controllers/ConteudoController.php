<?php

require_once __DIR__ . '/../Models/Database.php';
require_once __DIR__ . '/../Models/Conteudo.php';
require_once __DIR__ . '/../helpers/midia.php';
require_once __DIR__ . '/../helpers/upload_midias.php';
require_once __DIR__ . '/../helpers/auditoria.php';

final class ConteudoController
{
    private const STATUS_PERMITIDOS = ['rascunho', 'revisao', 'publicado', 'arquivado'];
    private const ITENS_POR_PAGINA = 10;

    public function index(): array
    {
        $dados = [
            'erros' => [],
            'mensagem' => '',
            'tipoMensagem' => 'error',
            'noticia' => [
                'id' => 0,
                'titulo' => '',
                'categoria_id' => '',
                'resumo' => '',
                'corpo' => '',
                'imagem_capa' => '',
                'link_youtube' => '',
                'tags' => '',
                'status' => 'rascunho',
                'destaque' => false,
            ],
            'categorias' => [],
            'midiasExistentes' => [],
            'noticias' => [],
            'mostrarFormulario' => isset($_GET['novo']),
            'pagina' => max(1, filter_input(INPUT_GET, 'pagina', FILTER_VALIDATE_INT) ?: 1),
            'totalPaginas' => 1,
            'totalNoticias' => 0,
            'estatisticas' => ['total' => 0, 'publicados' => 0, 'pendentes' => 0],
        ];

        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        try {
            $pdo = Database::connect();
            $conteudo = new Conteudo($pdo);
            $dados['categorias'] = $conteudo->getCategorias();
            if ($_SERVER['REQUEST_METHOD'] !== 'POST' && $dados['mostrarFormulario']) {
                $categoriaSelecionada = filter_input(INPUT_GET, 'categoria', FILTER_VALIDATE_INT);
                if ($categoriaSelecionada !== false && $categoriaSelecionada !== null) {
                    foreach ($dados['categorias'] as $categoria) {
                        if ((int) $categoria['id'] === $categoriaSelecionada) {
                            $dados['noticia']['categoria_id'] = $categoriaSelecionada;
                            break;
                        }
                    }
                }
            }
            $categoriasPorId = [];
            foreach ($dados['categorias'] as $categoria) {
                $categoriasPorId[(int) $categoria['id']] = true;
            }

            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $acao = is_string($_POST['acao'] ?? null) ? $_POST['acao'] : '';
                $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT) ?: 0;
                $dados['mostrarFormulario'] = in_array($acao, ['criar', 'atualizar'], true);

                if ($dados['mostrarFormulario']) {
                    $dados['noticia'] = [
                        'id' => $acao === 'atualizar' ? $id : 0,
                        'titulo' => $this->postString('titulo'),
                        'categoria_id' => filter_input(INPUT_POST, 'categoria_id', FILTER_VALIDATE_INT) ?: '',
                        'resumo' => $this->postString('resumo'),
                        'corpo' => $this->postString('corpo'),
                        'imagem_capa' => $this->postString('imagem_capa'),
                        'link_youtube' => $this->postString('link_youtube'),
                        'tags' => $this->postString('tags'),
                        'status' => $this->postString('status'),
                        'destaque' => isset($_POST['destaque']),
                    ];
                }

                $token = $_POST['csrf_token'] ?? '';
                if (!is_string($token) || !hash_equals($_SESSION['csrf_token'], $token)) {
                    $dados['erros'][] = 'Sua sessão expirou ou o formulário é inválido. Atualize a página e tente novamente.';
                } elseif ($acao === 'excluir') {
                    if ($id < 1) {
                        $dados['erros'][] = 'Notícia inválida para exclusão.';
                    } elseif (!$conteudo->delete($id)) {
                        $dados['erros'][] = 'A notícia não foi encontrada.';
                    } else {
                        registrar_auditoria($pdo, 'conteudo_excluido', 'conteudos', $id);
                        header('Location: /portal-si/app/Views/admin/painel_adimin.php?excluido=1');
                        exit;
                    }
                } elseif (in_array($acao, ['criar', 'atualizar'], true)) {
                    $this->validarNoticia($dados['noticia'], $categoriasPorId, $dados['erros']);
                    if ($acao === 'atualizar' && $id < 1) {
                        $dados['erros'][] = 'Notícia inválida para edição.';
                    }

                    $midiasCarregadas = [];
                    if ($dados['erros'] === []) {
                        $midiasCarregadas = processarUploadsMidias($_FILES['midias'] ?? null, $dados['erros']);
                    }

                    if ($dados['erros'] === []) {
                        try {
                            if ($acao === 'criar') {
                                $idSalvo = $conteudo->create($dados['noticia'], (int) $_SESSION['usuario_id'], $midiasCarregadas);
                                registrar_auditoria($pdo, 'conteudo_criado', 'conteudos', $idSalvo);
                            } elseif (!$conteudo->update($id, $dados['noticia'], (int) $_SESSION['usuario_id'], $midiasCarregadas)) {
                                $dados['erros'][] = 'A notícia não foi encontrada.';
                            } else {
                                registrar_auditoria($pdo, 'conteudo_atualizado', 'conteudos', $id);
                            }
                        } catch (Throwable $e) {
                            removerUploadsMidias($midiasCarregadas);
                            throw $e;
                        }

                        if ($dados['erros'] === []) {
                            header('Location: /portal-si/app/Views/admin/painel_adimin.php?salvo=1');
                            exit;
                        }
                        removerUploadsMidias($midiasCarregadas);
                    }
                } else {
                    $dados['erros'][] = 'Ação inválida.';
                }
            } elseif (isset($_GET['editar'])) {
                $id = filter_input(INPUT_GET, 'editar', FILTER_VALIDATE_INT) ?: 0;
                $registro = $id > 0 ? $conteudo->getById($id) : null;
                if ($registro) {
                    $dados['mostrarFormulario'] = true;
                    $dados['noticia'] = $registro;
                    $dados['noticia']['id'] = (int) $registro['id'];
                    $dados['noticia']['categoria_id'] = (int) $registro['categoria_id'];
                    $dados['noticia']['destaque'] = (bool) $registro['destaque'];
                    $dados['midiasExistentes'] = $conteudo->listarMidias($id);
                } else {
                    $dados['mensagem'] = $id > 0
                        ? 'A notícia solicitada não foi encontrada.'
                        : 'Identificador de notícia inválido.';
                }
            }

            if (isset($_GET['salvo'])) {
                $dados['mensagem'] = 'Notícia salva com sucesso.';
                $dados['tipoMensagem'] = 'success';
            } elseif (isset($_GET['excluido'])) {
                $dados['mensagem'] = 'Notícia excluída com sucesso.';
                $dados['tipoMensagem'] = 'success';
            }

            $dados['totalNoticias'] = $conteudo->countAll();
            $dados['estatisticas'] = $conteudo->getDashboardStats();
            $dados['totalPaginas'] = max(1, (int) ceil($dados['totalNoticias'] / self::ITENS_POR_PAGINA));
            $dados['pagina'] = min($dados['pagina'], $dados['totalPaginas']);
            $offset = ($dados['pagina'] - 1) * self::ITENS_POR_PAGINA;
            $dados['noticias'] = $conteudo->getAll(self::ITENS_POR_PAGINA, $offset);
        } catch (PDOException $e) {
            error_log('Falha no painel administrativo: ' . $e->getMessage());
            $dados['mensagem'] = 'Não foi possível concluir a operação no banco de dados. Verifique a conexão e tente novamente.';
        } catch (Throwable $e) {
            error_log('Falha inesperada no painel administrativo: ' . $e->getMessage());
            $dados['mensagem'] = 'Ocorreu um erro inesperado ao carregar o painel. Tente novamente.';
        }

        return $dados;
    }

    private function postString(string $campo): string
    {
        $valor = $_POST[$campo] ?? '';

        return is_string($valor) ? trim($valor) : '';
    }

    private function validarNoticia(array $noticia, array $categoriasPorId, array &$erros): void
    {
        $tamanhoTitulo = preg_match_all('/./us', $noticia['titulo']);
        if ($noticia['titulo'] === '' || $tamanhoTitulo === false || $tamanhoTitulo > 200) {
            $erros[] = 'Informe um título com até 200 caracteres.';
        }
        if ($noticia['resumo'] === '') {
            $erros[] = 'Informe um resumo para a notícia.';
        }
        if ($noticia['corpo'] === '') {
            $erros[] = 'Informe o conteúdo da notícia.';
        }
        $this->validarTags($noticia['tags'], $erros);
        if (!caminhoImagemValido($noticia['imagem_capa'])) {
            $erros[] = 'Informe uma URL HTTPS ou caminho local válido para a imagem de capa (até 255 caracteres).';
        }
        if ($noticia['link_youtube'] !== '' && obterIdYoutube($noticia['link_youtube']) === null) {
            $erros[] = 'Informe uma URL válida de vídeo do YouTube.';
        }
        if (!isset($categoriasPorId[(int) $noticia['categoria_id']])) {
            $erros[] = 'Selecione uma categoria válida.';
        }
        if (!in_array($noticia['status'], self::STATUS_PERMITIDOS, true)) {
            $erros[] = 'Selecione um status válido.';
        }
    }

    private function validarTags(string $listaTags, array &$erros): void
    {
        $tags = [];
        foreach (explode(',', $listaTags) as $tag) {
            $tag = trim($tag);
            if ($tag !== '') {
                $tags[mb_strtolower($tag)] = $tag;
            }
        }
        $tags = array_values($tags);
        if (count($tags) > 10) {
            $erros[] = 'Informe no máximo 10 tags, separadas por vírgula.';

            return;
        }

        foreach ($tags as $tag) {
            if (
                mb_strlen($tag) > 60
                || preg_match('/^[\p{L}\p{N}][\p{L}\p{N}\p{M} ._+#-]*$/u', $tag) !== 1
            ) {
                $erros[] = 'Cada tag deve ter até 60 caracteres e conter apenas letras, números, espaços, ponto, +, #, _ ou hífen.';

                return;
            }
        }
    }
}