<?php

namespace WhatsApp;

class Message extends CurlHttpClient
{
    private static $instance = null;
    private static $api_phone_id = null;

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private $sends;

    public function __construct()
    {
        $this->method = $_SERVER['REQUEST_METHOD'];
    }


    public function getMethod()
    {
        return $this->method;
    }

    public function isPOST()
    {
        return $this->method == 'POST';
    }
    public function addMessageText($message, $number = null, $onlyOne = false)
    {
        if (is_null($number))
            $number = self::$number;

        if ($onlyOne)
            $this->sends[$number] = $message;
        else
            $this->sends[$number][] = $message;
    }
    public function clearMessageText($number = null)
    {
        if (is_null($number))
            $this->sends = [];
        else
            $this->sends[$number] = [];
    }
    public function sendMultiMessageText($previewUrl = true)
    {
        if (!!$this->sends)
            foreach ($this->sends as $number => $messages) {
                $this->setRecipientNumber($number);
                foreach ($messages as  $message) {
                    $this->sendMessageText($message, $previewUrl);
                }
            }
    }



    public function sendMessageText($text, $previewUrl = true)
    {
        $this->array = [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => self::$number,
            'type' => 'text',
            'text' => [
                'preview_url' => $previewUrl,
                'body' => $text
            ]
        ];

        return $this->sendMessage();
    }

    public function sendImageMessage($mediaObjectId)
    {
        $this->array = [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => self::$number,
            'type' => 'image',
            'image' => [
                'id' => $mediaObjectId
            ]
        ];

        return $this->sendMessage();
    }
    public function sendLinkImageMessage($mediaObjectId)
    {
        $this->array = [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => self::$number,
            'type' => 'image',
            'image' => [
                'link' => $mediaObjectId
            ]
        ];

        return $this->sendMessage();
    }
    public function sendDocumentMessage($mediaObjectId, $document = "document")
    {
        $this->array = [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => self::$number,
            'type' => 'document',
            'document' => [
                "filename" => "$document.pdf",
                'id' => $mediaObjectId
            ]
        ];

        return $this->sendMessage();
    }
    public function sendLinkDocumentMessage($link, $document = "document")
    {
        $this->array = [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => self::$number,
            'type' => 'document',
            'document' => [
                "filename" => "$document.pdf",
                'link' => $link
            ]
        ];

        return $this->sendMessage();
    }
    public function sendReactionMessage($messageId, $emoji)
    {
        $this->array = [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => self::$number,
            'type' => 'reaction',
            'reaction' => [
                'message_id' => $messageId,
                'emoji' => $emoji
            ]
        ];

        return $this->sendMessage();
    }
    public function sendBoletoHospedagemSite($link, $document = "document")
    {
        $components = [
            [
                "type" => "header",
                "parameters" => [
                    [
                        'type' => 'document',
                        'document' => [
                            "filename" => "$document.pdf",
                            'link' => $link
                        ]
                    ]
                ]
            ],
            [
                "type" => "body",
                "parameters" => [
                    [
                        "type" => "text",
                        "text" => 'Boa tarde Sra. Helen,'
                    ],
                    [
                        "type" => "text",
                        "text" => 'a Hospedagem do Site e e-mails a vencer em 14/10/2023.'
                    ]
                ]
            ]
        ];

        return $this->sendTemplate("boleto_hospedagem_site", $components);
    }
    public function sendTemplate($name, $components)
    {
        $this->array =  array(
            'messaging_product' => 'whatsapp',
            'to' => self::$number,
            'type' => 'template',
            'template' => [
                'name' => $name,
                "language" => ["code" => "pt_BR"],
                "components" => $components
            ]
        );
        return  $this->sendMessage();
    }
    private function sendMessage($id_phone = null)
    {

        if (self::$number == '5511951936777') {
            $id_phone = '373419075865654';
        }

        $result = $this->sendRequest(getURL_MESSAGENS_WHATSAPP($id_phone), 'POST', json_encode($this->array));

        $decodedResult = json_decode($result, true);

        if (isset($decodedResult['error']['error_data']['details'])) {
            throw new \Exception($decodedResult['error']['error_data']['details']);
        }
        return $result;
    }
}
