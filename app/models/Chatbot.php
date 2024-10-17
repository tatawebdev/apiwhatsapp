<?php

use Models\Connection;
use WhatsApp\InteractiveMessages;
use WhatsApp\Message;

include_once __DIR__ . '/Connection.php';

class Chatbot extends Models\Connection
{
    public $numeroUsuario;
    public $mensagemUsuario;
    public $event_type;
    public $last_message_id;
    protected $connection; // Instância de conexão será armazenada aqui

    public function __construct($host, $dbname, $username, $password)
    {
        $this->connection = Connection::getInstance($host, $dbname, $username, $password);
    }


    public function getAtendimentosAntigosEmAndamento()
    {
        $sql = "SELECT * FROM `atendimento` WHERE status = 'Em Andamento' AND created_at <= NOW() - INTERVAL 3 HOUR ORDER BY `status` ASC";
        return $this->connection->fetchAll($sql);
    }

    // Função para processar a entrada do usuário
    public function processarEntradaTeste($data) {}


    public function processarEntrada($data)
    {

        if (!isset($data['celular']) || !isset($data['message'])) {
            return;
        }
        $tipo_documento = '';
        $this->numeroUsuario = $data['celular'];
        $this->mensagemUsuario = $data['message'];
        $this->last_message_id = $data['message_id'];
        $this->event_type = $data['event_type'];

        extract($this->obterEstado());
        if ($data['message_etapa']) {
            $etapa = $data['message_etapa'];
        }
        if ($data['message_tipo_documento']) {
            $tipo_documento = $data['message_tipo_documento'];
        }

        // Caso seja a primeira mensagem do dia ou não tenha etapa, define a etapa como 1
        if ($etapa === null || $this->isPrimeiraMensagemDoDia()) {
            $etapa = 1;
        }
        switch ($etapa) {
            case 1:
                $this->iniciarAtendimento();
                break;
            case 2:
                if ($this->validarNome()) {
                    $this->atualizarAtendimento('nome', $this->mensagemUsuario);

                    $this->solicitarDetalhamentoDuvida();
                } else {
                    $this->enviarMensagemWhatsApp($this->numeroUsuario, "Por favor, digite um nome válido para continuar.");
                }
                break;
            case 3:

                if ($this->verificarTipoEvento('message_text', 'Por favor, envie uma mensagem de texto com detalhes sobre sua dúvida.')) {

                    if ($this->validarMensagemDetalhes($this->mensagemUsuario, 'Por favor, forneça mais detalhes.')) {
                        $this->atualizarAtendimento('detalhes', $this->mensagemUsuario);
                        $this->enviarConsultaTributaria();
                    }
                }
                break;
            case 4:
                if ($this->verificarTipoEvento('interactive', 'Por favor, selecione uma opção válida da lista.')) {
                    $this->atualizarAtendimento('assunto', $this->mensagemUsuario);
                    $this->solicitarTipoPessoa();
                }
                break;
            case 5:
                if ($this->verificarTipoEvento('message_button', 'Por favor, utilize o botão para informar se a sua consulta é para uma *Pessoa Física* ou *Pessoa Jurídica*.')) {

                    if ($this->validarTipoPessoa()) {

                        $this->atualizarAtendimento('tipo_pessoa', $this->mensagemUsuario);

                        if (strtolower($this->mensagemUsuario) === 'pessoa jurídica') {
                            $this->solicitarDocumento();
                        } else {
                            $this->solicitarUrgenciaDemanda();
                        }
                    } else {
                        $this->enviarMensagemWhatsApp($this->numeroUsuario, "Por favor, escolha uma opção válida.");
                    }
                }
                break;
            case 6:
                $mensagem = $tipo_documento === 'cpf' ?
                    "Por favor, digite um CPF válido." :
                    "Por favor, digite um CNPJ válido.";

                if ($this->verificarTipoEvento('message_text', $mensagem)) {
                    if ($this->validarDocumento($this->mensagemUsuario, $tipo_documento)) {
                        $this->atualizarAtendimento('documento', $this->mensagemUsuario);
                        $this->solicitarUrgenciaDemanda();
                    } else {
                        $mensagem = $tipo_documento === 'cpf' ?
                            "O CPF fornecido é inválido. Por favor, digite um CPF válido." :
                            "O CNPJ fornecido é inválido. Por favor, digite um CNPJ válido.";
                        $this->enviarMensagemWhatsApp($this->numeroUsuario, $mensagem);
                    }
                }
                break;
            case 7:
                if ($this->verificarTipoEvento('message_button', 'Por favor, utilize os botões para informar a urgência da sua demanda.'))
                    $this->etapa7();
                break;
            default:
                $this->finalizarAtendimento();
                break;
        }
    }
    // Função para obter o estado da conversa do usuário
    public function obterEstado()
    {
        $sql = "SELECT id_step as etapa, type_documento as tipo_documento FROM interacoes WHERE numero_usuario = ? ORDER BY ultima_interacao DESC LIMIT 1";
        return $this->connection->fetchAssoc($sql, [$this->numeroUsuario], 's') ?? ['etapa' => null, 'tipo_documento' => null];
    }
    public function isPrimeiraMensagemDoDia()
    {
        $sql = "SELECT ultima_interacao FROM interacoes WHERE numero_usuario = ? ORDER BY ultima_interacao DESC LIMIT 1";
        $ultimaInteracao = $this->connection->fetchAssoc($sql, [$this->numeroUsuario], "s")['ultima_interacao'];

        // Verificar se a última interação é do dia atual
        if ($ultimaInteracao) {
            $ultimaData = new \DateTime($ultimaInteracao);
            $dataAtual = new \DateTime();

            return $ultimaData->format('Y-m-d') !== $dataAtual->format('Y-m-d');
        }

        // Se não houver interação registrada, considera como primeira mensagem do dia
        return true;
    }
    public function atualizarEstado($idStep, $typedocumento = null)
    {

        // Prepara a consulta SQL para verificar se a interação já existe
        $sqlCheck = "SELECT COUNT(*) FROM interacoes WHERE numero_usuario = ?";
        $count = $this->connection->fetchAssoc($sqlCheck, [$this->numeroUsuario], "s")['COUNT(*)'];

        // Determina se deve atualizar ou inserir
        if ($count > 0) {
            // Atualiza a interação existente
            $sqlUpdate = "UPDATE interacoes SET ultima_interacao = NOW(), id_step = ?"
                . ($typedocumento !== null ? ", type_documento = ?" : "") . " WHERE numero_usuario = ?";

            $stmt = $this->connection->query(
                $sqlUpdate,
                $typedocumento !== null ? [$idStep, $typedocumento, $this->numeroUsuario] : [$idStep, $this->numeroUsuario],
                $typedocumento !== null ? "iss" : "is"
            );
        } else {
            // Insere uma nova interação
            $sqlInsert = "INSERT INTO interacoes (numero_usuario, id_step, ultima_interacao"
                . ($typedocumento !== null ? ", type_documento" : "") . ") VALUES (?, ?, NOW()"
                . ($typedocumento !== null ? ", ?" : "") . ")";

            $stmt = $this->connection->query(
                $sqlInsert,
                $typedocumento !== null ? [$this->numeroUsuario, $idStep, $typedocumento] : [$this->numeroUsuario, $idStep],
                $typedocumento !== null ? "sis" : "si"
            );
        }

        // Executa a consulta e verifica se houve erros
        if (!$stmt) {
            throw new \Exception("Erro na execução da consulta: " . $this->mysqli->error);
        }

        // Fecha a declaração preparada
        $stmt->close();
    }

