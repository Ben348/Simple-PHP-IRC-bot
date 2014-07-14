<?php

/**
 * Stats plugin
 * 
 * Logs stats for dice games. Currently includes:
 *  - Dice Duel / Free for All
 *  - Under/Over
 *  - Percentile Dice
 * 
 * @author Ben Thomson <ben.thomson@myport.ac.uk>
 * @copyright 2011+
 */

class stats extends plugin {
    
    /**
     * The connection to the database.
     * @var resource
     */
    private $conn = null;

    /**
     * Setup the connection.
     */
    function __construct()
    {
        try
        {
            // Setup a new connection.
            $this->conn = new PDO('mysql:host=localhost;dbname=liv3_dice', 'liv3bot', 'pass');

            // Set attributes.
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            echo "====================== connected =====================";
        }
        catch(PDOException $e)
        {
            echo 'ERROR: ' . $e->getMessage();
        }
    }
    
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
    
    public function playerExists($name)
    {
        try
        {
            // Prepare the statement.
            $stmt = $this->conn->prepare('SELECT * FROM players WHERE name = :name');

            // Execute the query.
            $stmt->execute(array('name' => $name));

            // Get the result
            $result = $stmt->fetch();

            return $result === false ? false : true;
        }
        catch(PDOException $e)
        {
            echo 'ERROR: ' . $e->getMessage();
        }
    }

    public function newPlayer($name)
    {
        try
        {
            if($this->playerExists($name) !== true)
            {
                // Create a new player in all the tables.
                $stmt = $this->conn->prepare('INSERT INTO player_dd (name, bet, gp_won, gp_lost, won, lost) value (:name, :bet, :gw, :gl, :won, :lost)');

                // Execute the query.
                $stmt->execute(array('name' => $name, 'bet' => '0gp', 'gw' => '0gp', 'gl' => '0gp', 'won' => 0, 'lost' => 0));

                // Get the dice dual id.
                $dd_id = $this->conn->lastInsertId();

                // Create a new player in all the tables.
                $stmt = $this->conn->prepare('INSERT INTO player_pd (name, bet, gp_won, gp_lost, won, lost) value (:name, :bet, :gw, :gl, :won, :lost)');

                // Execute the query.
                $stmt->execute(array('name' => $name, 'bet' => '0gp', 'gw' => '0gp', 'gl' => '0gp', 'won' => 0, 'lost' => 0));

                // Get the dice dual id.
                $pd_id = $this->conn->lastInsertId();

                // Create a new player in all the tables.
                $stmt = $this->conn->prepare('INSERT INTO player_uo (name, bet, gp_won, gp_lost, won, lost) value (:name, :bet, :gw, :gl, :won, :lost)');

                // Execute the query.
                $stmt->execute(array('name' => $name, 'bet' => '0gp', 'gw' => '0gp', 'gl' => '0gp', 'won' => 0, 'lost' => 0));

                // Get the dice dual id.
                $uo_id = $this->conn->lastInsertId();

                // Insert final values into database.
                $stmt = $this->conn->prepare('INSERT INTO players (name, dd, pd, uo) value (:name, :dd, :pd, :uo)');
                $stmt->execute(array('name' => $name, 'dd' => $dd_id, 'pd' => $pd_id, 'uo' => $uo_id));
            }
        }
        catch(PDOException $e)
        {
            echo 'ERROR: ' . $e->getMessage();
        }
    }

    public function newDD($host, $players, $bet, $pot, $comm)
    {
        try
        {
            $type = (count($players) > 2 ? 'FFA' : 'DD');

            $comm = fh::readable((fh::raw($bet) * count($players)) * (5/100));
            $fee = fh::readable((fh::raw($comm) / 100) * 20);

            $players = fh::joinNames($players);

            // Prepare statement.
            $stmt = $this->conn->prepare('INSERT INTO dd (host, type, players, bet, pot, comm, fee) value (:host, :type, :players, :bet, :pot, :comm, :fee)');
            
            // Execute query.
            $stmt->execute(array('host' => $host, 'type' => $type, 'players' => $players, 'bet' => $bet, 'pot' => $pot, 'comm' => $comm, 'fee' => $fee));

            // Return the game id.
            return $this->conn->lastInsertId();
        }
        catch(PDOException $e)
        {
            echo 'ERROR: ' . $e->getMessage();
        }
    }

    public function ddFin($id, $winner, $finished, $who = null)
    {
        try
        {
            $stmt = $this->conn->prepare('UPDATE dd SET winner = :winner, finished = :fin WHERE id = :id');
            $stmt->execute(array('winner' => $winner, 'fin' => $finished, 'id' => $id));
        }
        catch(PDOException $e)
        {
            echo 'ERROR: ' . $e->getMessage();
        }
    }

