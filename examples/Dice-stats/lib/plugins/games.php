<?php

/**
 * Admin plugin
 * 
 * Defines commands for top admins and sub admins.
 * 
 * @author Ben Thomson <ben.thomson@myport.ac.uk>
 * @copyright 2011+
 */

class games extends plugin {
    
    /**
     * Stores all the current games. Key being hostname.
     */
    private $games = array();

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
        $this->getPlugin('hosts');
        $this->getPlugin('stats');
    }
    
    /**
     * When server stuff happens this is called.
     * $type can be: JOIN, PART, KICK, QUIT, NICK or MODE.
     */
    public function trigger($type, $data){}
    
    /**
     * Triggered when a IRC response is a PRIVMSG or NOTICE
     */
    public function message($m){}
    
    /**
     * Triggered when a command is received.
     */
    public function command($m)
    {
        switch ($m['cmd']) 
        {
            case '!dd':

                // No PM or notices.
                if($m['pm'] || $m['ty'] === 'NOTICE') return;

                // Must be logged in as a host.
                if($this->hosts->is_host($m['fr']['na']))
                {
                    if($this->hosts->is_loggedIn($m['fr']['na']) !== true)
                    {
                        $this->base->put($m['fr']['na'], 'NOTICE', 'You must be logged in to use this command.');
                        $this->hosts->login($m['fr']['na']);
                        return;
                    }
                }
                else {
                    return;
                }

                // Check the host is not already hosting a game.
                if($this->inGame($m['fr']['na'], true))
                {
                    $this->base->put($m['re'], 'PRIVMSG', $m['fr']['na'].' is already hosting a game.');
                    return;
                }

                // Check the syntax was correct.
                if(fh::checkFormat(end($m['me']['ex'])) === true && count($m['me']['ex']) >= 4 && !in_array(' ', $m['me']['ex']))
                {
                    // Get the players in their own array
                    $players = array_slice($m['me']['ex'], 1, -1);

                    // Check players aren't playing against themselves.
                    if(count($players) != count(array_unique($players)))
                    {
                        $this->base->put($m['re'], 'PRIVMSG', 'A player can not play against themselves.');
                        return;
                    }

                    // Check no users are in a game.
                    foreach ($players as $player)
                    {
                        if($this->inGame($player))
                        {
                            $this->base->put($m['re'], 'PRIVMSG', $player . " is already in a game.");
                            return;
                        }
                    }

                    // Create the game.
                    $game = new dice($m['fr']['na'], $players, end($m['me']['ex']), 5, $m['re'], $this->base, array($this, 'ddFinished'), $this->stats);

                    // Add to the games list.
                    $this->games[strtolower($m['fr']['na'])] = $game;

                    // Start the game!
                    $game->start();
                }   
                else
                {
                    $this->base->put($m['fr']['na'], 'NOTICE', 'Syntax: !dd <player> <player> [<player>,] <bet>');
                }

                break;
            
            case '!del':
                // No PM or notices.
                if($m['pm'] || $m['ty'] === 'NOTICE') return;

                // Must be logged in as a host.
                if($this->hosts->is_host($m['fr']['na']))
                {
                    if($this->hosts->is_loggedIn($m['fr']['na']) !== true)
                    {
                        $this->base->put($m['fr']['na'], 'NOTICE', 'You must be logged in to use this command.');
                        $this->hosts->login($m['fr']['na']);
                        return;
                    }
                }
                else {
                    return;
                }

                // Allow admins level 3 or above to delete other hosts games.
                if(isset($m['me']['ex'][1]) === true && $this->hosts->admin_level($m['fr']['na']) >= 3)
                {
                    if($this->hosts->is_host($m['me']['ex'][1]))
                    {
                        foreach ($this->games as $game) 
                        {
                            if(strtolower($m['me']['ex'][1]) === strtolower($game->getHost()))
                            {
                                $game->delete($m['fr']['na']);
                                return;
                            }
                        }
                        $this->base->put($m['fr']['na'], 'NOTICE', $m['me']['ex'][1].' is not currently hosting any games.');
                        return;
                    }
                    else
                    {
                        $this->base->put($m['fr']['na'], 'NOTICE', $m['me']['ex'][1].' is not a host.');
                        return;
                    }
                }

                // Delete own hosts game.
                foreach($this->games as $game)
                {
                     if(strtolower($m['fr']['na']) === strtolower($game->getHost()))
                     {
                        $game->delete();
                        return;
                     }
                }
                // Not hosting any games
                $this->base->put($m['fr']['na'], 'NOTICE', "You are not currently hosting any games.");

                break;

            case '!60':
            case '!60x2':

                // No PM or notices.
                if($m['pm'] || $m['ty'] === 'NOTICE') return;

                // Must be logged in as a host.
                if($this->hosts->is_host($m['fr']['na']))
                {
                    if($this->hosts->is_loggedIn($m['fr']['na']) !== true)
                    {
                        $this->base->put($m['fr']['na'], 'NOTICE', 'You must be logged in to use this command.');
                        $this->hosts->login($m['fr']['na']);
                        return;
                    }
                }
                else {
                    return;
                }

                // Check the syntax was correct.
                if(isset($m['me']['ex'][1]) && isset($m['me']['ex'][2]) && fh::checkFormat($m['me']['ex'][2]))
                {
                    // Create the game.
                    $percentile = new percentile($m['fr']['na'], $m['me']['ex'][1], $m['me']['ex'][2], $m['re'], $this->base);

                    // Start the game!
                    $percentile->start();
                }   
                else
                {
                    $this->base->put($m['fr']['na'], 'NOTICE', 'Syntax: !60x2 <player> <bet>');
                }
                break;

            case '!roll':
                foreach ($this->games as $game) 
                {
                    if($this->inGame($m['fr']['na'])) $game->roll($m['fr']['na']);
                }
                break;

            case '!stats':
                var_dump($m['me']['ex']);
                if(count($m['me']['ex']) > 1)
                {
                    if(isset($m['me']['ex'][1]))
                    {
                        if(isset($m['me']['ex'][2]))
                        {  
                            switch (strtolower($m['me']['ex'][2])) 
                            {
                                case 'all':
                                case 'dd':
                                case 'pd':
                                case 'uo':
                                    $this->base->put($m['re'], 'PRIVMSG', $this->stats->statString($m['me']['ex'][1], strtolower($m['me']['ex'][2])));
                                    break;
                                default:
                                    $this->base->put($m['fr']['na'], 'NOTICE', 'Syntax: !stats [<player>] [<all|dd|pd|uo>]');
                                    break;
                            }
                        }
                        else
                        {
                            $this->base->put($m['re'], 'PRIVMSG', $this->stats->statString($m['me']['ex'][1]));
                        }
                    }
                    else
                    {
                        $this->base->put($m['fr']['na'], 'NOTICE', 'Syntax: !stats [<player>] [<all|dd|pd|uo>]');
                    }
                }
                else
                {
                    $this->base->put($m['re'], 'PRIVMSG', $this->stats->statString($m['fr']['na']));
                }

                break;
            default:
                break;
        }
    }
    
    /**
     * Triggered on a numerical response from the server.
     */
    public function triggerNum($num, $data){}

    public function inGame($user, $host = false)
    {
        foreach($this->games as $game)
        {
            if($host === true){
                if(strtolower($game->getHost()) === strtolower($user)) return true;
            }
            else{
                if($game->inGame(strtolower($user))) return true;
            }
        }
    }

    public function ddFinished($game)
    {
        unset($this->games[strtolower($game->getHost())]);
    }
}


