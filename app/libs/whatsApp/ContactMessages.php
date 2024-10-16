<?php

namespace WhatsApp;

include_once __DIR__ . "/CurlHttpClient.php";
class ContactMessages extends CurlHttpClient
{

    public function __construct()
    {
    }

    public function sendContactMessage(
        $formattedName,
        $firstName,
        $lastName,
        $middleName,
        $suffix,
        $prefix,
        $street,
        $city,
        $state,
        $zip,
        $country,
        $countryCode,
        $type,
        $birthday,
        $emails,
        $company,
        $department,
        $title,
        $phones,
        $urls
    ) {
        $contactData = [
            'messaging_product' => 'whatsapp',
            'to' => self::$number,
            'type' => 'contacts',
            'contacts' => [
                [
                    'addresses' => [
                        [
                            'street' => $street,
                            'city' => $city,
                            'state' => $state,
                            'zip' => $zip,
                            'country' => $country,
                            'country_code' => $countryCode,
                            'type' => $type,
                        ],
                    ],
                    'birthday' => $birthday,
                    'emails' => $emails,
                    'name' => [
                        'formatted_name' => $formattedName,
                        'first_name' => $firstName,
                        'last_name' => $lastName,
                        'middle_name' => $middleName,
                        'suffix' => $suffix,
                        'prefix' => $prefix,
                    ],
                    'org' => [
                        'company' => $company,
                        'department' => $department,
                        'title' => $title,
                    ],
                    'phones' => $phones,
                    'urls' => $urls,
                ],
            ],
        ];

        $result = $this->sendRequest(URL_MESSAGENS_WHATSAPP, 'POST', json_encode($contactData));

        $decodedResult = json_decode($result, true);

        if ($decodedResult && isset($decodedResult['error'])) {
            $this->logError($decodedResult['error']);
        }
    }
}
