<?php

/**
 * Parser Class
 * 
 * Parses all the input lines from the server to determine what plugin
 * functions need calling
 * 
 * Builds data arrays associated for each plugin base function
 * 
 * @author Ben Thomson <ben.thomson@myport.ac.uk>
 * @copyright 2011+
 */

class parser {
    
    /**
     * Reference to the bot
     * @var type
     */
    private $bot = null;
    
    /**
     * Setup the $bot references
     * @param type $bot - reference to the bot class instance
     * @return void
     */
    public function __construct(&$bot)
    {
        $this->bot = $bot;
    }
    
    /**
     * Initial parsing for the irc response
     * @param string $data - irc response
     * @return array - parsed data
     */
    public function parseLine($data)
    {
        // Remove linebreaks
        $data = $this->removeLineBreaks( $data );
        
        // Setup dummy array
        $parsed = array(
            'raw'       => $data,
            'prefix'  => null,
            'type'    => null,
            'sender'  => array(
                'type'  => null,
                'nick'  => null,
                'id'    => null,
                'mask'  => null,
            ), 
            'params'  => array(
                'full'     => null,
                'middle'   => null,
                'trailing' => null
            )
        );
        
        // Initial splitting of line to: <prefix> <type> <parameters>
        $parts = explode(' ', $data, 3);
        
        // Setup the prefix, type and parameters variables in the return array
        if(isset($parts[0]) && substr($parts[0], 0, 1) == ':' && isset($parts[1]) && isset($parts[2]))
        {
            $parsed['prefix'] = $parts[0];
            $parsed['type'] = $parts[1];
            $parsed['params']['full'] = $parts[2];
        }
        elseif(isset($parts[0]) && substr($parts[0], 0, 1) !== ':' && isset($parts[1]) && !isset($parts[2])){
            $parsed['prefix'] = null;
            $parsed['type'] = $parts[0];
            $parsed['params']['full'] = $parts[1];
        }
        else
        {
            return false;
        }
        
        // Retrieve sender information: <nick> <type> <id> <host/mask>
        preg_match( "/^:(.*)!(.*)@(.*)/", $parsed['prefix'], $match );
        
        // Check for server response - normally numerical message.
        if(isset($match[1]) && isset($match[2]) && isset($match[3]))
        {
            $parsed['sender']['type'] = 'client';
            $parsed['sender']['nick'] = $match[1];
            $parsed['sender']['id']   = $match[2];
            $parsed['sender']['mask'] = $match[3];
        }
        else
        {
            $parsed['sender']['type'] = 'server';
            $parsed['sender']['nick'] = substr($parsed['prefix'], 1);
        }
        
        // Split parameters into sections: full, middle, trailing
        $parts = explode(' ', $parsed['params']['full'], 2);
        
        // Parameters after the command
        $parsed['params']['middle'] = trim($parts[0]);
        
        // Trailing parameters if they exist - anything after last ':'
        $parsed['params']['trailing'] = isset($parts[1]) ? $parts[1] : '';
        
        // Return the parsed data
        return $parsed;
    }
    
    /**
     * Removes line breaks from the input string
     * @param string $data - irc response
     * @return string - replaced
     */
    private function removeLineBreaks($data)
    {
        return str_replace(array(chr(10), chr(13)), '', $data);
    }
    
    /**
     * Creates a data array for numerical messages
     * @param array $parsed - array of parsed data
     * @return array - data array
     */
    public function getNumericData($parsed)
    {
        $data['o']  = $parsed['raw'];
        $data['ty'] = $parsed['type'];
        return $data;
    }
    
    /**
     * Builds array based on server command/type
     * @param array $parsed - parsed data
     * @return array - data array
     */
    public function getServerData($parsed)
    {
        switch($parsed['type'])
        {
            case 'JOIN':
                return $this->getJoinData($parsed);
                break;
            case 'PART':
                return $this->getPartData($parsed);
                break;
            case 'QUIT':
                return $this->getQuitData($parsed);
                break;
            case 'KICK':
                return $this->getKickData($parsed);
                break;
            case 'NICK':
                return $this->getNickData($parsed);
                break;
            case 'MODE':
                return $this->getModeData($parsed);
                break;
            default:
                return null;
        }
    }
    
    /**
     * Creates a data array for server message: JOIN
     * @param array $parsed - array of parsed data
     * @return array - data array
     */
    private function getJoinData($parsed)
    {
        $data['o'] = $parsed['raw'];
        $data['who']['nick']  = $parsed['sender']['nick'];
        $data['who']['mask'] = $parsed['sender']['mask'];
        $data['channel'] = $parsed['params']['middle'];
        return $data;
    }
    
