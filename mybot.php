<?php

/**
 * IRC Bot
 * 
 * A simple irc bot extendible by plugins
 * 
 * @author Ben Thomson <ben.thomson@myport.ac.uk>
 * @copyright 2011+
 */

    // Configure PHP
    set_time_limit(0);
    ini_set('display_errors', 'on');
    
    // Include essential files
    require_once 'lib/bot.php';
    
    // Create a new instance of the bot
    $bot = new bot();

    // Configure the bot
    $bot->setServer('irc.test.com');
    $bot->setPort(6667);
    
    // Bot identification vars
    $bot->setNick('Test_bot');
    $bot->setName('Test_bot');
    $bot->setMask('Test.Bot');
    $bot->setPassword('password');
    
    // Default chanels to join
    $bot->setChannel(array('#channel', '#channel passkey')); // or just a single channel : $bot->setChannel('#channel');
    
    // Load some plugins
    $bot->loadPlugin('base');
    $bot->loadPlugin('move');
    
    // Establish a connection
    $bot->connect();
?>