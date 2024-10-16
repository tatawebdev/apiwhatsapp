<?php

use Chatbot\Chatbot;

include_once __DIR__ .'/env.php';
include_once __DIR__ . '/Send/Chatbot.php';
// CNPJ da empresa que você deseja consultar
$dbHost = env('DB_HOST');
$dbUser = env('DB_USER');
$dbName = env('DB_NAME');
$dbPassword = env('DB_PASSWORD');

define('HOST_EMAIL', env('HOST_EMAIL'));
define('USERNAME_EMAIL', env('USERNAME_EMAIL'));
define('PASSWORD_EMAIL', env('PASSWORD_EMAIL'));
define('NOME_EMPRESA', env('NOME_EMPRESA'));
define('EMAIL_PRINCIPAL', env('EMAIL_PRINCIPAL'));


$chatbot = new Chatbot($dbHost, $dbUser, $dbName, $dbPassword);
$get = $chatbot->getById(1);


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
var_dump($email);