    public function enviarListaInterativaWhatsApp($numero, $tituloLista, $subtituloLista, $textoAgradecimento, $verOpcoes, $secoes)
    {
        // Instância de mensagens interativas
        $interactiveMessages = InteractiveMessages::getInstance();
        $interactiveMessages->setRecipientNumber($numero);


        // Adiciona seções e itens à mensagem interativa
        foreach ($secoes as $secaoTitulo => $itens) {
            foreach ($itens as $itemId => $item) {
                $interactiveMessages->addSection($secaoTitulo, $itemId, $item['titulo'], $item['descricao']);
            }
        }

        // Envia a mensagem com lista interativa
        $interactiveMessages->sendListMessage($tituloLista, $subtituloLista, $textoAgradecimento, $verOpcoes);
    }

    // Função para enviar mensagem via API do WhatsApp
    public function enviarMensagemWhatsApp($numero, $mensagem, $previewUrl = false)
    {
        $message = Message::getInstance();
        $message->setRecipientNumber($numero);
        $message->sendMessageText($mensagem, $previewUrl);
    }
    public function enviarReactionWhatsApp($numero, $messageId, $emoji)
    {
        $message = Message::getInstance();
        $message->setRecipientNumber($numero);
        $message->sendReactionMessage($messageId, $emoji);
    }
    public function enviarBotoesMensagemWhatsApp($numero, $buttonText, $opcoes)
    {
        $interactiveMessages = InteractiveMessages::getInstance();
        $interactiveMessages->setRecipientNumber($numero);

        $buttons = [];
        foreach ($opcoes as $key => $opcao) {
            // Adiciona cada opção ao array de botões
            $buttons[] = [
                'type' => 'reply',
                'reply' => [
                    'id' => 'button_' . $key, // Gera um ID único para o botão
                    'title' => $opcao['button'], // Título da opção
                ],
            ];
        }
        $interactiveMessages->sendButtonMessage($buttonText, $buttons);
    }
    private function validarCPF($cpf)
    {
        // Remove caracteres não numéricos
        $cpf = preg_replace('/\D/', '', $cpf);

        // Valida o CPF
        if (strlen($cpf) != 11) {
            return false;
        }

        // Validação do CPF (dígitos verificadores)
        for ($t = 9; $t < 11; $t++) {
            for ($d = 0, $c = 0; $c < $t; $c++) {
                $d += $cpf[$c] * (($t + 1) - $c); // Aqui foi alterado de $cpf{$c} para $cpf[$c]
            }
            $d = ((10 * $d) % 11) % 10;
            if ($cpf[$c] != $d) { // Aqui foi alterado de $cpf{$c} para $cpf[$c]
                return false;
            }
        }
        return true;
    }

