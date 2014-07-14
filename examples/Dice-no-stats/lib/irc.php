<?php

/**
 * IRC Class
 * 
 * Creates a connection to the server
 * 
 * @author Ben Thomson <ben.thomson@myport.ac.uk>
 * @copyright 2011+
 */

class irc {
    
    /**
     * Server to connect to
     * @var string
     */
    private $server = '';
    
    /**
     * Port to connect to - default is 6667
     * @var integer
     */
    private $port = 6667;
    
    /**
     * TCP/IP connection
     * @var type
     */
    private $socket;
    
    /**
     * Initialise stuff here
     */
    public function __construct(){}
    
    /**
     * Close the connection
     */
    public function __destruct()
    {
        $this->disconnect();   
    }
    
    /**
     * Check if a connection exists
     * @return boolean - TRUE if connection exists, FALSE if not or on error
     */
    public function isConnected()
    {
        return (is_resource($this->socket) ? true : false);
    }
    
    /**
     * Connect to the server
     * @return void
     */
    public function connect()
    {
        // Check the server and port were set. Possible add regex validation in the future
        if(isset($this->server) && isset($this->port))
        {
            // Create a new socket
            $this->socket = fsockopen($this->server, $this->port);
            
            // Check if we connected successfully
            if(!$this->isConnected())
            {
                // Throw an error
                throw new Exception("Unable to connect to server:" . $this->server . " and port: " . $this->port . ".");
            }
        }
        else
        {
            // No port or server was set.
            #$this->error(); // TO-DO: Change this to work
        }
    }
    
    /**
     * Disconnects from the server
     * @return boolean - TRUE if the connection was closed, FALSE otherwise
     */
    public function disconnect()
    {
        return fclose($this->socket);
    }
    
    /**
     * Reconnects to the server
     * @return void
     */
    public function reconnect()
    {
        if(!$this->isConnected())
        {
            $this->connect();
        }
        else
        {
            $this->disconnect();
            $this->connect();
        }
    }
    
    
    /**
     * Send data to the server
     * @return int|boolean the number of bytes written, or FALSE on error
     */
    public function sendData($data)
    {
        // Format the line for colours.
        $data = $this->colour($data);

        // Send the data to the server
        return fwrite($this->socket, $data . "\r\n");
    }
    
    /**
     * Set the server variable
     * @param string $server - server address
     * @return void
     */
    public function setServer($server)
    {
        // Casts the given variable as a string to make sure
        $this->server = (string) $server;
    }
    
    /**
     * Set the port variable
     * @param integer $port - port to connect to
     * @return void
     */
    public function setPort($port)
    {
        // Casts the given variable as a integer to make sure
        $this->port = (int) $port;
    }
    
    /**
     * Gets the current server address
     * @return string - current server address
     */
    public function getServer()
    {
        return $this->server;
    }
    
    /**
     * Gets the current port
     * @return integer - current port
     */
    public function getPort()
    {
        return $this->port;
    }
    
    /**
     * Returns data from the server
     * @return string|boolean The data as string, or FALSE if no data is available
     */
    public function getData()
    {
        // Gets the response
        $data = fgets($this->socket, 256);
        
        // Echo the data to the command prompt
        echo $data;
        
        // Return the data
        return $data;
    }


    /**
     * Replaces BB code colours with IRC colour codes
     * @return string - The formatted line to send to IRC server
     */
    private function colour($line)
    {
        return preg_replace(array(0=>'#\[w\](.*)\[/w\]#U', 1=>'#\[bla\](.*)\[/bla\]#U',
                            2=>'#\[bl2\](.*)\[/bl2\]#U', 3=>'#\[gn2\](.*)\[/gn2\]#U',
                            4=>'#\[r\](.*)\[/r\]#U', 5=>'#\[br\](.*)\[/br\]#U', 6=>'#\[l\](.*)\[/l\]#U',
                            7=>'#\[o\](.*)\[/o\]#U', 8=>'#\[y\](.*)\[/y\]#U', 9=>'#\[g\](.*)\[/g\]#U',
                            10=>'#\[t2\](.*)\[/t2\]#U', 11=>'#\[t\](.*)\[/t\]#U',
                            12=>'#\[bl\](.*)\[/bl\]#U', 13=>'#\[p\](.*)\[/p\]#U',
                            14=>'#\[gy2\](.*)\[/gy2\]#U', 15=>'#\[gy\](.*)\[/gy\]#U',
                            16=>'#\[B\](.*)?\[/B\]#U', 17=>'#\[U\](.*)?\[/U\]#U'),
                            array(chr(3)."00$1".chr(3)."\t", chr(3)."01$1".chr(3)."\t", chr(3)."02$1".chr(3)."\t", 
                                    chr(3)."03$1".chr(3)."\t", chr(3)."04$1".chr(3)."\t", chr(3)."05$1".chr(3)."\t",
                                    chr(3)."06$1".chr(3)."\t", chr(3)."07$1".chr(3)."\t", chr(3)."08$1".chr(3)."\t", 
                                    chr(3)."09$1".chr(3)."\t", chr(3)."10$1".chr(3)."\t", chr(3)."11$1".chr(3)."\t",
                                    chr(3)."12$1".chr(3)."\t", chr(3)."12$1".chr(3)."\t", chr(3)."14$1".chr(3)."\t", 
                                    chr(3)."15$1".chr(3)."\t", "\2$1\2", "\37$1\37"),
                                    $line);
    }
}
?>