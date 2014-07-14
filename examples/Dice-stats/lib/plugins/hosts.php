<?php

/**
 * Hosts plugin
 * 
 * Defines commands for admins & hosts
 * 
 * @author Ben Thomson <ben.thomson@myport.ac.uk>
 * @copyright 2011+
 */

class hosts extends plugin {

    /**
     * List of hosts
     * @var array
     */
    private $hosts = array(
        'ben' => array(
                    'name' => 'Ben', 
                    'authorised' => false, 
                    'admin' => true, 
                    'adminlevel' => 3
        ),
        'steve' => array(
                    'name' => 'Steve', 
                    'authorised' => false, 
                    'admin' => true, 
                    'adminlevel' => 2
        ),
        'test' => array(
                    'name' => 'Test', 
                    'authorised' => false, 
                    'admin' => false, 
                    'adminlevel' => 0
        ),
    );
    
    /**
     * Global callback
     * @var array
     */
    private $callback = array();

    /**
     * Global joined variable
     * @var boolean
     */
    private $joined = false;

    /**
     * Initialisation happens here
     */
    function __construct(){}
    
    /**
     * Close anything or shut down do in here.
     */
    function __destruct(){}
    
    /**
     * Require needed plugins
     */
    public function getPlugins()
    {
        $this->getPlugin('base'); // Access as $this->base->
    }
    
    /**
     * When server stuff happens this is called.
     * $type can be: JOIN, PART, KICK, QUIT, NICK or MODE.
     */
    public function trigger($type, $data)
    {
        switch ($type) 
        {
            case 'PART':
                if($this->is_host($data['who']['nick']))
                {
                    $this->logout($data['who']['nick']);
                }
                break;

            case 'QUIT':
                if($this->is_host($data['who']['nick']))
                {
                    $this->logout($data['who']['nick']);
                }
                break;

            case 'KICK':
                if($this->is_host($data['who']))
                {
                    $this->logout($data['who']['nick']);
                }
                break;

            case 'JOIN':
                if($this->is_host($data['who']['nick']))
                {
                    if($this->is_loggedIn($data['who']['nick']) === false)
                    {
                        $this->login($data['who']['nick']);
                    }
                }
                break;

            case 'MODE':
                $params = isset($data['params']) ? explode(' ', $data['params']) : array('');
                if($this->is_host($params[0]) === true && $this->is_loggedIn($params[0]) === false)
                {
                    $this->login($params[0]);
                }
                break;
            default:
                break;
        }
    }
    
    /**
     * Triggered when a IRC response is a PRIVMSG or NOTICE
     */
    public function message($m)
    {
        // Check for a NOTICE from NickServ
        if($m['ty'] === 'NOTICE' && strtolower($m['fr']['na']) === 'nickserv')
        {
            // Check for STATUS response
            if($m['me']['ex'][0] === 'STATUS')
            {
                // Get the user and status
                $user = $m['me']['ex'][1];
                $status = $m['me']['ex'][2];

                // Call the callback function.
                if(isset($this->callback[strtolower($user)]))
                {
                    call_user_func_array($this->callback[strtolower($user)], array($user, $status));
                }
            }
        }
    }
    