class dice
{
    /**
     * Refernce to the base class - used for the ->sendData() function.
     * @var oject
     */
    private $host;

    /**
     * List of players - access: $players[$name]['name'] & $players[$name]['roll']
     * @var array
     */
    private $players = null;

    /**
     * The bet placed.
     * @var string
     */
    private $bet;

    /**
     * Comission to take.
     * @var int
     */
    private $comission;

    /**
     * Channel the game is hosted in.
     * @var string
     */
    private $channel;

    /**
     * Refernce to the base class - used for the ->sendData() function.
     * @var oject
     */
    private $base = null;

    /**
     * Reference to the stats class - used to log stats.
     * @var object
     */
    private $stats = null;

    /** 
     * Stores the game id for stats.
     * @var int
     */
    private $game_id = null;

    /**
     * Callback function for when game has completed. Used to remove from game list.
     * @var callback
     */
    private $_remove = null;

    /**
     * Game status.
     * @var bool
     */
    private $paused = false;

    /**
     * Game type.
     * @var string
     */
    private $type = 'Dice Duel';

    /**
     * Array of losers - for FFA.
     * @var string
     */
    private $losers = array();

    /**
     * Setup the dice game.
     * @param string $host - host name.
     * @param array $players - list of players in the game.
     * @param string $bet - the bet (NOT THE POT).
     * @param int $com - comission to take.
     * @param string $channel - channel the game is hosted in.
     * @param object &$base - reference to the base class (To send messages back).
     * @param callback $_remove - callback function for when the game has finished.
     */
    function __construct($host, $players, $bet, $com, $channel, &$base, $_remove, &$stats)
    {
        $this->host = $host;
        $this->bet = $bet;
        $this->comission = $com;
        $this->channel = $channel;
        $this->base = &$base;
        $this->stats = &$stats;
        $this->_remove = $_remove;
        $this->type = (count($players) > 2) ? 'FFA' : 'Dice Duel';
        foreach ($players as $player) {
            $this->players[strtolower($player)] = array('name' => $player, 'roll' => null);
        }

        // Create a new game in the database.
        $pot = fh::getPot($this->bet, count($this->players), $com);
        $this->game_id = $this->stats->newDD($host, $players, $bet, $pot, $com);
    }

