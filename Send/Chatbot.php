<?php

namespace Chatbot;

use Models\Base\Send\WhatsApp\InteractiveMessages;
use Models\Base\Send\WhatsApp\Message;

require_once __DIR__ . "/Email.php";
include_once __DIR__ . '/CNPJConsulta.php';
class Chatbot
{

    public $mysqli;
    public $numeroUsuario;
    public $mensagemUsuario;
    public $event_type;
    public $last_message_id;

    public function __construct($host, $dbname, $username, $password)
    {
        $this->mysqli = new \mysqli($host, $username, $password, $dbname);
        if ($this->mysqli->connect_error) {
            die("Conexão falhou: " . $this->mysqli->connect_error);
        }
        $this->mysqli->set_charset("utf8");
    }
    public function atualizarAtendimento($coluna, $valor, $status = "Em Andamento")
    {
        // Sanitizar e validar os parâmetros
        $coluna = htmlspecialchars($coluna);
        $valor = htmlspecialchars($valor);
        $numero_usuario = htmlspecialchars($this->numeroUsuario);
        $status = htmlspecialchars($status);

        // Verificar se o registro existe e o status não é 'Em Andamento' (limitando a 1 registro)
        $checkSql = "SELECT COUNT(*) FROM atendimento WHERE numero_usuario = ? AND status = 'Em Andamento' order by id desc LIMIT 1";
        $stmt = $this->mysqli->prepare($checkSql);
        $stmt->bind_param('s', $numero_usuario);
        $stmt->execute();
        $stmt->bind_result($exists);
        $stmt->fetch();
        $stmt->close();

        if ($exists > 0) {
            // Atualizar o registro existente
            $allowedColumns = ['nome', 'tipo_pessoa', 'documento', 'assunto', 'detalhes', 'urgencia'];

            if (in_array($coluna, $allowedColumns)) {
                $updateSql = "UPDATE atendimento SET 
                    $coluna = ?, 
                    status = ?, 
                    updated_at = CURRENT_TIMESTAMP 
                    WHERE numero_usuario = ? AND status = 'Em Andamento' LIMIT 1"; // Adicionando LIMIT 1 aqui também

                $stmt = $this->mysqli->prepare($updateSql);
                $stmt->bind_param('sss', $valor, $status, $numero_usuario);
                $stmt->execute();
                $stmt->close();

                // Obter o ID do registro atualizado
                $getIdSql = "SELECT id FROM atendimento WHERE numero_usuario = ? AND status = ? order by id desc LIMIT 1"; // Adicionando LIMIT 1 aqui
                $stmt = $this->mysqli->prepare($getIdSql);
                $stmt->bind_param('ss', $numero_usuario, $status);
                $stmt->execute();
                $stmt->bind_result($id);
                $stmt->fetch();
                $stmt->close();

                return $id; // Retorna o ID do registro atualizado
            }
        } else {
            // Inserir novo atendimento
            $insertSql = "INSERT INTO atendimento (numero_usuario, $coluna, status, created_at, updated_at) VALUES (?, ?, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)";

            $stmt = $this->mysqli->prepare($insertSql);
            $stmt->bind_param('sss', $numero_usuario, $valor, $status);
            $stmt->execute();

            // Obter o ID do registro recém-inserido
            $id = $this->mysqli->insert_id; // Recupera o ID gerado
            $stmt->close();

            return $id; // Retorna o ID do registro inserido
        }

        return null; // Retorna null se nenhuma operação foi realizada
    }



    public function getByNumeroUsuario()
    {
        // Sanitizar o parâmetro
        $numero_usuario = htmlspecialchars($this->numeroUsuario);

        // Consultar a tabela atendimento pelo numero_usuario
        $sql = "SELECT * FROM atendimento WHERE numero_usuario = ? AND status = 'Em Andamento'";
        $stmt = $this->mysqli->prepare($sql);
        if (!$stmt) {
            die("Erro ao preparar a consulta: " . $this->mysqli->error);
        }

        $stmt->bind_param('s', $numero_usuario);
        $stmt->execute();

        // Obter os resultados
        $result = $stmt->get_result();

        // Verificar se há registros
        if ($result->num_rows > 0) {
            // Retornar todos os registros encontrados
            $atendimentos = [];
            while ($row = $result->fetch_assoc()) {
                $atendimentos[] = $row;
            }
            $stmt->close();
            return $atendimentos; // Retorna um array de registros
        } else {
            $stmt->close();
            return null; // Retorna null se não houver registros
        }
    }


