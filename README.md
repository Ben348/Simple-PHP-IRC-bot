Simple-PHP-IRC-bot
==================

Simple IRC bot written in PHP extendible by plugins

![ScreenShot](http://new.tinygrab.com/1ff8a7b5dc3e8c993efd06e336af8b8da36f60c461.png)

Content
------------
1. Introduction
2. Future improvements
  1. Dynamic plugins
  2. Error handling
  3. Restructure
3. How to use
4. Plugins
5. Examples

Introduction
------------
This is a simple IRC bot I wrote back in 2011 when I used to play RuneScape (yes I am a nerd). It was written for the purpose for hosting dice games on IRC. There are a few bugs as it only took me a few hours to write, but it worked well enough for what I wanted.


Future improvements
------------
### Dynamic plugins
Being able to load and unload a plugin without having to restart the bot is a huge advantage. I started working on this briefly as you can see in the `/dev/unplug.php` file. The idea is to go through the plugin file and give each class a unique name. There would be a main plugin array in the bot that would contain a reference to the correct plugin class, which would get updated every time a plugin was loaded/unloaded. Obviously after time you'd want to restart the bot to release memory.

This is probably a good example of why PHP isn't the best choice of language for a IRC bot. Node.js or Python would be better. V2 is done in Node and has a web panel

### Error handling
I never got around to implementing proper error handling so that is a must for future development.

### Restructure
The whole bot is a bit clunky; it needs to start from fresh. More regular expression could have been used on the `/lib/parser.php` file for example. 
Setting up certain bot parameters when initialising the bot would have been a good idea. e.g instead of this
````php
$bot = new bot();
````
Have something like
````php
$bot = new bot('Server', 'Port', 'Nick', 'Name', 'Password');
````

Other things include:
- Load multiple plugins from 1 array with the ability to pass params
- Condense the components
- Basic bot functions removed from a plugin file and part of core bot
- Some magic to do asynchronous things
- Stop plugins loading if required plugins don't exist
- Auto re-join

How to use
------------
Copy the `/lib/` folder to your project. Then create a file and include the same content as in `mybot.php`. Once you have done that run your bot with PHP and you are good to go!
````php
set_time_limit(0);
ini_set('display_errors', 'on');

// Include essential files
require_once 'lib/bot.php';

// Create a new instance of the bot
$bot = new bot();

// Configure the bot
$bot->setServer('irc.server.com');
$bot->setPort(6667);

// Bot identification vars
$bot->setNick('My_bot');
$bot->setName('My_bot');
$bot->setMask('My.Bot');
$bot->setPassword('password');

// Default chanels to join
$bot->setChannel(array('#channel', '#channel passkey')); // or just a single channel : $bot->setChannel('#channel');

// Load some plugins
$bot->loadPlugin('base');
$bot->loadPlugin('move');

// Establish a connection
$bot->connect();
````

Plugins
------------
The plugin template is included: `plugin_template.php`. It is important that all the plugins are placed in the folder `/lib/plugins/`. Plugin file names should be lower case and loaded in the order they are required. This will be fixed to be more flexible in future versions.

To load a plugin in the bot, use the following code in your bot file.
````php
$bot->loadPlugin('base');
````

The plugin template contains some core functions which are used to capture IRC events: `message($m)`, `command($m)`, `trigger($type, $data)` and `triggerNum($num, $data)`. These functions are detailed below including what data is returned.

### message($m) and command($m)
The `message` and `command` functions share the same format of what is returned, in fact you could arguably not need the `command` function and do it all based on the `message` function. The only difference is that the `command` function is only called when a command is called. It also has the `$m['cmd']` parameter.

````php
public function message($m)
{
    switch ($m['ty']) 
    {
        case 'NOTICE':
            // Do something
            break;
        case 'PRIVMSG':
            // Do something
            break;
    }
}

public function command($m)
{
    switch ($m['cmd']) 
    {
        case '!move':
            // Do something
            break;
        case '!help':
            // Do something
            break;
    }
}
````

The examples are based on the raw line below<br>
`:Test!Test@somemask.com PRIVMSG #channel :!help Hello world`

| Option | Type | Description | Example |
| --- | --- | --- | --- |
| `$m['ty']` | String | **Type**<br> Message type either: `PRIVMSG` or `NOTICE` | PRIVMSG |
| `$m['to']` | String | **To**<br> Where the message was sent | #channel |
| `$m['fr']['na']` | String | **From name**<br> Senders name | Test |
| `$m['fr']['ma']` | String | **From mask**<br> Senders mask | somemask.com |
| `$m['re']` | String | **Reply**<br> Where to reply  to. Name or channel | #channel |
| `$m['pm']` | Boolean | **Private message**<br> Was it a private message? | false |
| `$m['me']['pl']` | String | **Message line**<br> The message send by the user | !help Hello world |
| `$m['me']['ex']` | Array | **Message exploded**<br> Message parts split on space | `array('!help', 'Hello', 'world')` |
| `$m['cmd']` | String | **Command**<br> Command with the prefix (first word of the message) | !help |


### trigger($type, $data)
The `trigger` function as shown below is used to catch the following IRC events: `NICK`, `KICK`, `QUIT`, `JOIN`, `PART`. These events all return different $data - the tables below show exactly what is returned with working examples.
````php
public function trigger($type, $data){
    switch($type)
    {
        case: 'NICK':
            // Do something when someone changes their name
            break;
        case: 'JOIN':
            // Do something when someone joins
            break;
    }
}
````

#### NICK

The examples are based on the raw line below<br>
`:Test!Test@somemask.com NICK Test1`

| Option | Type | Description | Example |
| --- | --- | --- | --- |
| `$data['o']` | String | **Original line**<br> Original line from the server | `:Test!Test@somemask.com NICK Test1`  |
| `$data['from']['nick']` | String | **From name**<br> Original name | Test |
| `$data['from']['mask']` | String | **From mask**<br> Original mask | somemask.com |
| `$data['to']` | String | **To**<br> New nick | Test1 |

#### KICK

The examples are based on the raw line below<br>
`:Test!Test@somemask.com KICK #channel Spammer :Spamming. Please don't come back`

| Option | Type | Description | Example |
| --- | --- | --- | --- |
| `$data['o']` | String | **Original line**<br> Original line from the server | `:Test!Test@somemask.com KICK #channel Test :Spamming. Please don't come back`  |
| `$data['who']` | String | **Who**<br> Nick of the person who got kicked | Spammer |
| `$data['by']['nick']` | String | **Kickers nick**<br> Nick of who kicked the user | Test |
| `$data['by']['mask']` | String | **Kickers mask**<br> Mask of who kicked the user | somemask.com |
| `$data['why']` | String | **Why**<br> Reason for kick | Spamming. Please don't come back |

#### QUIT

The examples are based on the raw line below<br>
`:Test!Test@somemask.com QUIT :Bye for now!`

| Option | Type | Description | Example |
| --- | --- | --- | --- |
| `$data['o']` | String | **Original line**<br> Original line from the server | `:Test!Test@somemask.com QUIT :Bye for now!`  |
| `$data['who']['nick']` | String | **Quitters nick**<br> Nick of who quit | Test |
| `$data['who']['mask']` | String | **Quitters mask**<br> Mask of who quit | somemask.com |
| `$data['message']` | String | **Message**<br> Quit message | Bye for now! |

#### PART

The examples are based on the raw line below<br>
`:Test!Test@somemask.com PART #channel`

| Option | Type | Description | Example |
| --- | --- | --- | --- |
| `$data['o']` | String | **Original line**<br> Original line from the server | `:Test!Test@somemask.com PART #channel`  |
| `$data['who']['nick']` | String | **Leavers nick**<br> Nick of who left | Test |
| `$data['who']['mask']` | String | **Leavers mask**<br> Mask of who left | somemask.com |
| `$data['channel']` | String | **Channel**<br> Channel they left | #channel |

#### JOIN

The examples are based on the raw line below<br>
`:Test!Test@somemask.com JOIN :#channel`

| Option | Type | Description | Example |
| --- | --- | --- | --- |
| `$data['o']` | String | **Original line**<br> Original line from the server | `:Test!Test@somemask.com JOIN :#channel`  |
| `$data['who']['nick']` | String | **Joiners nick**<br> Nick of who joined | Test |
| `$data['who']['mask']` | String | **Joiners mask**<br> Mask of who joined | somemask.com |
| `$data['channel']` | String | **Channel**<br> Channel they joined | #channel |

#### MODE

The examples are based on the raw line below<br>
`:Test!Test@somemask.com MODE :#channel +o Test1`

| Option | Type | Description | Example |
| --- | --- | --- | --- |
| `$data['o']` | String | **Original line**<br> Original line from the server | `:Test!Test@somemask.com JOIN :#channel`  |
| `$data['who']['nick']` | String | **Joiners nick**<br> Nick of who set the mode | Test |
| `$data['who']['mask']` | String | **Joiners mask**<br> Mask of who set the mode | somemask.com |
| `$data['channel']` | String | **Channel**<br> Channel modes were set on | #channel |
| `$data['modes']` | Array | **Modes**<br> Modes array | `array(0 => array('mode' => '+h', 'who' => 'Test1'))` |

### triggerNum($num, $data)
The `triggerNum` function is called when the server gets a numerical response.

````php
public function triggerNum($num, $data) {
    switch($num)
    {
        case 366:
          // Do something
          break;
    }
}
````

### Referencing other plugins
Sometimes you may wish to split up your project into seperate plugins, but at the same time you'd like to be able to access another plugin from within a plugin. To do this we have a `getPlugins()` method.

To include another plugin in your plugin use the following code:
````php
public function getPlugins()
{
    $this->getPlugin('base');
}
````
Where **base** is the plugin name in LOWER case. Then from within your functions you can access the external plugin by doing:
````php
$this->base->someFunction();
````


Examples
------------
There are a few examples of bots in the `/examples/` folder. These are the bots that I used to use for my clan. Some of the plugins include an admin, move and games plugin. Look at the code to see how to use these with all the commands.

![ScreenShot](http://new.tinygrab.com/1ff8a7b5dcf34bb56c7f424e3ffc9c95ece3832f40.png)

PLEASE NOTE: A couple of the plugins require a database connection, I've lost the schema for the tables so you'll have to figure out that yourself until I can find it. Sorry xD
