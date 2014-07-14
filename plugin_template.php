<?php

/**
 * Template plugin
 * 
 * Template for plugins
 * 
 * @author Ben Thomson <ben.thomson@myport.ac.uk>
 * @copyright 2011+
 *
 *
 * Breakdown of the $m variable on the message($m) and command($m)
 * functions.
 *
 * $m['ty']       - String  - Type             - Message type: JOIN, PART, KICK, QUIT, NICK or MODE
 * $m['to']       - String  - To               - Where the message was sent e.g Test (a user) or #Test (a channel)
 * $m['fr']['na'] - String  - From name        - Sender name
 * $m['fr']['ma'] - String  - From mask        - Sender mask
 * $m['re']       - String  - Reply            - Where to reply name/channel e.g Test or #Test
 * $m['pm']       - Boolean - Private message  - Was it a private message? TRUE or FALSE
 * $m['me']['pl'] - String  - Message          - Complete message
 * $m['me']['ex'] - Array   - Message exploded - Message parts array split on space
 * $m['cmd']      - String  - Command          - Command with the prefix e.g !help
 *
 *
 *
 * The $data variable on the trigger($type, $data) function. When $type is:
 *
 * NICK
 * ---------
 * $data['o']            - String  - Original line from server
 * $data['from']['nick'] - String  - Original nick
 * $data['from']['mask'] - String  - Original mask
 * $data['to']           - String  - New nick
 *
 * KICK
 * ---------
 * $data['o']            - String  - Original line from server
 * $data['who']          - String  - Nick of who got kicked
 * $data['by']['nick']   - String  - Nick of who kicked a user
 * $data['by']['mask']   - String  - Mask of who kicked a user
 * $data['why']          - String  - Reason for kick
 * 
 * QUIT
 * ---------
 * $data['o']             - String  - Original line from server
 * $data['who']['nick']   - String  - Nick of who quit
 * $data['who']['mask']   - String  - Mask of who quit
 * $data['message']       - String  - Quit message
 *
 * PART
 * ---------
 * $data['o']             - String  - Original line from server
 * $data['who']['nick']   - String  - Nick of who left
 * $data['who']['mask']   - String  - Mask of who left
 * $data['channel']       - String  - Channel left
 * 
 * JOIN
 * ---------
 * $data['o']             - String  - Original line from server
 * $data['who']['nick']   - String  - Nick of who joined
 * $data['who']['mask']   - String  - Mask of who joined
 * $data['channel']       - String  - Channel joined
 * 
 * MODE
 * ---------
 * $data['o']             - String  - Original line from server
 * $data['who']['nick']   - String  - Nick of who set the mode
 * $data['who']['mask']   - String  - Mask of who set the mode
 * $data['channel']       - String  - Channel modes were set
 * $data['modes']         - Array   - Array of modes set e.g $data['modes'][0]['mode'] = '+h'  $data['modes'][0]['who'] = 'Test'
 *
 * 
 *
 * The triggerNum($num, $data) function is called when the server sends a numerical
 * response. Do a switch(){...} on $num to catch what is needed.
 *
 * 
 * 
 * Using other plugins
 *
 * To reference other plugins you need to include them in the getPlugins()
 * function. e.g $this->getPlugin('base');
 *
 * In order to use functions within the plugin you would do the following:
 *
 * $this->base->myFunction();
 *
 */

class template extends plugin {
    
    /**
     * Initialisation happens here
     */
    function __construct(){}
    
    /**
     * Close anything or shut down do in here.
     */
    function __destruct(){}
    
    
    /**
     * Call any plugins we require.
     */
    public function getPlugins()
    {
        $this->getPlugin('base');
    }
    
    /**
     * When server stuff happens this is called.
     * @param string $type - JOIN, PART, KICK, QUIT, NICK or MODE
     * @param array $data - associated data with it
     */
    public function trigger($type, $data){}
    
    /**
     * Triggered when a IRC response is a PRIVMSG or NOTICE
     * @param array $m - associated data
     * @return void
     */
    public function message($m){}
    
    /**
     * Triggered when a command is received.
     * @param array $m - associated data
     * @return void
     */
    public function command($m){}
    
    /**
     * Triggered on a numerical response from the server
     * @param int $num - numerical response
     * @param array $data - associated data
     * @return void
     */
    public function triggerNum($num, $data){}
}

?>