    public function newRoll($id, $player, $roll)
    {
        try
        {
            // Prepare the statement.
            $stmt = $this->conn->prepare('INSERT INTO rolls (dd_id, player, roll) value (:dd_id, :player, :roll)');

            // Execute the query.
            $stmt->execute(array('dd_id' => $id, 'player' => $player, 'roll' => $roll));
        }
        catch(PDOException $e)
        {
            echo 'ERROR: ' . $e->getMessage();
        }
    }

    public function newPD($host, $type, $player, $bet, $pot, $win, $roll)
    {
        try
        {
            
        }
        catch(PDOException $e)
        {
            echo 'ERROR: ' . $e->getMessage();
        }
    }

    public function updateStats($player, $bet, $pot, $won, $game)
    {
        try
        {
            // Create a player record if they don't exist.
            if($this->playerExists($player) !== true)
            {
                $this->newPlayer($player);
            }

            // Get the row ids.
            $stmt = $this->conn->prepare('SELECT * FROM players WHERE name = :name');
            $stmt->execute(array('name' => $player));
            $pids = $stmt->fetch();

            // Update if the player was found -> just a fallback.
            if($pids !== false)
            {
                // Get the current stats.
                $stats = $this->getStats($player, $game, true);

                // Update the local stats vars.
                $stats['bet'] = fh::readable($stats['bet'] += fh::raw($bet));

                // Update won/lost gp values.s
                $won ? $stats['gp_won']+= (fh::raw($pot) - fh::raw($bet)) : $stats['gp_lost'] += fh::raw($bet);

                $stats['gp_won'] = fh::readable($stats['gp_won']);
                $stats['gp_lost'] = fh::readable($stats['gp_lost']);

                // Update win/lose.
                $won ? ++$stats['won'] : ++$stats['lost'];

                // Prepare the statements.
                switch ($game) 
                {
                    case 'dd':
                        $stmt = $this->conn->prepare('UPDATE player_dd SET bet = :bet, gp_won = :gw, gp_lost = :gl, won = :won, lost = :lost WHERE name = :name');
                        break;
                    case 'pd':
                        $stmt = $this->conn->prepare('UPDATE player_pd SET bet = :bet, gp_won = :gw, gp_lost = :gl, won = :won, lost = :lost WHERE name = :name');
                        break;
                    case 'uo':
                        $stmt = $this->conn->prepare('UPDATE player_uo SET bet = :bet, gp_won = :gw, gp_lost = :gl, won = :won, lost = :lost WHERE name = :name');
                        break;
                }

                // Execute the query.
                $stmt->execute(array('bet' => $stats['bet'], 'gw' => $stats['gp_won'], 'gl' => $stats['gp_lost'], 'won' => $stats['won'], 'lost' => $stats['lost'], 'name' => $player));
            }
        }
        catch(PDOException $e)
        {
            echo 'ERROR: ' . $e->getMessage();
        }
    }


    public function statString($player, $game = 'all')
    {
        switch ($game) 
        {
            case 'dd':
                // Get the stats for the game.
                $stats = $this->getStats($player, $game);
                if($stats === false) {
                    return 'No dice duel stats found for ' . $player;
                }
                else {
                    return $this->formatStats($stats, $player, 'Dice Duel');
                }
                break;

            case 'pd':
                // Get the stats for the game.
                $stats = $this->getStats($player, $game);
                if($stats === false) {
                    return 'No percentile stats found for ' . $player;
                }
                else {
                    return $this->formatStats($stats, $player, 'Percentile');
                }
                break;

            case 'uo':
                // Get the stats for the game.
                $stats = $this->getStats($player, $game);
                if($stats === false) {
                    return 'No under/over stats found for ' . $player;
                }
                else {
                    return $this->formatStats($stats, $player, 'Under/Over');
                }
                break;

            case 'all':
                // Get stats for all games.
                $dd = $this->getStats($player, 'dd', true);
                $pd = $this->getStats($player, 'pd', true);
                $uo = $this->getStats($player, 'uo', true);

                if($dd === false && $pd === false && $uo === false)
                {
                    return 'No overall stats found for ' . $player;
                }
                else
                {
                    // Total bet value.
                    $bet = fh::readable(($dd !== false ? $dd['bet'] : 0) + ($pd !== false ? $pd['bet'] : 0) + ($uo !== false ? $uo['bet'] : 0));

                    // Total profit/loss.
                    $prolo = fh::readable(($dd !== false ? $dd['prolo'] : 0) + ($pd !== false ? $pd['prolo'] : 0) + ($uo !== false ? $uo['prolo'] : 0));

                    // Total gp won + lost
                    $gpw = fh::readable(($dd !== false ? $dd['gp_won'] : 0) + ($pd !== false ? $pd['gp_won'] : 0) + ($uo !== false ? $uo['gp_won'] : 0));
                    $gpl = fh::readable(($dd !== false ? $dd['gp_lost'] : 0) + ($pd !== false ? $pd['gp_lost'] : 0) + ($uo !== false ? $uo['gp_lost'] : 0));

                    // Total games won.
                    $won = ($dd !== false ? $dd['won'] : 0) + ($pd !== false ? $pd['won'] : 0) + ($uo !== false ? $uo['won'] : 0);

                    // Total games lost.
                    $lost = ($dd !== false ? $dd['lost'] : 0) + ($pd !== false ? $pd['lost'] : 0) + ($uo !== false ? $uo['lost'] : 0);

                    // Return the string of stats.
                    return $this->formatStats(array('game' => 'Overall', 'bet' => $bet, 'gp_won' => $gpw, 'gp_lost' => $gpl, 'prolo' => $prolo, 'won' => (float) $won, 'lost' => (float) $lost), $player, 'Overall');
                }
                break;
        }
    }

