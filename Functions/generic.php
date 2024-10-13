<?php

/**
 * Redireciona o usuário para uma URL com uma mensagem e tipo (opcional).
 *
 * @param string $url A URL para a qual o usuário será redirecionado.
 * @param string $message A mensagem que será exibida após o redirecionamento.
 * @param string $type O tipo da mensagem (por padrão, "success").
 */
function location($url = "", $message = "", $type = "success")
{
    if (!empty($message)) {
        $_SESSION['message'] = [$type => $message];
    }
    header("Location: " . URL_APP . $url);
    exit;
}


/**
 * Redireciona o usuário para uma URL com uma mensagem de erro.
 *
 * @param string $url A URL para a qual o usuário será redirecionado.
 * @param array $data Um array associativo contendo 'titulo' e 'mensagem'.
 */
function locationError($titulo = "Página não encontrada", $message = "Página não encontrada")
{
    $error_message = [
        'titulo' => $titulo,
        'message' => $message
    ];
    $_SESSION['message'] = ['error' => $error_message];
    header("Location: " . URL_APP . "not-found");
    exit;
}



/**
 * Obtém uma mensagem da sessão (por tipo) e opcionalmente limpa a mensagem.
 *
 * @param string $type O tipo da mensagem (por padrão, null para todas).
 * @param bool $clear Define se a mensagem deve ser removida da sessão.
 * @return mixed A mensagem obtida da sessão ou null se não houver mensagem do tipo especificado.
 */
function getSessionMessage($type = null, $clear = false)
{
    if (!isset($_SESSION['message']))
        return null;
    if (count($_SESSION['message']) < 1) {
        unset($_SESSION['message']);
        return null;
    }
    $message = $_SESSION['message'];
    if ($type === null) {
        $message = implode(", ", $message);
    } elseif (isset($message[$type])) {
        $message = $message[$type];
    }
    if ($clear) {
        clearSessionMessage($type);
    }
    return $message;
}

/**
 * Limpa uma mensagem da sessão (por tipo) ou todas as mensagens.
 *
 * @param string $type O tipo da mensagem (por padrão, null para todas).
 */
function clearSessionMessage($type = null)
{
    if (isset($_SESSION['message'])) {
        if ($type === null) {
            unset($_SESSION['message']);
        } elseif (isset($_SESSION['message'][$type])) {
            unset($_SESSION['message'][$type]);
        }
    }
}

/**
 * Formata uma chave de texto substituindo underscores por espaços e capitalizando as palavras.
 *
 * @param string $key A chave de texto a ser formatada.
 * @return string A chave formatada.
 */
function formatKeyName($key)
{
    return ucwords(str_replace("_", " ", $key));
}

/**
 * Formata uma data no formato brasileiro (dd/mm/yyyy) a partir de uma data em formato yyyy-mm-dd.
 *
 * @param string $cell A data no formato yyyy-mm-dd.
 * @return string A data formatada no estilo brasileiro (dd/mm/yyyy).
 */
function formatDateBrasil($cell, $mostrarhora = false)
{
    $formato1 = preg_match('/\d{2}:\d{2}:\d{2}/', $cell) ? "Y-m-d H:i:s" : "Y-m-d";
    $formato2 = preg_match('/\d{2}:\d{2}:\d{2}/', $cell) ? "d/m/Y" . (($mostrarhora) ? " H:i:s" : "")  : "d/m/Y" .  (($mostrarhora) ? " H:i:s" : "");
    $datetime = date_create_from_format($formato1, $cell);

    if ($datetime) {
        return $datetime->format($formato2);
    } else {
        return $cell;
    }
}

function formatMoedaBrasil($value)
{
    // Remove todos os caracteres que não são dígitos, ponto decimal ou vírgula
    $cleanedValue = preg_replace('/[^\d,.]/', '', $value);

    // Se o valor estiver vazio, retorna um hífen como indicador de valor vazio
    if ($cleanedValue === '' || $cleanedValue === null) {
        return '-';
    }
    $cleanedValue = str_replace(',', '.', $cleanedValue);

    return 'R$ ' . number_format((float) $cleanedValue, 2, ',', '.');
}




/**
 * Gera uma data formatada personalizada com o nome do local, dia, mês e ano especificados.
 *
 * @param string $location O nome do local.
 * @param int $day O dia da data.
 * @param int $month O mês da data.
 * @param int $year O ano da data.
 * @return string A data formatada com o nome do local, dia, mês e ano.
 */
function generateFormattedDate($location, $day = null, $month = null, $year = null)
{
    $day =  $day ?? date("d");
    $month =  $month ?? date("m");
    $year =  $year ?? date("Y");

    $months = [
        1 => 'JANEIRO', 2 => 'FEVEREIRO', 3 => 'MARÇO',
        4 => 'ABRIL', 5 => 'MAIO', 6 => 'JUNHO', 7 => 'JULHO',
        8 => 'AGOSTO', 9 => 'SETEMBRO', 10 => 'OUTUBRO',
        11 => 'NOVEMBRO', 12 => 'DEZEMBRO'
    ];

    $formattedDate = strtoupper($location . ', ' . $day . ' DE ' . $months[$month] . ' DE ' . $year);

    return $formattedDate;
}

/**
 * Converte um ou mais textos em um slug amigável para URLs.
 *
 * @param string ...$texts Um ou mais textos a serem convertidos em um slug.
 * @return string O slug resultante.
 */
function slug(...$texts)
{
    $combinedText = implode(' ', $texts);

    $combinedText = iconv('UTF-8', 'ASCII//TRANSLIT', $combinedText);
    $slug = preg_replace('/[^A-Za-z0-9-]+/', '-', $combinedText);

    $slug = strtolower(preg_replace('/-+/', '-', $slug));

    return $slug;
}

/**
 * Cria uma instância de gerador de PDF personalizada.
 *
 * @return \Models\Base\PDFGenerator A instância do gerador de PDF.
 */
function createPDFGenerator($modeloPDF = null)
{
    return new Models\Base\PDFGenerator(['pdf_modelo' => $modeloPDF]);
}


function isMD5($hash)
{
    return preg_match('/^[0-9a-fA-F]{32}$/', $hash);
}
function capture_var_dump($variable)
{
    ob_start();
    echo "<pre>";
    var_dump($variable);
    $html = ob_get_contents();
    ob_end_clean();

    return $html;
}

function curl_POST($url, $data = [])
{
    $data['session_id'] = session_id();
    $ch = curl_init($url);

    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        echo 'Erro ao fazer a solicitação cURL: ' . curl_error($ch);
    }

    curl_close($ch);

    return $response;
}
function convertToNumeric($value)
{
    $cleanedValue = preg_replace('/[^\d,.-]/', '', $value);

    // Substitui ',' por '.' para garantir que seja interpretado como número decimal
    $cleanedValue = str_replace(['.', ','], ['', '.'], $cleanedValue);

    // Converte para float
    $numericValue = floatval($cleanedValue);

    return $numericValue;
}

function generateStatusScript($statusMapping) {
    echo '<script>';
    echo 'function getStatus(statusCode) {';
    echo '    switch (statusCode) {';

    foreach ($statusMapping as $code => $text) {
        echo "        case $code:\n";
        echo "            return '$text';\n";
    }

    echo '        default:';
    echo "            return 'Desconhecido';";
    echo '    }';
    echo '}';
    echo '</script>';
}

