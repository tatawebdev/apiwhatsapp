<?php
function logError($message)
{
    $filePath = __DIR__ . '/../logs/error_log.txt';
    $errorMessage = '[' . date('Y-m-d H:i:s') . '] ' . $message . PHP_EOL;
    file_put_contents($filePath, $errorMessage, FILE_APPEND);
    echo $message;
}

set_error_handler(function ($errno, $errstr, $errfile, $errline) {
    logError("Erro: [$errno] $errstr - $errfile:$errline");
});

set_exception_handler(function ($exception) {
    logError("Exceção: " . $exception->getMessage() .
        " em " . $exception->getFile() .
        " na linha " . $exception->getLine());
});