    public function getById($id)
    {
        // Sanitizar o parâmetro
        $id = intval($id); // Convertendo para inteiro para evitar injeções

        // Consultar a tabela atendimento pelo id
        $sql = "SELECT * FROM atendimento WHERE id = ?";
        $stmt = $this->mysqli->prepare($sql);

        if (!$stmt) {
            die("Erro ao preparar a consulta: " . $this->mysqli->error);
        }

        $stmt->bind_param('i', $id);
        $stmt->execute();

        // Obter os resultados
        $result = $stmt->get_result();

        // Verificar se há registros
        if ($result->num_rows > 0) {
            // Retornar o registro encontrado
            $atendimento = $result->fetch_assoc();
            $stmt->close();
            return $atendimento; // Retorna o registro
        } else {
            $stmt->close();
            return null; // Retorna null se não houver registro
        }
    }


    // Função para obter o estado da conversa do usuário
    public function obterEstado()
    {
        $sql = "SELECT id_step, type_documento FROM interacoes WHERE numero_usuario = ? ORDER BY ultima_interacao DESC LIMIT 1";
        $stmt = $this->mysqli->prepare($sql);

        if ($stmt === false) {
            throw new \Exception("Erro na preparação da consulta: " . $this->mysqli->error);
        }

        $stmt->bind_param("s", $this->numeroUsuario);
        $stmt->execute();

        // Bindando os resultados das colunas
        $stmt->bind_result($idStep, $type_documento);
        $result = $stmt->fetch(); // Obtém o resultado da consulta
        $stmt->close();

        // Verifica se houve um resultado
        if (!$result) {
            return ['etapa' => null, 'tipo_documento' => null]; // Retorna nulo se não houver resultado
        }

        return ['etapa' => $idStep, 'tipo_documento' => $type_documento]; // Retorna os valores encontrados
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

        $stmt = $this->mysqli->prepare($sql);
        $stmt->bind_param("s", $this->numeroUsuario);
        $stmt->execute();
        $stmt->bind_result($pergunta, $tipo_resposta);
        $stmt->fetch();
        $stmt->close();

        // Se a pergunta estiver vazia, busca a primeira pergunta do banco
        if (empty($pergunta)) {
            $sql = "SELECT s.pergunta, s.tipo_resposta FROM steps s
                    ORDER BY s.id ASC 
                    LIMIT 1";

            $stmt = $this->mysqli->prepare($sql);
            $stmt->execute();
            $stmt->bind_result($pergunta, $tipo_resposta);
            $stmt->fetch();
            $stmt->close();
        }

        return ['pergunta' => $pergunta, 'tipo_resposta' => $tipo_resposta];
    }


    // Novo método para obter opções de resposta
    public function obterOpcoesResposta($idStep)
    {
        $sql = "SELECT resposta_opcional, id_step_proximo FROM options WHERE id_step = ?";
        $stmt = $this->mysqli->prepare($sql);
        $stmt->bind_param("i", $idStep);
        $stmt->execute();
        $result = $stmt->get_result();

        $opcoes = [];
        while ($row = $result->fetch_assoc()) {
            var_dump($row);
            $opcoes[] = $row;
        }
        $stmt->close();

        return $opcoes;
    }

    // Função para processar a entrada do usuário
    public function processarEntradaTeste($data)
    {

    }
    public function processarEntrada($data)
    {

        $filePath = __DIR__ . '/error_log.txt';
        $errorMessage = '[' . date('Y-m-d H:i:s') . '] ' . json_encode($data) . PHP_EOL;
        file_put_contents($filePath, $errorMessage, FILE_APPEND);

        if (!isset($data['celular']) || !isset($data['message'])) {
            return;
        }

        $this->numeroUsuario = $data['celular'];
        $this->mensagemUsuario = $data['message'];
        $this->last_message_id = $data['message_id'];
        $this->event_type = $data['event_type'];

        extract($this->obterEstado());



        // Caso seja a primeira mensagem do dia ou não tenha etapa, define a etapa como 1
        if ($etapa === null || $this->isPrimeiraMensagemDoDia()) {
            $etapa = 1;
        }
        switch ($etapa) {
            case 1:
                $this->etapa1();
                break;
            case 2:
                $this->etapa2();
                break;
            case 3:
                $this->etapa3();
                break;
            case 4:
                $this->etapa4($tipo_documento);
                break;
            case 5:
                $this->etapa5();
                break;
            case 6:
                $this->etapa6();
                break;
            case 7:
                $this->etapa7();
                break;

            default:
                $this->finalizarAtendimento();
                break;
        }

        // // Atualiza o estado do usuário após processar a etapa
        // $this->atualizarEstado( $etapa);

        // // Envia a resposta da etapa atual
        // $etapaAtual = $this->obterEtapaAtual($this->numeroUsuario);
        // // $this->enviarMensagemWhatsApp($this->numeroUsuario, json_encode($etapaAtual));
    }

