<?php

//This file was written by Valithor#5947 <@116927250145869826>
//Special thanks to keira#7829 <@297969955356540929> for helping me get this behemoth working after converting from DiscordPHP

//DO NOT VAR_DUMP GETS, most objects like GuildMember have a guild property which references all members
//Use get_class($object) to verify the main object (usually a collection, check src/Models/)
//Use get_class($object->first())to verify you're getting the right kind of object. IE, $author_guildmember->roles should be Models\Role)
//If any of these methods resolve to a class of React\Promise\Promise you're probably passing an invalid parameter for the class
//Always subtract 1 when counting roles because everyone has an @everyone role
$vm = false; //Set this to true if using a VM that can be paused

include __DIR__ . '/vendor/autoload.php';
define('MAIN_INCLUDED', 1); //Token and SQL credential files are protected, this must be defined to access
ini_set('memory_limit', '-1'); //Unlimited memory usage

start:
//Global variables
include 'config.php'; //Global config variables
include 'species.php'; //Used by the species role picker function
include 'sexualities.php'; //Used by the sexuality role picker function
include 'gender.php'; //Used by the gender role picker function
include 'custom_roles.php'; //Create your own roles with this template!

include 'blacklisted_owners.php'; //Array of guild owner user IDs that are not allowed to use this bot
include 'blacklisted_guilds.php'; //Array of Guilds that are not allowed to use this bot
include 'whitelisted_guilds.php'; //Only guilds in the $whitelisted_guilds array should be allowed to access the bot.

require 'token.php';
use charlottedunois\yasmin;
$loop = \React\EventLoop\Factory::create();
$discord = new \CharlotteDunois\Yasmin\Client(array(), $loop);
echo PHP_EOL;

use RestCord\DiscordClient;
$restcord = new DiscordClient(['token' => "{$token}"]); // Token is required
//var_dump($restcord->guild->getGuild(['guild.id' => 116927365652807686]));

/*
set_exception_handler(function (Throwable $e) { //stops execution completely
	//
});
*/

include_once "custom_functions.php";
$rescue = VarLoad("_globals", "RESCUE.php"); //Check if recovering from a fatal crash
if ($rescue == true){ //Attempt to restore crashed session
	echo "[RESCUE START]" . PHP_EOL;
	$rescue_dir = __DIR__ . '/_globals';
	$rescue_vars = scandir($rescue_dir);
	foreach ($rescue_vars as $var){
		$backup_var = VarLoad("_globals", "$var");
				
		$filter = ".php";
		$value = str_replace($filter, "", $var);
		$GLOBALS["$value"] = $backup_var;
		
		$target_dir = $rescue_dir . "/" . $var; echo $target_dir . PHP_EOL;
		unlink($target_dir);
	}
	VarSave("_globals", "rescue.php", false);
	echo "[RESCUE DONE]" . PHP_EOL;
}

