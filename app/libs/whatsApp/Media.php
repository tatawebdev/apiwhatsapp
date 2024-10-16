<?php

namespace WhatsApp;

class Media extends CurlHttpClient
{
    public function __construct()
    {
    }

    public function uploadMedia($mediaFilePath, $mediaType = 'image/jpeg')
    {
        $url = 'https://graph.facebook.com/v18.0/<MEDIA_ID>/media'; // Substitua <MEDIA_ID> pelo ID da mídia

        $headers = ["Authorization: Bearer ". TOKEN_WHATSAPP];

        // Configuração do arquivo de mídia
        $mediaFile = new \CURLFile($mediaFilePath, $mediaType);
        $postData = [
            'file' => $mediaFile,
            'type' => $mediaType,
            'messaging_product' => 'whatsapp'
        ];

        $result = $this->sendRequest($url, 'POST', $headers, $postData);

        $decodedResult = json_decode($result, true);

        if ($decodedResult && isset($decodedResult['id'])) {
            return $decodedResult['id']; // Retorna o ID da mídia enviada
        } else {
            // Lidar com qualquer erro aqui, por exemplo, log ou relatório do erro
            $this->logError("Erro ao enviar mídia: $result");
            return false;
        }
    }

    public function getMediaInfo($mediaId)
    {
        $url = "https://graph.facebook.com/v18.0/{$mediaId}/"; // Substitua {$mediaId} pelo ID da mídia

        $headers = ["Authorization: Bearer" . TOKEN_WHATSAPP];

        $result = $this->sendRequest($url, 'GET', $headers);

        $decodedResult = json_decode($result, true);

        if ($decodedResult && !isset($decodedResult['error'])) {
            return $decodedResult; // Retorna as informações da mídia
        } else {
            // Lidar com qualquer erro aqui, por exemplo, log ou relatório do erro
            $this->logError("Erro ao recuperar informações da mídia: $result");
            return false;
        }
    }
}
