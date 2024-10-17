<?php



use WhatsApp\Message;
use WhatsApp\WebhookProcessor;

try {
    $config = ENV;

    $chatbot = new Chatbot($config['DB_HOST'], $config['DB_USER'], $config['DB_NAME'], $config['DB_PASSWORD']);

    if (WebhookProcessor::isPOST()) {
        WebhookProcessor::debugOn();
        $webhookInfo = WebhookProcessor::tratarWebhookWhatsApp();

        $objMensagem = Message::getInstance();
        $objMensagem->setRecipientNumber($webhookInfo['celular']);

        switch ($webhookInfo['event_type']) {
            case 'audio':
            case 'unsupported':
            case 'contacts':
            case 'image':
            case 'document':
            case 'sticker':
            case 'status':
                break;
            case 'interactive':
            case 'message_text':
            case 'message_button':
            case 'message_interactive':
            case 'text':
                $testNumbersString = env('TEST_NUMBERS', '');
                $numerosTeste = array_map('trim', explode(',', $testNumbersString));
                if (in_array($webhookInfo['api_phone_number'], $numerosTeste)) {
                    $newChatbot = new newChatbot();
                    $respostaBot = $newChatbot->processarEntrada($data);
                } else {
                    $chatbot->processarEntrada($webhookInfo);
                }
                break;
            default:
                $objMensagem->sendMessageText($webhookInfo['event_type']);
                $objMensagem->sendMessageText($webhookInfo['json']);
                break;
        }

        $objMensagem->sendMultiMessageText();
    } else {
        echo $_REQUEST['hub_challenge'] ?? '';
    }
} catch (Exception $e) {
    logError('Erro ao conectar ao banco de dados: ' . $e->getMessage() . ' na linha ' . $e->getLine() . ' em ' . $e->getFile());
}
