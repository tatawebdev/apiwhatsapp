<?php

namespace Models\Base;

class Send
{
    private static array $contacts = [];
    private static array $addedContacts = [];
    private static array $skippedContacts = [];

    /**
     * Adiciona contatos à lista de contatos.
     *
     * @param mixed $contacts Pode ser uma string (para número de WhatsApp ou endereço de e-mail)
     *                        ou um array de contatos.
     */
    private static function addContacts($contacts)
    {
        if (is_array($contacts)) {
            foreach ($contacts as $contact) {
                static::addContact($contact);
            }
        } elseif (is_string($contacts)) {
            static::addContact($contacts);
        } else {
            echo "Erro: Os contatos devem ser um array ou uma string.\n";
        }
    }

    /**
     * Adiciona um endereço de e-mail à lista de contatos.
     *
     * @param string $emailAddress O endereço de e-mail a ser adicionado.
     */
    public static function addAddressEmail($emailAddress)
    {
        if (static::validateAddressEmail($emailAddress)) {
            static::addContact(['email' => [$emailAddress]]);
            static::$addedContacts[] = $emailAddress;
        } else {
            static::$skippedContacts[] = $emailAddress;
        }
    }

    /**
     * Adiciona um número do WhatsApp à lista de contatos.
     *
     * @param string $phoneNumber O número do WhatsApp a ser adicionado.
     */
    public static function addNumberWhatsApp($phoneNumber)
    {
        if (static::validateNumberWhatsApp($phoneNumber)) {
            static::addContact(['whatsapp' => [$phoneNumber]]);
            static::$addedContacts[] = $phoneNumber;
        } else {
            static::$skippedContacts[] = $phoneNumber;
        }
    }

    private static function addContact($contact)
    {
        static::$contacts[] = $contact;
    }

    private static function validateAddressEmail($email)
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    private static function validateNumberWhatsApp($phoneNumber)
    {
        return preg_match('/^\d{10,15}$/', $phoneNumber);
    }

    /**
     * Envia uma mensagem via WhatsApp para todos os contatos na lista.
     *
     * @param string $message A mensagem a ser enviada via WhatsApp.
     */
    public static function sendWhatsAppMessage($message)
    {
        foreach (static::$contacts as $contact) {
            if (is_array($contact) && isset($contact['whatsapp'])) {
                foreach ($contact['whatsapp'] as $phoneNumber) {
                    // Implemente a lógica real para enviar a mensagem via WhatsApp aqui
                    echo "Enviando mensagem para WhatsApp: $phoneNumber\n";
                    echo "Mensagem: $message\n";
                }
            } elseif (is_string($contact)) {
                // Implemente a lógica real para enviar a mensagem via WhatsApp aqui
                echo "Enviando mensagem para WhatsApp: $contact\n";
                echo "Mensagem: $message\n";
            }
        }
    }

    /**
     * Envia um e-mail para todos os contatos na lista.
     *
     * @param string $subject O assunto do e-mail.
     * @param string $message O conteúdo do e-mail.
     */
    public static function sendEmail($subject, $message)
    {
        foreach (static::$contacts as $contact) {
            if (is_array($contact) && isset($contact['email'])) {
                foreach ($contact['email'] as $emailAddress) {
                    // Implemente a lógica real para enviar o e-mail aqui
                    echo "Enviando e-mail para: $emailAddress\n";
                    echo "Assunto: $subject\n";
                    echo "Mensagem: $message\n";
                }
            } elseif (is_string($contact)) {
                // Implemente a lógica real para enviar o e-mail aqui
                echo "Enviando e-mail para: $contact\n";
                echo "Assunto: $subject\n";
                echo "Mensagem: $message\n";
            }
        }
    }

    /**
     * Limpa a lista de contatos.
     */
    public static function clearContacts()
    {
        static::$contacts = [];
    }

    /**
     * Retorna o número total de contatos na lista.
     *
     * @return int O número total de contatos na lista.
     */
    public static function countContacts()
    {
        return count(static::$contacts);
    }
}

