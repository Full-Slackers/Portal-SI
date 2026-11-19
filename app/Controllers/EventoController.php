<?php

require_once __DIR__ . '/../Models/Database.php';
require_once __DIR__ . '/../Models/Evento.php';
require_once __DIR__ . '/../helpers/auditoria.php';

final class EventoController
{
    private const STATUS_PERMITIDOS = ['rascunho', 'publicado', 'cancelado'];

    public function index(): array
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        $dados = [
            'categorias' => [],
            'eventos' => [],
            'evento' => [
                'id' => 0,
                'titulo' => '',
                'descricao' => '',
                'local' => '',
                'data_inicio' => '',
                'data_fim' => '',
                'categoria_id' => '',
                'status' => 'rascunho',
            ],
            'mostrarFormulario' => isset($_GET['novo']),
            'erros' => [],
            'mensagem' => isset($_GET['salvo']) ? 'Evento salvo com sucesso.' : '',
        ];

        try {
            $pdo = Database::connect();
            $eventos = new Evento($pdo);
            $dados['categorias'] = $eventos->listarCategorias();

            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $acao = $this->postString('acao');
                $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT) ?: 0;
                $dados['mostrarFormulario'] = in_array($acao, ['criar', 'atualizar'], true);

                if ($dados['mostrarFormulario']) {
                    $dados['evento'] = [
                        'id' => $acao === 'atualizar' ? $id : 0,
                        'titulo' => $this->postString('titulo'),
                        'descricao' => $this->postString('descricao'),
                        'local' => $this->postString('local'),
                        'data_inicio' => $this->postString('data_inicio'),
                        'data_fim' => $this->postString('data_fim'),
                        'categoria_id' => $this->postString('categoria_id'),
                        'status' => $this->postString('status'),
                    ];
                }

                $token = $_POST['csrf_token'] ?? '';
                if (!is_string($token) || !hash_equals($_SESSION['csrf_token'], $token)) {
                    $dados['erros'][] = 'Sua sessão expirou. Atualize a página e tente novamente.';
                } elseif ($acao === 'excluir') {
                    if ($id < 1 || !$eventos->excluir($id)) {
                        $dados['erros'][] = 'O evento não foi encontrado.';
                    } else {
                        registrar_auditoria($pdo, 'evento_excluido', 'eventos', $id);
                        header('Location: /portal-si/app/Views/admin/eventos.php?excluido=1');
                        exit;
                    }
                } elseif (in_array($acao, ['criar', 'atualizar'], true)) {
                    $this->validar($dados['evento'], $dados['categorias'], $dados['erros']);
                    if ($acao === 'atualizar' && $id < 1) {
                        $dados['erros'][] = 'Identificador de evento inválido.';
                    }

                    if ($dados['erros'] === []) {
                        $dados['evento']['data_inicio'] = str_replace('T', ' ', $dados['evento']['data_inicio']) . ':00';
                        $dados['evento']['data_fim'] = $dados['evento']['data_fim'] !== ''
                            ? str_replace('T', ' ', $dados['evento']['data_fim']) . ':00'
                            : '';

                        if ($acao === 'criar') {
                            $idSalvo = $eventos->criar($dados['evento'], (int) $_SESSION['usuario_id']);
                            registrar_auditoria($pdo, 'evento_criado', 'eventos', $idSalvo);
                        } elseif (!$eventos->atualizar($id, $dados['evento'])) {
                            $dados['erros'][] = 'O evento não foi encontrado.';
                        } else {
                            registrar_auditoria($pdo, 'evento_atualizado', 'eventos', $id);
                        }

                        if ($dados['erros'] === []) {
                            header('Location: /portal-si/app/Views/admin/eventos.php?salvo=1');
                            exit;
                        }
                    }
                } else {
                    $dados['erros'][] = 'Ação inválida.';
                }
            } elseif (isset($_GET['editar'])) {
                $id = filter_input(INPUT_GET, 'editar', FILTER_VALIDATE_INT) ?: 0;
                $evento = $id > 0 ? $eventos->buscarPorId($id) : null;
                if ($evento !== null) {
                    $dados['evento'] = $evento;
                    $dados['evento']['id'] = (int) $evento['id'];
                    $dados['evento']['data_inicio'] = date('Y-m-d\TH:i', strtotime($evento['data_inicio']));
                    $dados['evento']['data_fim'] = $evento['data_fim']
                        ? date('Y-m-d\TH:i', strtotime($evento['data_fim']))
                        : '';
                    $dados['mostrarFormulario'] = true;
                } else {
                    $dados['mensagem'] = 'O evento solicitado não foi encontrado.';
                }
            }

            if (isset($_GET['excluido'])) {
                $dados['mensagem'] = 'Evento excluído com sucesso.';
            }
            $dados['eventos'] = $eventos->listarTodos();
        } catch (Throwable $e) {
            error_log('Falha ao carregar o gerenciamento de eventos: ' . $e->getMessage());
            $dados['erros'][] = 'Não foi possível carregar ou salvar os eventos. Verifique a conexão com o banco e tente novamente.';
        }

        return $dados;
    }

    private function postString(string $campo): string
    {
        $valor = $_POST[$campo] ?? '';

        return is_string($valor) ? trim($valor) : '';
    }

    private function validar(array $evento, array $categorias, array &$erros): void
    {
        if ($evento['titulo'] === '' || mb_strlen($evento['titulo']) > 200) {
            $erros[] = 'Informe o título do evento (até 200 caracteres).';
        }
        if (mb_strlen($evento['descricao']) > 10000 || mb_strlen($evento['local']) > 160) {
            $erros[] = 'A descrição ou o local excede o limite permitido.';
        }
        if (!$this->dataValida($evento['data_inicio'])) {
            $erros[] = 'Informe uma data e horário inicial válidos.';
        }
        if ($evento['data_fim'] !== '' && !$this->dataValida($evento['data_fim'])) {
            $erros[] = 'Informe uma data e horário final válidos.';
        } elseif ($evento['data_fim'] !== '' && $evento['data_fim'] < $evento['data_inicio']) {
            $erros[] = 'A data final não pode ser anterior ao início do evento.';
        }

        if ($evento['categoria_id'] !== '') {
            $categoriasPorId = array_column($categorias, 'id');
            if (!in_array((int) $evento['categoria_id'], array_map('intval', $categoriasPorId), true)) {
                $erros[] = 'Selecione uma categoria válida.';
            }
        }
        if (!in_array($evento['status'], self::STATUS_PERMITIDOS, true)) {
            $erros[] = 'Selecione um status válido.';
        }
    }

    private function dataValida(string $valor): bool
    {
        $data = DateTime::createFromFormat('!Y-m-d\TH:i', $valor);

        return $data !== false && $data->format('Y-m-d\TH:i') === $valor;
    }
}