    /**
     * Checks if the host is in a game.
     * @param string $player - player to check for.
     * @return bool - TRUE if the player is in the game, FALSE otherwise.
     */
    public function inGame($player)
    {
        return array_key_exists(strtolower($player), array_change_key_case($this->players, CASE_LOWER));
    }

    /**
     * Gets the name of the host hosting the game.
     * @return string - name of host.
     */
    public function getHost()
    {
        return $this->host;
    }


    /**
     * Get the game status.
     * @return bool - TRUE if game is paused, FALSE otherwise.
     */
    public function isPaused()
    {
        return $this->paused;
    }

    /**
     * Starts the dice duel - output all messages etc...
     */
    public function start()
    {
        // Restart the game if its paused.
        if($this->paused === true) {$this->paused = false; return;}

        // Get the players in a string
        $player_str = fh::joinNames(fh::array_values_2d($this->players, 'name'), '[t2]', '[/t2]');

        // Get the pot.
        $pot = fh::getPot($this->bet, count($this->players), $this->comission);

        // Send the start message
        $this->send('[gy2]['.$this->type.'][/gy2] '.$player_str.'! [gn2]'.$pot.' pot[/gn2] Type !roll to roll now!! Hosted by [o]['.$this->host.'][/o]');
    }

    /**
     * Pauses the game.
     */
    public function pause()
    {
        $this->paused = true;
    }

    /**
     * Stops & deletes the current game.
     */
    public function delete($who = null)
    {
        // List of players in nice string format.
        $player_str = fh::joinNames(fh::array_values_2d($this->players, 'name'), '[t2]', '[/t2]');

        // Send the message.
        $this->send('[gy2]['.$this->type.'][/gy2] The game between '.$player_str.' [o][Hosted by '.$this->host.'][/o] was [r]ended[/r]' . (($who != null) ? ' by [t2]['.$who.'][/t2].' : '.'));

        // Remove the game from the array.
        $this->finished('-', null, false, '0m', $who);
    }


    /**
     * Rolls a random dice
     * @param string $player - The player to roll for.
     */
    public function roll($player)
    {
        $player = strtolower($player);
        if(array_key_exists($player, $this->players) && $this->paused === false)
        {
            if($this->players[$player]['roll'] === null)
            {
                // Set the roll.
                $this->players[$player]['roll'] = fh::rand(2,12);

                // Log the roll in the database.
                $this->stats->newRoll($this->game_id, $player, $this->players[$player]['roll']);

                // Send the roll message.
                $this->send('[gy2]['.$this->type.'][/gy2] [t2]'.$this->players[$player]['name'].'[/t2] rolled a [r]'.$this->players[$player]['roll'].'[/r] on [o]Two Six-sided Dice[/o]');

                // Check for winner if this was the last roll.
                if($this->allRolled()) $this->checkWinner();
            }
        }
    }

