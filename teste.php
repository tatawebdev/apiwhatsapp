<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Formulário Chatbot</title>
</head>
<body>
    <h1>Testar Chatbot</h1>
    <form action="" method="GET">
        <label for="tipo_documento">Tipo de Documento:</label>
        <select id="tipo_documento" name="tipo_documento">
            <option value="cpf">CPF</option>
            <option value="cnpj">CNPJ</option>
        </select><br><br>

        <input type="submit" value="Enviar">
    </form>
</body>
</html>

<?php


use PhpParser\Node\Expr\Isset_;

define('PATH', __DIR__  . '/');

include_once __DIR__ . '/autoload.php';
include_once __DIR__ . '/app/webhook_handler.php';

$etapa = 0;

if (isset($_GET['tipo_documento'])) {
    $tipo_documento = $_GET['tipo_documento'];

    for ($i = 7; $i <= 7; $i++) {
        testarChatbot('5511951936777', $i, $tipo_documento);
        usleep(500000); // Aguarda 0,5 segundos (500.000 microsegundos)
    }

    echo "<p>Chatbot testado com o número: 5511951936777, tipo de documento: $tipo_documento</p>";
}

$etapa += 1;

?>

