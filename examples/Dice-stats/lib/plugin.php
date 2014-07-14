<?php

/**
 * Plugin Class
 * 
 * Defines the base functions for the plugin class and handles 
 * some behind-the-scenes stuff
 * 
 * @author Ben Thomson <ben.thomson@myport.ac.uk>
 * @copyright 2011+
 */

abstract class plugin {

    /**
     * Reference to the list of currently loaded plugins
     * @var array
     */
    private $plugins;
    
    /**
     * Reference to the irc connection
     * @var type
     */
    public $irc;
    
    /**
     * Initialisation happens here
     * @return void
     */
    function __construct(){}
    
    /**
     * Close anything or shut down do in here
     * @return void
     */
    function __destruct(){}
    
    /**
     * Loads the plugin reference to the base of the plugin.
     * Used for including other plugins in custom plugin
     * @var ref array $plugins - list of plugins
     */
    final function loadPluginRef(&$plugins)
    {
        $this->plugins =& $plugins;
    }
    
    /**
     * Setup IRC reference
     * @var ref
     */
    final function loadIRCref(&$irc)
    {
        $this->irc =& $irc;
    }
    
    /**
     * Includes all the requested plugins
     * See getPlugin() function
     */
    public function getPlugins(){}
    
    /**
     * Includes the requested plugin classes in custom plugin
     * @param string $plugin - plugin name e.g 'commands'
     */
    final function getPlugin($plugin)
    {
        // Setup new plugin object, usage: $this->pluginName
        $this->$plugin = $this->plugins[$plugin];
    }
    
    /**
     * Server stuff function e.g MODE, JOIN, PART etc..
     * Called everyime this happens
     */
    public function trigger($type, $data){}
    
    /**
     * Triggered when a IRC response is a PRIVMSG or NOTICE
     */
    public function message($m){}
    
    /**
     * Triggered when a IRC response is a PRIVMSG or NOTICE
     * BUT also the first letter starts with the command prefix
     */
    public function command($m){}
    
    /**
     * Triggered on a numerical response from the server
     */
    public function triggerNum($num, $data){}
}

?>