    /**
     * Checks all players have rolled
     * @return bool - TRUE if all players have rolled, FALSE otherwise.
     */
    private function allRolled()
    {
        return in_array(null, fh::array_values_2d($this->players, 'roll')) === true ? false : true ;
    }

    /**
     * Checks for a winner or tie. Calls appropiate functions & re-sets game on ties.
     */
    private function checkWinner()
    {
        // Get a list of rolls in a new array: name => roll
        $rolls = fh::array_values_2d($this->players, 'roll', true);

        // Remove previous losers from this array
        foreach ($rolls  as $name => $roll) {
            if(in_array($name, $this->losers)) unset($rolls[$name]);
        }

        // Get the highest rolls.
        $win_roll = max($rolls);

        // Get the winning player/-s
        $winner = array_keys($rolls, $win_roll);


        // Get the lowest roll - for dice duel only.
        $lose_roll = min($rolls);

        // Get the losing players
        $loser = array_keys($rolls, $lose_roll);
		
		if($win_roll !== $lose_roll)
		{
			// Wack the losers in the loser array.
			foreach ($loser as $player) {
				$this->losers[] = $player;
			}
		}

        // Check if we have a tie.
        if(count($winner) >= 2)
        {
            // Reset the rolls for the people who tied.
            foreach($winner as $player)
            {
                $this->players[$player]['roll'] = null;
                $wn[] = $this->players[$player]['name'];
            }

            // Get the players in a string.
            $player_str = fh::joinNames($wn, '[t2]', '[/t2]');

            // Send the tie message.
            $this->send('[gy2]['.$this->type.'][/gy2] '.$player_str.' tied on a [r]'.$win_roll.'[/r] Roll again now!');

            // Exit function.
            return;
        }
        else
        {
            // Get the pot.
            $pot = fh::getPot($this->bet, count($this->players), $this->comission);

            // Free for all message
            if($this->type === 'FFA')
            {
                $this->send('[gy2]['.$this->type.'][/gy2] [t2]'.$this->players[$winner[0]]['name'].'[/t2] won the [gn2]'.$pot.' pot[/gn2] with a final roll of [r]'.$win_roll.'[/r].Hosted by [o]['.$this->host.'][/o]');
            }
            else // Dice Duel message
            {
                $this->send('[gy2]['.$this->type.'][/gy2] [t2]'.$this->players[$winner[0]]['name'].'[/t2] won the [gn2]'.$pot.' pot[/gn2] against [t2]'.$this->players[$loser[0]]['name'].'[/t2]: [r]'.$win_roll.'[/r] - [r]'.$lose_roll.'[/r]. Hosted by [o]['.$this->host.'][/o]');
                
            }

            // Call the finish function
            $this->finished($this->players[$winner[0]]['name'], $this->losers, true, $pot);
        }
    }

    /**
     * Calls the callback function to remove dice duel from the game array.
     */
    private function finished($winner, $losers, $finished, $pot, $who = null)
    {
        // Update the game stats.
        $this->stats->ddfin($this->game_id, $winner, $finished, $who);
        if($finished)
        {
            // update the players stats - winner.
            $this->stats->updateStats($winner, $this->bet, $pot, true, 'dd');

            // update the losers.
            foreach ($losers as $loser) {
                $this->stats->updateStats($loser, $this->bet, $pot, false, 'dd');
            }
        }

        // Remove the game from the game array.
        call_user_func_array($this->_remove, array($this));
    }
    
    /**
     * Sends data to the server in the correct channel.
     * @param string $msg - message to send.
     * @param string $type - PRIVMSG or NOTICE.
     */
    private function send($msg, $type = 'PRIVMSG')
    {
        $this->base->put($this->channel, $type, $msg);
    }
}

class percentile
{
    /**
     * Refernce to the base class - used for the ->sendData() function.
     * @var oject
     */
    private $host;

