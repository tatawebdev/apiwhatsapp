<?php

namespace WhatsApp;

include_once __DIR__ . "/Config.php";

class CurlHttpClient extends Config
{
    public function sendRequest($url, $method, $data = [], $headers = [])
    {
        if (count($headers) == 0)
            array_push($headers, "Authorization: Bearer " . TOKEN_WHATSAPP, "Content-Type: application/json");

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        }
        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
    }
    public function logError($error)
    {
        throw new \Exception($error);
    }
}