    // Função para a Etapa 1 - Solicitar nome do usuário
    public function etapa1()
    {
        // Primeira mensagem de introdução com emoji e texto em negrito
        $mensagem = "Olá! Sou o assistente virtual da *Cassia Souza Advocacia* ⚖️\n" .
            "Estou aqui para compreender a sua demanda e encaminhá-la da melhor maneira!";
        $this->enviarMensagemWhatsApp($this->numeroUsuario, $mensagem);

        // Segunda mensagem solicitando o nome
        $mensagem = "Para iniciar o seu atendimento, por favor, informe o seu nome.";
        $this->enviarMensagemWhatsApp($this->numeroUsuario, $mensagem);

        // Atualiza o estado para a próxima etapa
        $this->atualizarEstado(2, "");
    }



    // Função para a Etapa 2 - Solicitar se é Pessoa Física ou Jurídica
    public function etapa2()
    {


        
        if ($this->event_type != 'message_text' || empty(trim($this->mensagemUsuario)) || trim($this->mensagemUsuario) === '.' || preg_match('/^\d+$/', trim($this->mensagemUsuario))) {
            $mensagem = "Por favor, digite um seu nome para continuar.";
            $this->enviarMensagemWhatsApp($this->numeroUsuario, $mensagem);
            return; // Finaliza a execução da função
        }

        // Verifica se o nome tem menos de 3 caracteres
        if (strlen(trim($this->mensagemUsuario)) < 5) {
            $mensagem = "O nome deve ter pelo menos 5 caracteres. Por favor, digite seu nome.";
            $this->enviarMensagemWhatsApp($this->numeroUsuario, $mensagem);
            return; // Finaliza a execução da função
        }

        $nome = $this->mensagemUsuario; // Supondo que a mensagem seja o nome do usuário

        $this->atualizarAtendimento('nome', $nome);

        $mensagem = "Por favor, informe se a sua consulta é para uma *Pessoa Física* ou *Pessoa Jurídica*:";
        $opcoes = [
            ['button' => 'Pessoa Física'],
            ['button' => 'Pessoa Jurídica']
        ];
        $this->enviarBotoesMensagemWhatsApp($this->numeroUsuario, $mensagem, $opcoes);
        $this->atualizarEstado(3);
    }


    // Função para a Etapa 3 - Solicitar CPF ou CNPJ
    public function etapa3()
    {

        if ($this->event_type !== 'message_button') {
            $mensagem = "Por favor, utilize o botão para informar se a sua consulta é para uma *Pessoa Física* ou *Pessoa Jurídica*.";
            $this->enviarMensagemWhatsApp($this->numeroUsuario, $mensagem);
            return; // Finaliza a execução da função
        }

        $tipoPessoa = $this->mensagemUsuario; // Supondo que o usuário responda se é PJ ou PF

        $this->atualizarAtendimento('tipo_pessoa', $tipoPessoa);

        if (strtolower($tipoPessoa) === 'pessoa jurídica') {
            $mensagem = "Entendi! Qual é o seu CNPJ?";
        } else {
            $mensagem = "Entendi! Qual é o seu CPF?";
        }
        $this->enviarMensagemWhatsApp($this->numeroUsuario, $mensagem);
        $tipo_documento = strtolower($tipoPessoa) === 'pessoa jurídica'  ? "cnpj" : "cpf";
        $this->atualizarEstado(4, $tipo_documento);
    }

    // Função para a Etapa 4 - Solicitar o assunto da consulta
    public function etapa4($tipo_documento)
    {
        // Verifica se o evento não é do tipo texto
        if ($this->event_type !== 'message_text') {
            $mensagem = "Por favor, digite seu CPF ou CNPJ.";
            $this->enviarMensagemWhatsApp($this->numeroUsuario, $mensagem);
            return; // Finaliza a execução da função
        }

        $documento = $this->mensagemUsuario; // Supondo que o usuário forneça CPF ou CNPJ
        $documento = preg_replace('/\D/', '', $documento);


        // Valida o tipo de documento e chama a função correspondente
        if ($tipo_documento === 'cpf') {
            if (!$this->validarCPF($documento)) {
                $mensagem = "O CPF fornecido é inválido. Por favor, digite um CPF válido.";
                $this->enviarMensagemWhatsApp($this->numeroUsuario, $mensagem);
                return; // Finaliza a execução da função
            }
        } elseif ($tipo_documento === 'cnpj') {
            if (!$this->validarCNPJ($documento)) {
                $mensagem = "O CNPJ fornecido é inválido. Por favor, digite um CNPJ válido.";
                $this->enviarMensagemWhatsApp($this->numeroUsuario, $mensagem);
                return; // Finaliza a execução da função
            }
        }
        $this->atualizarAtendimento('documento', $documento);

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

        // Envia a lista interativa para o usuário
        $this->enviarListaInterativaWhatsApp(
            $this->numeroUsuario,
            'Selecione o assunto da sua consulta',
            'Escolha um dos itens abaixo:',
            '',
            'Ver opções',
            $secoes
        );

        // Atualiza o estado para a próxima etapa
        $this->atualizarEstado(5);
    }