    /**
     * List of players - access: $players[$name]['name'] & $players[$name]['roll']
     * @var array
     */
    private $players = null;

    /**
     * The bet placed.
     * @var string
     */
    private $bet;

    /**
     * Channel the game is hosted in.
     * @var string
     */
    private $channel;

    /**
     * Refernce to the base class - used for the ->sendData() function.
     * @var oject
     */
    private $base = null;

    function __construct($host, $player, $bet, $channel, &$base)
    {
        $this->host = $host;
        $this->player = $player;
        $this->bet = $bet;
        $this->base = &$base;
        $this->channel = $channel;
    }

    /**
     * Starts the percentile duel & outputs the winning/losing messages.
     */
    public function start()
    {
        // Get the roll.
        $roll = $this->getRoll();

        // Get the pot
        $pot = fh::getPot($this->bet);

        // Output correct message.
        if($roll == 60) // Re-roll or refund.
        {
            $this->send('[gy2][60x2][/gy2] [t2]'.$this->host.'[/t2] rolled a [r]'.$roll.'[/r] on the percentile dice! Roll again or refund!');
        }
        elseif($roll >= 61) // Player win.
        {
            $this->send('[gy2][60x2][/gy2] [t2]'.$this->host.'[/t2] rolled a [r]'.$roll.'[/r] on the percentile dice! '.$this->player.' won the [gn2]'.$pot.' pot[/gn2]');
        }
        elseif($roll <= 59) // Host win.
        {
            $this->send('[gy2][60x2][/gy2] [t2]'.$this->host.'[/t2] rolled a [r]'.$roll.'[/r] on the percentile dice! Better luck next time [t2]'.$this->player.'![/t2]');
        }
    }

    /**
     * Calculates the roll by rolling 2 dice.
     * @return float - number rolled.
     */
    private function getRoll()
    {
        // Calculate the roll.
        $roll_1 = fh::rand(0, 9);
        $roll_2 = fh::rand(0, 9);

        // Final roll.
        $final = ($roll_1 . $roll_2);

        // Parsing.
        if($final == 00) $final = 100;
        if($roll_1 == 0 && $roll_2 != 0) $final = $roll_2;

        // Retun the roll
        return $final;
    }

    /**
     * Sends data to the server in the correct channel.
     * @param string $msg - message to send.
     * @param string $type - PRIVMSG or NOTICE.
     */
    private function send($msg, $type = 'PRIVMSG')
    {
        $this->base->put($this->channel, $type, $msg);
    }
}

class functionHelper
{
    /**
     * Removes line breaks from the input string.
     * @param string $data - irc response.
     */
    public function removeLineBreaks($data)
    {
        return str_replace(array(chr(10), chr(13)), '', $data);
    }

    /**
     * Joins an array of names together seperated by ',' and 'and'.
     * @param array $aNames - array of names to join.
     * @param string $ws - start tag to wrap name in.
     * @param string $we - end tag to wrap name in.
     * @return string - joined names.
     */
    public function joinNames(array $aNames, $ws = '', $we = '')
    {
        $sResult = $ws . array_pop($aNames) . $we;
        if(!empty($aNames))
        {
            $sResult = $ws . implode(($we.', '.$ws), $aNames) . ($we.' and ') . $sResult;
        }
        return preg_replace('!\s+!', ' ', $sResult);
    }

    /**
     * Returns vaues from given key in a 2d array.
     * @param array $array - parent array to search in.
     * @param string $key - key to get values from.
     * @param string $outKey - key value for new array - modified for the dice plugin.
     * @return array - array of values.
     */
    public function array_values_2d(array $array, $key, $outKey = false)
    {
        foreach ($array as $k => $value) {
            if($outKey === false)
                $output_arr[] = $value[$key];
            else
                $output_arr[$k] = $value[$key];
        }
        return $output_arr;
    }

