<?php
class game {

    private $host, $bet, $comission, $game_arr, $channel;
    private $base = null;
    public $players = array();
    private $paused = false;

    function __construct($host, $players, $bet, &$base, $channel, $comission, &$game_arr)
    {
        $this->host = $host;
        $this->bet = $bet;
        $this->comission = $comission;
        $this->channel = $channel;
        foreach ($players as $player) {
            $this->players[strtolower($player)] = array('name' => $player, 'roll' => null);
        }
        $this->base = &$base;
        $this->game_arr = &$game_arr;
    }

    public function get_host()
    {
        return $this->host;
    }

    public function in_game($player)
    {
        return array_key_exists(strtolower($player), array_change_key_case($this->players, CASE_LOWER));
    }

    public function is_paused()
    {
        return $this->paused;
    }

    public function roll($player)
    {
        $player = strtolower($player);
        if(array_key_exists($player, $this->players) && $this->paused === false)
        {
            if($this->players[$player]['roll'] === null)
            {
                // Get the roll.
                $roll = $this->devurandom_rand(2,12);

                // Set the roll.
                $this->players[$player]['roll'] = $roll;

                // Tell the IRC the roll.
                $this->base->put($this->channel, 'PRIVMSG', "[gy2][Dice Duel][/gy2] [t2]".$this->players[$player]['name']."[/t2] rolled a [r]".$roll."[/r] on [o]Two Six-sided Dice[/o]");
                
                // Check for winners if all users have rolled.
                if($this->all_rolled()) $this->check_winner();
            }
        }
    }

    public function start()
    {
        // Restart the game if it was paused.
        if($this->paused === true){
            $this->paused === false;
            return;
        }  

        // Get the users in their own vars.
        $p = array_keys($this->players);
        $player1 = $this->players[$p[0]]['name'];
        $player2 = $this->players[$p[1]]['name'];

        // Create the message
        $msg = "[gy2][Dice Duel][/gy2] [t2]".$player1."[/t2] vs [t2]".$player2."[/t2]! [gn2]".$this->getPot()." pot[/gn2] !Roll now. Hosted by [o][".$this->host."][/o]";
    
        // START - Send the message to server.
        $this->send($msg);
    }

    public function pause()
    {
        $this->paused = true;
    }

    public function delete()
    {
        // Get the users in the game.
        $p = array_keys($this->players);
        $player1 = $this->players[$p[0]]['name'];
        $player2 = $this->players[$p[1]]['name'];

        // Build the message
        $msg = "[gy2][Dice Duel][/gy2] The game between [t2]".$player1."[/t2] and [t2]".$player2."[/t2] was [r]ended[/r]. Hosted by [o][".$this->host."][/o]";

        // Tell the user game was ended.
        $this->send($msg);

        // Remove game from array.
        unset($this->game_arr[$this->host]);
    }

    private function finished()
    {
        unset($this->game_arr[$this->host]);
    }

    private function send($line)
    {
        // Replace the colours
        $line = str_ireplace("{:^C", chr(3), $line);
        $line = str_ireplace(":}", "", $line);

        // Send the line to irc
        return $this->base->put($this->channel, 'PRIVMSG', $line);
        #return $this->base->sendData('PRIVMSG ' . $this->channel . ' :' . $line);
    }

    private function all_rolled()
    {
        foreach($this->players as $player){
        if($player['roll'] === null)
            return false;
        }
        return true;
    }

    private function check_winner()
    {
        $roll_comp = array();
        foreach ($this->players as $key => $player) {
            $roll_comp[$key] = $player['roll'];
        }

        $winner_roll = max(array_values($roll_comp));
        $winner = array_keys($roll_comp, $winner_roll);

        $loser_roll = min(array_values($roll_comp));
        $loser = array_keys($roll_comp, $loser_roll);


        if(count($winner) > 1)
        {
            foreach ($winner as $player) {
                $this->players[$player]['roll'] = null;
            }
            $msg = "[gy2][Dice Duel][/gy2] [t2]".$this->players[$winner[0]]['name']."[/t2] and [t2]".$this->players[$winner[1]]['name']."[/t2] tied on a [r]".$winner_roll."[/r]. Roll again now!";
            $this->send($msg);
            return;
        }
        else 
        {
            $msg = "[gy2][Dice Duel][/gy2] [t2]".$this->players[$winner[0]]['name']."[/t2] won the [gn2]".$this->getPot()." pot[/gn2] against [t2]".$this->players[$loser[0]]['name']."[/t2]: [r]".$winner_roll."[/r] - [r]".$loser_roll."[/r]. Hosted by [o][".$this->host."][/o]";
            $this->send($msg);
            $this->finished();
        }
    }

    private function getPot()
    {
        $pot = $this->toRaw($this->bet) * count($this->players);
        return $this->toReadable($pot * ((100 - $this->comission)/100));
    }

    private function toRaw($n){

        $last_c = strtolower(substr ($n, -1));
        $result = (in_array($last_c, array('k', 'm', 'b')) ? $last_c : null);
        if($result !== null)
        {
            $num = substr($n,0,-1);
            if($result == 'k'){
                $times = 1000;
            }
            elseif($result == 'm'){
                $times = 1000000;   
            }
            elseif($result == 'b'){
                $times = 1000000000;
            }
            $n = $num * $times;
        }
        return (float) $n;
    }
        