    function validarCNPJ($cnpj)
    {
        $cnpj = preg_replace('/[^0-9]/', '', (string) $cnpj);

        // Valida tamanho
        if (strlen($cnpj) != 14)
            return false;

        // Verifica se todos os digitos são iguais
        if (preg_match('/(\d)\1{13}/', $cnpj))
            return false;

        // Valida primeiro dígito verificador
        for ($i = 0, $j = 5, $soma = 0; $i < 12; $i++) {
            $soma += $cnpj[$i] * $j;
            $j = ($j == 2) ? 9 : $j - 1;
        }

        $resto = $soma % 11;

        if ($cnpj[12] != ($resto < 2 ? 0 : 11 - $resto))
            return false;

        // Valida segundo dígito verificador
        for ($i = 0, $j = 6, $soma = 0; $i < 13; $i++) {
            $soma += $cnpj[$i] * $j;
            $j = ($j == 2) ? 9 : $j - 1;
        }

        $resto = $soma % 11;

        return $cnpj[13] == ($resto < 2 ? 0 : 11 - $resto);
    }
    public function atualizarAtendimento($coluna, $valor, $status = "Em Andamento")
    {
        // Sanitizar e validar os parâmetros
        $coluna = htmlspecialchars($coluna);
        $valor = htmlspecialchars($valor);
        $numero_usuario = htmlspecialchars($this->numeroUsuario);
        $status = htmlspecialchars($status);

        // Verificar se o registro existe e o status não é 'Em Andamento' (limitando a 1 registro)
        $checkSql = "SELECT COUNT(*) FROM atendimento WHERE numero_usuario = ? AND status = 'Em Andamento' LIMIT 1";
        $exists = $this->connection->fetchAssoc($checkSql, [$numero_usuario], "s")['COUNT(*)'];

        if ($exists > 0) {
            // Atualizar o registro existente
            $allowedColumns = ['nome', 'tipo_pessoa', 'documento', 'assunto', 'detalhes', 'urgencia'];

            if (in_array($coluna, $allowedColumns)) {
                $updateSql = "UPDATE atendimento SET 
                    $coluna = ?, 
                    status = ?, 
                    updated_at = CURRENT_TIMESTAMP 
                    WHERE numero_usuario = ? AND status = 'Em Andamento' LIMIT 1";

                $this->connection->query($updateSql, [$valor, $status, $numero_usuario], "sss");

                // Obter o ID do registro atualizado
                $getIdSql = "SELECT id FROM atendimento WHERE numero_usuario = ? AND status = ? ORDER BY id DESC LIMIT 1";
                $id = $this->connection->fetchAssoc($getIdSql, [$numero_usuario, $status], "ss")['id'];

                return $id; // Retorna o ID do registro atualizado
            }
        } else {
            // Inserir novo atendimento
            $insertSql = "INSERT INTO atendimento (numero_usuario, $coluna, status, created_at, updated_at) VALUES (?, ?, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)";
            $this->connection->query($insertSql, [$numero_usuario, $valor, $status], "sss");

            // Obter o ID do registro recém-inserido
            return $this->mysqli->insert_id; // Recupera e retorna o ID gerado
        }

        return null; // Retorna null se nenhuma operação foi realizada
    }