    /**
     * Triggered when a command is received.
     */
    public function command($m)
    {
        switch ($m['cmd'])
        {
            case '!login':
                // Get the user to login (if set)
                $user = (isset($m['me']['ex'][1]) ? $m['me']['ex'][1] : null);

                if($this->is_admin($m['fr']['na']) && isset($user)) {
                    // Allows admins to login other hosts
                    $this->login($user);
                }
                else{
                    // Login yourself
                    $this->login($m['fr']['na']);
                }
                break;

            case '!kill':
                // Only let admins use this command - they must be logged in.
                if($this->is_admin($m['fr']['na']))
                {
                    if($this->is_loggedIn($m['fr']['na']))
                    {
                        die();
                    }
                    else
                    {
                        $this->base->put($m['fr']['na'], 'NOTICE', 'You must be logged in to use this command.');
                        $this->login($m['fr']['na']);
                    }
                }
                else
                {
                    $this->base->put($m['fr']['na'], 'NOTICE', 'You must be logged in as an admin to use this command.');
                }
                break;

            case '!host':
                // Only let admins use this command - they must be logged in.
                if($this->is_admin($m['fr']['na']))
                {
                    if($this->is_loggedIn($m['fr']['na']))
                    {
                        // Get the command
                        $command = isset($m['me']['ex'][1]) ? strtolower($m['me']['ex'][1]) : null;

                        // Check syntax was correct
                        if(($command === 'add' || $command === 'del' || $command === 'list'))
                        {
                            switch ($command) 
                            {
                                case 'add':
                                    if(isset($m['me']['ex'][2])){
                                        $this->addHost($m['me']['ex'][2], $m);
                                    }
                                    else {
                                        // Tell the user its the wrong syntax
                                        $this->base->put($m['fr']['na'], 'NOTICE', 'Syntax: !host <add|del|list> [<player>]');
                                    }
                                    break;
                                case 'del':
                                    if(isset($m['me']['ex'][2])){
                                        $this->delHost($m['me']['ex'][2], $m);
                                    }
                                    else {
                                        // Tell the user its the wrong syntax
                                        $this->base->put($m['fr']['na'], 'NOTICE', 'Syntax: !host <add|del|list> [<player>]');
                                    }
                                    break;
                                case 'list':
                                    #$this->base->put($m['fr']['na'], 'NOTICE', 'Coming soon.');
                                    $this->base->put($m['fr']['na'], 'NOTICE', $this->listHosts());
                                    break;
                            }
                        }
                        else
                        {
                            // Tell the user its the wrong syntax
                            $this->base->put($m['fr']['na'], 'NOTICE', 'Syntax: !host <add|del|list> [<player>]');
                        }
                    }
                    else 
                    {
                        $this->base->put($m['fr']['na'], 'NOTICE', 'You must be logged in to use admin commands.');
                        $this->login($m['fr']['na']);
                    }
                }
                break;

            default:
                break;
        }
    }

    /**
     * Triggered on a numerical response from the server.
     */
    public function triggerNum($num, $data)
    {
        switch ($num) 
        {
            case 366:
                if($this->joined === false)
                {
                    foreach ($this->hosts as $host => $obj) {
                        $this->login($host);
                    }
                    $this->joined = true;
                }
                break;
        }
    }

    /**
     * Get a list of current hosts as string
     * @return string
     */
    private function listHosts()
    {
        $list = array();
        foreach($this->hosts as $host)
        {
            $list[] = $host['name'];
        }
        $sResult = array_pop($list);
        if (!empty($list))
        {
            $sResult = implode(', ', $list) . ' and ' . $sResult;
        }
        return $sResult;
    }

    /**
     * Delete a host
     * @param string $user - Username
     * @param array $m - host data e.g nick, mask etc..
     * @return void
     */
    private function delHost($user, $m)
    {
        if($this->is_host($user))
        {
            // Set mode
            $mode = array('hop', '-h');

            // Check they are trying not to remove an admin
            if($this->is_admin($user))
            {
                if($this->admin_level($m['fr']['na']) <= 2)
                {
                    // Must be a top level admin
                    $this->base->put($m['fr']['na'], 'NOTICE', 'You must be a top level admin to remove other admins.');
                    return;
                }
                else 
                {
                    $mode = array('aop', '-o');
                }
            }

            // Remove the host to the HOP list
            $this->base->put('ChanServ', 'PRIVMSG', $mode[0] . ' ' . $m['re'] . ' del ' . $user);

            // Remove the host from the host array
            unset($this->hosts[strtolower($user)]);

            // Remove halfoperator
            $this->base->modeAdd($m['re'], $mode[1], $user);

            // Tell the admin the host was deleted
            $this->base->put($m['fr']['na'], 'NOTICE', $user . " has been [r]removed[/r] from the host list.");
        }
        else 
        {
            $this->base->put($m['fr']['na'], 'NOTICE', $user . " is not a host.");
        }
    }

    /**
     * Add a host
     * @param string $user - Username
     * @param array $m - host data e.g nick, mask etc..
     * @return void
     */
    private function addHost($user, $m)
    {
        if($this->is_host($user) === false)
        {
            // Add a callback
            $this->callback[strtolower($user)] = array($this, 'addHostComplete');

            // Set global data
            $this->addHostData = $m;

            // Send the status command
            $this->base->put('NickServ', 'PRIVMSG', 'STATUS ' . $user);
        }
        else 
        {
            // Already a host
            $this->base->put($m['re'], 'PRIVMSG', $user . " is already on the host list.");
        }
    }

