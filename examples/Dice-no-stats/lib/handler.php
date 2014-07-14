<?php

/**
 * Handler Class
 * 
 * Handles all IRC messages/commands & plugin calls
 * 
 * @author Ben Thomson <ben.thomson@myport.ac.uk>
 * @copyright 2011+
 */

include 'parser.php';
include 'plugin.php';

class handler {

    /**
     * Reference to the bot instance
     * @var type
     */
    private $bot = null;
    
    /**
     * Reference to the irc connection
     * @var type
     */
    private $irc = null;
    
    /**
     * Parser class to handle irc responses
     * @var type
     */
    private $parser;
    
    /**
     * List of loaded plugins
     * @var array
     */
    private $plugins = array();
    
    /**
     * Setup the $bot and $irc references
     * @param type $bot - reference to the bot class instance
     * @param type $irc - reference to the irc class instance
     */
    public function __construct(&$bot, &$irc)
    {
        // Setup references
        $this->bot = $bot;
        $this->irc = $irc;
        
        // Setup a new parser class - handles all the input responses from the server
        $this->parser = new parser($bot);
        
        // Load the plugins
        foreach($this->bot->getPluginList() as $plugin)
        {
            $this->loadPlugin($plugin);
        }
        
        // Start reading the input
        $this->main();
    }
    
    /**
     * Main event loop
     * @return void
     */
    private function main()
    {
        do
        {
            // Get the irc response
            $data = $this->irc->getData();
            
            // Parse the input line
            $parsed = $this->parser->parseLine($data);
            
            // Determine what plugin function we need to call
            if($parsed !== false && isset($parsed['type']))
            {
                // Numerical response from server
                if(is_numeric($parsed['type']))
                {
                    // Trigger the 'triggerNum()' function
                    $this->triggerPlugin($this->parser->getNumericData($parsed), 'triggerNum', $parsed['type']);
                }
                elseif(in_array($parsed['type'], array('JOIN', 'PART', 'KICK', 'QUIT', 'NICK', 'MODE')))
                {
                    // Trigger the 'trigger()' function
                    $this->triggerPlugin($this->parser->getServerData($parsed), 'trigger', $parsed['type']);
                }
                elseif($parsed['type'] == 'PRIVMSG' || $parsed['type'] == 'NOTICE')
                {
                    // Remove first colon
                    $parsed['params']['trailing'] = substr($parsed['params']['trailing'], 1);
                    
                    // Check if it was a command or just a message
                    $function = ($parsed['params']['trailing'][0] === '!' ? 'command' : 'message');
                    
                    // Trigger the 'message()' or 'command()' function
                    $this->triggerPlugin($this->parser->getMessageData($parsed), $function);
                }
            }
            
            // Split the raw input line into arguments
            $args = explode(' ', $data);
            
            // Respond to PING requests to stay connected to the server
            if($args[0] == 'PING')
            {
                // Response with 'PONG'
                $this->irc->sendData('PONG ' . $args[1]);
            }
            if($args[0] == 'PING' && !isset($args[3]))
            {
                // Skip the rest of the loop
                continue;
            }
        }
        while($this->irc->isConnected());
    }
    
    /**
     * Loads plugins
     * @param string $plugin - plugin name
     * @return void
     */
    private function loadPlugin($plugin)
    {
        // Only add if it doesn't already exists
        if(!class_exists($plugin))
        {
            // Include the new plugin file
            require_once("plugins/{$plugin}.php");
            
            // Create a new instance of the plugin
            $instance = new $plugin;
            
            // Add the plugin to the plugins array
            $this->plugins[$plugin] =& $instance;
            
            // Create a reference to the plugin array on the new plugin
            $instance->loadPluginRef($this->plugins);
            
            // Load the IRC reference - used for sendData() function
            $instance->loadIRCref($this->irc);
            
            // Call the getPlugins() function on the new plugin to set it up
            $instance->getPlugins();
        }
    }
    
    /**
     * Removes the plugin from the arrays - can no longer be used after this
     * @param string $plugin - plugin name
     * @return void
     */
     private function removePlugin($plugin)
     {
        unset($this->plugins[$plugin]);
     }
    
    /**
     * Trigger commands for all plugins
     * @param array $data - data array associated with function being called
     * @param string $function - function to call, e.g message($m), triggerNum($,$)
     * @param string|int $type - OPTIONAL - used for trigger() and triggerNum() functions
     */
    private function triggerPlugin($data, $function, $type = false)
    {
        // Loops through all the plugins calling the functions
        foreach($this->plugins as $plugin => $obj)
        {
            // Only pass 1 parameter to the plugin function
            if($type === false)
            {
                // Triggers plugin function.
                call_user_func_array(array($obj, $function), array($data));
                
            }
            // Pass 2 parameters to the plugin function
            else 
            {
                // Triggers plugin function
                call_user_func_array(array($obj, $function), array($type, $data)); 
            }
        }
    }
}

?>