try {	
	$discord->on('error', function($error) { //Handling of thrown errors
		echo "[ERROR] $error" . PHP_EOL;
	});

	$discord->once('ready', function() use ($discord, $loop, $token, $restcord){	// Listen for events here
		echo "[SETUP]" . PHP_EOL;
		//$line_count = COUNT(FILE(basename($_SERVER['PHP_SELF']))); //No longer relevant due to includes
		$version = "RC V1.4.1";
		
		$discord->user->setPresence( //Discord status
			array(
				'since' => null, //unix time (in milliseconds) of when the client went idle, or null if the client is not idle
				'game' => array(
				//'name' => "$line_count lines of code! $version",
				'name' => $version,
				'type' => 3, //0, 1, 2, 3, 4 | Game/Playing, Streaming, Listening, Watching, Custom Status
				'url' => null //stream url, is validated when type is 1, only Youtube and Twitch allowed
				/*
				Bots are only able to send name, type, and optionally url.
				As bots cannot send states or emojis, they can't make effective use of custom statuses.
				The header for a "Custom Status" may show up on their profile, but there is no actual custom status, because those fields are ignored.
				*/
				),
				'status' => 'dnd', //online, dnd, idle, invisible, offline
				'afk' => false
			)
		);
		$GLOBALS['id'] = $discord->user->id;
		$tag = $discord->user->tag;
		echo "[READY] Logged in as $tag (" . $GLOBALS['id'] . ") created on " . $discord->user->createdAt->format('d.m.Y H:i:s') . PHP_EOL;
		$timestampSetup = time();
		echo "[timestampSetup]: ";
		$dt = new DateTime("now"); // convert UNIX timestamp to PHP DateTime
		echo $dt->format('d-m-Y H:i:s') . PHP_EOL; // output = 2017-01-01 00:00:00
		
		$discord->on('message', function($message) use ($discord, $loop, $token, $restcord){ //Handling of a message
			include "message-include.php";
		});
			
		$discord->on('guildMemberAdd', function($guildmember) { //Handling of a member joining the guild
			include "guildmemberadd-include.php";
		});
		
		$discord->on('guildMemberUpdate', function($member_new, $member_old) { //Handling of a member getting updated
			include "guildmemberupdate-include.php";
		});
		
		$discord->on('guildMemberRemove', function($guildmember) { //Handling of a user leaving the guild
			include 'guildmemberremove-include.php';
		});
			
		$discord->on('guildBanAdd', function($guild, $user) { //Handling of a user getting banned
			include "guildbanadd-include.php";
		});
		
		$discord->on('guildBanRemove', function($guild, $user) { //Handling of a user getting unbanned
			include "guildbanremove-include.php";
		});
		
		$discord->on('messageUpdate', function($message_new, $message_old) { //Handling of a message being changed
			include "messageupdate-include.php";
		});
		
		$discord->on('messageUpdateRaw', function($channel, $data_array) { //Handling of an old/uncached message being changed		
			include "messageupdateraw-include.php";
		});
		
		$discord->on('messageDelete', function($message) { //Handling of a message being deleted
			include "messagedelete-include.php";
		});
		
		$discord->on('messageDeleteRaw', function($channel, $message_id) { //Handling of an old/uncached message being deleted
			include "messagedeleteraw-include.php";
		});
		
		$discord->on('messageDeleteBulk', function($messages) { //Handling of multiple messages being deleted
			echo "[messageDeleteBulk]" . PHP_EOL;
		});
		
		$discord->on('messageDeleteBulkRaw', function($messages) { //Handling of multiple old/uncached messages being deleted
			echo "[messageDeleteBulkRaw]" . PHP_EOL;
		});
		
		$discord->on('messageReactionAdd', function($reaction, $respondent_user) { //Handling of a message being reacted to
			include "messagereactionadd-include.php";
		});
		
		$discord->on('messageReactionRemove', function($reaction, $respondent_user) { //Handling of a message reaction being removed
			include "messagereactionremove-include.php";
		});
		
		$discord->on('messageReactionRemoveAll', function($message) { //Handling of all reactions being removed from a message
			//$message_content = $message->content;
			echo "[messageReactionRemoveAll]" . PHP_EOL;
		});
		
		$discord->on('channelCreate', function($channel) { //Handling of a channel being created
			echo "[channelCreate]" . PHP_EOL;
		});
		
		$discord->on('channelDelete', function($channel) { //Handling of a channel being deleted
			echo "[channelDelete]" . PHP_EOL;
		});
		
		$discord->on('channelUpdate', function($channel) { //Handling of a channel being changed
			echo "[channelUpdate]" . PHP_EOL;
		});
			
		$discord->on('userUpdate', function($user_new, $user_old) { //Handling of a user changing their username/avatar/etc
			include "userupdate-include.php";
		});
			
		$discord->on('roleCreate', function($role) { //Handling of a role being created
			echo "[roleCreate]" . PHP_EOL;
		});
		
		$discord->on('roleDelete', function($role) { //Handling of a role being deleted
			echo "[roleDelete]" . PHP_EOL;
		});
		
		$discord->on('roleUpdate', function($role_new, $role_old) { //Handling of a role being changed
			echo "[roleUpdate]" . PHP_EOL;
		});
		
		$discord->on('voiceStateUpdate', function($member_new, $member_old) { //Handling of a member's voice state changing (leaves/joins/etc.)
			echo "[voiceStateUpdate]" . PHP_EOL;
		});
		
		/*
		$discord->on('error', function ($error){ //Handling of thrown errors
			echo "[ERROR] $error" . PHP_EOL;
		});
		*/
		$discord->on("error", function(\Throwable $e) {
			echo '[ERROR]' . $error->getMessage() . " in file " . $error->getFile() . " on line " . $error->getLine() . PHP_EOL;
		});
		
		/*
		$discord->wsmanager()->on('debug', function ($debug) {
			echo "[WS DEBUG] $debug" . PHP_EOL;
		});
		*/
		
	}); //end main function ready

	$discord->on('disconnect', function($erMsg, $code) use ($discord, $loop, $token, $restcord, $vm){
		//Restart the bot if it disconnects
		//This is almost always going to be caused by error code 1006, meaning the bot did not get heartbeat from Discord
		include "disconnect-include.php";
	});

	set_error_handler(function(int $number, string $message, string $filename, int $fileline) {
		if ($message != "Undefined variable: suggestion_pending_channel") //Expected to be null
		if ($message != "Trying to access array offset on value of type null") //Expected to be null, part of ;validate
			echo PHP_EOL . PHP_EOL . "Handler captured error $number: '$message' in $filename on line $fileline" . PHP_EOL  . PHP_EOL;
	});
	
	$discord->login($token)->done();
	$loop->run();
}catch (Throwable $e){ //Restart the bo
	echo "Captured Throwable: " . $e->getMessage() . " in file " . $e->getFile() . " on line " . $e->getLine(). PHP_EOL;

	//Rescue global variables
	$GLOBALS["RESCUE"] = true;
	$blacklist_globals = array (
		"GLOBALS",
		"loop",
		"discord",
		"restcord",
		"MachiKoro_Games"
	);
	echo "Skipped: ";
	foreach($GLOBALS as $key => $value){
		$temp = array($value);
		if (!in_array($key, $blacklist_globals)){
			try{
				VarSave("_globals", "$key.php", $value);
			}catch (Throwable $e){ //This will probably crash the bot
				echo "$key, ";
			}
		}else{
			echo "$key, ";
		}
	}
	echo PHP_EOL;
    
	sleep(300);
	
	echo "RESTARTING BOT" . PHP_EOL;
	$discord->destroy();
	//$restart_cmd = 'cmd /c "'. __DIR__  . '\run.bat"'; //echo $restart_cmd . PHP_EOL;
	//system($restart_cmd);
	//die();
	goto start;
}
?> 
