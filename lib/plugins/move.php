<?php

/**
 * Move plugin
 * 
 * Allows the bot to joina and leave channels.
 * 
 * @author Ben Thomson <ben.thomson@myport.ac.uk>
 * @copyright 2011+
 */

class move extends plugin {
    
    function __construct(){}
    
    function __destruct(){}
    
    public function getPlugins()
    {
        $this->getPlugin('base');
    }
    
    public function trigger($type, $data){}
    
    public function message($m){}
    
    public function command($m)
    {
        switch ($m['cmd'])
        {
            case '!move':
                // Get the command: join|part
                $command = (isset($m['me']['ex'][1]) ? strtolower($m['me']['ex'][1]) : null);

                // Get the channel.
                $channel = (isset($m['me']['ex'][2]) ? $m['me']['ex'][2] : null);

                // Get the password for the channel.
                $password = (isset($m['me']['ex'][3]) ? $m['me']['ex'][3] : null);

                // Only join if a channel was set.
                if($channel !== null)
                {
                    // Join/Part channel based on command.
                    switch ($command) 
                    {
                        case 'join':
                            $this->base->join($channel, $password);
                            break;

                        case 'part':
                            $this->base->part($channel);
                            break;

                        default:
                            $this->base->put($m['re'], 'PRIVMSG', 'Syntax: !move <join|part> <channel> [<password>]');
                            break;
                    }
                }
                else
                {
                    $this->base->put($m['re'], 'PRIVMSG', 'Syntax: !move <join|part> <channel> [<password>]');
                }
                break;
        }
    }
    
    public function triggerNum($num, $data){}
}

?>