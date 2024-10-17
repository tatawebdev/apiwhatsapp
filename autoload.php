<?php
include_once __DIR__ . '/vendor/autoload.php';
include_once __DIR__ . '/app/config/env_loader.php';
include_once __DIR__ . '/app/config/env.php';
include_once __DIR__ . '/app/config/constants.php';

function carregarClasse($nomeClasse)
{
    $caminhoCompleto = PATH_MODELS . $nomeClasse . '.php';
    if (file_exists($caminhoCompleto)) {
        include_once $caminhoCompleto;
    }
}
function carregarClassesRecursivamente($diretorio, $namespace = '')
{
    $itens = scandir($diretorio);

    foreach ($itens as $item) {
        if ($item === '.' || $item === '..') {
            continue;
        }

        $caminhoCompleto = $diretorio . '/' . $item;

        if (is_dir($caminhoCompleto)) {
            $novoNamespace = $namespace . '\\' . $item;
            carregarClassesRecursivamente($caminhoCompleto, $novoNamespace);
        } elseif (pathinfo($item, PATHINFO_EXTENSION) === 'php') {
            // Se o item for um arquivo PHP, carrega a classe
            $classe = pathinfo($item, PATHINFO_FILENAME);
            $caminhoRelativo = str_replace(PATH_MODELS, '', $diretorio) . '/' . $classe;
            $classeCompleta = $namespace . '\\' . $classe;
            carregarClasse($caminhoRelativo, $classeCompleta);
        }
    }
}
function carregarFuncao($nomeFuncao)
{
    $caminhoCompleto = PATH_FUNCTIONS . $nomeFuncao . '.php';
    if (file_exists($caminhoCompleto)) {
        include_once $caminhoCompleto;
    }
}

function carregarFuncoesRecursivamente($diretorio)
{
    $itens = scandir($diretorio);

    foreach ($itens as $item) {
        if ($item === '.' || $item === '..') {
            continue;
        }

        $caminhoCompleto = $diretorio . '/' . $item;

        if (is_dir($caminhoCompleto)) {
            // Se o item for um diretório, chama a função recursivamente
            carregarFuncoesRecursivamente($caminhoCompleto);
        } elseif (pathinfo($item, PATHINFO_EXTENSION) === 'php') {
            // Se o item for um arquivo PHP, carrega a função
            $funcao = pathinfo($item, PATHINFO_FILENAME);
            carregarFuncao($funcao);
        }
    }
}
carregarFuncoesRecursivamente(PATH_FUNCTIONS);

include_once PATH_LIBS . 'whatsApp/Config.php';
include_once PATH_LIBS . 'whatsApp/ContactMessages.php';
include_once PATH_LIBS . 'whatsApp/CurlHttpClient.php';
include_once PATH_LIBS . 'whatsApp/InteractiveMessages.php';
include_once PATH_LIBS . 'whatsApp/Media.php';
include_once PATH_LIBS . 'whatsApp/Message.php';
include_once PATH_LIBS . 'whatsApp/WebhookProcessor.php';

carregarClassesRecursivamente(PATH_MODELS);

Model::init();