    private function toReadable($number){
        if($number <= 999) {
            return $number;
        }
        elseif($number >= 1000 && $number <= 999999){
            return $number/1000 . "k"; 
        }
        elseif($number >= 1000000 && $number <= 999999999){
            return $number/1000000 . "m";
        }
        elseif($number >= 1000000000) {
            return $number/1000000000 . "b";
        }
    }

    private function devurandom_rand($min = 0, $max = 0x7FFFFFFF) {
        $diff = $max - $min;
        if ($diff < 0 || $diff > 0x7FFFFFFF) {
            #throw new RuntimeException("Bad range");
            return mt_rand(2,12);
        }
        $bytes = mcrypt_create_iv(4, MCRYPT_DEV_URANDOM);
        if ($bytes === false || strlen($bytes) != 4) {
            #throw new RuntimeException("Unable to get 4 bytes");
            return mt_rand(2,12);
        }
        $ary = unpack("Nint", $bytes);
        $val = $ary['int'] & 0x7FFFFFFF;   // 32-bit safe                           
        $fp = (float) $val / 2147483647.0; // convert to [0,1]                          
        return round($fp * $diff) + $min;
    }
}


class dice extends plugin {
    
    /**
     * Stores all the current games. Key being hostname.
     */
    private $games = array();

    function __construct(){}
    
    function __destruct(){}

    public function getPlugins()
    {
        $this->getPlugin('base');
        $this->getPlugin('hosts');
    }

    public function trigger($type, $data){}
    
    public function message($m){}
    
    public function command($m)
    {
        switch($m['cmd'])
        {
            case '!roll':

                $player = $m['fr']['na'];
                foreach($this->games as $game)
                {
                    if($this->in_game($player))
                    {
                        $game->roll($player);
                    }
                }

                break;

            case '!del':

                if($this->hosts->is_host($m['fr']['na']) === true)
                {
                    // Check they are loggedin
                    if($this->hosts->is_loggedIn($m['fr']['na']))
                    {
                        // Who sent the command
                        $host = $m['fr']['na'];

                        // Find the game the host is in.
                        foreach($this->games as $game)
                        {
                            if($this->in_game($host, true))
                            {
                                $game->delete();
                                return;
                            }
                        }

                        // Not in a game
                        $this->base->put($host, 'NOTICE', "You are not currently hosting any games.");
                    }
                    else
                    {
                        $this->base->put($m['fr']['na'], 'NOTICE', 'You must be logged in to use this command. Type !login');
                    }
                }
                break;
            
            case '!dd':

                // Don't let people PM the bot.
                if($m['pm'] === true || $m['ty'] === 'NOTICE')
                {
                    return;
                }

                // Check they are a host.
                if($this->hosts->is_host($m['fr']['na']) === false)
                {
                    return;
                }

                // Check they are a host and logged in
                if($this->hosts->is_loggedIn($m['fr']['na']) === false)
                {
                    $this->base->put($m['fr']['na'], 'NOTICE', 'You must be logged in to use this command. Type !login');
                    return;
                }

                // Get the host name.
                $host = $m['fr']['na'];

                // Get the players
                $player_1 = isset($m['me']['ex'][1]) ? $m['me']['ex'][1] : null;
                $player_2 = isset($m['me']['ex'][2]) ? $m['me']['ex'][2] : null;

                // Get the bet.
                $bet = isset($m['me']['ex'][3]) ? $m['me']['ex'][3] : null;

                // Check the host is not in a game.
                if($this->in_game($host, true))
                {
                    $this->base->put($m['to'], 'PRIVMSG', $host . " is already hosting a game.");
                    return;
                }


                // Check players and bet was set.
                if($player_1 !== null && $player_2 !== null && $bet !== null)
                {
                    // Check the bet is in the correct format - continue if so.
                    if($this->check_format($bet))
                    {
                         // Check the players aren't the same
                        if(strtolower($player_1) === strtolower($player_2))
                        {
                            $this->base->put($m['to'], 'PRIVMSG', "A player can not play against themself.");
                            return;
                        }

                        // Check the players are not in a game.
                        if($this->in_game($player_1))
                        {
                            $this->base->put($m['to'], 'PRIVMSG', $player_1 . " is already in a game.");
                            return;
                        }
                        if($this->in_game($player_2))
                        {
                            $this->base->put($m['to'], 'PRIVMSG', $player_2 . " is already in a game.");
                            return;
                        }
                    }
                    else
                    {
                        $this->base->put($host, 'NOTICE', 'Bet is not in the correct format.');
                        return;
                    }
                }
                else {
                    $this->base->put($host, 'NOTICE', 'Syntax: !dd <player1> <player2> <pot>');
                    return;
                }

                
                // Createa a new game
                $game = new game($host, array($player_1, $player_2), $bet, $this->base, $m['to'], 5, $this->games);

                // Add to game list
                $this->games[$host] = $game;

                // Start the game
                $game->start();

                break;
                
            default:
                break;
        }
    }

    private function in_game($user, $host = false)
    {
        foreach($this->games as $game)
        {
            if($host === true){
                if(strtolower($game->get_host()) === strtolower($user)) return true;
            }
            else{
                if($game->in_game(strtolower($user))) return true;
            }
        }
    }

    private function check_format($bet)
    {
        $pattern = "/^[0-9][0-9\.,]*(k|b|m)?$/i"; 
        if (preg_match($pattern, $bet)) { 
            return true;
        } else { 
            return false;
        }  
    }
    
    public function triggerNum($num, $data){}
}

?>