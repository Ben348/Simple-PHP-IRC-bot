<?php

/**
 * Bot Class
 * 
 * Base class for the bot
 * 
 * @author Ben Thomson <ben.thomson@myport.ac.uk>
 * @copyright 2011+
 */

include 'irc.php';
include 'handler.php';

class bot {
    
    /**
     * Server to connect to
     * @var string
     */
    private $irc = null;
    
    /**
     * List of channels
     * @var array
     */
    private $channel = array();
    
    /**
     * Bot identification variables
     * @var string
     */
    private $name, $nick, $mask, $nickToUse;
    
    /**
     * Bot identification password for irc
     * @var string
     */
    private $pass = false;
    
    /**
     * Nick counter - for use when nickname is already in use
     * @var integer
     */
    private $nickCounter = 1;
    
    /**
     * Command prefixes to watch for
     * @var array
     */
    private $commandPrefix = array('base' => '!');
    
    /**
     * Log file stream
     * @var resource
     */
    private $logStream;
    
    /**
     * Plugins to load
     * @var array
     */
    private $plugins = array();
    
    /**
     * Create a new IRC class and log stream
     * @return void
     */
    public function __construct()
    {
        // New IRC class.
        $this->irc = new irc();
        
        // Open a file to store log data in.
        $this->logStream = fopen('log.txt', 'a');
    }
    
    /**
     * Disconnect from server and close log file
     * @return void
     */
    public function __destruct()
    {
        // Disconnect from server.
        $this->disconnect();
        
        // Close log file.
        fclose($this->logStream);
    }
    
    /**
     * Connect to server and identify
     * @return void
     */
    public function connect()
    {
        // Setup the nick name for the bot.
        if(empty($this->nickToUse))
        {
            $this->nickToUse = $this->nick;
        }
        
        // Disconnect if we are already connected.
        if($this->irc->isConnected())
        {
            $this->irc->disconnect();
        }
        
        // Connect to the irc server.
        $this->irc->connect();
        
        // Specifies the <username> <hostname> <servername> <realname> of the bot.
        $this->irc->sendData('USER ' . $this->nickToUse . ' ' . $this->mask . ' ' . $this->nickToUse . ' :' . $this->name);
        
        // Set the irc nickname.
        $this->irc->sendData('NICK ' . $this->nickToUse);
        
        // Setup the checking variable.
        $checking = true;
        
        // Identify the bot and change the nick if already in use.
        do
        {
            // Get server response.
            $data = $this->irc->getData();
            
            // Nick in use - append with a number until we find a free nick.
            if(stripos($data, 'Nickname is already in use.') !== false)
            {
                // Change the nickname to use.
                $this->nickToUse = $this->nick . (++$this->nickCounter);
                
                // Send the change nick command to the server.
                $this->irc->sendData('NICK ' . $this->nickToUse);
            }
            
            // Found a nick that is free, join default channels.
            if (stripos($data, 'Welcome') !== false) 
            {
                // Identify the bot.
                $this->irc->sendData('PRIVMSG NICKSERV :IDENTIFY ' . $this->pass);
                
                // Join channels
                $this->joinChannel($this->channel);
                
                // We have finished with the loop, move on.
                $checking = false;
            }
            
            // Check if something went wrong.
            if (stripos($data, 'Registration Timeout') !== false || stripos($data, 'Erroneous Nickname') !== false || stripos($data, 'Closing Link') !== false)
            {
                //exit();
                die(); // TO-DO: Fix this
                // Log error
            }
        }
        while($checking == true);
        
        // Initiate the handler class - handles IRC responses & plugins.
        $handler = new handler($this, $this->irc);
    }
    
    /**
     * Disconnects from the server
     * @return void
     */
    public function disconnect()
    {
        if( $this->irc->isConnected() ) {
            $this->irc->disconnect();
        }
    }
    
    /**
     * Joins default channel(s)
     * @param array $channel - array of channel(s)
     * @return void
     */
    private function joinChannel($channel)
    {
        // Cast $channel to an array
        $channel = (array) $channel;

        // Loop through all channels in the array.
        foreach($channel as $chan)
        {
            // Only send join command if the channel name isn't empty.
            if(!empty($chan))
            {
                // Split channel up from the key.
                $chan = explode(' ', $chan);
                if(count($chan) <= 1)
                {
                    // Join channel without key.
                    $this->irc->sendData('JOIN ' . $chan[0]);
                }
                else
                {
                    // Join channel with a key.
                    $this->irc->sendData('JOIN ' . $chan[0] . ' ' . $chan[1]);
                }
            }
        }
    }
    
    /**
     * Sets the server
     * @param string $server - server address
     * @return void
     */
    public function setServer($server)
    {
        $this->irc->setServer($server);
    }
    
    /**
     * Sets the port
     * @param integer $port - server port
     * @return void
     */
    public function setPort($port)
    {
        $this->irc->setPort($port);
    }
    
    /**
     * Sets the bot name
     * @param string $name - bot name
     * @return void
     */
    public function setName($name)
    {
        $this->name = (string) $name;
    }
    
    /**
     * Sets the bot nickname.
     * @param string $nick - bot nickname.
     */
    public function setNick($nick)
    {
        $this->nick = (string) $nick;
    }
    
    /**
     * Sets the bots mask
     * @param string $mask - bot mask/hostname
     * @return void
     */
    public function setMask($mask)
    {
        $this->mask = (string) $mask;
    }
    
    /**
     * Sets the bot identification password
     * @param string|bool $password - password if needed, false if no password set
     * @return void
     */
    public function setPassword($password)
    {
        $this->pass = $password;
    }
    
    /**
     * Sets the default channel(s)
     * @param array $channel - array of default channel(s)
     * @return void
     */
    public function setChannel($channel)
    {
        $this->channel = (array) $channel;
    }
    
    /**
     * Add to the list of default plugins to load
     * @param string $plugin - plugin name
     * @return void
     */
    public function loadPlugin($plugin)
    {
        array_push($this->plugins, $plugin);
    }
    
    /**
     * Allows the handler class to retreive a list list of
     * default plugins to load
     * @return array - plugin names
     */
    public function getPluginList()
    {
        return $this->plugins;
    }
    
    /**
     * Gets the bots nickname
     * @return string - bots nick name
     */
    public function getName()
    {
        return $this->nickToUse;
    }
}
?>