    public function getByNumeroUsuario()
    {
        // Sanitizar o parâmetro
        $numero_usuario = htmlspecialchars($this->numeroUsuario);

        // Consultar a tabela atendimento pelo numero_usuario
        $sql = "SELECT * FROM atendimento WHERE numero_usuario = ? AND status = 'Em Andamento'";
        $result = $this->connection->fetchAll($sql, [$numero_usuario], "s");

        return !empty($result) ? $result : null; // Retorna um array de registros ou null
    }

    public function getById($id)
    {
        // Sanitizar o parâmetro
        $id = intval($id); // Convertendo para inteiro para evitar injeções

        // Consultar a tabela atendimento pelo id
        $sql = "SELECT * FROM atendimento WHERE id = ?";
        $atendimento = $this->connection->fetchAssoc($sql, [$id], "i");

        return $atendimento ?: null; // Retorna o registro ou null se não houver registro
    }

    // Novo método para obter a etapa atual do fluxo
    public function obterEtapaAtual()
    {
        // Primeiro, tenta obter a última pergunta do usuário
        $sql = "SELECT s.pergunta, s.tipo_resposta FROM steps s
                INNER JOIN interacoes i ON s.id = i.id_step  
                WHERE i.numero_usuario = ? 
                ORDER BY i.ultima_interacao DESC 
                LIMIT 1";

        $result = $this->connection->fetchAssoc($sql, [$this->numeroUsuario], "s");

        // Se a pergunta estiver vazia, busca a primeira pergunta do banco
        if (empty($result)) {
            $sql = "SELECT s.pergunta, s.tipo_resposta FROM steps s
                    ORDER BY s.id ASC 
                    LIMIT 1";

            $result = $this->connection->fetchAssoc($sql, [], "");
        }

        return $result ?: ['pergunta' => null, 'tipo_resposta' => null]; // Retorna a pergunta e tipo de resposta
    }

    // Novo método para obter opções de resposta
    public function obterOpcoesResposta($idStep)
    {
        // Sanitizar o parâmetro
        $idStep = intval($idStep); // Convertendo para inteiro para evitar injeções

        // Consultar as opções de resposta com base no id_step
        $sql = "SELECT resposta_opcional, id_step_proximo FROM options WHERE id_step = ?";
        $opcoes = $this->connection->fetchAll($sql, [$idStep], "i");

        return $opcoes ?: []; // Retorna um array de opções ou um array vazio se não houver opções
    }



    // Função para a Etapa 1 -  Função responsável por iniciar o atendimento enviando mensagens iniciais ao usuário via WhatsApp.
    public function iniciarAtendimento()
    {
        $mensagem = "Olá! Sou o assistente virtual da *Cassia Souza Advocacia* ⚖️\n" .
            "Estou aqui para compreender a sua demanda e encaminhá-la da melhor maneira!";
        $this->enviarMensagemWhatsApp($this->numeroUsuario, $mensagem);

        $mensagem = "Para iniciar o seu atendimento, por favor, informe o seu nome.";
        $this->enviarMensagemWhatsApp($this->numeroUsuario, $mensagem);

        $this->atualizarEstado(2, "");
    }