    /**
     * Creates a data array for server message: PART
     * @param array $parsed - array of parsed data
     * @return array - data array
     */
    private function getPartData($parsed)
    {
        $data['o'] = $parsed['raw'];
        $data['who']['nick']  = $parsed['sender']['nick'];
        $data['who']['mask'] = $parsed['sender']['mask'];
        $data['channel'] = $parsed['params']['middle']; // This line is different to getJoinData
        return $data;
    }
    
    /**
     * Creates a data array for server message: QUIT
     * @param array $parsed - array of parsed data
     * @return array - data array
     */
    private function getQuitData($parsed)
    {
        $data['o'] = $parsed['raw'];
        $data['who']['nick']  = $parsed['sender']['nick'];
        $data['who']['mask'] = $parsed['sender']['mask'];
        $data['message'] = $parsed['params']['trailing'];
        return $data;
    }
    
    /**
     * Creates a data array for server message: KICK
     * @param array $parsed - array of parsed data
     * @return array - data array
     */
    private function getKickData($parsed)
    {
        // Split the nick and channel
        $who = explode(' ', $parsed['params']['full']);
        
        // Build array
        $data['o'] = $parsed['raw'];
        $data['who'] = $who[1];
        $data['channel'] = $who[0];
        $data['by']['nick'] = $parsed['sender']['nick'];
        $data['by']['mask'] = $parsed['sender']['mask'];
        $data['why'] = $parsed['params']['trailing'];
        return $data;
    }
    
    /**
     * Creates a data array for server message: NICK
     * @param array $parsed - array of parsed data
     * @return array - data array
     */
    private function getNickData($parsed)
    {
        $data['o'] = $parsed['raw'];
        $data['from']['nick'] = $parsed['sender']['nick'];
        $data['from']['mask'] = $parsed['sender']['mask'];
        $data['to'] = $parsed['params']['middle'];
        return $data;
    }
    
    /**
     * Creates a data array for server message: MODE
     * @param array $parsed - array of parsed data
     * @return array - data array
     */
    private function getModeData($parsed)
    {
        // Split the raw line into segmants
        $seg = explode(' ', $parsed['raw'], 4);
        
        // Setup a few variables
        $data['o'] = $parsed['raw'];
        $data['who']['nick'] = $parsed['sender']['nick'];
        $data['who']['mask'] = $parsed['sender']['mask'];
        $data['channel'] = $seg[0];
        $data['modes'] = array();
        
        // Only carry on if the line was in the correct format
        if(isset($seg[3]))
        {
            // Remove first colon
            if(stripos($seg[3], ':')) $seg[3] = substr($seg[3], 1, strlen($seg[3]) - 1);
            
            // Split the modes from parameters
            $mode_param = explode(' ', $seg[3], 2);
            
            // Put modes and parameters into their own variables
            $paramaters = (isset($mode_param[1]) ? $mode_param[1] : null);
            $modes = $mode_param[0];
            $data['params'] = $paramaters;
            
            // Seperate each mode into its own array: $data['modes']['mode']
            $modes = str_split($modes);
            
            // Mode prefix - used in loop
            $prefix = '';
            
            // Loop through each mode building mode arrays
            foreach($modes as $mode)
            {
                if($mode === '+' || $mode === '-')
                {
                    $prefix = $mode;
                    continue;
                }
                // TODO: Finish who
                array_push($data['modes'], array('mode' => $prefix.$mode, 'who' => ''));
            }
        }
        else
        {
            return false;    
        }
        
        // Return the data array
        return $data;
    }
    
    /**
     * Creates a data array for message/command responses.
     * @param array $parsed - array of parsed dat
     * @return array - data array
     */
    public function getMessageData($parsed)
    {
        $m = array();
        $m['ty'] = $parsed['type'];
        $m['to'] = $parsed['params']['middle'];
        $m['fr']['na'] = $parsed['sender']['nick'];
        $m['fr']['ma'] = $parsed['sender']['mask'];
        if ($m['to'] === $this->bot->getName()) {
          $m['re'] = $m['fr']['na'];
          $m['pm'] = TRUE;
        } else {
          $m['re'] = $m['to'];
          $m['pm'] = FALSE;
        }
        $m['me']['pl'] = $parsed['params']['trailing'];
        $m['me']['ex'] = explode(' ', $m['me']['pl']);
        $m['cmd'] = strtolower($m['me']['ex'][0]);
        #$m['me']['ex'][0] = substr($m['me']['ex'][0], 1);
		return $m;
    }
}

?>