    /**
     * Add a host to the host list handler
     * @param string $user - Username
     * @param int $status
     * @return void
     */
    private function addHostComplete($user, $status)
    {
        // Get global data
        $m = $this->addHostData;

        if($status == 0)
        {
            $this->base->put($m['fr']['na'], 'NOTICE', "[r]ERROR:[/r] " . $user . " must have a registered nickname & be in the channel before being added to the host list.");
        }
        else if($status == 1)
        {
            // Tell the admin there was an error
            $this->base->put($m['fr']['na'], 'NOTICE', "[r]ERROR:[/r] " . $user . " needs to be identified before being added to the host list.");

            // Tell the new host to identify
            $this->base->put($user, 'NOTICE', "Please identify before being added to the host list: /ns identify <password>");
        }
        else if ($status == 3)
        {
            // Add the host to the HOP list
            $this->base->put('ChanServ', 'PRIVMSG', 'hop ' . $m['re'] . ' add ' . $user);

            // Add the host to the array
            $this->hosts[strtolower($user)] = array('name' => $user, 'authorised' => false, 'admin' => false);

            // Tell the admin the host was added
            $this->base->put($m['fr']['na'], 'NOTICE', $user . " has been added to the host list.");

            // Tell the new host to rejoin to get halfop
            $this->base->put($user, 'NOTICE', "Please rejoin the channel to get halfop. Type /cylce or /rejoin");

            // Welcome messaeg
            $msg = chr(3)."7,1". "<<========= " .chr(3)."4,1". "Liv3_Dice would like to welcome " .chr(3)."9,1". $user .chr(3)."4,1". " as a new host!!" .chr(3)."7,1". " =========>>";
            $this->base->put($m['re'], 'PRIVMSG', $msg);

            // Give them halfop
            $this->base->modeAdd($m['re'], '+h', $user);

            // Login new host
            $this->login($user);
        }
    }

    /**
     * Login a user handler
     * @param string $user - Username
     * @param int $status
     * @return void
     */
    private function loginComplete($user, $status)
    {
        if(!$this->is_loggedIn($user)) // Stops the JOIN + MODE instances intefering sending two messages
        {
            if($status == 3)
            {
                $this->hosts[strtolower($user)]['authorised'] = true;
                $this->base->put($user, 'NOTICE', 'You are now logged into Liv3_Dice.');
            }
            else {
                $this->base->put($user, 'NOTICE', 'Please identify with NickServ to host dice: /ns identify <password>');
            }
        }
        unset($this->callback[strtolower($user)]);
    }

    /**
     * Login a user
     * @param string $user - Username
     * @return void
     */
    public function login($user)
    {
        if($this->is_host($user))
        {
            if($this->is_loggedIn($user) == false)
            {
                // Add a callback
                $this->callback[strtolower($user)] = array($this, 'loginComplete');

                // Send the status command
                $this->base->put('NickServ', 'PRIVMSG', 'STATUS ' . $user);
            }
            else
            {
                $this->base->put($user, 'NOTICE', 'You are already logged in.');
            }
        }
    }

    /**
     * Logout a user
     * @param string $user - Username
     * @return void
     */
    private function logout($user)
    {
        $this->hosts[strtolower($user)]['authorised'] = false;
    }

    /**
     * Check if a user is logged in
     * @param string $user - Username
     * @return boolean
     */
    public function is_loggedIn($user)
    {
        if($this->is_host($user))
        {
            return $this->hosts[strtolower($user)]['authorised'];
        }
        else 
        {
            $this->login($user);
            return false;
        }
    }

    /**
     * Check if a user is a host
     * @param string $user - Username
     * @return boolean
     */
    public function is_host($user)
    {
        return in_array(strtolower($user), array_map('strtolower', array_keys($this->hosts)));
    }

    /**
     * Check if a user is an admin
     * @param string $user - Username
     * @return boolean
     */
    public function is_admin($user)
    {
        $exist = in_array(strtolower($user), array_map('strtolower', array_keys($this->hosts)));

        if($exist === false) {
            return false;
        }
        else {
            return $this->hosts[strtolower($user)]['admin'];
        }
    }

    /**
     * Retrieve the admin level of a user
     * @param string $user - Username
     * @return int
     */
    public function admin_level($user)
    {
        if($this->is_admin($user))
        {
            return $this->hosts[strtolower($user)]['adminlevel'];
        }
    }
}

?>