    // Função para a Etapa 2 - Assunto da Consulta
    /**
     * Envia uma lista interativa de opções de consulta tributária para o usuário no WhatsApp.
     * A lista inclui seções específicas para Consultas Tributárias e Outros Assuntos,
     * permitindo que o usuário selecione o tema desejado para a consulta.
     */
    public function enviarConsultaTributaria()
    {
        // Definindo as seções e itens da lista interativa
        $secoes = [
            'Consultas Tributárias' => [
                'row_1_id' => ['titulo' => 'Dívidas Tributárias', 'descricao' => 'Consulta sobre Dívidas Tributárias'],
                'row_2_id' => ['titulo' => 'Planejamento Tributário', 'descricao' => 'Consulta sobre Planejamento Tributário'],
                'row_3_id' => ['titulo' => 'Execução Fiscal', 'descricao' => 'Consulta sobre Execução Fiscal'],
                'row_4_id' => ['titulo' => 'Consultoria Tributária', 'descricao' => 'Consultoria em assuntos tributários'],
            ],
            'Outros Assuntos' => [
                'row_5_id' => ['titulo' => 'Assuntos Tributários', 'descricao' => 'Assuntos tributários diversos'],
                'row_6_id' => ['titulo' => 'Assuntos Não Tributários', 'descricao' => 'Assuntos não tributários'],
            ]
        ];

        $this->enviarListaInterativaWhatsApp(
            $this->numeroUsuario,
            'Selecione o assunto da sua consulta',
            'Escolha um dos itens abaixo:',
            '',
            'Ver opções',
            $secoes
        );
        $this->atualizarEstado(3);
    }


    // Função para a Etapa 3 - Detalhes da Dúvida
    /**
     * Solicita ao usuário que detalhe sua dúvida para fornecer uma orientação mais adequada.
     * Essa mensagem é enviada via WhatsApp e atualiza o estado do assistente para 4.
     */
    public function solicitarDetalhamentoDuvida()
    {
        $mensagem = "Entendi! Poderia detalhar a sua dúvida para que possamos oferecer uma orientação mais adequada?";
        $this->enviarMensagemWhatsApp($this->numeroUsuario, $mensagem);

        $this->atualizarEstado(4);
    }

    // Função para a Etapa 4 - Tipo de Pessoa: PF ou PJ
    /**
     * Solicita ao usuário que informe se a consulta é para uma *Pessoa Física* ou *Pessoa Jurídica*.
     * A mensagem é enviada via WhatsApp com botões de opção para facilitar a resposta.
     * O estado do assistente é atualizado para 5 após o envio da mensagem.
     */
    public function solicitarTipoPessoa()
    {
        $mensagem = "Por favor, informe se a sua consulta é para uma *Pessoa Física* ou *Pessoa Jurídica*:";
        $opcoes = [
            ['button' => 'Pessoa Física'],
            ['button' => 'Pessoa Jurídica']
        ];
        $this->enviarBotoesMensagemWhatsApp($this->numeroUsuario, $mensagem, $opcoes);
        $this->atualizarEstado(5);
    }


    // Função para a Etapa 5 - Solicitar CPF ou CNPJ
    /**
     * Determina se a consulta é para uma *Pessoa Física* ou *Pessoa Jurídica* 
     * e solicita o número correspondente (CNPJ ou CPF) ao usuário.
     * A mensagem é enviada via WhatsApp e o estado do assistente é atualizado para 6,
     * incluindo o tipo de documento solicitado.
     */
    public function solicitarDocumento()
    {
        $tipoPessoa = $this->mensagemUsuario;

        if (strtolower($tipoPessoa) === 'pessoa jurídica') {
            $mensagem = "Entendi! Qual é o seu CNPJ?";
        } else {
            $mensagem = "Entendi! Qual é o seu CPF?";
        }

        $this->enviarMensagemWhatsApp($this->numeroUsuario, $mensagem);
        $tipo_documento = strtolower($tipoPessoa) === 'pessoa jurídica' ? "cnpj" : "cpf";
        $this->atualizarEstado(6, $tipo_documento);
    }

    // Função para a Etapa 6 - Solicitar urgência da demanda
    /**
     * Solicita ao usuário que informe a urgência da sua demanda, 
     * oferecendo três opções: Alta, Média e Baixa. 
     * A mensagem é enviada via WhatsApp e o estado do assistente é atualizado para 7.
     */
    public function solicitarUrgenciaDemanda()
    {
        $mensagem = "Para finalizar, informe qual é a urgência da sua demanda:\n" .
            "• Alta - preciso de uma resposta imediata\n" .
            "• Média - não é urgente, mas preciso de retorno rápido\n" .
            "• Baixa - posso aguardar um pouco mais";
        $this->enviarMensagemWhatsApp($this->numeroUsuario, $mensagem);

        $buttonText = "Qual é a urgência da sua demanda?";
        $opcoes = [
            ['button' => 'Alta'],
            ['button' => 'Média'],
            ['button' => 'Baixa'],
        ];

        $this->enviarBotoesMensagemWhatsApp($this->numeroUsuario, $buttonText, $opcoes);
        $this->atualizarEstado(7);
    }