    // Função para a Etapa 5 - Solicitar mais detalhes da dúvida
    public function etapa5()
    {
        if ($this->event_type !== 'interactive') {
            $mensagem = "Por favor, selecione uma opção válida da lista interativa.";
            $this->enviarMensagemWhatsApp($this->numeroUsuario, $mensagem);
            return; // Finaliza a execução da função
        }
        $assunto = $this->mensagemUsuario; // Supondo que o usuário escolha o assunto

        $this->atualizarAtendimento('assunto', $assunto);


        // Mensagem solicitando mais detalhes
        $mensagem = "Entendi! Poderia detalhar a sua dúvida para que possamos oferecer uma orientação mais adequada?";
        $this->enviarMensagemWhatsApp($this->numeroUsuario, $mensagem);

        // Atualiza o estado para indicar que o usuário está na etapa 6
        $this->atualizarEstado(6);
    }

    // Função para a Etapa 6 - Solicitar urgência da demanda
    public function etapa6()
    {
        // Verifica se o tipo de evento é "message_text"
        if ($this->event_type !== 'message_text') {
            $mensagem = "Por favor, envie uma mensagem de texto com detalhes sobre sua dúvida.";
            $this->enviarMensagemWhatsApp($this->numeroUsuario, $mensagem);
            return; // Finaliza a execução da função
        }

        // Verifica se a mensagem tem pelo menos 5 palavras ou 15 caracteres
        if (str_word_count(trim($this->mensagemUsuario)) < 5 && strlen(trim($this->mensagemUsuario)) < 15) {
            $mensagem = "A mensagem precisa ter pelo menos 5 palavras ou 15 caracteres. Por favor, forneça mais detalhes.";
            $this->enviarMensagemWhatsApp($this->numeroUsuario, $mensagem);
            return; // Finaliza a execução da função
        }

        $detalhes = $this->mensagemUsuario;

        $this->atualizarAtendimento('detalhes', $detalhes);


        // Mensagem solicitando a urgência da demanda
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

        // Enviar a mensagem com os botões
        $this->enviarBotoesMensagemWhatsApp($this->numeroUsuario, $buttonText, $opcoes);

        // Atualiza o estado para a próxima etapa
        $this->atualizarEstado(7);
    }
    public function etapa7()
    {
        // Verifica se o tipo de evento é "message_button"
        if ($this->event_type !== 'message_button') {
            $mensagem = "Por favor, escolha a urgência da sua demanda clicando em um dos botões abaixo:";
            $this->enviarMensagemWhatsApp($this->numeroUsuario, $mensagem);
            return; // Finaliza a execução da função
        }
        $urgencia = $this->mensagemUsuario;

        $id = $this->atualizarAtendimento('urgencia', $urgencia, 'Finalizado');

        $get = $this->getById($id);

        $email = new \Email();
        $dadosEmail = [
            'assunto' => 'Chatbot do Whatsapp',
            'numero_usuario' => $get['numero_usuario'],
            'nome' => $get['nome'],
            'tipo_pessoa' => $get['tipo_pessoa'],
            'documento' => $get['documento'],
            'assuntoUsuario' => $get['assunto'],
            'detalhes' => $get['detalhes'],
            'urgencia' => $get['urgencia'],
        ];
        $email->send($dadosEmail);
        

        $this->atualizarEstado(8);


        // Chama a função para finalizar o atendimento
        $this->finalizarAtendimento();




    }