    private function formatStats($stats, $player, $game)
    {
        // Work out total games.
        $games = $stats['won']+$stats['lost'];

        // Work out ratio.
        $ratio = $this->ratio($stats['won'], $stats['lost']);

        // Work out if the player is in profit or not.
        $wl = fh::raw($stats['gp_won']) >= fh::raw($stats['gp_lost']) ? 'Total Profit: [gn2]'.$stats['prolo'].'[/gn2]' : 'Total Loss: [r]'.$stats['prolo'].'[/r]';

        // Return the formatted stats string.
        #$s = '[[o]Overall Stats[/o]] [t2]Ben348[/t2] | Total Bet: [t2]'.$stats['bet'].'[/t2] | '.$wl.' | Games Played: [o]'.$games.'[/o] [[gn2]Won: '.$stats['won'].' [/gn2]-[r] Lost: '.$stats['lost'].'[/r]] | Win/Loss ratio: [t2]'.$ratio.'[/t2]';
        return '[gy2]['.$game.' Stats: [/gy2][t2]'.$player.'[/t2][gy2]][/gy2] Total Bet: [t2]'.$stats['bet'].'[/t2] | '.$wl.' | Games Played: [o]'.$games.'[/o] [[gn2]Won: '.$stats['won'].' [/gn2]-[r] Lost: '.$stats['lost'].'[/r]] | Win/Loss ratio: [t2]'.$ratio.'[/t2]';
    }

    private function ratio($win, $lose)
    {
        if($lose == 0)
        {
            return $win;
        }
        else
        {
            return round(($win/$lose), 2, PHP_ROUND_HALF_DOWN);
        }
    }

    private function getStats($player, $game, $raw = false)
    {
        try
        {
            switch ($game) 
            {
                case 'dd':
                    $stmt = $this->conn->prepare('SELECT * FROM player_dd WHERE name = :name');
                    break;
                case 'pd':
                    $stmt = $this->conn->prepare('SELECT * FROM player_pd WHERE name = :name');
                    break;
                case 'uo':
                    $stmt = $this->conn->prepare('SELECT * FROM player_uo WHERE name = :name');
                    break;
                default:
                    $stmt = $this->conn->prepare('SELECT * FROM player_dd WHERE name = :name');
                    break;
            }

            //
            $stmt->execute(array('name' => $player));
            $stats = $stmt->fetch();

            if($stats === false) {
                return false;
            }
            else
            {
                if($raw)
                {
                    $stats['bet'] = fh::raw($stats['bet']);
                    $stats['gp_won'] = fh::raw($stats['gp_won']);
                    $stats['gp_lost'] = fh::raw($stats['gp_lost']);
                    $stats['prolo'] = abs(fh::raw($stats['gp_won']) - fh::raw($stats['gp_lost']));
                    return $stats;
                }
                else
                {
                    $stats['prolo'] = fh::readable(abs(fh::raw($stats['gp_won']) - fh::raw($stats['gp_lost'])));
                    return $stats;
                }
            }
        }
        catch(PDOException $e)
        {
            echo 'ERROR: ' . $e->getMessage();
        }
    }
}

?>