    // Função para a Etapa 7 - Finalização
    public function etapa7()
    {

        $urgencia = $this->mensagemUsuario;
        $id = $this->atualizarAtendimento('urgencia', $urgencia, 'Finalizado');

        $this->finalizarAtendimento();
        $this->enviarEmailbyID($id);
    }

    public function verificarTipoEvento($tipoEsperado, $mensagemErro)
    {
        if ($this->event_type !== $tipoEsperado) {
            $this->enviarMensagemWhatsApp($this->numeroUsuario, $mensagemErro);
            return false; // Retorna falso se o tipo de evento for diferente do esperado
        }
        return true; // Retorna verdadeiro se o tipo de evento for o esperado
    }

    // Valida o nome do usuário
    public function validarNome()
    {
        // Remove espaços extras
        $nome = trim($this->mensagemUsuario);

        // Verifica se o nome está vazio
        if (empty($nome)) {
            return false;
        }

        // Verifica se contém números
        if (preg_match('/\d/', $nome)) {
            return false;
        }

        // Verifica se contém símbolos ou "?" (exceto letras, espaços e acentos comuns)
        if (preg_match('/[^\p{L}\s]/u', $nome)) {
            return false;
        }

        // Lista de palavras proibidas
        $palavrasProibidasExatas = ['oi', 'ola', 'olá', 'e aí', 'salve', 'bom dia', 'boa tarde', 'boa noite', 'hey', 'hello'];
        $palavrasProibidas = [
            'atendem',
            'vocês',
            'como',
            'eles',
            'você',
            'quem',
            'o que',
            'qual',
            'quais',
            'onde',
            'por que',
            'quando',
            'quanto',
            'quantos',
            'quantas',
            'pra que',
            'para que',
            'será',
            'posso',
            'pode',
            'podes',
            'podemos',
            'podem',
            'devo',
            'devemos',
            'devem',
            'preciso',
            'precisamos',
            'precisa',
            'precisam',
            'quer',
            'querem',
            'queremos',
            'tem',
            'temos',
            'têm',
            'há',
            'havia',
            'somos',
            'são',
            'estou',
            'estamos',
            'está',
            'estão',
            'fui',
            'foi',
            'vamos',
            'venho',
            'vem',
            'veio',
            'vai',
            'vão',
            'têm',
            'tinha',
            'tinham',
            'sabemos',
            'sabem',
            'sabia',
            'sabiam',
            'sabe',
            'poderia',
            'poderiam',
            'posso',
            'devemos',
            'teria',
            'tenho',
            'teriam',
            'há quanto',
            'por quanto',
            'seriam',
            'poderá',
            'queria',
            'estarei',
            'pergunto',
            'gostaria',
            'precisaria',
            'bom dia',
            'boa tarde',
            'boa noite',
            'noite',
            'tarde',
            'prefeitura',
            'contato',
            'entrar',
            'atende em',
            'atende',
            'físico',
            'escritório',


            'atendimento',
            'seus',
            'a gente',
            'quem',
            'qualquer',
            'para onde',
            'porque',
            'quando',
            'quanto',
            'quantidade',
            'porque',
            'onde',
            'como',
            'seja',
            'estamos',
            'está',
            'fomos',
            'você',
            'queria',
            'existem',
            'pode',
            'podes',
            'poderia',
            'deveria',
            'deveria',
            'devo',
            'quero',
            'tinha',
            'tiveram',
            'estou',
            'sabe',
            'sabemos',
            'perguntei',
            'pergunta',
            'alguma',
            'alguns',
            'algumas',
            'nada',
            'tudo',
            'apenas',
            'somente',
            'daqui',
            'entre',
            'entre em',
            'disponível',
            'opções',
            'informa',
            'ajuda',
            'por favor',
            'favor',
            'solicito',
            'gentileza',
            'verifique',
            'agendamento',
            'obrigado',
            'ajuda'
        ];

        // Mapeia os acentos para suas versões sem acento
        $acentos = ['á', 'à', 'â', 'ã', 'é', 'ê', 'í', 'ó', 'ô', 'õ', 'ú', 'ç', 'ä', 'ë', 'ï', 'ö', 'ü'];
        $semAcento = ['a', 'a', 'a', 'a', 'e', 'e', 'i', 'o', 'o', 'o', 'u', 'c', 'a', 'e', 'i', 'o', 'u'];

        // Verifica se contém alguma palavra proibida
        foreach ($palavrasProibidas as $palavra) {
            // Remove os acentos da palavra
            $palavra = strtolower(($palavra));
            $nome = strtolower(trim($nome));
            $palavraSemAcento = str_replace($acentos, $semAcento, $palavra);
            $nome = str_replace($acentos, $semAcento, $nome);

            if (stripos($nome, $palavraSemAcento) !== false) {
                return false;
            }
        }
        foreach ($palavrasProibidasExatas as $palavra) {
            // Remove os acentos da palavra
            $palavra = strtolower(trim($palavra));
            $nome = strtolower(trim($nome));
            $palavraSemAcento = str_replace($acentos, $semAcento, $palavra);
            $nome = str_replace($acentos, $semAcento, $nome);

            if ($nome == $palavraSemAcento) {
                return false;
            }
        }

        return true;
    }

