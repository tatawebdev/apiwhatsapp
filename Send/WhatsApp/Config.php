<?php

namespace Models\Base\Send\WhatsApp;

class Config
{
    protected static $number = "";
    protected $array = [];
    protected static $debug = false;
    protected $method = null;

    public function setRecipientNumber($number)
    {
        preg_match('/^(\d+)\s*(\d+)/', $number, $matches);
    
        if (count($matches) >= 3) {
            $prefix = $matches[1];
            $cleanedNumber = preg_replace('/[ ()-]/', '', $matches[2]);
            $formattedNumber = $prefix . $cleanedNumber;
        } else {
            $formattedNumber = '55' . preg_replace('/[ ()-]/', '', $number);
        }
    
        // Atribua o número formatado à propriedade
        self::$number = $formattedNumber;
    }
    


}
