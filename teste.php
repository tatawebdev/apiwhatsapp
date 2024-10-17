<?php
define('PATH', __DIR__  . '/');

include_once __DIR__ . '/autoload.php';
include_once __DIR__ . '/app/webhook_handler.php';

$newChatbot = new newChatbot();
session_start();

// if (!isset($_SESSION['respostas'])) {
$_SESSION['respostas'] = [];
// }

$data = [
    'celular' => '5511951936777',
    'message' => 'Jeremias',
    'message_id' => '1',
    'event_type' => 'text'
];


$respostaBot = $newChatbot->processarEntrada($data);

var_dump($respostaBot);

exit;



if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mensagem'])) {
    $data = [
        'celular' => $_POST['telefone'],
        'message' => $_POST['mensagem'],
        'message_id' => '1',
        'event_type' => 'text'
    ];


    $respostaBot = $newChatbot->processarEntrada($data);

    $_SESSION['respostas'][] = ['usuario' => $entradaUsuario, 'bot' => $respostaBot];
}




include_once PATH_VIEW . 'chatbot.html';