    /**
     * Provides a random number within a range from /dev/urandom.
     * @param int $min - bottom number of range.
     * @param int $max - top number of range.
     * @return int in *closed* interval.
     */
    public static function rand($min = 0, $max = 0x7FFFFFFF) 
    {
        $diff = $max - $min;
        if ($diff < 0 || $diff > 0x7FFFFFFF) { 
            return mt_rand(2,12); // Bad range
        }
        $bytes = mcrypt_create_iv(4, MCRYPT_DEV_URANDOM);
        if ($bytes === false || strlen($bytes) != 4) {
            return mt_rand(2,12);  // Unable to get 4 bytes
        }
        $ary = unpack("Nint", $bytes);
        $val = $ary['int'] & 0x7FFFFFFF;   // 32-bit safe                           
        $fp = (float) $val / 2147483647.0; // convert to [0,1]                          
        return round($fp * $diff) + $min;
    }

    /**
     * Checks if a string ends in specified character/-s.
     * @param string $string - string to search in.
     * @param string $needle - string to look for at the end.
     * @return bool - TRUE or FALSE.
     */
    public static function endsWith($string, $needle)
    {
        return substr_compare($string, $needle, -strlen($needle), strlen($needle)) === 0;
    }

    /**
     * Checks the format of the bet
     * @param string $number - bet to check format of.
     * @return bool - TRUE or FALSE
     */
    public static function checkFormat($number)
    {
        $pattern = "/^[0-9][0-9\.,]*(k|b|m|gp)?$/i"; 
        if (preg_match($pattern, $number)) { 
            return true;
        } else { 
            return false;
        } 
    }

    /**
     * Strips all the K,M,GP etc.. and replaces it with the raw format.
     * @param int $num - number with letters.
     * @return int - raw number stripped of all letters.
     */
    public static function toRaw($num)
    {
        if(self::endsWith($num, 'gp')){
            $num = substr($num,0,-2);
        }
        elseif(self::endsWith($num, 'k')){
            $num = substr($num, 0, -1) * 1000;
        }
        elseif(self::endsWith($num, 'm')){
            $num = substr($num, 0, -1) * 1000000;
        }
        elseif(self::endsWith($num, 'b')){
            $num = substr($num, 0, -1) * 1000000000;
        }
        return (float) $num;
    }

    /**
     * Converts large numbers to smaller numbers with letter (K,M,B etc..) at the end.
     * @param int $num - number to reduce with letters.
     * @return string - large number replaces with appropiate letter.
     */
    public static function toReadable($num)
    {
        if($num <= 999){
            return $num . "gp";
        }
        elseif($num >= 1000 && $num <= 999999){
            return $num/1000 . "k"; 
        }
        elseif($num >= 1000000 && $num <= 999999999){
            return $num/1000000 . "m";
        }
        elseif($num >= 1000000000){
            return $num/1000000000 . "b";
        }
    }

    public static function raw($num)
    {
        if(is_numeric($num))
        {
            return (float) $num;
        }
        if(strlen($num) > 1)
        {
            if(self::endsWith($num, 'gp')){
                $num = substr($num,0,-2);
            }
            elseif(self::endsWith($num, 'k')){
                $num = substr($num, 0, -1) * 1000;
            }
            elseif(self::endsWith($num, 'm')){
                $num = substr($num, 0, -1) * 1000000;
            }
            elseif(self::endsWith($num, 'b')){
                $num = substr($num, 0, -1) * 1000000000;
            }
        }
        else
        {
            $num = 0;
        }
        return (float) $num;
    }

    public static function readable($num)
    {
        if($num <= 999){
            return $num . "gp";
        }
        elseif($num >= 1000 && $num <= 999999){
            return $num/1000 . "k"; 
        }
        elseif($num >= 1000000 && $num <= 999999999){
            return $num/1000000 . "m";
        }
        elseif($num >= 1000000000){
            return $num/1000000000 . "b";
        }
    }

    /**
     * Calculates the final pot for number of players & comission.
     * @param string $bet - amount each player bet.
     * @param int $players - number of players in the game.
     * @param int $comission - comission to take from the pot.
     * @return string - final pot in readable format.
     */
    public static function getPot($bet, $players = 2, $comission = 0)
    {
        return self::toReadable((self::toRaw($bet) * $players) * ((100 - $comission)/100));
    }
}

class fh extends functionHelper{}

?>