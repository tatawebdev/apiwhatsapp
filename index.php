<?php

function loadEnv($filePath = '.env') {
    if (!file_exists($filePath)) {
        return;
    }

    $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) {
            continue;
        }

        list($key, $value) = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);

        if (!array_key_exists($key, $_ENV)) {
            $_ENV[$key] = $value;
        }
    }
}

function env($key, $default = null) {
    return $_ENV[$key] ?? $default;
}
loadEnv();

function logError($message)
{
    $filePath = __DIR__ . '/error_log.txt';
    $errorMessage = '[' . date('Y-m-d H:i:s') . '] ' . $message . PHP_EOL;
    file_put_contents($filePath, $errorMessage, FILE_APPEND);
    echo $message;
}


// Configuração do manipulador de erro
set_error_handler(function ($errno, $errstr, $errfile, $errline) {
    logError("Erro: [$errno] $errstr - $errfile:$errline");
});

// Configuração do manipulador de exceção
set_exception_handler(function ($exception) {
    // Logando a mensagem da exceção, o arquivo e a linha onde ocorreu
    logError("Exceção: " . $exception->getMessage() .
        " em " . $exception->getFile() .
        " na linha " . $exception->getLine());
});

define('TOKEN_WHATSAPP', env('TOKEN_WHATSAPP'));
define('API_PHONE_PRODUCAO', env('API_PHONE_PRODUCAO'));

function getURL_MESSAGENS_WHATSAPP($id_phone = null)
{
    return 'https://graph.facebook.com/v20.0/' . (!$id_phone ? API_PHONE_ID : $id_phone) . '/messages';
}

$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'];


define('URL_APP', "$protocol://$host/");
define('ASSETS', URL_APP .  "assets/");
define('IMAGES', URL_APP .  "images/");
define('UPLOADS', URL_APP .  "uploads/");
define('DOCUMENTOS_VISUALIZAR', URL_APP .  "documentos/visualizar/");

// Diretórios do Aplicativo
define('PATH_APP', __DIR__ . '/');

define('PATH_WHATSAPP', PATH_APP . 'WhatsApp/');


define('PATH_MODELS', PATH_APP . 'Send/');

// URLs
define('PATH_FUNCTIONS', PATH_APP . 'Functions/');


include_once __DIR__ . '/autoload.php';

use Chatbot\Chatbot;
use Models\Base\Send\WhatsApp\Message;
use Models\Base\Send\WhatsApp\WebhookProcessor;




try {
    $dbHost = env('DB_HOST');
    $dbUser = env('DB_USER');
    $dbName = env('DB_NAME');
    $dbPassword = env('DB_PASSWORD');
    
    $chatbot = new Chatbot($dbHost, $dbUser, $dbName, $dbPassword);
    

    if (WebhookProcessor::isPOST()) {
        WebhookProcessor::debugOn();


        $webhookInfo = WebhookProcessor::tratarWebhookWhatsApp();




        $objMensagem = Message::getInstance();


        $objMensagem->setRecipientNumber($webhookInfo['celular']);
        // $objMensagem->sendMessageText($webhookInfo['json']);
        // $objMensagem->addMessageText('ok');
        // $objMensagem->sendMessageText('a');


        switch ($webhookInfo['event_type']) {
            case 'audio':
                break;
            case 'unsupported':
                break;
            case 'contacts':
                break;
            case 'image':
                break;
            case 'document':
                break;
            case 'sticker':
                break;
            case 'status':
                // var_dump($webhookInfo['status']);
                break;
            case 'interactive':
            case 'message_text':
            case 'message_button':
            case 'message_interactive':
            case 'text':

                $testNumbersString = env('TEST_NUMBERS', '');
                $numerosTeste = array_map('trim', explode(',', $testNumbersString));

                // Verifica se o número está no array de números de teste
                if (in_array($webhookInfo['api_phone_number'], $numerosTeste)) {
                    $chatbot->processarEntradaTeste($webhookInfo);
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
