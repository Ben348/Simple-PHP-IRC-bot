<?php

/**
 * Base plugin
 * 
 * Base commands to interact with the bot and irc server.
 * Most plugins will reference to this class.
 * 
 * @author Ben Thomson <ben.thomson@myport.ac.uk>
 * @copyright 2011+
 */

class base extends plugin {
    
    // Bot properties functions.
    public function name($name = null, $password = null){}
    
    public function getName(){}
    
    public function getPort(){}
    
    public function getServer(){}
    
    public function ignore($name, $mask){}
    
    public function names($channel)
    {
        $this->sendData('NAMES ' . implode(', ', $channel));
    }
    
    public function listChan($channel)
    {
        $this->sendData('LIST ' . implode(', ', $channel));
    }


    // Misc functions.
    public function put($channel, $type, $message)
    {
        $this->sendData($type . ' ' . $channel . ' :' . $message);
    }
    
    public function join($channel, $password = null)
    {
        $this->sendData('JOIN ' . $channel . ($password !== null ? (' ' . $password) : ''));
    }
    
    public function part($channel)
    {
        $this->sendData('PART ' . $channel);
    }
    
    public function quit($message = '')
    {
        $this->sendData('QUIT ' . $message);
    }
    
    public function who($channel)
    {
        $this->sendData('WHO ' . $channel);
    }
    
    public function kick($name, $channel, $reason ='')
    {
        $this->irc->sendData('KICK ' . $channel . ' ' . $name . ' ' . $reason);
    }
    
    public function sendData($data)
    {
        return $this->irc->sendData($data);
    }
    

    // Mode functions
    public function modeAdd($channel, $mode, $params = null)
    {
        $this->sendData('MODE ' . $channel . ' ' . $mode . (isset($params) ? ' ' . $params : ''));
    }
    
    public function modeDel($channel, $mode, $params = null){}
    
    
    // Ban functions.
    public function banAdd($mask){}
    
    public function banDel($mask){}

    /**
     * Stips text formatting from text.
     * @param string $text - line of text from IRC.
     * @return string - unformatted line of text.
     */
    public function stripmIRC($text) {
        $controlCodes = array(
            '/(\x03(?:\d{1,2}(?:,\d{1,2})?)?)/',    // Color code
            '/\x02/',                               // Bold
            '/\x0F/',                               // Escaped
            '/\x16/',                               // Italic
            '/\x1F/'                                // Underline
        );
        return preg_replace($controlCodes,'',$text);
    }
}

?>