    // Função para finalizar o atendimento
    public function finalizarAtendimento()
    {
        $mensagem = "Agradecemos as informações!\n\n" .
            "*Nossa equipe irá analisar a sua demanda e retornará em breve.* \n\n" .
            "_Siga-nos nas redes sociais para acompanhar nossos conteúdos tributários_:\nhttps://www.instagram.com/cassia.souza.adv/";

        $this->enviarMensagemWhatsApp($this->numeroUsuario, $mensagem);
        $this->atualizarEstado(null); // Finaliza o atendimento
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
        for ($i = 0, $j = 5, $soma = 0; $i < 12; $i++)
        {
            $soma += $cnpj[$i] * $j;
            $j = ($j == 2) ? 9 : $j - 1;
        }
    
        $resto = $soma % 11;
    
        if ($cnpj[12] != ($resto < 2 ? 0 : 11 - $resto))
            return false;
    
        // Valida segundo dígito verificador
        for ($i = 0, $j = 6, $soma = 0; $i < 13; $i++)
        {
            $soma += $cnpj[$i] * $j;
            $j = ($j == 2) ? 9 : $j - 1;
        }
    
        $resto = $soma % 11;
    
        return $cnpj[13] == ($resto < 2 ? 0 : 11 - $resto);
    }
    
    


    public function isPrimeiraMensagemDoDia()
    {
        $sql = "SELECT ultima_interacao FROM interacoes WHERE numero_usuario = ? ORDER BY ultima_interacao DESC LIMIT 1";
        $stmt = $this->mysqli->prepare($sql);
        $stmt->bind_param("s", $this->numeroUsuario);
        $stmt->execute();
        $stmt->bind_result($ultimaInteracao);
        $stmt->fetch();
        $stmt->close();

        // Verificar se a última interação é do dia atual
        if ($ultimaInteracao) {
            $ultimaData = new \DateTime($ultimaInteracao);
            $dataAtual = new \DateTime();

            return $ultimaData->format('Y-m-d') !== $dataAtual->format('Y-m-d');
        }

        // Se não houver interação registrada, considera como primeira mensagem do dia
        return true;
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

    // Função para atualizar o estado da conversa do usuário
    public function atualizarEstado($idStep, $typedocumento = null)
    {
        // Verifica se o ID do step é um número inteiro
        if (!is_int($idStep)) {
            throw new \InvalidArgumentException("ID do step deve ser um número inteiro.");
        }

        // Prepara a consulta SQL para verificar se a interação já existe
        $sqlCheck = "SELECT COUNT(*) FROM interacoes WHERE numero_usuario = ?";
        $stmtCheck = $this->mysqli->prepare($sqlCheck);
        if ($stmtCheck === false) {
            throw new \Exception("Erro na preparação da consulta de verificação: " . $this->mysqli->error);
        }

        // Liga os parâmetros e executa a consulta de verificação
        $stmtCheck->bind_param("s", $this->numeroUsuario);
        $stmtCheck->execute();
        $stmtCheck->bind_result($count);
        $stmtCheck->fetch();
        $stmtCheck->close();  // Fecha a consulta de verificação

        // Determina se deve atualizar ou inserir
        if ($count > 0) {
            // Atualiza a interação existente
            $sqlUpdate = "UPDATE interacoes SET ultima_interacao = NOW(), id_step = ?"
                . ($typedocumento !== null ? ", type_documento = ?" : "") . " WHERE numero_usuario = ?";

            $stmt = $this->mysqli->prepare($sqlUpdate);
            if ($stmt === false) {
                throw new \Exception("Erro na preparação da consulta de atualização: " . $this->mysqli->error);
            }

            // Liga os parâmetros para a consulta de atualização
            if ($typedocumento !== null) {
                $stmt->bind_param("iss", $idStep, $typedocumento, $this->numeroUsuario);
            } else {
                $stmt->bind_param("is", $idStep, $this->numeroUsuario);
            }
        } else {
            // Insere uma nova interação
            $sqlInsert = "INSERT INTO interacoes (numero_usuario, id_step, ultima_interacao"
                . ($typedocumento !== null ? ", type_documento" : "") . ") VALUES (?, ?, NOW()"
                . ($typedocumento !== null ? ", ?" : "") . ")";

            $stmt = $this->mysqli->prepare($sqlInsert);
            if ($stmt === false) {
                throw new \Exception("Erro na preparação da consulta de inserção: " . $this->mysqli->error);
            }

            // Liga os parâmetros para a consulta de inserção
            if ($typedocumento !== null) {
                $stmt->bind_param("sis", $this->numeroUsuario, $idStep, $typedocumento);
            } else {
                $stmt->bind_param("si", $this->numeroUsuario, $idStep);
            }
        }

        // Executa a consulta e verifica se houve erros
        if (!$stmt->execute()) {
            throw new \Exception("Erro na execução da consulta: " . $stmt->error);
        }

        // Fecha a declaração preparada
        $stmt->close();
    }




    // Destrutor para fechar a conexão com o banco de dados
    public function __destruct()
    {
        $this->mysqli->close();
    }
}