    // Valida se a mensagem contém pelo menos 5 palavras
    public function validarMensagemDetalhes($mensagem, $mensagemErro)
    {
        $mensagemValida = str_word_count(trim($mensagem)) >= 3;
        if (!$mensagemValida)
            $this->enviarMensagemWhatsApp($this->numeroUsuario, $mensagemErro);
        return $mensagemValida;
    }

    // Valida o tipo de pessoa
    public function validarTipoPessoa()
    {
        return strtolower($this->mensagemUsuario) === 'pessoa física' || strtolower($this->mensagemUsuario) === 'pessoa jurídica';
    }

    // Valida CPF ou CNPJ
    public function validarDocumento($documento, $tipo_documento)
    {
        $documento = preg_replace('/\D/', '', $documento);

        if ($tipo_documento === 'cpf') {
            return $this->validarCPF($documento);
        } else {
            return $this->validarCNPJ($documento);
        }
    }
    // Função para finalizar o atendimento
    public function finalizarAtendimento()
    {
        $mensagem = "Agradecemos as informações!\n\n" .
            "*Nossa equipe irá analisar a sua demanda e retornará em breve.* \n\n" .
            "_Siga-nos nas redes sociais para acompanhar nossos conteúdos tributários_:\nhttps://www.instagram.com/cassia.souza.adv/";

        $this->enviarMensagemWhatsApp($this->numeroUsuario, $mensagem);
        $this->atualizarEstado(8); // Finaliza o atendimento
    }

    /**
     * Finaliza o atendimento atual, atualiza a urgência da demanda e envia um e-mail com os detalhes do atendimento.
     */
    public function enviarEmailbyID($id)
    {
        // Recupera os detalhes do atendimento utilizando o ID
        checkAbandono();

        $get = $this->getById($id);
        $this->enviarEmailAtendimento($get);
    }

    public function marcarAtendimentoComoAbandono($id)
    {
        $updateSql = "UPDATE atendimento 
                      SET status = 'Abandono', 
                          updated_at = CURRENT_TIMESTAMP 
                      WHERE id = ? 
                      LIMIT 1";

        // Executa a query passando o ID como parâmetro
        $this->connection->query($updateSql, [$id], "i");
    }

    // Função para enviar um e-mail com os dados do atendimento
    public function enviarEmailAtendimento($get)
    {

        // Cria uma nova instância de Email
        $email = new Email();

        // Prepara os dados que serão enviados no e-mail
        $dadosEmail = [
            'assunto' => $get['assuntoEmail'] ?? 'Chatbot do Whatsapp',
            'numero_usuario' => $get['numero_usuario'],
            'nome' => $get['nome'],
            'tipo_pessoa' => $get['tipo_pessoa'],
            'documento' => $get['documento'],
            'assuntoUsuario' => $get['assunto'],
            'detalhes' => $get['detalhes'],
            'urgencia' => $get['urgencia'],
        ];

        // Envia o e-mail com os dados do atendimento
        $email->send($dadosEmail);
    }
}
