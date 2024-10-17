<?php

function proxima_etapa($selected)
{
    return [
        'result' => true,
        'message' => [],
        'data' => []
    ];
}

// include_once __DIR__ . '/PalavrasProibidas.php';

function validar_nome($selected)
{
    $nome = trim($selected['data']['message']);


    // Verifica se o nome é válido
    if (empty($nome) || !preg_match('/^[\p{L}\s]+$/u', $nome)) {
        return [
            'result' => false,
            'message' => ['Nome inválido ou vazio'],
            'data' => []
        ];
    }

    $palavrasProibidasExatas = ['oi', 'ola', 'olá', 'e aí', 'salve', 'bom dia', 'boa tarde', 'boa noite', 'hey', 'hello'];
    $palavrasProibidas = PalavrasProibidas::getAllPalavrasOnly();

    // Mapeia os acentos para suas versões sem acento
    $acentos = ['á', 'à', 'â', 'ã', 'é', 'ê', 'í', 'ó', 'ô', 'õ', 'ú', 'ç', 'ä', 'ë', 'ï', 'ö', 'ü'];
    $semAcento = ['a', 'a', 'a', 'a', 'e', 'e', 'i', 'o', 'o', 'o', 'u', 'c', 'a', 'e', 'i', 'o', 'u'];

    // Função auxiliar para remover acentos e verificar palavras
    $verificaPalavra = function ($nome, $palavra) use ($acentos, $semAcento) {
        $nomeSemAcento = strtolower(str_replace($acentos, $semAcento, $nome));
        $palavraSemAcento = strtolower(str_replace($acentos, $semAcento, trim($palavra)));
        return stripos($nomeSemAcento, $palavraSemAcento) !== false;
    };

    // Verifica palavras proibidas
    foreach (array_merge($palavrasProibidas) as $palavra) {
        if ($verificaPalavra($nome, $palavra) || strtolower(trim($nome)) === strtolower(trim($palavra))) {
            return [
                'result' => false,
                'message' => ['Nome contém palavras proibidas'],
                'data' => []
            ];
        }
    }
    foreach ($palavrasProibidasExatas as $palavra) {
        // Remove os acentos da palavra
        $palavra = strtolower(trim($palavra));
        $nome = strtolower(trim($nome));
        $palavraSemAcento = str_replace($acentos, $semAcento, $palavra);
        $nome = str_replace($acentos, $semAcento, $nome);

        if ($verificaPalavra($nome, $palavra) || $nome == $palavraSemAcento) {
            return [
                'result' => false,
                'message' => ['Nome contém palavras proibidas'],
                'data' => []
            ];        }
    }
    // Retorna sucesso se passar todas as validações
    return [
        'result' => true,
        'message' => [],
        'data' => []
    ];
}
