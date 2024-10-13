<?php

namespace Models\Base\Send\WhatsApp;

class InteractiveMessages extends CurlHttpClient
{
    private static $instance = null;
    private  $sections = [];

    public static function getInstance($debug_json = false)
    {
        if (self::$instance === null) {
            self::$instance = new self($debug_json);
        }
        return self::$instance;
    }


    private function sendInteractiveMessage($messageType, $messageData,$id_phone = null)
    {

        $interactiveMessage = [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => self::$number,
            'type' => 'interactive',
            'interactive' => $messageData,
        ];
        $result = $this->sendRequest(getURL_MESSAGENS_WHATSAPP($id_phone), 'POST', json_encode($interactiveMessage));


        $decodedResult = json_decode($result, true);

        if ($decodedResult && isset($decodedResult['error'])) {
            $this->logError($decodedResult['error']);
        }
    }

    public function sendSingleProductMessage($catalogId, $productRetailerId, $bodyText = '', $footerText = '')
    {
        $messageData = [
            'type' => 'product',
            'body' => ['text' => $bodyText],
            'footer' => ['text' => $footerText],
            'action' => ['catalog_id' => $catalogId, 'product_retailer_id' => $productRetailerId],
        ];

        $this->sendInteractiveMessage('product', $messageData);
    }

    public function sendMultiProductMessage($catalogId, $headerText, $bodyText, $footerText, $sections)
    {
        $messageData = [
            'type' => 'product_list',
            'header' => ['type' => 'text', 'text' => $headerText],
            'body' => ['text' => $bodyText],
            'footer' => ['text' => $footerText],
            'action' => ['catalog_id' => $catalogId, 'sections' => $sections],
        ];

        $this->sendInteractiveMessage('product_list', $messageData);
    }

    public function sendCatalogMessage($thumbnailProductRetailerId, $text = '')
    {
        $messageData = [
            'type' => 'catalog_message',
            'body' => ['text' => $text],
            'action' => ['name' => 'catalog_message', 'parameters' => ['thumbnail_product_retailer_id' => $thumbnailProductRetailerId]],
        ];

        $this->sendInteractiveMessage('catalog_message', $messageData);
    }

    public function sendListMessage($headerText, $bodyText, $footerText, $buttonText)
    {

        $messageData = [
            'type' => 'list',
            'header' => ['type' => 'text', 'text' => $headerText],
            'body' => ['text' => $bodyText],
            'footer' => ['text' => $footerText],
            'action' => ['button' => $buttonText, 'sections' => $this->sections],
        ];

        $this->sendInteractiveMessage('list', $messageData);
    }
    function clearSection()
    {
        $this->sections = [];
    }
    function addSection($title, $rowID, $rowTitle, $rowDescription)
    {
        // Verifica se a seção já existe no array
        if (!isset($this->sections[$title])) {
            $this->sections[$title] = [
                'title' => $title,
                'rows' => [],
            ];
        }

        // Adiciona a nova linha à seção
        $this->sections[$title]['rows'][] = [
            'id' => $rowID,
            'title' => $rowTitle,
            'description' => $rowDescription,
        ];
    }

    public function sendButtonMessage($buttonText, $buttons)
    {
        $messageData = [
            'type' => 'button',
            'body' => ['text' => $buttonText],
            'action' => ['buttons' => $buttons],
        ];

        $this->sendInteractiveMessage('button', $messageData);
    }

    public function setRecipientNumber($number)
    {
        self::$number = $number;
    }
}
