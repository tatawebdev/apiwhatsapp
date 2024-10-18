<?php

use Models\Connection;
use WhatsApp\InteractiveMessages;
use WhatsApp\Message;

include_once __DIR__ . '/Connection.php';

class newChatbot
{
    protected $connection; // Instância de conexão será armazenada aqui
    public $numeroUsuario;
    public $mensagemUsuario;
    public $event_type;
    public $last_message_id;




    public function processarEntrada($data)
    {


        if (empty($data['celular']) || empty($data['message']))
            return;

        $this->numeroUsuario = $data['celular'];
        $this->mensagemUsuario = $data['message'];
        $this->last_message_id = $data['message_id'];
        $this->event_type = $data['event_type'];

        $telefone = $this->numeroUsuario; // Telefone do usuário


        $usuario = ChatbotInteracoesUsuario::addUserAndInteraction($telefone);
        extract($usuario);

        $step = new ChatbotSteps;
        $step = $step->getStepById($id_step)->selected;
        extract($step);

        if (!empty($nome_da_funcao)) {
            if (function_exists($nome_da_funcao)) {
                $step['step_id'] =  $step['id'];
                unset($step['id']);
                $params = array_merge($step, $usuario);
                $params['data'] = $data;

                $resultado_validacao = call_user_func($nome_da_funcao, $params);
                if ($resultado_validacao['result']) {
                    ChatbotInteracoesUsuario::updateStepById($params['interacoes_id'], $params['id_step_proximo']);
                } else {

                    foreach ($resultado_validacao['message'] as $message) {
                        $this->enviarMensagemWhatsApp($message);
                    }
                    return;
                }
            }
        }

        switch ($tipo_interacao) {
            case 'message_button':;
                $this->enviarMensagemComBotoes($pergunta, $id_step);
                break;
            case 'message_interactive':;
                $tituloLista = 'Escolha uma das opções abaixo';
                $subtituloLista = 'Por favor, selecione uma opção:';
                $textoAgradecimento = 'Obrigado por escolher!';
                $verOpcoes = 'Ver Opções';
                $this->enviarListaInterativaComDados(
                    $id_step,
                    $tituloLista,
                    $subtituloLista,
                    $textoAgradecimento,
                    $verOpcoes
                );
                break;

            default:
                $this->enviarMensagemWhatsApp($pergunta);
        }
    }


    public function enviarListaInterativaWhatsApp($tituloLista, $subtituloLista, $textoAgradecimento, $verOpcoes, $secoes, $numero = null)
    {

        if ($numero === null) {
            $numero = $this->numeroUsuario;
        }

        // Instância de mensagens interativas
        $interactiveMessages = InteractiveMessages::getInstance();
        $interactiveMessages->setRecipientNumber($numero);


        // Adiciona seções e itens à mensagem interativa
        foreach ($secoes as $itemId => $itens) {

                $interactiveMessages->addSection($itens['secao_titulo'], $itemId, $itens['titulo'], $itens['descricao']);
        }

        // Envia a mensagem com lista interativa
        $interactiveMessages->sendListMessage($tituloLista, $subtituloLista, $textoAgradecimento, $verOpcoes);
    }


    public function enviarListaInterativaComDados($id_step, $tituloLista, $subtituloLista, $textoAgradecimento, $verOpcoes, $numero = null)
    {
        $secoes = ChatbotOptions::getListaInterativaWhatsApp($id_step);
        if (empty($secoes)) {
            return;
        }

        $this->enviarListaInterativaWhatsApp($tituloLista, $subtituloLista, $textoAgradecimento, $verOpcoes, $secoes, $numero);
    }

    // Função para enviar mensagem via API do WhatsApp
    public function enviarMensagemWhatsApp($mensagem, $numero = null, $previewUrl = false)
    {
        if ($numero === null) {
            $numero = $this->numeroUsuario;
        }

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
    public function enviarBotoesMensagemWhatsApp($buttonText, $opcoes, $numero = null)
    {

        if ($numero === null) {
            $numero = $this->numeroUsuario;
        }


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
        $this->enviarMensagemComBotoes($buttonText, $opcoes, $numero);
    }

    private function enviarMensagemComBotoes($buttonText, $id_step, $numero = null)
    {

        $buttons = ChatbotOptions::getBotoesMensagemWhatsApp($id_step);

        if ($numero === null) {
            $numero = $this->numeroUsuario;
        }

        // Obtém a instância de InteractiveMessages e define o número do destinatário
        $interactiveMessages = InteractiveMessages::getInstance();
        $interactiveMessages->setRecipientNumber($numero);

        // Envia a mensagem com os botões
        $interactiveMessages->sendButtonMessage($buttonText, $buttons);
    }
}
