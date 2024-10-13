<?php
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

carregarFuncoesRecursivamente(PATH_WHATSAPP . "Functions");
carregarFuncoesRecursivamente(PATH_FUNCTIONS);
carregarClassesRecursivamente(PATH_MODELS);
