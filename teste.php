<?php

use Chatbot\Chatbot;

include_once __DIR__ . '/Send/Chatbot.php';
// CNPJ da empresa que você deseja consultar
$dbHost = env('DB_HOST');
$dbUser = env('DB_USER');
$dbName = env('DB_NAME');
$dbPassword = env('DB_PASSWORD');

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
