<?php
namespace WhatsApp;

class WebhookProcessor extends CurlHttpClient
{
    public static function debugOn()
    {
        WebhookProcessor::$debug = true;
        echo "<pre>";
    }
    public static function debugOFF()
    {
        WebhookProcessor::$debug = false;
    }


    public static function sanitizeString($string)
    {
        $what = array('ä', 'ã', 'à', 'á', 'â', 'ê', 'ë', 'è', 'é', 'ï', 'ì', 'í', 'ö', 'õ', 'ò', 'ó', 'ô', 'ü', 'ù', 'ú', 'û', 'À', 'Á', 'É', 'Í', 'Ó', 'Ú', 'ñ', 'Ñ', 'ç', 'Ç');
        $by = array('a', 'a', 'a', 'a', 'a', 'e', 'e', 'e', 'e', 'i', 'i', 'i', 'o', 'o', 'o', 'o', 'o', 'u', 'u', 'u', 'u', 'a', 'a', 'e', 'i', 'o', 'u', 'n', 'n', 'c', 'c');
        return str_replace($what, $by, $string);
    }

    public static function salvar($data)
    {
        $filename = "newfile.json";

        if (!file_exists($filename)) {
            $conteudoOriginalArray = [];
        } else {
            $conteudoOriginal = file_get_contents($filename);
            $conteudoOriginalArray = $conteudoOriginal ? json_decode($conteudoOriginal, true) : [];
        }

        $jsonData = json_decode($data, true);
        $conteudoOriginalArray[] = $jsonData;
        $novoConteudoJSON = json_encode($conteudoOriginalArray, JSON_PRETTY_PRINT);

        $myfile = fopen($filename, "w") or die("Unable to open file!");
        fwrite($myfile, $novoConteudoJSON);
        fclose($myfile);
    }


    public static function getMethod()
    {
        return $_SERVER['REQUEST_METHOD'];
    }

    public static function isPOST()
    {
        return $_SERVER['REQUEST_METHOD'] == 'POST';
    }

    public static function tratarWebhookWhatsApp($webhookData = null)
    {
        // Se $webhookData não for fornecido, leia-o a partir de php://input
        if (is_null($webhookData)) {
            $webhookData = file_get_contents("php://input");
        }
        self::salvar($webhookData);
        // Inicializa o array de resultados
        $result = [
            'event_type' => null,
            'celular' => null,
        ];

        // Verifique se há dados no webhook
        if (empty($webhookData)) {
            return $result;
        }

        // Decodifique o JSON do webhook
        $event = json_decode($webhookData, true);
        $entry = $event['entry'][0] ?? null;
        $changes = $entry['changes'][0] ?? null;
        $changesValue = $changes['value'] ?? null;
        $contacts = $changesValue['contacts'][0] ?? null;


        // Verifique e atribua o ID, se estiver presente
        if (isset($entry['id'])) {
            $result['id'] = $entry['id'];
        }
        if (isset($contacts['profile']['name'])) {
            $result['name'] = $contacts['profile']['name'];
        }
        if (isset($changesValue['metadata']['phone_number_id'])) {
            $result['api_phone_id'] = $changesValue['metadata']['phone_number_id'];
            $result['api_phone_number'] = $changesValue['metadata']['display_phone_number'];
        }



        define('API_PHONE_ID', $result['api_phone_id'] ?? API_PHONE_PRODUCAO);

        // Determine o tipo de evento com base nos dados do webhook
        if (isset($changesValue['statuses'])) {
            $result['event_type'] = 'status';
            $result['celular'] = $changesValue['statuses'][0]['recipient_id'];
            $result['status'] = $changesValue['statuses'][0]['status'];
            $result['status_id'] = $changesValue['statuses'][0]['id'];

            $result['conversation'] = isset($changesValue['statuses'][0]['conversation'])
                ? $changesValue['statuses'][0]['conversation']
                : null;
        } elseif (isset($changesValue['messages'])) {
            $message = $changesValue['messages'][0];
            $result['celular'] = $message['from'];
            $result['event_type'] = $message['type'];
            $result['message_id'] = $message['id'] ?? null;

            // Determine a ação com base no tipo de mensagem
            switch ($message['type']) {
                case 'text':
                    if (isset($message['text']['body'])) {
                        $result['event_type'] = 'message_text';
                        $result['message'] = $message['text']['body'];
                    }
                    break;
                case 'button':
                    if (isset($message['button']['payload'])) {
                        $result['event_type'] = 'message_button';
                        $result['message'] = $message['button']['payload'];
                    }
                    break;
                case 'interactive':
                    if (isset($message['interactive']['button_reply']['title'])) {
                        $result['event_type'] = 'message_button';
                        $result['message'] = $message['interactive']['button_reply']['title'];
                    } elseif (isset($message['interactive']['list_reply']['id'])) {
                        $result['event_type'] = 'interactive';
                        $result['interactive'] = $message['interactive']['list_reply'];
                        $result['message'] = $message['interactive']['list_reply']['title'];
                    }
                    break;
            }
        }

        // Armazene o JSON original do webhook
        $result['json'] = json_encode($event);

        return $result;
    }
}
