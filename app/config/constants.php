<?php
$config = ENV;
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'];


define('TOKEN_WHATSAPP', $config['TOKEN_WHATSAPP']);
define('API_PHONE_PRODUCAO', $config['API_PHONE_PRODUCAO']);
define('URL_APP', $protocol . '://' . $_SERVER['HTTP_HOST'] . '/');
define('ASSETS', URL_APP . "assets/");
define('IMAGES', URL_APP . "images/");
define('UPLOADS', URL_APP . "uploads/");
define('DOCUMENTOS_VISUALIZAR', URL_APP . "documentos/visualizar/");
define('HOST_EMAIL', $config['HOST_EMAIL']);
define('USERNAME_EMAIL', $config['USERNAME_EMAIL']);
define('PASSWORD_EMAIL', $config['PASSWORD_EMAIL']);
define('NOME_EMPRESA', $config['NOME_EMPRESA']);
define('EMAIL_PRINCIPAL', $config['EMAIL_PRINCIPAL']);
define('PATH_APP', realpath(__DIR__ . "/..") . '/');
define('PATH_MODELS', realpath(PATH_APP . '/models') . '/');
define('PATH_VIEW', realpath(PATH_APP . '/views') . '/');
define('PATH_FUNCTIONS', PATH_APP . 'helpers/');
define('PATH_LIBS', PATH_APP . 'libs/');


function getURL_MESSAGENS_WHATSAPP($id_phone = null)
{
    $defaultPhoneId = env('API_PHONE_PRODUCAO');
    $phoneId = $id_phone ?? $defaultPhoneId;
    return 'https://graph.facebook.com/v20.0/' . $phoneId . '/messages';
}
