<?php
$message_content = $message->content;
if ( ($message_content == NULL) || ($message_content == "") ) return true;
$message_id = $message->id;
$message_content_lower = strtolower($message_content);

/*
*********************
*********************
Required includes
*********************
*********************
*/

include_once "custom_functions.php";
include "constants.php"; //Redeclare $now every time

//Load author data from message
$author_user													= $message->author; //User object
$author_channel 												= $message->channel;
$author_channel_id												= $author_channel->id; 											//echo "author_channel_id: " . $author_channel_id . PHP_EOL;
$author_channel_class											= get_class($author_channel);
$is_dm															= false;
if ($author_channel_class === "CharlotteDunois\Yasmin\Models\DMChannel") //True if direct message
$is_dm															= true;
$author_username 												= $author_user->username; 										//echo "author_username: " . $author_username . PHP_EOL;
$author_discriminator 											= $author_user->discriminator;									//echo "author_discriminator: " . $author_discriminator . PHP_EOL;
$author_id 														= $author_user->id;												//echo "author_id: " . $author_id . PHP_EOL;
$author_avatar 													= $author_user->getAvatarURL();									//echo "author_avatar: " . $author_avatar . PHP_EOL;
$author_check 													= "$author_username#$author_discriminator"; 					//echo "author_check: " . $author_check . PHP_EOL;

if ($message_content_lower == ';invite'){
	$author_user->createDM()->then(function($author_dmchannel) use ($discord){
		$discord->generateOAuthInvite(8)->then(function($BOTINVITELINK) use ($author_dmchannel){
			$author_dmchannel->send($BOTINVITELINK);
		});
	});
	return true;
}
/*
*********************
*********************
Get the guild and guildmember collections for the author
*********************
*********************
*/

if ($is_dm === false){ //Guild message
	$author_guild 												= $author_channel->guild;
	$author_guild_id 											= $author_guild->id; 											//echo "discord_guild_id: " . $author_guild_id . PHP_EOL;
	$author_guild_name											= $author_guild->name;
	$guild_owner_id												= $author_guild->ownerID;
	
	//Leave the guild if the owner is blacklisted
	GLOBAL $blacklisted_owners;
	if ($blacklisted_owners)
	if (in_array($guild_owner_id, $blacklisted_owners)){
		$author_guild->leave($author_guild_id)->done(null, function ($error){
			if (strlen($error) < (2049) ){
				echo "[ERROR] $error" . PHP_EOL; //Echo any errors
			}else{
				echo "[ERROR] [BLACKLISTED OWNER] $author_guild_id";
			}
		});
	}
	if (in_array($author_id, $blacklisted_owners)){ //Ignore all commands from blacklisted guild owners
		return true;
	}
	//Leave the guild if blacklisted
	GLOBAL $blacklisted_guilds;
	if ($blacklisted_guilds)
	if (in_array($author_guild_id, $blacklisted_guilds)){
		$author_guild->leave($author_guild_id)->done(null, function ($error){
			if (strlen($error) < (2049) ){
				echo "[ERROR] $error" . PHP_EOL; //Echo any errors
			}else{
				echo "[ERROR] [BLACKLISTED GUILD] $author_guild_id";
			}
		});
	}
	//Leave the guild if not whitelisted
	GLOBAL $whitelisted_guilds;
	if ($whitelisted_guilds)
	if (!in_array($author_guild_id, $whitelisted_guilds)){
		$author_guild->leave($author_guild_id)->done(null, function ($error){
			echo "[ERROR] $error".PHP_EOL; //Echo any errors
		});
	}
	
	$guild_folder = "\\guilds\\$author_guild_id"; //echo "guild_folder: $guild_folder" . PHP_EOL;
	//Create a folder for the guild if it doesn't exist already
	if(!CheckDir($guild_folder)){
		if(!CheckFile($guild_folder, "guild_owner_id.php")){
			VarSave($guild_folder, "guild_owner_id.php", $guild_owner_id);
		}else $guild_owner_id = VarLoad($guild_folder, "guild_owner_id.php");
	}
	if ($guild_owner_id == $author_id){
		$owner = true; //Enable usage of restricted commands
	} else $owner = false;
	
	//Load config variables for the guild
	$guild_config_path = __DIR__  . "$guild_folder\\guild_config.php";														//echo "guild_config_path: " . $guild_config_path . PHP_EOL;
	if(!CheckFile($guild_folder, "guild_config.php")){
		$file = 'guild_config_template.php';
		if (!copy($file, $guild_config_path)){
			$message->reply("Failed to create guild_config file! Please contact <@116927250145869826> for assistance.");
		}else $author_channel->send("<@$guild_owner_id>, I'm here! Please ;setup the bot." . PHP_EOL . "While interacting with this bot, any conversations made through direct mention of the bot name are stored anonymously in a secure database. Avatars, IDs, Names, or any other unique user identifier is not stored with these messages. Through continuing to use this bot, you agree to allow it to track user information to support its functions and for debugging purposes. Your message data will never be used for anything more. If you wish to have any associated information removed, please contact Valithor#5937.");
	}
	include "$guild_config_path"; //Configurable channel IDs, role IDs, and message IDs used in the guild for special functions
	
	$author_guild_avatar 										= $author_guild->getIconURL();
	$author_guild_roles 										= $author_guild->roles;
	if($getverified_channel_id) 	$getverified_channel 		= $author_guild->channels->get($getverified_channel_id);
	if($verifylog_channel_id) 		$verifylog_channel 			= $author_guild->channels->get($verifylog_channel_id); //Modlog is used if this is not declared
	if($watch_channel_id) 			$watch_channel 				= $author_guild->channels->get($watch_channel_id);
	if($modlog_channel_id) 			$modlog_channel 			= $author_guild->channels->get($modlog_channel_id);
	if($general_channel_id) 		$general_channel			= $author_guild->channels->get($general_channel_id);
	if($rolepicker_channel_id) 		$rolepicker_channel			= $author_guild->channels->get($rolepicker_channel_id);
	if($games_channel_id)			$games_channel				= $author_guild->channels->get($games_channel_id);
	if($suggestion_pending_channel_id) 	$suggestion_pending_channel		= $author_guild->channels->get(strval($suggestion_pending_channel_id));
	if($suggestion_approved_channel_id) $suggestion_approved_channel	= $author_guild->channels->get(strval($suggestion_approved_channel_id));
	$author_member 												= $author_guild->members->get($author_id); 				//GuildMember object
	$author_member_roles 										= $author_member->roles; 								//Role object for the author);
}else{ //Direct message
	if ($author_id != $discord->user->id){ //Don't trigger on messages sent by this bot
		GLOBAL $server_invite;
		echo "[DM-EARLY BREAK]" . PHP_EOL;			
		$dm_text = "Please use commands for this bot within a server unless otherwise prompted.";
		//$message->reply("$dm_text \n$server_invite");
		//$message->reply("$dm_text");
	}
	return true;
}

/*
*********************
*********************
Options
*********************
*********************
*/
if(!CheckFile($guild_folder, "command_symbol.php")){
												//Author must prefix text with this to use commands
}else $command_symbol = VarLoad($guild_folder, "command_symbol.php");			//Load saved option file (Not used yet, but might be later)

//Chat options
GLOBAL $react_option, $vanity_option, $nsfw_option, $games_option;
if(!CheckFile($guild_folder, "react_option.php"))
														$react	= $react_option;								//Bot will not react to messages if false
else 													$react 	= VarLoad($guild_folder, "react_option.php");			//Load saved option file
if(!CheckFile($guild_folder, "vanity_option.php"))
														$vanity	= $vanity_option;								//Allow SFW vanity like hug, nuzzle, kiss
else 													$vanity = VarLoad($guild_folder, "vanity_option.php");			//Load saved option file
if(!CheckFile($guild_folder, "nsfw_option.php"))
														$nsfw	= $nsfw_option;									//Allow NSFW commands
else 													$nsfw 	= VarLoad($guild_folder, "nsfw_option.php");				//Load saved option file
if(!CheckFile($guild_folder, "games_option.php"))
														$games	= $games_option;									//Allow games like Yahtzee
else 													$games 	= VarLoad($guild_folder, "games_option.php");				//Load saved option file

//Role picker options		
GLOBAL $rolepicker_option, $species_option, $gender_option, $sexuality_option, $custom_option;		
if ( ($rolepicker_id != "") || ($rolepicker_id != NULL) ){
	if(!CheckFile($guild_folder, "rolepicker_option.php")){
														$rp0	= $rolepicker_option;							//Allow Rolepicker
	}else 												$rp0	= VarLoad($guild_folder, "rolepicker_option.php");
	if ( ($species_message_id != "") || ($species_message_id != NULL) ){
		if(!CheckFile($guild_folder, "species_option.php")){
														$rp1	= $species_option;								//Species role picker
		}else 											$rp1	= VarLoad($guild_folder, "species_option.php");
	} else												$rp1	= false;
	if ( ($gender_message_id != "") || ($gender_message_id != NULL) ){
		if(!CheckFile($guild_folder, "gender_option.php")){
														$rp2	= $gender_option;								//Gender role picker
		}else 											$rp2	= VarLoad($guild_folder, "gender_option.php");
	} else												$rp2 	= false;
	if ( ($sexuality_message_id != "") || ($species_message_id != NULL) ){
		if(!CheckFile($guild_folder, "sexuality_option.php")){
														$rp3	= $sexuality_option;							//Sexuality role picker
		}else 											$rp3	= VarLoad($guild_folder, "sexuality_option.php");
	} else												$rp3	= false;
	if ( ($customroles_message_id != "") || ($customroles_message_id != NULL) ){
		if(!CheckFile($guild_folder, "customrole_option.php"))
														$rp4	= $custom_option;								//Custom role picker
		else 											$rp4	= VarLoad($guild_folder, "customrole_option.php");
	}else												$rp4	= false;
}else{ //All functions are disabled
														$rp0 	= false;
														$rp1 	= false;
														$rp3 	= false;
														$rp2 	= false;
														$rp4 	= false;
}

echo "$author_check <@$author_id> ($author_guild_id): {$message_content}", PHP_EOL;
$author_webhook = $author_user->webhook;
if ($author_webhook === true) return true; //Don't process webhooks
$author_bot = $author_user->bot;
if ($author_bot === true) return true; //Don't process bots

/*

*********************
*********************
Load persistent variables for author
*********************
*********************
*/

$author_folder = $guild_folder."\\".$author_id;
CheckDir($author_folder); //Check if folder exists and create if it doesn't
if(CheckFile($author_folder, "watchers.php")){
echo "[WATCH] $author_id" . PHP_EOL;
	$watchers = VarLoad($author_folder, "watchers.php");
//	echo "WATCHERS: " . var_dump($watchers); //array of user IDs
	$null_array = true; //Assume the object is empty
	foreach ($watchers as $watcher){
		if ($watcher != NULL){																									//echo "watcher: " . $watcher . PHP_EOL;
			$null_array = false; //Mark the array as valid
			try{ //Get objects for the watcher
				$watcher_member = $author_guild->members->get($watcher);													//echo "watcher_member class: " . get_class($watcher_member) . PHP_EOL;
				$watcher_user = $watcher_member->user;																		//echo "watcher_user class: " . get_class($watcher_user) . PHP_EOL;
				$watcher_user->createDM()->then(function($watcher_dmchannel) use ($message){	//Promise
//					echo "watcher_dmchannel class: " . get_class($watcher_dmchannel) . PHP_EOL; //DMChannel
					if($watch_channel) $watch_channel->send("<@{$message->author->id}> sent a message in <#{$message->channel->id}>: \n{$message->content}");
					elseif($watcher_dmchannel) $watcher_dmchannel->send("<@{$message->author->id}> sent a message in <#{$message->channel->id}>: \n{$message->content}");
					return true;
				});
			}catch(Exception $e){
//				RuntimeException: Unknown property
			}
		}
	}
	if($null_array === true){ //Delete the null file
		VarDelete($author_folder, "watchers.php");
		echo "[REMOVE WATCH] $author_id" . PHP_EOL;
	}
}

/*
*********************
*********************
Guild-specific variables
*********************
*********************
*/


include 'CHANGEME.php';
if($author_id != $creator_id) 		$creator	= false;
else 								$creator 	= true;


$adult 		= false;

//$owner		= false; //This is populated directly from the guild
$dev		= false; //This is a higher rank than admin because they're assumed to have administrator privileges
$admin 		= false;
$mod		= false;
$assistant  = false; $role_assistant_id = "688346849349992494";
$tech  		= false; $role_tech_id 		= "688349304691490826";
$verified	= false;
$bot		= false;
$vzgbot		= false;
$muted		= false;

$author_guild_roles_names 				= array(); 												//Names of all guild roles
$author_guild_roles_ids 				= array(); 												//IDs of all guild roles
foreach ($author_guild_roles as $role){
	$author_guild_roles_names[] 		= $role->name; 																		//echo "role[$x] name: " . PHP_EOL; //var_dump($role->name);
	$author_guild_roles_ids[] 			= $role->id; 																		//echo "role[$x] id: " . PHP_EOL; //var_dump($role->id);
	if ($role->name == "Palace Bot")	$role_vzgbot_id = $role->id;						//Author is this bot
}																															//echo "discord_guild_roles_names" . PHP_EOL; var_dump($author_guild_roles_names);
																															//echo "discord_guild_roles_ids" . PHP_EOL; var_dump($author_guild_roles_ids);
/*
*********************
*********************
Get the guild-related collections for the author
*********************
*********************
*/
//Populate arrays of the info we need
$author_member_roles_names 										= array();
$author_member_roles_ids 										= array();
$x=0;
foreach ($author_member_roles as $role){
	if ($x!=0){ //0 is always @everyone so skip it
		$author_member_roles_names[] 							= $role->name; 												//echo "role[$x] name: " . PHP_EOL; //var_dump($role->name);
		$author_member_roles_ids[]								= $role->id; 												//echo "role[$x] id: " . PHP_EOL; //var_dump($role->id);
		if ($role->id == $role_18_id)			$adult 			= true;							//Author has the 18+ role
		if ($role->id == $role_dev_id)    		$dev 			= true;							//Author has the dev role
		if ($role->id == $role_owner_id)    	$owner	 		= true;							//Author has the owner role
		if ($role->id == $role_admin_id)		$admin 			= true;							//Author has the admin role
		if ($role->id == $role_mod_id)			$mod 			= true;							//Author has the mod role
		if ($role->id == $role_assistant_id)	$assistant 		= true;							//Author has the assistant role
		if ($role->id == $role_tech_id)			$tech 		= true;							//Author has the tech role
		if ($role->id == $role_verified_id)		$verified 		= true;							//Author has the verified role
		if ($role->id == $role_bot_id)			$bot 			= true;							//Author has the bot role
		if ($role->id == $role_vzgbot_id)		$vzgbot 		= true;							//Author is this bot
		if ($role->id == $role_muted_id)		$muted 			= true;							//Author is this bot
	}
	$x++;
}
if ($creator || $owner || $dev)	$bypass = true; //Ignore spam restrictions
else					$bypass = false;

if( ($rolepicker_id == "") || ($rolepicker_id == "0") || ($rolepicker_id === NULL) ){ //Message rolepicker menus
	$rolepicker_id = $GLOBALS['id']; //Default to Palace Bot
}
GLOBAL $species, $species2, $species3, $species_message_text, $species2_message_text, $species3_message_text;
GLOBAL $gender, $gender_message_text;
GLOBAL $sexualities, $sexuality_message_text;
GLOBAL $customroles, $customroles_message_text;


//Early break
if(substr($message_content_lower, 0, 1) == $command_symbol){
	$message_content_lower = trim(substr($message_content_lower, 1));
	$message_content = trim(substr($message_content, 1));
}elseif (substr($message_content_lower, 0, 2) == '!s'){
	$message_content_lower = trim(substr($message_content_lower, 2));
	$message_content = trim(substr($message_content, 2));
}else{ //Expected prefix is missing
	return true;
}
	/*
	*********************
	*********************
	Owner setup command (NOTE: Changes made here will not affect servers using a manual config file)
	*********************
	*********************
	*/

	if ($creator || $owner || $dev){
		switch ($message_content_lower){
			case 'setup': //;setup
				$documentation = $documentation . "`currentsetup` send DM with current settings\n";
				$documentation = $documentation . "`updateconfig` updates the configuration file (needed for updates)\n";
				$documentation = $documentation . "`clearconfig` deletes all configuration information for the srver\n";
				//Roles
				$documentation = $documentation . "\n**Roles:**\n";
				$documentation = $documentation . "`setup dev @role`\n";
				$documentation = $documentation . "`setup admin @role`\n";
				$documentation = $documentation . "`setup mod @role`\n";
				$documentation = $documentation . "`setup bot @role`\n";
				$documentation = $documentation . "`setup vzg @role` (Role with the name Palace Bot, not the actual bot)\n";
				$documentation = $documentation . "`setup muted @role`\n";
				$documentation = $documentation . "`setup verified @role`\n";
				$documentation = $documentation . "`setup adult @role`\n";
				//User
				/* Deprecated
				$documentation = $documentation . "**Users:**\n";
				$documentation = $documentation . "`setup rolepicker @user` The user who posted the rolepicker messages\n";
				*/
				//Channels
				$documentation = $documentation . "**Channels:**\n";
				$documentation = $documentation . "`setup general #channel` The primary chat channel, also welcomes new users to everyone\n";
				$documentation = $documentation . "`setup welcome #channel` Simple welcome message tagging new user\n";
				$documentation = $documentation . "`setup welcomelog #channel` Detailed message about the user\n";
				$documentation = $documentation . "`setup log #channel` Detailed log channel\n"; //Modlog
				$documentation = $documentation . "`setup verify channel #channel` Detailed log channel\n";
				$documentation = $documentation . "`setup watch #channel` ;watch messages are duplicated here instead of in a DM\n";
				/* Deprecated
				$documentation = $documentation . "`setup rolepicker channel #channel` Where users pick a role\n";
				*/
				$documentation = $documentation . "`setup games channel #channel` Where users can play games\n";
				$documentation = $documentation . "`setup suggestion pending #channel` \n";
				$documentation = $documentation . "`setup suggestion approved #channel` \n";
				//Messages
				
				$documentation = $documentation . "**Messages:**\n";
				/* Deprecated
				$documentation = $documentation . "`setup species messageid`\n";
				$documentation = $documentation . "`setup species2 messageid`\n";
				$documentation = $documentation . "`setup species3 messageid`\n";
				$documentation = $documentation . "`setup sexuality messageid`\n";
				$documentation = $documentation . "`setup gender messageid`\n";
				$documentation = $documentation . "`setup customroles messageid`\n";
				*/
				$documentation = $documentation . "`message species`\n";
				$documentation = $documentation . "`message species2`\n";
				$documentation = $documentation . "`message species3`\n";
				$documentation = $documentation . "`message gender`\n";
				$documentation = $documentation . "`message sexuality`\n";
				$documentation = $documentation . "`message customroles`\n";
				
				$documentation_sanitized = str_replace("\n","",$documentation);
				$doc_length = strlen($documentation_sanitized);
				if ($doc_length < 1024){
					$embed = new \CharlotteDunois\Yasmin\Models\MessageEmbed();
					$embed
						->setTitle("Setup commands for $author_guild_name")														// Set a title
						->setColor("e1452d")																	// Set a color (the thing on the left side)
						->setDescription("$documentation")														// Set a description (below title, above fields)
						//->addField("⠀", "$documentation")														// New line after this			
						//->setThumbnail("$author_avatar")														// Set a thumbnail (the image in the top right corner)
						//->setImage('https://avatars1.githubusercontent.com/u/4529744?s=460&v=4')             	// Set an image (below everything except footer)
						//->setTimestamp()                                                                     	// Set a timestamp (gets shown next to footer)
						//->setAuthor("$author_check", "$author_guild_avatar")  									// Set an author with icon
						->setFooter("Palace Bot by Valithor#5947")                             					// Set a footer without icon
						->setURL("");                             												// Set the URL
					//Open a DM channel then send the rich embed message
					$author_user->createDM()->then(function($author_dmchannel) use ($message, $embed){	//Promise
						echo "[;SETUP EMBED]" . PHP_EOL;
						return $author_dmchannel->send('', array('embed' => $embed))->done(null, function ($error){
							echo "[ERROR] $error" . PHP_EOL; //Echo any errors
						});
					});
					return true;
				}else{
					$author_user->createDM()->then(function($author_dmchannel) use ($message, $documentation){	//Promise
						echo "[;SETUP MESSAGE]" . PHP_EOL;
						$author_dmchannel->send($documentation);
					});
					return true;
				}
				break;
			case 'currentsetup': //;currentsetup
				//Send DM with current settings
				//Roles
				$documentation = $documentation . "\n**Roles:**\n";
				$documentation = $documentation . "`dev @role` $role_dev_id\n";
				$documentation = $documentation . "`admin @role` $role_admin_id\n";
				$documentation = $documentation . "`mod @role` $role_mod_id\n";
				$documentation = $documentation . "`bot @role` $role_bot_id\n";
				$documentation = $documentation . "`vzg @role` $role_vzgbot_id\n";
				$documentation = $documentation . "`muted @role` $role_muted_id\n";
				$documentation = $documentation . "`verified @role` $role_verified_id\n";
				$documentation = $documentation . "`adult @role` $role_18_id\n";
				//User
				$documentation = $documentation . "**Users:**\n";
				$documentation = $documentation . "`rolepicker @user` $rolepicker_id\n";
				//Channels
				$documentation = $documentation . "**Channels:**\n";
				$documentation = $documentation . "`general #channel` $general_channel\n";
				if($welcome_public_channel_id) 		$welcome_public_channel			= $author_guild->channels->get($welcome_public_channel_id);
				if($welcome_log_channel_id) 		$welcome_log_channel			= $author_guild->channels->get($welcome_log_channel_id);
				$documentation = $documentation . "`welcome #channel` $welcome_public_channel\n";
				$documentation = $documentation . "`welcomelog #channel` $welcome_log_channel\n";
				$documentation = $documentation . "`log #channel` $modlog_channel\n";
				$documentation = $documentation . "`verify channel #channel` $getverified_channel\n";
				if ($verifylog_channel_id)
					$documentation = $documentation . "`verifylog #channel` $verifylog_channel\n";
				else $documentation = $documentation . "`verifylog #channel` (defaulted to log channel)\n";
				if ($watch_channel_id)
					$documentation = $documentation . "`watch #channel` $watch_channel\n";
				else $documentation = $documentation . "`watch #channel` (defaulted to direct message only)\n";
				$documentation = $documentation . "`rolepicker channel #channel` $rolepicker_channel\n";
				$documentation = $documentation . "`games #channel` $games_channel\n";
				$documentation = $documentation . "`suggestion pending #channel` $suggestion_pending_channel\n";
				$documentation = $documentation . "`suggestion approved #channel` $suggestion_approved_channel\n";
				//Messages
				$documentation = $documentation . "**Messages:**\n";
				if ($species_message_id) $documentation = $documentation . "`species messageid` $species_message_id\n";
				else $documentation = $documentation . "`species messageid` Message not yet sent!\n";
				if ($species2_message_id) $documentation = $documentation . "`species2 messageid` $species2_message_id\n";
				else $documentation = $documentation . "`species2 messageid` Message not yet sent!\n";
				if ($species3_message_id) $documentation = $documentation . "`species3 messageid` $species3_message_id\n";
				else $documentation = $documentation . "`species3 messageid` Message not yet sent!\n";
				if ($gender_message_id) $documentation = $documentation . "`gender messageid` $gender_message_id\n";
				else $documentation = $documentation . "`gender messageid` Message not yet sent!\n";
				if ($sexuality_message_id) $documentation = $documentation . "`sexuality messageid` $sexuality_message_id\n";
				else $documentation = $documentation . "`sexuality messageid` Message not yet sent!\n";
				if ($customroles_message_id) $documentation = $documentation . "`customroles messageid` $customroles_message_id\n";
				else $documentation = $documentation . "`customroles messageid` Message not yet sent!\n";
				
				$documentation_sanitized = str_replace("\n","",$documentation);
				$doc_length = strlen($documentation_sanitized);
				if ($doc_length < 1024){
					$embed = new \CharlotteDunois\Yasmin\Models\MessageEmbed();
					$embed
						->setTitle("Current setup for $author_guild_name")														// Set a title
						->setColor("e1452d")																	// Set a color (the thing on the left side)
						->setDescription("$documentation")														// Set a description (below title, above fields)
			//					->addField("⠀", "$documentation")														// New line after this			
			//					->setThumbnail("$author_avatar")														// Set a thumbnail (the image in the top right corner)
			//					->setImage('https://avatars1.githubusercontent.com/u/4529744?s=460&v=4')             	// Set an image (below everything except footer)
			//					->setTimestamp()                                                                     	// Set a timestamp (gets shown next to footer)
			//					->setAuthor("$author_check", "$author_guild_avatar")  									// Set an author with icon
						->setFooter("Palace Bot by Valithor#5947")                             					// Set a footer without icon
						->setURL("");                             												// Set the URL
			//				Open a DM channel then send the rich embed message
					$author_user->createDM()->then(function($author_dmchannel) use ($message, $embed){	//Promise
						echo "[;CURRENTSETUP EMBED]" . PHP_EOL;
						return $author_dmchannel->send('', array('embed' => $embed))->done(null, function ($error){
							echo "[ERROR] $error" . PHP_EOL; //Echo any errors
						});
					});
					return true;
				}else{
					$author_user->createDM()->then(function($author_dmchannel) use ($message, $documentation){	//Promise
						echo "[;CURRENTSETUP MESSAGE]" . PHP_EOL;
						$author_dmchannel->send($documentation);
					});
					return true;
				}
				break;
			case 'updateconfig': //;updateconfig
				$file = 'guild_config_template.php';
				if (sha1_file($guild_config_path) == sha1_file('guild_config_template.php')) {
					$message->reply("Guild configuration is already up to date!");
				}else{
					if (!copy($file, $guild_config_path)){
						$message->reply("Failed to create guild_config file! Please contact <@116927250145869826> for assistance.");
					}else $author_channel->send("The server's configuration file was recently updated by <@$author_id>. Please check the ;currentsetup");
				}
				break;
			case 'clearconfig': //;clearconfig
				$files = glob(__DIR__  . "$guild_folder" . '/*');
				// Deleting all the files in the list 
				foreach($files as $file) { 
					if(is_file($file))
						unlink($file); //Delete the file
				}
				$author_channel->send("The server's configuration files were recently delete by <@$author_id>. Please run the ;setup commands again.");
				return true;
				break;
			//Role Messages Setup
			case 'message species': //;message species
				VarSave($guild_folder, "rolepicker_channel_id.php", strval($author_channel_id)); //Make this channel the rolepicker channel
				$author_channel->send($species_message_text)->then(function($new_message) use ($guild_folder, $species, $message){
					VarSave($guild_folder, "species_message_id.php", strval($new_message->id));
					foreach($species as $var_name => $value){
						$new_message->react($value);
					}
					$message->delete();
					return true;
				});
				return true;
				break;
			case 'message species2': //;message species2
				VarSave($guild_folder, "rolepicker_channel_id.php", strval($author_channel_id)); //Make this channel the rolepicker channel
				$author_channel->send($species2_message_text)->then(function($new_message) use ($guild_folder, $species2, $message){;
					VarSave($guild_folder, "species2_message_id.php", strval($new_message->id));
					foreach($species2 as $var_name => $value){
						$new_message->react($value);
					}
					$message->delete();
					return true;
				});
				return true;
				break;
			case 'message species3': //;message species3
				VarSave($guild_folder, "rolepicker_channel_id.php", strval($author_channel_id)); //Make this channel the rolepicker channel
				$author_channel->send($species3_message_text)->then(function($new_message) use ($guild_folder, $species3, $message){;
					VarSave($guild_folder, "species3_message_id.php", strval($new_message->id));
					foreach($species3 as $var_name => $value){
						$new_message->react($value);
					}
					$message->delete();
					return true;
				});
				return true;
				break;
			case 'message gender': //;message gender
				echo '[GENDER MESSAGE GEN]' . PHP_EOL;
				VarSave($guild_folder, "rolepicker_channel_id.php", strval($author_channel_id)); //Make this channel the rolepicker channel
				$author_channel->send($gender_message_text)->then(function($new_message) use ($guild_folder, $gender, $message){;
					VarSave($guild_folder, "gender_message_id.php", strval($new_message->id));
					foreach($gender as $var_name => $value){
						$new_message->react($value);
					}
					$message->delete();
					return true;
				});
				return true;
				break;
			case 'message sexuality':
			case 'message sexualities':
				VarSave($guild_folder, "rolepicker_channel_id.php", strval($author_channel_id)); //Make this channel the rolepicker channel
				$author_channel->send($sexuality_message_text)->then(function($new_message) use ($guild_folder, $sexualities, $message){;
					VarSave($guild_folder, "sexuality_message_id.php", strval($new_message->id));
					foreach($sexualities as $var_name => $value){
						$new_message->react($value);
					}
					$message->delete();
					return true;
				});
				return true;
				break;
			case 'message customroles': //;message customroles
				VarSave($guild_folder, "rolepicker_channel_id.php", strval($author_channel_id)); //Make this channel the rolepicker channel
				$author_channel->send($customroles_message_text)->then(function($new_message) use ($guild_folder, $customroles, $message){;
					VarSave($guild_folder, "customroles_message_id.php", strval($new_message->id));
					foreach($customroles as $var_name => $value){
						$new_message->react($value);
					}
					$message->delete();
					return true;
				});
				return true;
				break;
		//Toggles
			case 'react':
				if(!CheckFile($guild_folder, "react_option.php")){
					VarSave($guild_folder, "react_option.php", $react_option);
					echo "[NEW REACT OPTION FILE]";
				}
				$react_var = VarLoad($guild_folder, "react_option.php");
				$react_flip = !$react_var;
				VarSave($guild_folder, "react_option.php", $react_flip);
				if($react) $message->react("👍");
				if ($react_flip === true)
					$message->reply("Reaction functions enabled!");
				else $message->reply("Reaction functions disabled!");
				return true;
				break;
			case 'vanity': //toggle vanity functions ;vanity
				if(!CheckFile($guild_folder, "vanity_option.php")){
					VarSave($guild_folder, "vanity_option.php", $vanity_option);
					echo "[NEW VANITY OPTION FILE]" . PHP_EOL;
				}
				$vanity_var = VarLoad($guild_folder, "vanity_option.php");
				$vanity_flip = !$vanity_var;
				VarSave($guild_folder, "vanity_option.php", $vanity_flip);
				if($react) $message->react("👍");
				if ($vanity_flip === true)
					$message->reply("Vanity functions enabled!");
				else $message->reply("Vanity functions disabled!");
				return true;
				break;
			case 'nsfw':
				if(!CheckFile($guild_folder, "nsfw_option.php")){
					VarSave($guild_folder, "nsfw_option.php", $nsfw_option);
					echo "[NEW NSFW OPTION FILE]" . PHP_EOL;
				}
				$nsfw_var = VarLoad($guild_folder, "nsfw_option.php");
				$nsfw_flip = !$nsfw_var;
				VarSave($guild_folder, "nsfw_option.php", $nsfw_flip);
				if($react) $message->react("👍");
				if ($nsfw_flip === true)
					$message->reply("NSFW functions enabled!");
				else $message->reply("NSFW functions disabled!");
				return true;
				break;
			case 'games':
				if(!CheckFile($guild_folder, "games_option.php")){
					VarSave($guild_folder, "games_option.php", $games_option);
					echo "[NEW GAMES OPTION FILE]" . PHP_EOL;
				}
				$games_var = VarLoad($guild_folder, "games_option.php");
				$games_flip = !$games_var;
				VarSave($guild_folder, "games_option.php", $games_flip);
				if($react) $message->react("👍");
				if ($games_flip === true)
					$message->reply("Games functions enabled!");
				else $message->reply("Games functions disabled!");
				return true;
				break;
			case 'rolepicker':
				if(!CheckFile($guild_folder, "rolepicker_option.php")){
					VarSave($guild_folder, "rolepicker_option.php", $rolepicker_option);
					echo "[NEW ROLEPICKER FILE]" . PHP_EOL;
				}
				$rolepicker_var = VarLoad($guild_folder, "rolepicker_option.php");
				$rolepicker_flip = !$rolepicker_var;
				VarSave($guild_folder, "rolepicker_option.php", $rolepicker_flip);
				if($react) $message->react("👍");
				if ($rolepicker_flip === true)
					$message->reply("Rolepicker enabled!");
				else $message->reply("Rolepicker disabled!");
				return true;
				break;
			case 'species':
				if(!CheckFile($guild_folder, "species_option.php")){
					VarSave($guild_folder, "species_option.php", $species_option);
					echo "[NEW SPECIES FILE]" . PHP_EOL;
				}
				$species_var = VarLoad($guild_folder, "species_option.php");
				$species_flip = !$species_var;
				VarSave($guild_folder, "species_option.php", $species_flip);
				if($react) $message->react("👍");
				if ($species_flip === true)
					$message->reply("Species roles enabled!");
				else $message->reply("Species roles	disabled!");
				return true;
				break;
			case 'gender':
				if(!CheckFile($guild_folder, "gender_option.php")){
					VarSave($guild_folder, "gender_option.php", $gender_option);
					echo "[NEW GENDER FILE]" . PHP_EOL;
				}
				$gender_var = VarLoad($guild_folder, "gender_option.php");
				$gender_flip = !$gender_var;
				VarSave($guild_folder, "gender_option.php", $gender_flip);
				if($react) $message->react("👍");
				if ($gender_flip === true)
					$message->reply("Gender roles enabled!");
				else $message->reply("Gender roles disabled!");
				return true;
				break;
			case 'sexuality':
				if(!CheckFile($guild_folder, "sexuality_option.php")){
					VarSave($guild_folder, "sexuality_option.php", $sexuality_option);
					echo "[NEW SEXUALITY FILE]" . PHP_EOL;
				}
				$sexuality_var = VarLoad($guild_folder, "sexuality_option.php");
				$sexuality_flip = !$sexuality_var;
				VarSave($guild_folder, "sexuality_option.php", $sexuality_option);
				if($react) $message->react("👍");
				if ($sexuality_flip === true)
					$message->reply("Sexuality roles enabled!");
				else $message->reply("Sexuality roles disabled!");
				return true;
				break;
			case 'customroles':
				if(!CheckFile($guild_folder, "custom_option.php")){
					VarSave($guild_folder, "custom_option.php", $custom_option);
					echo "[NEW CUSTOM ROLE OPTION FILE]" . PHP_EOL;
				}
				$custom_var = VarLoad($guild_folder, "custom_option.php");
				$custom_flip = !$custom_var;
				VarSave($guild_folder, "custom_option.php", $custom_flip);
				if($react) $message->react("👍");
				if ($custom_flip === true)
					$message->reply("Custom roles enabled!");
				else $message->reply("Custom roles disabled!");
				return true;
				break;
		}
		//End switch
		//Roles
		if (substr($message_content_lower, 0, 10) == 'setup dev '){
			$filter = "setup dev ";
			$value = str_replace($filter, "", $message_content_lower);
			$value = str_replace("<@&", "", $value);
			$value = str_replace(">", "", $value);
			$value = trim($value);
			if(is_numeric($value)){
				VarSave($guild_folder, "role_dev_id.php", $value);
				$message->reply("Developer role ID saved!");
			}else $message->reply("Invalid input! Please enter an ID or @mention the role");
			return true;
		}
		if (substr($message_content_lower, 0, 12) == 'setup admin '){
			$filter = "setup admin ";
			$value = str_replace($filter, "", $message_content_lower);
			$value = str_replace("<@&", "", $value);
			$value = str_replace(">", "", $value);
			$value = trim($value);
			if(is_numeric($value)){
				VarSave($guild_folder, "role_admin_id.php", $value);
				$message->reply("Admin role ID saved!");
			}else $message->reply("Invalid input! Please enter an ID or @mention the role");
			return true;
		}
		if (substr($message_content_lower, 0, 10) == 'setup mod '){
			$filter = "setup mod ";
			$value = str_replace($filter, "", $message_content_lower);
			$value = str_replace("<@&", "", $value);
			$value = str_replace(">", "", $value);
			$value = trim($value);
			if(is_numeric($value)){
				VarSave($guild_folder, "role_mod_id.php", $value);
				$message->reply("Moderator role ID saved!");
			}else $message->reply("Invalid input! Please enter an ID or @mention the role");
			return true;
		}
		if (substr($message_content_lower, 0, 10) == 'setup bot '){
			$filter = "setup bot ";
			$value = str_replace($filter, "", $message_content_lower);
			$value = str_replace("<@&", "", $value);
			$value = str_replace(">", "", $value);
			$value = trim($value);
			if(is_numeric($value)){
				VarSave($guild_folder, "role_bot_id.php", $value);
				$message->reply("Bot role ID saved!");
			}else $message->reply("Invalid input! Please enter an ID or @mention the role");
			return true;
		}
		if (substr($message_content_lower, 0, 13) == 'setup vzgbot '){
			$filter = "setup vzgbot ";
			$value = str_replace($filter, "", $message_content_lower);
			$value = str_replace("<@&", "", $value);
			$value = str_replace(">", "", $value);
			$value = trim($value);
			if(is_numeric($value)){
				VarSave($guild_folder, "role_vzgbot_id.php", $value);
				$message->reply("Palace Bot role ID saved!");
			}else $message->reply("Invalid input! Please enter an ID or @mention the role");
			return true;
		}
		if (substr($message_content_lower, 0, 12) == 'setup muted '){
			$filter = "setup muted ";
			$value = str_replace($filter, "", $message_content_lower);
			$value = str_replace("<@&", "", $value);
			$value = str_replace(">", "", $value);
			$value = trim($value);//echo "value: '$value';" . PHP_EOL;
			if(is_numeric($value)){
				VarSave($guild_folder, "role_muted_id.php", $value);
				$message->reply("Muted role ID saved!");
			}else $message->reply("Invalid input! Please enter an ID or @mention the role");
			return true;
		}
		if (substr($message_content_lower, 0, 15) == 'setup verified '){
			$filter = "setup verified ";
			$value = str_replace($filter, "", $message_content_lower);
			$value = str_replace("<@&", "", $value);
			$value = str_replace(">", "", $value);
			$value = trim($value);
			if(is_numeric($value)){
				VarSave($guild_folder, "role_verified_id.php", $value);
				$message->reply("Verified role ID saved!");
			}else $message->reply("Invalid input! Please enter an ID or @mention the role");
			return true;
		}
		if (substr($message_content_lower, 0, 12) == 'setup adult '){
			$filter = "setup adult ";
			$value = str_replace($filter, "", $message_content_lower);
			$value = str_replace("<@&", "", $value);
			$value = str_replace(">", "", $value);
			$value = trim($value);
			if(is_numeric($value)){
				VarSave($guild_folder, "role_18_id.php", $value);
				$message->reply("Adult role ID saved!");
			}else $message->reply("Invalid input! Please enter an ID or @mention the role");
			return true;
		}
		//Channels
		if (substr($message_content_lower, 0, 14) == 'setup general '){
			$filter = "setup general ";
			$value = str_replace($filter, "", $message_content_lower);
			$value = str_replace("<#", "", $value);
			$value = str_replace(">", "", $value);
			$value = trim($value);
			if(is_numeric($value)){
				VarSave($guild_folder, "general_channel_id.php", $value);
				$message->reply("General channel ID saved!");
			}else $message->reply("Invalid input! Please enter a channel ID or <#mention> a channel");
			return true;
		}
		if (substr($message_content_lower, 0, 14) == 'setup welcome '){
			$filter = "setup welcome ";
			$value = str_replace($filter, "", $message_content_lower);
			$value = str_replace("<#", "", $value);
			$value = str_replace(">", "", $value);
			$value = trim($value);
			if(is_numeric($value)){
				VarSave($guild_folder, "welcome_public_channel_id.php", $value);
				$message->reply("Welcome channel ID saved!");
			}else $message->reply("Invalid input! Please enter a channel ID or <#mention> a channel");
			return true;
		}
		if (substr($message_content_lower, 0, 17) == 'setup welcomelog '){
			$filter = "setup welcomelog ";
			$value = str_replace($filter, "", $message_content_lower);
			$value = str_replace("<#", "", $value);
			$value = str_replace(">", "", $value);
			$value = trim($value);
			if(is_numeric($value)){
				VarSave($guild_folder, "welcome_log_channel_id.php", $value);
				$message->reply("Welcome log channel ID saved!");
			}else $message->reply("Invalid input! Please enter a channel ID or <#mention> a channel");
			return true;
		}
		if (substr($message_content_lower, 0, 10) == 'setup log '){
			$filter = "setup log ";
			$value = str_replace($filter, "", $message_content_lower);
			$value = str_replace("<#", "", $value);
			$value = str_replace(">", "", $value);
			$value = trim($value);
			if(is_numeric($value)){
				VarSave($guild_folder, "modlog_channel_id.php", $value);
				$message->reply("Log channel ID saved!");
			}else $message->reply("Invalid input! Please enter a channel ID or <#mention> a channel");
			return true;
		}
		if (substr($message_content_lower, 0, 21) == 'setup verify channel '){
			$filter = "setup verify channel ";
			$value = str_replace($filter, "", $message_content_lower);
			$value = str_replace("<#", "", $value);
			$value = str_replace(">", "", $value);
			$value = trim($value);
			if(is_numeric($value)){
				VarSave($guild_folder, "getverified_channel_id.php", $value);
				$message->reply("Verify channel ID saved!");
			}else $message->reply("Invalid input! Please enter a channel ID or <#mention> a channel");
			return true;
		}
		if (substr($message_content_lower, 0, 16) == 'setup verifylog '){
			$filter = "setup verifylog ";
			$value = str_replace($filter, "", $message_content_lower);
			$value = str_replace("<#", "", $value);
			$value = str_replace(">", "", $value);
			$value = trim($value);
			if(is_numeric($value)){
				VarSave($guild_folder, "verifylog_channel_id.php", $value);
				$message->reply("Verifylog channel ID saved!");
			}else $message->reply("Invalid input! Please enter a channel ID or <#mention> a channel");
			return true;
		}
		if (substr($message_content_lower, 0, 12) == 'setup watch '){
			$filter = "setup watch ";
			$value = str_replace($filter, "", $message_content_lower);
			$value = str_replace("<#", "", $value);
			$value = str_replace(">", "", $value);
			$value = trim($value);
			if(is_numeric($value)){
				VarSave($guild_folder, "watch_channel_id.php", $value);
				$message->reply("Watch channel ID saved!");
			}else $message->reply("Invalid input! Please enter a channel ID or <#mention> a channel");
			return true;
		}
		if (substr($message_content_lower, 0, 25) == 'setup rolepicker channel '){
			$filter = "setup rolepicker channel ";
			$value = str_replace($filter, "", $message_content_lower);
			$value = str_replace("<#", "", $value);
			$value = str_replace(">", "", $value);
			$value = trim($value); //echo "value: " . $value . PHP_EOL;
			if(is_numeric($value)){
				VarSave($guild_folder, "rolepicker_channel_id.php", $value);
				$message->reply("Rolepicker channel ID saved!");
			}else $message->reply("Invalid input! Please enter a channel ID or <#mention> a channel");
			return true;
		}
		if (substr($message_content_lower, 0, 12) == 'setup games '){
			$filter = "setup games ";
			$value = str_replace($filter, "", $message_content_lower);
			$value = str_replace("<#", "", $value);
			$value = str_replace(">", "", $value);
			$value = trim($value); //echo "value: " . $value . PHP_EOL;
			if(is_numeric($value)){
				VarSave($guild_folder, "games_channel_id.php", $value);
				$message->reply("Games channel ID saved!");
			}else $message->reply("Invalid input! Please enter a channel ID or <#mention> a channel");
			return true;
		}
		if (substr($message_content_lower, 0, 25) == 'setup suggestion pending '){
			$filter = "setup suggestion pending ";
			$value = str_replace($filter, "", $message_content_lower);
			$value = str_replace("<#", "", $value);
			$value = str_replace(">", "", $value);
			$value = trim($value); //echo "value: " . $value . PHP_EOL;
			if(is_numeric($value)){
				VarSave($guild_folder, "suggestion_pending_channel_id.php", $value);
				$message->reply("Suggestion pending channel ID saved!");
			}else $message->reply("Invalid input! Please enter a channel ID or <#mention> a channel");
			return true;
		}
		if (substr($message_content_lower, 0, 26) == 'setup suggestion approved '){
			$filter = "setup suggestion approved ";
			$value = str_replace($filter, "", $message_content_lower);
			$value = str_replace("<#", "", $value);
			$value = str_replace(">", "", $value);
			$value = trim($value); //echo "value: " . $value . PHP_EOL;
			if(is_numeric($value)){
				VarSave($guild_folder, "suggestion_approved_channel_id.php", $value);
				$message->reply("Suggestion approved channel ID saved!");
			}else $message->reply("Invalid input! Please enter a channel ID or <#mention> a channel");
			return true;
		}	
		//Users
		if (substr($message_content_lower, 0, 17) == 'setup rolepicker '){
			$filter = "setup rolepicker ";
			$value = str_replace($filter, "", $message_content_lower);
			$value = str_replace("<@!", "", $value); $value = str_replace("<@", "", $value); $value = str_replace("<@", "", $value); 
			$value = str_replace(">", "", $value);
			$value = trim($value); //echo "value: " . $value . PHP_EOL;
			if(is_numeric($value)){
				VarSave($guild_folder, "rolepicker_id.php", $value);
				$message->reply("Rolepicker user ID saved!");
			}else $message->reply("Invalid input! Please enter an ID or @mention the user");
			return true;
		}
		//Messages
		if (substr($message_content_lower, 0, 14) == 'setup species '){
			$filter = "setup species ";
			$value = str_replace($filter, "", $message_content_lower);
			$value = trim($value);
			if(is_numeric($value)){
				VarSave($guild_folder, "species_message_id.php", $value);
				$message->reply("Species message ID saved!");
			}else $message->reply("Invalid input! Please enter a message ID");
			return true;
		}
		if (substr($message_content_lower, 0, 15) == 'setup species2 '){
			$filter = "setup species2 ";
			$value = str_replace($filter, "", $message_content_lower);
			$value = trim($value);
			if(is_numeric($value)){
				VarSave($guild_folder, "species2_message_id.php", $value);
				$message->reply("Species2 message ID saved!");
			}else $message->reply("Invalid input! Please enter a message ID");
			return true;
		}
		if (substr($message_content_lower, 0, 15) == 'setup species3 '){
			$filter = "setup species3 ";
			$value = str_replace($filter, "", $message_content_lower);
			$value = trim($value);
			if(is_numeric($value)){
				VarSave($guild_folder, "species3_message_id.php", $value);
				$message->reply("Species3 message ID saved!");
			}else $message->reply("Invalid input! Please enter a message ID");
			return true;
		}
		if (substr($message_content_lower, 0, 13) == 'setup gender '){
			$filter = "setup gender ";
			$value = str_replace($filter, "", $message_content_lower);
			$value = trim($value);
			if(is_numeric($value)){
				VarSave($guild_folder, "gender_message_id.php", $value);
				$message->reply("Gender message ID saved!");
			}else $message->reply("Invalid input! Please enter a message ID");
			return true;
		}
		if (substr($message_content_lower, 0, 16) == 'setup sexuality '){
			$filter = "setup sexuality ";
			$value = str_replace($filter, "", $message_content_lower);
			$value = trim($value);
			if(is_numeric($value)){
				VarSave($guild_folder, "sexuality_message_id.php", $value);
				$message->reply("Sexuality message ID saved!");
			}else $message->reply("Invalid input! Please enter a message ID");
			return true;
		}
		if (substr($message_content_lower, 0, 18) == 'setup customroles '){
			$filter = "setup customroles ";
			$value = str_replace($filter, "", $message_content_lower);
			$value = trim($value);
			if(is_numeric($value)){
				VarSave($guild_folder, "customroles_message_id.php", $value);
				$message->reply("Custom roles message ID saved!");
			}else $message->reply("Invalid input! Please enter a message ID");
			return true;
		}
	}

	/*
	*********************
	*********************
	Server Setup Functions
	*********************
	*********************
	*/	

	if ($message_content_lower == 'help'){ //;help
		$documentation ="\n`;invite` sends a DM with an OAuth2 link to invite Palace Bot to your server\n";
		$documentation = $documentation . "**\nCommand symbol: $command_symbol**\n";
		if($creator || $owner || $dev){ //toggle options
			$documentation = $documentation . "\n__**Owner:**__\n";
			//toggle options
			$documentation = $documentation . "*Bot settings:*\n";
			//react
			$documentation = $documentation . "`react`\n";
			//vanity
			$documentation = $documentation . "`vanity`\n";
			//nsfw
			$documentation = $documentation . "`nsfw`\n";
			//gamnes
			$documentation = $documentation . "`games`\n";
			//rolepicker
			$documentation = $documentation . "`rolepicker`\n";
			//species
			$documentation = $documentation . "`species`\n";
			/*
			//species2
			$documentation = $documentation . "`species2`\n";
			//species3
			$documentation = $documentation . "`species3`\n";
			*/
			//gender
			$documentation = $documentation . "`gender`\n";
			//sexuality
			$documentation = $documentation . "`sexuality`\n";
			//customrole
			$documentation = $documentation . "`customrole`\n";
			
			
			//TODO:
			//tempmute/tm
		}
		if($creator || $owner || $dev || $admin){
			$documentation = $documentation . "\n__**High Staff:**__\n";
			//current settings
			$documentation = $documentation . "`settings` sends a DM with current settings\n";
			
			//v
			if( ($role_verified_id === NULL) || ($role_verified_id == "") || ($role_verified_id == "0") ) $documentation = $documentation . "~~";
			$documentation = $documentation . "`v` or `verify` gives the verified role\n";
			if( ($role_verified_id === NULL) || ($role_verified_id == "") || ($role_verified_id == "0") ) $documentation = $documentation . "~~";
			//cv
			if( ($getverified_channel === NULL) || ($getverified_channel == "") || ($getverified_channel == "0") )  $documentation = $documentation . "~~";
			$documentation = $documentation . "`cv` or `clearv` clears the verification channel and posts a short notice\n";
			if( ($getverified_channel === NULL) || ($getverified_channel == "") || ($getverified_channel == "0") )  $documentation = $documentation . "~~";
			//clearall
			$documentation = $documentation . "`clearall` clears the current channel of up to 100 messages\n";
			//clear #
			$documentation = $documentation . "`clear #` clears the current channel of # messages\n";
			//watch
			$documentation = $documentation . "`watch` sends a direct message to the author whenever the mentioned sends a message\n";
			//unwatch
			$documentation = $documentation . "`unwatch` removes the effects of the watch command\n";
			//vwatch
			if( ($role_verified_id === NULL) || ($role_verified_id == "") || ($role_verified_id == "0") ) $documentation = $documentation . "~~";
			$documentation = $documentation . "`vw` or `vwatch` gives the verified role to the mentioned and watches them\n";
			if( ($role_verified_id === NULL) || ($role_verified_id == "") || ($role_verified_id == "0") ) $documentation = $documentation . "~~";
			//warn
			$documentation = $documentation . "`warn` logs an infraction\n";
			//infractions
			$documentation = $documentation . "`infractions` replies with a list of infractions for someone\n";
			//removeinfraction
			$documentation = $documentation . "`removeinfraction @mention #`\n";
			//kick
			$documentation = $documentation . "`kick @mention reason`\n";
			//ban
			$documentation = $documentation . "`ban @mention reason`\n";
			//Strikeout invalid options			
			if ( ($suggestion_pending_channel === NULL) || ($suggestion_pending_channel == "") || ($suggestion_pending_channel == "0") ) $documentation = $documentation . "~~";
			//suggest approve
			$documentation = $documentation . "`suggest approve #`\n";
			//suggest deny
			$documentation = $documentation . "`suggest deny #`\n";
			//Strikeout invalid options			
			if ( ($suggestion_pending_channel === NULL) || ($suggestion_pending_channel == "") || ($suggestion_pending_channel == "0") ) $documentation = $documentation . "~~";
		}
		if($creator || $owner || $dev || $admin || $mod){
			$documentation = $documentation . "\n__**Moderators:**__\n";
			//Strikeout invalid options
			if ( ($role_muted_id === NULL) || ($role_muted_id == "") || ($role_muted_id == "0") ) $documentation = $documentation . "~~"; //Strikeout invalid options
			//mute/m
			$documentation = $documentation . "`mute @mention reason`\n";
			//unmute
			$documentation = $documentation . "`unmute @mention reason`\n";
			//Strikeout invalid options
			if ( ($role_muted_id === NULL) || ($role_muted_id == "") || ($role_muted_id == "0") ) $documentation = $documentation . "~~"; //Strikeout invalid options
			//whois
			$documentation = $documentation . "`whois` displays known information about a user\n";
			//lookup
			$documentation = $documentation . "`lookup` retrieves a username#discriminator using either a discord id or mention\n";
			
		}
		if($vanity){
			$documentation = $documentation . "\n__**Vanity:**__\n";
			//cooldown
			$documentation = $documentation . "`cooldown` or `cd` tells you how much time you must wait before using another Vanity command \n";
			//hug/snuggle
			$documentation = $documentation . "`hug` or `snuggle`\n";
			//kiss/smooch
			$documentation = $documentation . "`kiss` or `smooch`\n";
			//nuzzle
			$documentation = $documentation . "`nuzzle`\n";
			//boop
			$documentation = $documentation . "`boop`\n";
			//bap
			$documentation = $documentation . "`bap`\n";
			//bap
			$documentation = $documentation . "`pet`\n";
		}
		if($nsfw && $adult){
			//TODO
		}
		if($games){
			$documentation = $documentation . "\n__**Games:**__\n";
			//yahtzee
			$documentation = $documentation . "`yahtzee start` Starts a new game of Yahtzee\n";
			$documentation = $documentation . "`yahtzee end` Ends the game and deletes all progress\n";
			$documentation = $documentation . "`yahtzee pause` Pauses the game and can be resumed later \n";
			$documentation = $documentation . "`yahtzee resume` Resumes the paused game \n";
		}
		//All other functions
		$documentation = $documentation . "\n__**General:**__\n";
		$documentation = $documentation . "`poll # description` creates a timed poll\n";
		//ping
		$documentation = $documentation . "`ping` replies with 'Pong!'\n";
		//roles / roles @
		$documentation = $documentation . "`roles` displays the roles for the author or user being mentioned\n";
		//avatar
		$documentation = $documentation . "`avatar` displays the profile picture of the author or user being mentioned\n";
		//poll
		$documentation = $documentation . "`poll` creates a message for people to vote on\n";
		//remindme
		$documentation = $documentation . "`remindme #` send a DM after # of seconds have passed\n";
		//suggest
		if ( ($suggestion_pending_channel === NULL) || ($suggestion_pending_channel == "") || ($suggestion_pending_channel == "0") ) $documentation = $documentation . "~~";
		$documentation = $documentation . "`suggest` posts a suggestion for staff to vote on\n";
		if ( ($suggestion_pending_channel === NULL) || ($suggestion_pending_channel == "") || ($suggestion_pending_channel == "0") ) $documentation = $documentation . "~~";

		$documentation_sanitized = str_replace("\n","",$documentation_sanitized);
		$doc_length = strlen($documentation_sanitized);
		if ($doc_length < 1024){

			$embed = new \CharlotteDunois\Yasmin\Models\MessageEmbed();
			$embed
				->setTitle("Commands for $author_guild_name")											// Set a title
				->setColor("e1452d")																	// Set a color (the thing on the left side)
				->setDescription("$documentation")														// Set a description (below title, above fields)
	//					->addField("⠀", "$documentation")														// New line after this			
	//					->setThumbnail("$author_avatar")														// Set a thumbnail (the image in the top right corner)
	//					->setImage('https://avatars1.githubusercontent.com/u/4529744?s=460&v=4')             	// Set an image (below everything except footer)
	//					->setTimestamp()                                                                     	// Set a timestamp (gets shown next to footer)
	//					->setAuthor("$author_check", "$author_guild_avatar")  									// Set an author with icon
				->setFooter("Palace Bot by Valithor#5947")                             					// Set a footer without icon
				->setURL("");                             												// Set the URL
	//				Open a DM channel then send the rich embed message
			$author_user->createDM()->then(function($author_dmchannel) use ($message, $embed){	//Promise
				echo "[;HELP EMBED]" . PHP_EOL;
				return $author_dmchannel->send('', array('embed' => $embed))->done(null, function ($error){
					echo "[ERROR] $error" . PHP_EOL; //Echo any errors
				});
			});
			return true;
		}else{
			$author_user->createDM()->then(function($author_dmchannel) use ($message, $documentation){	//Promise
				echo "[;HELP MESSAGE]" . PHP_EOL;
				$author_dmchannel->send($documentation);
			});
			return true;
		}
	}

	if($creator || $owner || $dev || $admin)
	if ($message_content_lower == 'settings'){ //;settings
		$documentation = "Command symbol: $command_symbol\n";
		$documentation = $documentation . "\nBot options:\n";
		//react
		$documentation = $documentation . "`react:` ";
		if ($react) $documentation = $documentation . "**Enabled**\n";
		else $documentation = $documentation . "**Disabled**\n";
		//vanity
		$documentation = $documentation . "`vanity:` ";
		if ($vanity) $documentation = $documentation . "**Enabled**\n";
		else $documentation = $documentation . "**Disabled**\n";
		//nsfw
		$documentation = $documentation . "`nsfw:` ";
		if ($nsfw) $documentation = $documentation . "**Enabled**\n";
		else $documentation = $documentation . "**Disabled**\n";
		//games
		$documentation = $documentation . "`games:` ";
		if ($games) $documentation = $documentation . "**Enabled**\n";
		else $documentation = $documentation . "**Disabled**\n";
		//rolepicker
		$documentation = $documentation . "`\nrolepicker:` ";
		if ($rp0) $documentation = $documentation . "**Enabled**\n";
		else $documentation = $documentation . "**Disabled**\n";
		
		//Strikeout invalid options
		if (!$rp0) $documentation = $documentation . "~~"; //Strikeout invalid options
		
		//species
		$documentation = $documentation . "`species:` ";
		if ($rp1) $documentation = $documentation . "**Enabled**\n";
		else $documentation = $documentation . "**Disabled**\n";
		//gender
		$documentation = $documentation . "`gender:` ";
		if ($rp2) $documentation = $documentation . "**Enabled**\n";
		else $documentation = $documentation . "**Disabled**\n";
		//sexuality
		$documentation = $documentation . "`sexuality:` ";
		if ($rp3) $documentation = $documentation . "**Enabled**\n";
		else $documentation = $documentation . "**Disabled**\n";
		//customrole
		$documentation = $documentation . "`customrole:` ";
		if ($rp4) $documentation = $documentation . "**Enabled**\n";
		else $documentation = $documentation . "**Disabled**\n";
		
		//Strikeout invalid options
		if (!$rp0) $documentation = $documentation . "~~"; //Strikeout invalid options
		
		$doc_length = strlen($documentation);
		if ($doc_length < 1024){
			$embed = new \CharlotteDunois\Yasmin\Models\MessageEmbed();
			$embed
				->setTitle("Settings for $author_guild_name")											// Set a title
				->setColor("e1452d")																	// Set a color (the thing on the left side)
				->setDescription("$documentation")														// Set a description (below title, above fields)
	//					->addField("⠀", "$documentation")														// New line after this
				
	//					->setThumbnail("$author_avatar")														// Set a thumbnail (the image in the top right corner)
	//					->setImage('https://avatars1.githubusercontent.com/u/4529744?s=460&v=4')             	// Set an image (below everything except footer)
	//					->setTimestamp()                                                                     	// Set a timestamp (gets shown next to footer)
	//					->setAuthor("$author_check", "$author_guild_avatar")  									// Set an author with icon
				->setFooter("Palace Bot by Valithor#5947")                             					// Set a footer without icon
				->setURL("");                             												// Set the URL
	//				Open a DM channel then send the rich embed message
			$author_user->createDM()->then(function($author_dmchannel) use ($message, $embed){	//Promise
				echo "[;SETTINGS EMBED]" . PHP_EOL;
				return $author_dmchannel->send('', array('embed' => $embed))->done(null, function ($error){
					echo "[ERROR] $error".PHP_EOL; //Echo any errors
				});
			});
			return true;
		}else{
			$author_user->createDM()->then(function($author_dmchannel) use ($message, $documentation){	//Promise
			echo "[;SETTINGS MESSAGE]" . PHP_EOL;
				$author_dmchannel->send($documentation);
			});
			return true;
		}
	}



	/*
	*********************
	*********************
	Creator/Owner option functions
	*********************
	*********************
	*/


	/*
	*********************
	*********************
	Gerneral command functions
	*********************
	*********************
	*/

	if ($nsfw){ //This currently doesn't serve a purpose
		if ($message_content_lower == '18+'){
			if ($adult){
				if($react) $message->react("👍");
				$message->reply("You have the 18+ role!");
			}else{
				if($react) $message->react("👎");
				$message->reply("You do NOT have the 18+ role!");
			}
			return true;
		}
	}
	if ($games){
		if ($author_channel_id == $games_channel_id){
			//yahtzee
			include "yahtzee.php";
			//machi koro
			//include_once (__DIR__ . "/machikoro/classes.php");
			//include (__DIR__ . "/machikoro/game.php");
		}
	}
	if ($message_content_lower == 'ping'){
		echo 'PING' . PHP_EOL;
		$message->reply("Pong!");
		return true;
	}
	/*
	if (substr($message_content_lower, 0, 10) == 'remindme '){ //;remindme
		echo "[REMINDER]" . PHP_EOL;
		$filter = "remindme ";
		$value = str_replace($filter, "", $message_content_lower);
		if(is_numeric($value)){
			$discord->addTimer($value, function() use ($author_user) {
				$author_user->createDM()->then(function($author_dmchannel) use ($message){	//Promise
					if($author_dmchannel) $author_dmchannel->send("This is your requested reminder!");
					return true;
				});
			});
			if($react) $message->react("👍");
		}else return $message->reply("Invalid input! Please use the format `;remindme #` where # is seconds.");
	}
	*/
	if ($message_content_lower == 'roles'){ //;roles
		echo "[GET AUTHOR ROLES]" . PHP_EOL;
	//	Build the string for the reply
		$author_role_name_queue 									= "";
	//	$author_role_name_queue_full 								= "Here's a list of roles for you:" . PHP_EOL;
		foreach ($author_member_roles_ids as $author_role){
			$author_role_name_queue 								= "$author_role_name_queue<@&$author_role> ";
		}
		$author_role_name_queue 									= substr($author_role_name_queue, 0, -1);
		$author_role_name_queue_full 								= PHP_EOL . $author_role_name_queue;
	//	Send the message
		if($react) $message->react("👍");
	//	$message->reply($author_role_name_queue_full . PHP_EOL);
	//	Build the embed
		$embed = new \CharlotteDunois\Yasmin\Models\MessageEmbed();
		$embed
	//		->setTitle("Roles")																		// Set a title
			->setColor("e1452d")																	// Set a color (the thing on the left side)
			->setDescription("$author_guild_name")												// Set a description (below title, above fields)
			->addField("Roles", 		"$author_role_name_queue_full")								// New line after this if ,true
			
			->setThumbnail("$author_avatar")														// Set a thumbnail (the image in the top right corner)
	//		->setImage('https://avatars1.githubusercontent.com/u/4529744?s=460&v=4')             	// Set an image (below everything except footer)
			->setTimestamp()                                                                     	// Set a timestamp (gets shown next to footer)
			->setAuthor("$author_check", "$author_guild_avatar")  									// Set an author with icon
			->setFooter("Palace Bot by Valithor#5947")                             					// Set a footer without icon
			->setURL("");                             												// Set the URL
	//	Send the message
	//	We do not need another promise here, so we call done, because we want to consume the promise
		$author_channel->send('', array('embed' => $embed))->done(null, function ($error){
			echo "[ERROR] $error".PHP_EOL; //Echo any errors
		});
		return true;
	}
	if (substr($message_content_lower, 0, 6) == 'roles '){//;roles @
		echo "[GET MENTIONED ROLES]" . PHP_EOL;
	//	Get an array of people mentioned
		$mentions_arr 												= $message->mentions->users; 									//echo "mentions_arr: " . PHP_EOL; var_dump ($mentions_arr); //Shows the collection object
		if (!strpos($message_content_lower, "<")){ //String doesn't contain a mention
			$filter = "roles ";
			$value = str_replace($filter, "", $message_content_lower);
			$value = str_replace("<@!", "", $value); $value = str_replace("<@", "", $value);
			$value = str_replace(">", "", $value); //echo "value: " . $value . PHP_EOL;
			if(is_numeric($value)){
				$mention_member				= $author_guild->members->get($value);
				$mention_user				= $mention_member->user;
				$mentions_arr				= array($mention_user);
			}else return $message->reply("Invalid input! Please enter a valid ID or @mention the user");
			if ($mention_member == NULL) return $message->reply("Invalid input! Please enter an ID or @mention the user");
		}
		//$mention_role_name_queue_full								= "Here's a list of roles for the requested users:" . PHP_EOL;
		$mention_role_name_queue_default							= "";
	//	$mentions_arr_check = (array)$mentions_arr;																					//echo "mentions_arr_check: " . PHP_EOL; var_dump ($mentions_arr_check); //Shows the collection object
	//	$mentions_arr_check2 = empty((array) $mentions_arr_check);																	//echo "mentions_arr_check2: " . PHP_EOL; var_dump ($mentions_arr_check2); //Shows the collection object			
		foreach ( $mentions_arr as $mention_param ){																				//echo "mention_param: " . PHP_EOL; var_dump ($mention_param);
	//		id, username, discriminator, bot, avatar, email, mfaEnabled, verified, webhook, createdTimestamp
			$mention_param_encode 									= json_encode($mention_param); 									//echo "mention_param_encode: " . $mention_param_encode . PHP_EOL;
			$mention_json 											= json_decode($mention_param_encode, true); 					//echo "mention_json: " . PHP_EOL; var_dump($mention_json);
			$mention_id 											= $mention_json['id']; 											//echo "mention_id: " . $mention_id . PHP_EOL; //Just the discord ID
			$mention_username 										= $mention_json['username']; 									//echo "mention_username: " . $mention_username . PHP_EOL; //Just the discord ID
			
			$mention_discriminator 									= $mention_json['discriminator']; 								//echo "mention_discriminator: " . $mention_discriminator . PHP_EOL; //Just the discord ID
			$mention_check 											= $mention_username ."#".$mention_discriminator; 				//echo "mention_check: " . $mention_check . PHP_EOL; //Just the discord ID
			
	//				Get the roles of the mentioned user
			$target_guildmember 									= $message->guild->members->get($mention_id); 	//This is a GuildMember object
			$target_guildmember_role_collection 					= $target_guildmember->roles;					//This is the Role object for the GuildMember
			
	//				Get the avatar URL of the mentioned user
			$target_guildmember_user								= $target_guildmember->user;									//echo "member_class: " . get_class($target_guildmember_user) . PHP_EOL;
			$mention_avatar 										= "{$target_guildmember_user->getAvatarURL()}";					//echo "mention_avatar: " . $mention_avatar . PHP_EOL;				//echo "target_guildmember_role_collection: " . (count($target_guildmember_role_collection)-1);
			
	//				Populate arrays of the info we need
	//				$target_guildmember_roles_names 						= array();
			$target_guildmember_roles_ids 							= array(); //Not being used here, but might as well grab it
			$x=0;
			foreach ($target_guildmember_role_collection as $role){
				if ($x!=0){ //0 is @everyone so skip it
	//						$target_guildmember_roles_names[] 				= $role->name; 													//echo "role[$x] name: " . PHP_EOL; //var_dump($role->name);
					$target_guildmember_roles_ids[] 				= $role->id; 													//echo "role[$x] id: " . PHP_EOL; //var_dump($role->id);
				}
				$x++;
			}
			
	//				Build the string for the reply
	//				$mention_role_name_queue 								= "**$mention_id:** ";
			//$mention_role_id_queue 								= "**<@$mention_id>:**\n";
			foreach ($target_guildmember_roles_ids as $mention_role){
	//					$mention_role_name_queue 							= "$mention_role_name_queue$mention_role, ";
				$mention_role_id_queue 								= "$mention_role_id_queue<@&$mention_role> ";
			}
	//				$mention_role_name_queue 								= substr($mention_role_name_queue, 0, -2); 		//Get rid of the extra ", " at the end
			$mention_role_id_queue 									= substr($mention_role_id_queue, 0, -1); 		//Get rid of the extra ", " at the end 
	//				$mention_role_name_queue_full 							= $mention_role_name_queue_full . PHP_EOL . $mention_role_name_queue;
			$mention_role_id_queue_full 							= PHP_EOL . $mention_role_id_queue;
		
	//				Check if anyone had their roles changed
	//				if ($mention_role_name_queue_default != $mention_role_name_queue){
			if ($mention_role_name_queue_default != $mention_role_id_queue){
	//					Send the message
				if($react) $message->react("👍");
				//$message->reply($mention_role_name_queue_full . PHP_EOL);
	//					Build the embed
				$embed = new \CharlotteDunois\Yasmin\Models\MessageEmbed();
				$embed
	//						->setTitle("Roles")																		// Set a title
					->setColor("e1452d")																	// Set a color (the thing on the left side)
					->setDescription("$author_guild_name")												// Set a description (below title, above fields)
	//						->addField("Roles", 	"$mention_role_name_queue_full")								// New line after this
					->addField("Roles", 	"$mention_role_id_queue_full", true)							// New line after this
					
					->setThumbnail("$mention_avatar")														// Set a thumbnail (the image in the top right corner)
	//						->setImage('https://avatars1.githubusercontent.com/u/4529744?s=460&v=4')             	// Set an image (below everything except footer)
					->setTimestamp()                                                                     	// Set a timestamp (gets shown next to footer)
					->setAuthor("$mention_check", "$author_guild_avatar")  									// Set an author with icon
					->setFooter("Palace Bot by Valithor#5947")                             					// Set a footer without icon
					->setURL("");                             												// Set the URL
	//					Send the message
	//					We do not need another promise here, so we call done, because we want to consume the promise
				$author_channel->send('', array('embed' => $embed))->done(null, function ($error){
					echo "[ERROR] $error".PHP_EOL; //Echo any errors
				});
				return true; //No more processing
			}else{
				if($react) $message->react("👎");
				$message->reply("Nobody in the guild was mentioned!");
				return true;  //No more processing
			}
		}
		//Foreach method didn't return, so nobody was mentioned
		$author_channel->send("<@$author_id>, you need to mention someone!");
		return true;
	}

	//ymdhis cooldown time
	$avatar_limit['year']	= 0;
	$avatar_limit['month']	= 0;
	$avatar_limit['day']	= 0;
	$avatar_limit['hour']	= 0;
	$avatar_limit['min']	= 10;
	$avatar_limit['sec']	= 0;
	$avatar_limit_seconds = TimeArrayToSeconds($avatar_limit);																		//echo "TimeArrayToSeconds: " . $avatar_limit_seconds . PHP_EOL;
	if ($message_content_lower == 'avatar'){ //;avatar
		echo "[GET AUTHOR AVATAR]" . PHP_EOL;
		//$cooldown = CheckCooldown($author_folder, "avatar_time.php", $avatar_limit); //	Check Cooldown Timer
		$cooldown = CheckCooldownMem($author_id, "avatar", $avatar_limit);
		if ( ($cooldown[0] == true) || ($bypass) ){
	//		Build the embed
			$embed = new \CharlotteDunois\Yasmin\Models\MessageEmbed();
			$embed
	//			->setTitle("Avatar")																	// Set a title
				->setColor("e1452d")																	// Set a color (the thing on the left side)
	//			->setDescription("$author_guild_name")													// Set a description (below title, above fields)
	//			->addField("Total Given", 		"$vanity_give_count")									// New line after this
				
	//			->setThumbnail("$author_avatar")														// Set a thumbnail (the image in the top right corner)
				->setImage("$author_avatar")             													// Set an image (below everything except footer)
				->setTimestamp()                                                                     	// Set a timestamp (gets shown next to footer)
				->setAuthor("$author_check", "$author_guild_avatar")  									// Set an author with icon
				->setFooter("Palace Bot by Valithor#5947")                             					// Set a footer without icon
				->setURL("");                             												// Set the URL
			
	//		Send the message
	//		We do not need another promise here, so we call done, because we want to consume the promise
			$author_channel->send('', array('embed' => $embed))->done(null, function ($error){
				echo "[ERROR] $error".PHP_EOL; //Echo any errors
			});
			//SetCooldown($author_folder, "avatar_time.php");
			SetCooldownMem($author_id, "avatar");
			return true;
		}else{
	//		Reply with remaining time
			$waittime = $avatar_limit_seconds - $cooldown[1];
			$formattime = FormatTime($waittime);
			$message->reply("You must wait $formattime before using this command again.");
			return true;
		}
	}
	if (substr($message_content_lower, 0, 7) == 'avatar '){//;avatar @
		echo "GETTING AVATAR FOR MENTIONED" . PHP_EOL;
		//$cooldown = CheckCooldown($author_folder, "avatar_time.php", $avatar_limit); //Check Cooldown Timer
		$cooldown = CheckCooldownMem($author_id, "avatar", $avatar_limit);
		if ( ($cooldown[0] == true) || ($bypass) ){
			$mentions_arr = $message->mentions->users; 									//echo "mentions_arr: " . PHP_EOL; var_dump ($mentions_arr); //Shows the collection object
			if (!strpos($message_content_lower, "<")){ //String doesn't contain a mention
			$filter = "avatar ";
			$value = str_replace($filter, "", $message_content_lower);
			$value = str_replace("<@!", "", $value); $value = str_replace("<@", "", $value);
			$value = str_replace(">", "", $value);//echo "value: " . $value . PHP_EOL;
			if(is_numeric($value)){
				$mention_member				= $author_guild->members->get($value);
				$mention_user				= $mention_member->user;
				$mentions_arr				= array($mention_user);
			}else return $message->reply("Invalid input! Please enter a valid ID or @mention the user");
			if ($mention_member == NULL) return $message->reply("Invalid input! Please enter an ID or @mention the user");
		}
			foreach ( $mentions_arr as $mention_param ){																				//echo "mention_param: " . PHP_EOL; var_dump ($mention_param);
	//			id, username, discriminator, bot, avatar, email, mfaEnabled, verified, webhook, createdTimestamp
				$mention_param_encode 								= json_encode($mention_param); 									//echo "mention_param_encode: " . $mention_param_encode . PHP_EOL;
				$mention_json 										= json_decode($mention_param_encode, true); 					//echo "mention_json: " . PHP_EOL; var_dump($mention_json);
				$mention_id 										= $mention_json['id']; 											//echo "mention_id: " . $mention_id . PHP_EOL; //Just the discord ID
				$mention_username 									= $mention_json['username']; 									//echo "mention_username: " . $mention_username . PHP_EOL; //Just the discord ID
				
				$mention_discriminator 								= $mention_json['discriminator']; 								//echo "mention_discriminator: " . $mention_discriminator . PHP_EOL; //Just the discord ID
				$mention_check 										= $mention_username ."#".$mention_discriminator; 				//echo "mention_check: " . $mention_check . PHP_EOL; //Just the discord ID

	//			Get the avatar URL of the mentioned user
				$target_guildmember 								= $message->guild->members->get($mention_id); 	//This is a GuildMember object
				$target_guildmember_user							= $target_guildmember->user;									//echo "member_class: " . get_class($target_guildmember_user) . PHP_EOL;
				$mention_avatar 									= "{$target_guildmember_user->getAvatarURL()}";
				
	//			Build the embed
				$embed = new \CharlotteDunois\Yasmin\Models\MessageEmbed();
				$embed
	//			->setTitle("Avatar")																	// Set a title
				->setColor("e1452d")																	// Set a color (the thing on the left side)
	//			->setDescription("$author_guild_name")													// Set a description (below title, above fields)
	//			->addField("Total Given", 		"$vanity_give_count")									// New line after this
					
	//			->setThumbnail("$author_avatar")														// Set a thumbnail (the image in the top right corner)
				->setImage("$mention_avatar")             												// Set an image (below everything except footer)
				->setTimestamp()                                                                     	// Set a timestamp (gets shown next to footer)
				->setAuthor("$mention_check", "$author_guild_avatar")  									// Set an author with icon
				->setFooter("Palace Bot by Valithor#5947")                             					// Set a footer without icon
				->setURL("");                             												// Set the URL
				
	//			Send the message
	//			We do not need another promise here, so we call done, because we want to consume the promise
				$author_channel->send('', array('embed' => $embed))->done(null, function ($error){
					echo "[ERROR] $error".PHP_EOL; //Echo any errors
				});
	//			Set Cooldown
				//SetCooldown($author_folder, "avatar_time.php");
				SetCooldownMem($author_id, "avatar");
				return true;					
			}
			//Foreach method didn't return, so nobody was mentioned
			$author_channel->send("<@$author_id>, you need to mention someone!");
			return true;
		}else{
	//		Reply with remaining time
			$waittime = $avatar_limit_seconds - $cooldown[1];
			$formattime = FormatTime($waittime);
			$message->reply("You must wait $formattime before using this command again.");
			return true;
		}
	}

	//if ($suggestion_approved_channel_id)
	if ($creator || $owner || $dev || $admin || $mod){
		if ( (substr($message_content_lower, 0, 19) == 'suggestion approve ') || (substr($message_content_lower, 0, 17) == 'suggest approve ') ) { //;suggestion
			$filter = "suggestion approve ";
			$value = str_replace($filter, "", $message_content_lower);
			$filter = "suggest approve ";
			$value = str_replace($filter, "", $value);
			if( ($value == "") || ($value == NULL) ) return $message->reply("Invalid input! Please enter an integer number");
			if(is_numeric($value)){
				//Get the message stored at the index
				$array = VarLoad($guild_folder, "guild_suggestions.php");
				if( ($array[$value]) && ($array[$value] != "Approved" ) && ($array[$value] != "Denied" ) ){
					$embed = $array[$value];
					$suggestion_approved_channel->send("{$embed->title}", array('embed' => $embed))->then(function($message) use ($guild_folder, $embed){
					//Repost the suggestion
						$message->react("👍");
						$message->react("👎");
					});
					//Clear the value stored in the array
					$array[$value] = "Approved";
					if($react) $message->react("👍");
					//Send a DM to the person who made the suggestion to let them know that it has been approved.
					return true;
				}else return $message->reply("Suggestion not found or already processed!");
			}else return $message->reply("Invalid input! Please enter an integer number");
			return true; //catch
		}
		if ( (substr($message_content_lower, 0, 16) == 'suggestion deny ') || (substr($message_content_lower, 0, 13) == 'suggest deny ') ) { //;suggestion
			$filter = "suggestion deny ";
			$value = str_replace($filter, "", $message_content_lower);
			$filter = "suggest deny ";
			$value = str_replace($filter, "", $value);
			if( ($value == "") || ($value == NULL) ) return $message->reply("Invalid input! Please enter an integer number");
			if(is_numeric($value)){
				//Get the message stored at the index
				$array = VarLoad($guild_folder, "guild_suggestions.php");
				if( ($array[$value]) && ($array[$value] != "Approved" ) && ($array[$value] != "Denied" ) ){
					$embed = $array[$value];
					//Clear the value stored in the array
					$array[$value] = "Denied";
					if($react) $message->react("👍");
					return true;
				}else return $message->reply("Suggestion not found or already processed!");
			}else return $message->reply("Invalid input! Please enter an integer number");
			return true; //catch
		}
	}

	if ($suggestion_pending_channel)
	if ( (substr($message_content_lower, 0, 11) == 'suggestion ') || (substr($message_content_lower, 0, 8) == 'suggest ') ){ //;suggestion
		$filter = "suggestion ";
		$value = str_replace($filter, "", $message_content_lower);
		$filter = "suggest ";
		$value = str_replace($filter, "", $value);
		if ( ($value == "") || ($value == NULL) ) return $message->reply("Invalid input! Please enter text for your suggestion");
		//Build the embed message
		$message_sanitized = str_replace("*","",$value);
		$message_sanitized = str_replace("@","",$message_sanitized);
		$message_sanitized = str_replace("_","",$message_sanitized);
		$message_sanitized = str_replace("`","",$message_sanitized);
		$message_sanitized = str_replace("\n","",$message_sanitized);
		$doc_length = strlen($message_sanitized);
		if ($doc_length <= 2048){

			//Find the size of $suggestions and get what will be the next number
			$array = VarLoad($guild_folder, "guild_suggestions.php");
			$array_count = sizeof($array);
			//Build the embed
			$embed = new \CharlotteDunois\Yasmin\Models\MessageEmbed();
			$embed
				->setTitle("#$array_count")																// Set a title
				->setColor("e1452d")																	// Set a color (the thing on the left side)
				->setDescription("$message_sanitized")													// Set a description (below title, above fields)
	//			->addField("⠀", "$reason")																// New line after this
				
	//			->setThumbnail("$author_avatar")														// Set a thumbnail (the image in the top right corner)
	//			->setImage('https://avatars1.githubusercontent.com/u/4529744?s=460&v=4')             	// Set an image (below everything except footer)
				->setTimestamp()                                                                     	// Set a timestamp (gets shown next to footer)
				->setAuthor("$author_check ($author_id)", "$author_avatar")  							// Set an author with icon
				->setFooter("Palace Bot by Valithor#5947")                             					// Set a footer without icon
				->setURL("");                             												// Set the URL
	//				Post embedded suggestion to suggestion_pending_channel
			$suggestion_pending_channel->send("#$array_count", array('embed' => $embed))->then(function($new_message) use ($guild_folder, $embed){
				$new_message->react("👍");
				$new_message->react("👎");
				//Save the suggestion somewhere
				$array = VarLoad($guild_folder, "guild_suggestions.php");
				$array[] = $embed;
				VarSave($guild_folder, "guild_suggestions.php", $array);
			});
		}else{
			$message->reply("Please shorten your suggestion!");
		}
		$message->reply("Your suggestion has been logged and is pending approval!")->then(function($new_message) use ($discord, $message){
			$message->delete(); //Delete the original ;suggestion message
			$discord->addTimer(10, function() use ($new_message) {
				$new_message->delete(); //Delete message confirming the suggestion was logged
				return true;
			});
			return true;
		});
		return true;		
	}

	/*
	*********************
	*********************
	Mod/Admin command functions
	*********************
	*********************
	*/


	/*
	*********************
	*********************
	Vanity command functions
	*********************
	*********************
	*/
	if ($vanity){
		//ymdhis cooldown time
		$vanity_limit['year'] = 0;
		$vanity_limit['month'] = 0;
		$vanity_limit['day'] = 0;
		$vanity_limit['hour'] = 0;
		$vanity_limit['min'] = 10;
		$vanity_limit['sec'] = 0;
		$vanity_limit_seconds = TimeArrayToSeconds($vanity_limit);
	//	Load author give statistics
		if(!CheckFile($author_folder, "vanity_give_count.php"))	$vanity_give_count	= 0;													
		else 													$vanity_give_count	= VarLoad($author_folder, "vanity_give_count.php");		
		if(!CheckFile($author_folder, "hugger_count.php"))		$hugger_count		= 0;													
		else 													$hugger_count 		= VarLoad($author_folder, "hugger_count.php");				
		if(!CheckFile($author_folder, "kisser_count.php"))		$kisser_count		= 0;													
		else 													$kisser_count 		= VarLoad($author_folder, "kisser_count.php");				
		if(!CheckFile($author_folder, "nuzzler_count.php"))		$nuzzler_count		= 0;													
		else 													$nuzzler_count		= VarLoad($author_folder, "nuzzler_count.php");			
		if(!CheckFile($author_folder, "booper_count.php"))		$booper_count		= 0;													
		else 													$booper_count		= VarLoad($author_folder, "booper_count.php");			
		if(!CheckFile($author_folder, "baper_count.php"))		$baper_count		= 0;													
		else 													$baper_count		= VarLoad($author_folder, "baper_count.php");			
		if(!CheckFile($author_folder, "peter_count.php"))		$peter_count		= 0;													
		else 													$peter_count		= VarLoad($author_folder, "peter_count.php");			

	//	Load author get statistics
		if(!CheckFile($author_folder, "vanity_get_count.php"))	$vanity_get_count	= 0;													
		else 													$vanity_get_count 	= VarLoad($author_folder, "vanity_get_count.php");		
		if(!CheckFile($author_folder, "hugged_count.php"))		$hugged_count		= 0;													
		else 													$hugged_count 		= VarLoad($author_folder, "hugged_count.php");				
		if(!CheckFile($author_folder, "kissed_count.php"))		$kissed_count		= 0;													
		else 													$kissed_count 		= VarLoad($author_folder, "kissed_count.php");				
		if(!CheckFile($author_folder, "nuzzled_count.php"))		$nuzzled_count		= 0;													
		else 													$nuzzled_count		= VarLoad($author_folder, "nuzzled_count.php");				
		if(!CheckFile($author_folder, "booped_count.php"))		$booped_count		= 0;													
		else 													$booped_count		= VarLoad($author_folder, "booped_count.php");
		if(!CheckFile($author_folder, "baped_count.php"))		$baped_count		= 0;													
		else 													$baped_count		= VarLoad($author_folder, "baped_count.php");
		if(!CheckFile($author_folder, "peted_count.php"))		$peted_count		= 0;													
		else 													$peted_count		= VarLoad($author_folder, "peted_count.php");				
		
		if ( ($message_content_lower == 'cooldown') || ($message_content_lower == 'cd') ){//;cooldown ;cd
			echo "[COOLDOWN CHECK]" . PHP_EOL;
	//		Check Cooldown Timer
			//$cooldown = CheckCooldown($author_folder, "vanity_time.php", $vanity_limit);
			$cooldown = CheckCooldownMem($author_id, "vanity", $vanity_limit);
			if ( ($cooldown[0] == true) || ($bypass) ){
				return $message->reply("No cooldown.");
			}else{
	//			Reply with remaining time
				$waittime = $avatar_limit_seconds - $cooldown[1];
				$formattime = FormatTime($waittime);
				return $message->reply("You must wait $formattime before using this command again.");
			}
		}
		if ( (substr($message_content_lower, 0, 4) == 'hug ') || (substr($message_content_lower, 0, 8) == 'snuggle ') ){ //;hug ;snuggle
			echo "[HUG/SNUGGLE]" . PHP_EOL;
	//		Check Cooldown Timer
			//$cooldown = CheckCooldown($author_folder, "vanity_time.php", $vanity_limit);
			$cooldown = CheckCooldownMem($author_id, "vanity", $vanity_limit);
			if ( ($cooldown[0] == true) || ($bypass) ){
	//			Get an array of people mentioned
				$mentions_arr 										= $message->mentions->users; 									//echo "mentions_arr: " . PHP_EOL; var_dump ($mentions_arr); //Shows the collection object
				foreach ( $mentions_arr as $mention_param ){
					$mention_param_encode 							= json_encode($mention_param); 									//echo "mention_param_encode: " . $mention_param_encode . PHP_EOL;
					$mention_json 									= json_decode($mention_param_encode, true); 					//echo "mention_json: " . PHP_EOL; var_dump($mention_json);
					$mention_id 									= $mention_json['id']; 											//echo "mention_id: " . $mention_id . PHP_EOL; //Just the discord ID
					
					if ($author_id != $mention_id){
						$hug_messages								= array();
						$hug_messages[]								= "<@$author_id> has given <@$mention_id> a hug! How sweet!";
						$hug_messages[]								= "<@$author_id> saw that <@$mention_id> needed attention, so <@$author_id> gave them a hug!";
						$hug_messages[]								= "<@$author_id> gave <@$mention_id> a hug! Isn't this adorable?";
						$index_selection							= GetRandomArrayIndex($hug_messages);

						//Send the message
						$author_channel->send($hug_messages[$index_selection]);
						//Increment give stat counter of author
						$vanity_give_count++;
						VarSave($author_folder, "vanity_give_count.php", $vanity_give_count);
						$hugger_count++;
						VarSave($author_folder, "hugger_count.php", $hugger_count);
						//Load target get statistics
						if(!CheckFile($guild_folder."/".$mention_id, "vanity_get_count.php"))	$vanity_get_count	= 0;
						else 																	$vanity_get_count 	= VarLoad($guild_folder."/".$mention_id, "vanity_get_count.php");
						if(!CheckFile($guild_folder."/".$mention_id, "hugged_count.php"))		$hugged_count		= 0;
						else 																	$hugged_count 		= VarLoad($guild_folder."/".$mention_id, "hugged_count.php");
						//Increment get stat counter of target
						$vanity_get_count++;
						VarSave($guild_folder."/".$mention_id, "vanity_get_count.php", $vanity_get_count);
						$hugged_count++;
						VarSave($guild_folder."/".$mention_id, "hugged_count.php", $hugged_count);
	//					Set Cooldown
						//SetCooldown($author_folder, "vanity_time.php");
						SetCooldownMem($author_id, "vanity");
						return true; //No more processing, we only want to process the first person mentioned
					}else{
						$self_hug_messages							= array();
						$self_hug_messages[]						= "<@$author_id> hugs themself. What a wierdo!";
						$index_selection							= GetRandomArrayIndex($self_hug_messages);
						//Send the message
						$author_channel->send($self_hug_messages[$index_selection]);
						//Increment give stat counter of author
						$vanity_give_count++;
						VarSave($author_folder, "vanity_give_count.php", $vanity_give_count);
						$hugger_count++;
						VarSave($author_folder, "hugger_count.php", $hugger_count);
						//Increment get stat counter of author
						$vanity_get_count++;
						VarSave($author_folder, "vanity_get_count.php", $vanity_get_count);
						$hugged_count++;
						VarSave($author_folder, "hugged_count.php", $hugged_count);
						//Set Cooldown
						//SetCooldown($author_folder, "vanity_time.php");
						SetCooldownMem($author_id, "vanity");
						return true; //No more processing, we only want to process the first person mentioned
					}
				}
				//foreach method didn't return, so nobody was mentioned
				$author_channel->send("<@$author_id>, you need to mention someone!");
				return true;
			}else{
	//		Reply with remaining time
			$waittime = $vanity_limit_seconds - $cooldown[1];
			$formattime = FormatTime($waittime);
			$message->reply("You must wait $formattime before using vanity commands again.");
			return true;
			}
		}
		if ( (substr($message_content_lower, 0, 5) == 'kiss ') || (substr($message_content_lower, 0, 7)) == 'smooch '){ //;kiss ;smooch
			echo "[KISS]" . PHP_EOL;
	//		Check Cooldown Timer
			//$cooldown = CheckCooldown($author_folder, "vanity_time.php", $vanity_limit);
			$cooldown = CheckCooldownMem($author_id, "vanity", $vanity_limit);
			if ( ($cooldown[0] == true) || ($bypass) ){
	//			Get an array of people mentioned
				$mentions_arr 										= $message->mentions->users; 									//echo "mentions_arr: " . PHP_EOL; var_dump ($mentions_arr); //Shows the collection object
				foreach ( $mentions_arr as $mention_param ){
					$mention_param_encode 							= json_encode($mention_param); 									//echo "mention_param_encode: " . $mention_param_encode . PHP_EOL;
					$mention_json 									= json_decode($mention_param_encode, true); 					//echo "mention_json: " . PHP_EOL; var_dump($mention_json);
					$mention_id 									= $mention_json['id']; 											//echo "mention_id: " . $mention_id . PHP_EOL; //Just the discord ID
					
					if ($author_id != $mention_id){
						$kiss_messages								= array();
						$kiss_messages[]							= "<@$author_id> put their nose to <@$mention_id>’s for a good old smooch! Now that’s cute!";
						$kiss_messages[]							= "<@$mention_id> was surprised when <@$author_id> leaned in and gave them a kiss! Hehe!";
						$kiss_messages[]							= "<@$author_id> has given <@$mention_id> the sweetest kiss on the cheek! Yay!";
						$kiss_messages[]							= "<@$author_id> gives <@$mention_id> a kiss on the snoot.";
						$kiss_messages[]							= "<@$author_id> rubs their snoot on <@$mention_id>, how sweet!";
						$index_selection							= GetRandomArrayIndex($kiss_messages);						//echo "random kiss_message: " . $kiss_messages[$index_selection];
	//					Send the message
						$author_channel->send($kiss_messages[$index_selection]);
						//Increment give stat counter of author
						$vanity_give_count++;
						VarSave($author_folder, "vanity_give_count.php", $vanity_give_count);
						$kisser_count++;
						VarSave($author_folder, "kisser_count.php", $kisser_count);
						//Load target get statistics
						if(!CheckFile($guild_folder."/".$mention_id, "vanity_get_count.php"))	$vanity_get_count	= 0;
						else 																	$vanity_get_count 	= VarLoad($guild_folder."/".$mention_id, "vanity_get_count.php");
						if(!CheckFile($guild_folder."/".$mention_id, "kissed_count.php"))		$kissed_count		= 0;
						else 																	$kissed_count 		= VarLoad($guild_folder."/".$mention_id, "kissed_count.php");
						//Increment get stat counter of target
						$vanity_get_count++;
						VarSave($guild_folder."/".$mention_id, "vanity_get_count.php", $vanity_get_count);
						$kissed_count++;
						VarSave($guild_folder."/".$mention_id, "kissed_count.php", $kissed_count);\
	//					Set Cooldown
						//SetCooldown($author_folder, "vanity_time.php");
						SetCooldownMem($author_id, "vanity");
						return true; //No more processing, we only want to process the first person mentioned
					}else{
						$self_kiss_messages							= array();
						$self_kiss_messages[]						= "<@$author_id> tried to kiss themselves in the mirror. How silly!";
						$index_selection							= GetRandomArrayIndex($self_kiss_messages);
						//Send the message
						$author_channel->send($self_kiss_messages[$index_selection]);
						//Increment give stat counter of author
						$vanity_give_count++;
						VarSave($author_folder, "vanity_give_count.php", $vanity_give_count);
						$kisser_count++;
						VarSave($author_folder, "kisser_count.php", $kisser_count);
						//Increment get stat counter of author
						$vanity_get_count++;
						VarSave($author_folder, "vanity_get_count.php", $vanity_get_count);
						$kissed_count++;
						VarSave($author_folder, "kissed_count.php", $kissed_count);
	//							Set Cooldown
						//SetCooldown($author_folder, "vanity_time.php");
						SetCooldownMem($author_id, "vanity");
						return true; //No more processing, we only want to process the first person mentioned
					}
				}
				//foreach method didn't return, so nobody was mentioned
				$author_channel->send("<@$author_id>, you need to mention someone!");
				return true;
			}else{
	//					Reply with remaining time
				$waittime = $vanity_limit_seconds - $cooldown[1];
				$formattime = FormatTime($waittime);
				$message->reply("You must wait $formattime before using vanity commands again.");
				return true;
			}
		}
		if (substr($message_content_lower, 0, 7) == 'nuzzle '){ //;nuzzle @
			echo "[NUZZLE]" . PHP_EOL;
	//		Check Cooldown Timer
			//$cooldown = CheckCooldown($author_folder, "vanity_time.php", $vanity_limit);
			$cooldown = CheckCooldownMem($author_id, "vanity", $vanity_limit);
			if ( ($cooldown[0] == true) || ($bypass) ){
	//			Get an array of people mentioned
				$mentions_arr 										= $message->mentions->users; 									//echo "mentions_arr: " . PHP_EOL; var_dump ($mentions_arr); //Shows the collection object
				foreach ( $mentions_arr as $mention_param ){
					$mention_param_encode 							= json_encode($mention_param); 									//echo "mention_param_encode: " . $mention_param_encode . PHP_EOL;
					$mention_json 									= json_decode($mention_param_encode, true); 					//echo "mention_json: " . PHP_EOL; var_dump($mention_json);
					$mention_id 									= $mention_json['id']; 											//echo "mention_id: " . $mention_id . PHP_EOL; //Just the discord ID
					
					if ($author_id != $mention_id){
						$nuzzle_messages							= array();
						$nuzzle_messages[]							= "<@$author_id> nuzzled into <@$mention_id>’s neck! Sweethearts~ :blue_heart:";
						$nuzzle_messages[]							= "<@$mention_id> was caught off guard when <@$author_id> nuzzled into their chest! How cute!";
						$nuzzle_messages[]							= "<@$author_id> wanted to show <@$mention_id> some more affection, so they nuzzled into <@$mention_id>’s fluff!";
						$nuzzle_messages[]							= "<@$author_id> rubs their snoot softly against <@$mention_id>, look at those cuties!";
						$nuzzle_messages[]							= "<@$author_id> takes their snoot and nuzzles <@$mention_id> cutely.";
						$index_selection							= GetRandomArrayIndex($nuzzle_messages);
	//					echo "random nuzzle_messages: " . $nuzzle_messages[$index_selection];
	//					Send the message
						$author_channel->send($nuzzle_messages[$index_selection]);
						//Increment give stat counter of author
						$vanity_give_count++;
						VarSave($author_folder, "vanity_give_count.php", $vanity_give_count);
						$nuzzler_count++;
						VarSave($author_folder, "nuzzler_count.php", $nuzzler_count);
						//Load target get statistics
						if(!CheckFile($guild_folder."/".$mention_id, "vanity_get_count.php"))	$vanity_get_count	= 0;
						else 																	$vanity_get_count 	= VarLoad($guild_folder."/".$mention_id, "vanity_get_count.php");
						if(!CheckFile($guild_folder."/".$mention_id, "nuzzled_count.php"))		$nuzzled_count		= 0;
						else 																	$nuzzled_count 		= VarLoad($guild_folder."/".$mention_id, "nuzzled_count.php");
						//Increment get stat counter of target
						$vanity_get_count++;
						VarSave($guild_folder."/".$mention_id, "vanity_get_count.php", $vanity_get_count);
						$nuzzled_count++;
						VarSave($guild_folder."/".$mention_id, "nuzzled_count.php", $nuzzled_count);
	//					Set Cooldown
						//SetCooldown($author_folder, "vanity_time.php");
						SetCooldownMem($author_id, "vanity");
						return true; //No more processing, we only want to process the first person mentioned
					}else{
						$self_nuzzle_messages						= array();
						$self_nuzzle_messages[]						= "<@$author_id> curled into a ball in an attempt to nuzzle themselves.";
						$index_selection							= GetRandomArrayIndex($self_nuzzle_messages);
	//					Send the mssage
						$author_channel->send($self_nuzzle_messages[$index_selection]);
						//Increment give stat counter of author
						$vanity_give_count++;
						VarSave($author_folder, "vanity_give_count.php", $vanity_give_count);
						$nuzzler_count++;
						VarSave($author_folder, "nuzzler_count.php", $nuzzler_count);
						//Increment get stat counter of author
						$vanity_get_count++;
						VarSave($author_folder, "vanity_get_count.php", $vanity_get_count);
						$nuzzled_count++;
						VarSave($author_folder, "nuzzled_count.php", $nuzzled_count);
	//					Set Cooldown
						//SetCooldown($author_folder, "vanity_time.php");
						SetCooldownMem($author_id, "vanity");
						return true; //No more processing, we only want to process the first person mentioned
					}
				}
				//Foreach method didn't return, so nobody was mentioned
				$author_channel->send("<@$author_id>, you need to mention someone!");
				return true;
			}else{
	//					Reply with remaining time
				$waittime = $vanity_limit_seconds - $cooldown[1];
				$formattime = FormatTime($waittime);
				$message->reply("You must wait $formattime before using vanity commands again.");
				return true;
			}
		}
		if (substr($message_content_lower, 0, 5) == 'boop '){ //;boop @
			echo "[BOOP]" . PHP_EOL;
	//		Check Cooldown Timer
			//$cooldown = CheckCooldown($author_folder, "vanity_time.php", $vanity_limit);
			$cooldown = CheckCooldownMem($author_id, "vanity", $vanity_limit);
			if ( ($cooldown[0] == true) || ($bypass) ){
	//			Get an array of people mentioned
				$mentions_arr 										= $message->mentions->users; 									//echo "mentions_arr: " . PHP_EOL; var_dump ($mentions_arr); //Shows the collection object
				foreach ( $mentions_arr as $mention_param ){
					$mention_param_encode 							= json_encode($mention_param); 									//echo "mention_param_encode: " . $mention_param_encode . PHP_EOL;
					$mention_json 									= json_decode($mention_param_encode, true); 					//echo "mention_json: " . PHP_EOL; var_dump($mention_json);
					$mention_id 									= $mention_json['id']; 											//echo "mention_id: " . $mention_id . PHP_EOL; //Just the discord ID
					
					if ($author_id != $mention_id){
						$boop_messages								= array();
						$boop_messages[]							= "<@$author_id> slowly and strategically booped the snoot of <@$mention_id>.";
						$boop_messages[]							= "With a playful smile, <@$author_id> booped <@$mention_id>'s snoot.";
						$index_selection							= GetRandomArrayIndex($boop_messages);
	//					echo "random boop_messages: " . $boop_messages[$index_selection];
	//					Send the message
						$author_channel->send($boop_messages[$index_selection]);
						//Increment give stat counter of author
						$vanity_give_count++;
						VarSave($author_folder, "vanity_give_count.php", $vanity_give_count);
						$booper_count++;
						VarSave($author_folder, "booper_count.php", $booper_count);
						//Load target get statistics
						if(!CheckFile($guild_folder."/".$mention_id, "vanity_get_count.php"))	$vanity_get_count	= 0;
						else 																	$vanity_get_count 	= VarLoad($guild_folder."/".$mention_id, "vanity_get_count.php");
						if(!CheckFile($guild_folder."/".$mention_id, "booped_count.php"))		$booped_count		= 0;
						else 																	$booped_count 		= VarLoad($guild_folder."/".$mention_id, "booped_count.php");
						//Increment get stat counter of target
						$vanity_get_count++;
						VarSave($guild_folder."/".$mention_id, "vanity_get_count.php", $vanity_get_count);
						$booped_count++;
						VarSave($guild_folder."/".$mention_id, "booped_count.php", $booped_count);
	//					Set Cooldown
						//SetCooldown($author_folder, "vanity_time.php");
						SetCooldownMem($author_id, "vanity");
						return true; //No more processing, we only want to process the first person mentioned
					}else{
						$self_boop_messages							= array();
						$self_boop_messages[]						= "<@$author_id> placed a paw on their own nose. How silly!";
						$index_selection							= GetRandomArrayIndex($self_boop_messages);
	//					Send the mssage
						$author_channel->send($self_boop_messages[$index_selection]);
						//Increment give stat counter of author
						$vanity_give_count++;
						VarSave($author_folder, "vanity_give_count.php", $vanity_give_count);
						$booper_count++;
						VarSave($author_folder, "booper_count.php", $booper_count);
						//Increment get stat counter of author
						$vanity_get_count++;
						VarSave($author_folder, "vanity_get_count.php", $vanity_get_count);
						$booped_count++;
						VarSave($author_folder, "booped_count.php", $booped_count);
	//					Set Cooldown
						//SetCooldown($author_folder, "vanity_time.php");
						SetCooldownMem($author_id, "vanity");
						return true; //No more processing
					}
				}
				//Foreach method didn't return, so nobody was mentioned
				$author_channel->send("<@$author_id>, you need to mention someone!");
				return true;
			}else{
	//			Reply with remaining time
				$waittime = $vanity_limit_seconds - $cooldown[1];
				$formattime = FormatTime($waittime);
				$message->reply("You must wait $formattime before using vanity commands again.");
				return true;
			}
		}
		if (substr($message_content_lower, 0, 4) == 'bap '){ //;bap @
			echo "[BAP]" . PHP_EOL;
	//				Check Cooldown Timer
			//$cooldown = CheckCooldown($author_folder, "vanity_time.php", $vanity_limit);
			$cooldown = CheckCooldownMem($author_id, "vanity", $vanity_limit);
			if ( ($cooldown[0] == true) || ($bypass) ){
	//					Get an array of people mentioned
				$mentions_arr 										= $message->mentions->users; 									//echo "mentions_arr: " . PHP_EOL; var_dump ($mentions_arr); //Shows the collection object
				foreach ( $mentions_arr as $mention_param ){
					$mention_param_encode 							= json_encode($mention_param); 									//echo "mention_param_encode: " . $mention_param_encode . PHP_EOL;
					$mention_json 									= json_decode($mention_param_encode, true); 					//echo "mention_json: " . PHP_EOL; var_dump($mention_json);
					$mention_id 									= $mention_json['id']; 											//echo "mention_id: " . $mention_id . PHP_EOL; //Just the discord ID
					
					if ($author_id != $mention_id){
						$bap_messages								= array();
						$bap_messages[]								= "<@$mention_id> was hit on the snoot by <@$author_id>!";
						$bap_messages[]								= "<@$author_id> glared at <@$mention_id>, giving them a bap on the snoot!";
						$bap_messages[]								= "Snoot of <@$mention_id> was attacked by <@$author_id>!";
						$index_selection							= GetRandomArrayIndex($bap_messages);
	//							echo "random bap_messages: " . $bap_messages[$index_selection];
	//					Send the message
						$author_channel->send($bap_messages[$index_selection]);
						//Increment give stat counter of author
						$vanity_give_count++;
						VarSave($author_folder, "vanity_give_count.php", $vanity_give_count);
						$baper_count++;
						VarSave($author_folder, "baper_count.php", $baper_count);
						//Load target get statistics
						if(!CheckFile($guild_folder."/".$mention_id, "vanity_get_count.php"))	$vanity_get_count	= 0;
						else 																	$vanity_get_count 	= VarLoad($guild_folder."/".$mention_id, "vanity_get_count.php");
						if(!CheckFile($guild_folder."/".$mention_id, "baped_count.php"))		$baped_count		= 0;
						else 																	$baped_count 		= VarLoad($guild_folder."/".$mention_id, "baped_count.php");
						//Increment get stat counter of target
						$vanity_get_count++;
						VarSave($guild_folder."/".$mention_id, "vanity_get_count.php", $vanity_get_count);
						$baped_count++;
						VarSave($guild_folder."/".$mention_id, "baped_count.php", $baped_count);
	//					Set Cooldown
						//SetCooldown($author_folder, "vanity_time.php");
						SetCooldownMem($author_id, "vanity");
						return true; //No more processing, we only want to process the first person mentioned
					}else{
						$self_bap_messages							= array();
						$self_bap_messages[]						= "<@$author_id> placed a paw on their own nose. How silly!";
						$index_selection							= GetRandomArrayIndex($self_bap_messages);
	//					Send the mssage
						$author_channel->send($self_bap_messages[$index_selection]);
						//Increment give stat counter of author
						$vanity_give_count++;
						VarSave($author_folder, "vanity_give_count.php", $vanity_give_count);
						$baper_count++;
						VarSave($author_folder, "baper_count.php", $baper_count);
						//Increment get stat counter of author
						$vanity_get_count++;
						VarSave($author_folder, "vanity_get_count.php", $vanity_get_count);
						$baped_count++;
						VarSave($author_folder, "baped_count.php", $baped_count);
	//					Set Cooldown
						//SetCooldown($author_folder, "vanity_time.php");
						SetCooldownMem($author_id, "vanity");
						return true; //No more processing
					}
				}
				//Foreach method didn't return, so nobody was mentioned
				$author_channel->send("<@$author_id>, you need to mention someone!");
				return true;
			}else{
	//					Reply with remaining time
				$waittime = $vanity_limit_seconds - $cooldown[1];
				$formattime = FormatTime($waittime);
				$message->reply("You must wait $formattime before using vanity commands again.");
				return true;
			}
		}
		if (substr($message_content_lower, 0, 4) == 'pet '){ //;pet @
			echo "[PET]" . PHP_EOL;
	//				Check Cooldown Timer
			//$cooldown = CheckCooldown($author_folder, "vanity_time.php", $vanity_limit);
			$cooldown = CheckCooldownMem($author_id, "vanity", $vanity_limit);
			if ( ($cooldown[0] == true) || ($bypass) ){
	//					Get an array of people mentioned
				$mentions_arr 										= $message->mentions->users; 									//echo "mentions_arr: " . PHP_EOL; var_dump ($mentions_arr); //Shows the collection object
				foreach ( $mentions_arr as $mention_param ){
					$mention_param_encode 							= json_encode($mention_param); 									//echo "mention_param_encode: " . $mention_param_encode . PHP_EOL;
					$mention_json 									= json_decode($mention_param_encode, true); 					//echo "mention_json: " . PHP_EOL; var_dump($mention_json);
					$mention_id 									= $mention_json['id']; 											//echo "mention_id: " . $mention_id . PHP_EOL; //Just the discord ID
					
					if ($author_id != $mention_id){
						$pet_messages								= array();
						$pet_messages[]								= "<@$author_id> pets <@$mention_id>";
						$index_selection							= GetRandomArrayIndex($pet_messages);
	//							echo "random pet_messages: " . $pet_messages[$index_selection];
	//					Send the message
						$author_channel->send($pet_messages[$index_selection]);
						//Increment give stat counter of author
						$vanity_give_count++;
						VarSave($author_folder, "vanity_give_count.php", $vanity_give_count);
						$peter_count++;
						VarSave($author_folder, "peter_count.php", $peter_count);
						//Load target get statistics
						if(!CheckFile($guild_folder."/".$mention_id, "vanity_get_count.php"))	$vanity_get_count	= 0;
						else 																	$vanity_get_count 	= VarLoad($guild_folder."/".$mention_id, "vanity_get_count.php");
						if(!CheckFile($guild_folder."/".$mention_id, "peted_count.php"))		$peted_count		= 0;
						else 																	$peted_count 		= VarLoad($guild_folder."/".$mention_id, "peted_count.php");
						//Increment get stat counter of target
						$vanity_get_count++;
						VarSave($guild_folder."/".$mention_id, "vanity_get_count.php", $vanity_get_count);
						$peted_count++;
						VarSave($guild_folder."/".$mention_id, "peted_count.php", $peted_count);
	//					Set Cooldown
						//SetCooldown($author_folder, "vanity_time.php");
						SetCooldownMem($author_id, "vanity");
						return true; //No more processing, we only want to process the first person mentioned
					}else{
						$self_pet_messages							= array();
						$self_pet_messages[]						= "<@$author_id> placed a paw on their own nose. How silly!";
						$index_selection							= GetRandomArrayIndex($self_pet_messages);
	//					Send the mssage
						$author_channel->send($self_pet_messages[$index_selection]);
						//Increment give stat counter of author
						$vanity_give_count++;
						VarSave($author_folder, "vanity_give_count.php", $vanity_give_count);
						$peter_count++;
						VarSave($author_folder, "peter_count.php", $peter_count);
						//Increment get stat counter of author
						$vanity_get_count++;
						VarSave($author_folder, "vanity_get_count.php", $vanity_get_count);
						$peted_count++;
						VarSave($author_folder, "peted_count.php", $peted_count);
	//					Set Cooldown
						//SetCooldown($author_folder, "vanity_time.php");
						SetCooldownMem($author_id, "vanity");
						return true; //No more processing
					}
				}
				//Foreach method didn't return, so nobody was mentioned
				$author_channel->send("<@$author_id>, you need to mention someone!");
				return true;
			}else{
	//					Reply with remaining time
				$waittime = $vanity_limit_seconds - $cooldown[1];
				$formattime = FormatTime($waittime);
				$message->reply("You must wait $formattime before using vanity commands again.");
				return true;
			}
		}
		
		//ymdhis cooldown time
		$vstats_limit['year'] = 0;
		$vstats_limit['month'] = 0;
		$vstats_limit['day'] = 0;
		$vstats_limit['hour'] = 0;
		$vstats_limit['min'] = 30;
		$vstats_limit['sec'] = 0;
		$vstats_limit_seconds = TimeArrayToSeconds($vstats_limit);
		
		if ($message_content_lower == 'vstats'){ //;vstats //Give the author their vanity stats as an embedded message
	//		Check Cooldown Timer
			//$cooldown = CheckCooldown($author_folder, "vstats_limit.php", $vstats_limit);
			$cooldown = CheckCooldownMem($author_id, "vstats", $vanity_limit);
			if ( ($cooldown[0] == true) || ($bypass) ){
	//			Build the embed
				$embed = new \CharlotteDunois\Yasmin\Models\MessageEmbed();
				$embed
					->setTitle("Vanity Stats")																// Set a title
					->setColor("e1452d")																	// Set a color (the thing on the left side)
					->setDescription("$author_guild_name")												// Set a description (below title, above fields)
					->addField("Total Given", 		"$vanity_give_count")									// New line after this
					->addField("Hugs", 				"$hugger_count", true)
					->addField("Kisses", 			"$kisser_count", true)
					->addField("Nuzzles", 			"$nuzzler_count", true)
					->addField("Boops", 			"$booper_count", true)
					->addField("Baps", 				"$baper_count", true)
					->addField("Pets", 				"$peter_count", true)
					->addField("⠀", 				"⠀", true)												// Invisible unicode for separator
					->addField("Total Received", 	"$vanity_get_count")									// New line after this
					->addField("Hugs", 				"$hugged_count", true)
					->addField("Kisses", 			"$kissed_count", true)
					->addField("Nuzzles", 			"$nuzzled_count", true)
					->addField("Boops", 			"$booped_count", true)
					->addField("Baps", 				"$baped_count", true)
					->addField("Pets", 				"$peted_count", true)
					
					->setThumbnail("$author_avatar")														// Set a thumbnail (the image in the top right corner)
	//				->setImage('https://avatars1.githubusercontent.com/u/4529744?s=460&v=4')             	// Set an image (below everything except footer)
					->setTimestamp()                                                                     	// Set a timestamp (gets shown next to footer)
					->setAuthor("$author_check", "$author_guild_avatar")  									// Set an author with icon
					->setFooter("Palace Bot by Valithor#5947")                             					// Set a footer without icon
					->setURL("");                             												// Set the URL
				
	//			Send the message
	//			We do not need another promise here, so we call done, because we want to consume the promise
				if($react) $message->react("👍");
				$author_channel->send('', array('embed' => $embed))->done(null, function ($error){
					echo "[ERROR] $error".PHP_EOL; //Echo any errors
				});
	//			Set Cooldown
				//SetCooldown($author_folder, "vstats_limit.php");
				SetCooldownMem($author_id, "vstats");
				return true;
			}else{
	//			Reply with remaining time
				$waittime = ($vstats_limit_seconds - $cooldown[1]);
				$formattime = FormatTime($waittime);
				if($react) $message->react("👎");
				$message->reply("You must wait $formattime before using vstats on yourself again.");
				return true;
			}
		}
		
		if (substr($message_content_lower, 0, 7) == 'vstats '){ //;vstats @
			echo "[GET MENTIONED VANITY STATS]" . PHP_EOL;
	//		Check Cooldown Timer
			//$cooldown = CheckCooldown($author_folder, "vstats_limit.php", $vstats_limit);
			$cooldown = CheckCooldownMem($author_id, "vstats", $vanity_limit);
			if ( ($cooldown[0] == true) || ($bypass) ){
	//			Get an array of people mentioned
				$mentions_arr 										= $message->mentions->users; 									//echo "mentions_arr: " . PHP_EOL; var_dump ($mentions_arr); //Shows the collection object			
				foreach ( $mentions_arr as $mention_param ){																				//echo "mention_param: " . PHP_EOL; var_dump ($mention_param);
	//				id, username, discriminator, bot, avatar, email, mfaEnabled, verified, webhook, createdTimestamp
					$mention_param_encode 							= json_encode($mention_param); 									//echo "mention_param_encode: " . $mention_param_encode . PHP_EOL;
					$mention_json 									= json_decode($mention_param_encode, true); 					//echo "mention_json: " . PHP_EOL; var_dump($mention_json);
					$mention_id 									= $mention_json['id']; 											//echo "mention_id: " . $mention_id . PHP_EOL; //Just the discord ID
					$mention_username 								= $mention_json['username']; 									//echo "mention_username: " . $mention_username . PHP_EOL; //Just the discord ID
					$mention_discriminator 							= $mention_json['discriminator']; 								//echo "mention_discriminator: " . $mention_discriminator . PHP_EOL; //Just the discord ID
					$mention_check 									= $mention_username ."#".$mention_discriminator; 				//echo "mention_check: " . $mention_check . PHP_EOL; //Just the discord ID
					
	//				Get the avatar URL
					$target_guildmember 							= $message->guild->members->get($mention_id); 	//This is a GuildMember object
					$target_guildmember_user						= $target_guildmember->user;									//echo "member_class: " . get_class($target_guildmember_user) . PHP_EOL;
					$mention_avatar 								= "{$target_guildmember_user->getAvatarURL()}";					//echo "mention_avatar: " . $mention_avatar . PHP_EOL;
					
					
					//Load target get statistics
					if(!CheckFile($guild_folder."/".$mention_id, "vanity_get_count.php"))	$target_vanity_get_count	= 0;
					else 																	$target_vanity_get_count 	= VarLoad($guild_folder."/".$mention_id, "vanity_get_count.php");
					if(!CheckFile($guild_folder."/".$mention_id, "vanity_give_count.php"))	$target_vanity_give_count	= 0;
					else 																	$target_vanity_give_count 	= VarLoad($guild_folder."/".$mention_id, "vanity_give_count.php");
					if(!CheckFile($guild_folder."/".$mention_id, "hugged_count.php"))		$target_hugged_count		= 0;
					else 																	$target_hugged_count 		= VarLoad($guild_folder."/".$mention_id, "hugged_count.php");
					if(!CheckFile($guild_folder."/".$mention_id, "hugger_count.php"))		$target_hugger_count		= 0;
					else 																	$target_hugger_count 		= VarLoad($guild_folder."/".$mention_id, "hugger_count.php");
					if(!CheckFile($guild_folder."/".$mention_id, "kissed_count.php"))		$target_kissed_count		= 0;
					else 																	$target_kissed_count 		= VarLoad($guild_folder."/".$mention_id, "kissed_count.php");
					if(!CheckFile($guild_folder."/".$mention_id, "kisser_count.php"))		$target_kisser_count		= 0;
					else 																	$target_kisser_count 		= VarLoad($guild_folder."/".$mention_id, "kisser_count.php");
					if(!CheckFile($guild_folder."/".$mention_id, "nuzzled_count.php"))		$target_nuzzled_count		= 0;
					else 																	$target_nuzzled_count 		= VarLoad($guild_folder."/".$mention_id, "nuzzled_count.php");
					if(!CheckFile($guild_folder."/".$mention_id, "nuzzler_count.php"))		$target_nuzzler_count		= 0;
					else 																	$target_nuzzler_count 		= VarLoad($guild_folder."/".$mention_id, "nuzzler_count.php");
					if(!CheckFile($guild_folder."/".$mention_id, "booped_count.php"))		$target_booped_count		= 0;
					else 																	$target_booped_count 		= VarLoad($guild_folder."/".$mention_id, "booped_count.php");
					if(!CheckFile($guild_folder."/".$mention_id, "booper_count.php"))		$target_booper_count		= 0;
					else 																	$target_booper_count 		= VarLoad($guild_folder."/".$mention_id, "booper_count.php");
					
					//Build the embed
					$embed = new \CharlotteDunois\Yasmin\Models\MessageEmbed();
					$embed
						->setTitle("Vanity Stats")																// Set a title
						->setColor("e1452d")																	// Set a color (the thing on the left side)
						->setDescription("$author_guild_name")												// Set a description (below title, above fields)
						->addField("Total Given", 		"$target_vanity_give_count")							// New line after this
						->addField("Hugs", 				"$target_hugger_count", true)
						->addField("Kisses", 			"$target_kisser_count", true)
						->addField("Nuzzles", 			"$target_nuzzler_count", true)
						->addField("Boops", 			"$target_booper_count", true)
						->addField("⠀", 				"⠀", true)												// Invisible unicode for separator
						->addField("Total Received", 	"$target_vanity_get_count")								// New line after this
						->addField("Hugs", 				"$target_hugged_count", true)
						->addField("Kisses", 			"$target_kissed_count", true)
						->addField("Nuzzles", 			"$target_nuzzled_count", true)
						->addField("Boops", 			"$target_booped_count", true)
						
						->setThumbnail("$mention_avatar")														// Set a thumbnail (the image in the top right corner)
	//					->setImage('https://avatars1.githubusercontent.com/u/4529744?s=460&v=4')             		// Set an image (below everything except footer)
						->setTimestamp()                                                                     	// Set a timestamp (gets shown next to footer)
						->setAuthor("$mention_check", "$author_guild_avatar")  // Set an author with icon
						->setFooter("Palace Bot by Valithor#5947")                             					// Set a footer without icon
						->setURL("");                             												// Set the URL
					
	//				Send the message
	//				We do not need another promise here, so we call done, because we want to consume the promise
					if($react) $message->react("👍");
					$author_channel->send('', array('embed' => $embed))->done(null, function ($error){
						echo "[ERROR] $error".PHP_EOL; //Echo any errors
					});
	//				Set Cooldown
					//SetCooldown($author_folder, "vstats_limit.php");
					SetCooldownMem($author_id, "vstats");
					return true; //No more processing, we only want to process the first person mentioned
				}
				//Foreach method didn't return, so nobody was mentioned
				$author_channel->send("<@$author_id>, you need to mention someone!");
				return true;
			}else{
	//			Reply with remaining time
				$waittime = ($vstats_limit_seconds - $cooldown[1]);
				$formattime = FormatTime($waittime);
				if($react) $message->react("👎");
				$message->reply("You must wait $formattime before using vstats on yourself again.");
				return true;
			}
		}
		
	} //End of vanity commands

	/*
	*********************
	*********************
	Role picker functions
	*********************
	*********************
	*/

	//TODO? (This is already done with messageReactionAdd)

	/*
	*********************
	*********************
	Restricted command functions
	*********************
	*********************
	*/

	/*
	if($creator || $owner || $dev || $admin || $mod){ //Only allow these roles to use this
	}
	*/

	if ($creator){ //Mostly just debug commands
		if($message_content_lower == 'debug'){
			echo '[DEBUG]' . PHP_EOL;
			ob_start();
			
			//echo print_r(get_defined_vars(), true); //REALLY REALLY BAD IDEA
			print_r(get_defined_constants(true));
			
			$debug_output = ob_get_contents();
			ob_end_clean(); //here, output is cleaned. You may want to flush it with ob_end_flush()
			file_put_contents('debug.txt', $debug_output);
			ob_end_flush();
		}
		if ( substr($message_content_lower, 0, 7) == 'mention'){
			//Get an array of people mentioned
			$GetMentionResult = GetMention([&$author_guild, substr($message_content_lower, 8, strlen($message_content_lower)), null, 1, &$restcord]);
			if ($GetMentionResult === false ) return $message->reply("Invalid input! Please enter a valid ID or @mention the user");
			
			$output_string = "Mentions IDs: ";
			$keys = array_keys($GetMentionResult);
			for($i = 0; $i < count($GetMentionResult); $i++) {
				if (is_numeric($keys[$i])){
					$output_string = $output_string . " " . $keys[$i];
				}else{
					foreach($GetMentionResult[$keys[$i]] as $key => $value) {
						$clean_string = $value;
					}
				}
			}
			$output_string = $output_string  . PHP_EOL . "Clean string: " . $clean_string;
			$author_channel->send($output_string);
			
		}
		if ($message_content_lower == 'genimage'){
			include "imagecreate_include.php"; //Generates $img_output_path
			$image_path = "http://www.valzargaming.com/discord%20-%20palace/" . $img_output_path;
			//echo "image_path: " . $image_path . PHP_EOL;
		//	Build the embed message
			$embed = new \CharlotteDunois\Yasmin\Models\MessageEmbed();
			$embed
		//		->setTitle("$author_check")																// Set a title
				->setColor("e1452d")																	// Set a color (the thing on the left side)
				->setDescription("$author_guild_name")									// Set a description (below title, above fields)
		//		->addField("⠀", "$documentation")														// New line after this
				
				->setThumbnail("$author_avatar")														// Set a thumbnail (the image in the top right corner)
		//		->setImage('https://avatars1.githubusercontent.com/u/4529744?s=460&v=4')             	// Set an image (below everything except footer)
				->setImage("$image_path")             													// Set an image (below everything except footer)
				->setTimestamp()                                                                     	// Set a timestamp (gets shown next to footer)
				->setAuthor("$author_check", "$author_guild_avatar")  									// Set an author with icon
				->setFooter("Palace Bot by Valithor#5947")                             					// Set a footer without icon
				->setURL("");                             												// Set the URL
		//		Open a DM channel then send the rich embed message
			/*
			$author_user->createDM()->then(function($author_dmchannel) use ($message, $embed){	//Promise
				echo 'SEND GENIMAGE EMBED' . PHP_EOL;
				$author_dmchannel->send('', array('embed' => $embed))->done(null, function ($error){
					echo "[ERROR] $error".PHP_EOL; //Echo any errors
				});
			});
			*/
			$author_channel->send('', array('embed' => $embed))->done(null, function ($error){
				echo "[ERROR] $error".PHP_EOL; //Echo any errors
			});
			return true;
		}
		if ($message_content_lower == 'promote'){ //;promote
			$author_member->addRole($role_dev_id)->done( //echo "role_admin_id: $role_admin_id" . PHP_EOL;
				function ($error) {
					echo "[ERROR] $error".PHP_EOL;
				}
			);
		}
		if ($message_content_lower == 'demote'){ //;demote
			$author_member->removeRole($role_dev_id)->done( //echo "role_admin_id: $role_admin_id" . PHP_EOL;
				function ($error) {
					echo "[ERROR] $error".PHP_EOL;
				}
			);
		}
		if ($message_content_lower == 'processmessages'){	
			//$verifylog_channel																				//TextChannel				//echo "channel_messages class: " . get_class($verifylog_channel) . PHP_EOL;
			//$author_messages = $verifylog_channel->fetchMessages(); 											//Promise
			//echo "author_messages class: " . get_class($author_messages) . PHP_EOL; 							//Promise
			$verifylog_channel->fetchMessages()->then(function($message_collection) use ($verifylog_channel){	//Resolve the promise
				//$verifylog_channel and the new $message_collection can be used here
				//echo "message_collection class: " . get_class($message_collection) . PHP_EOL; 				//Collection messages
				foreach ($message_collection as $message){														//Model/Message				//echo "message_collection message class:" . get_class($message) . PHP_EOL;
					//DO STUFF HERE TO MESSAGES
				}
			});
			return true;
		}
		if ($message_content_lower == 'connections'){	
		
			return true;
		}
		if ($message_content_lower == 'restart'){
			echo "[RESTART LOOP]" . PHP_EOL;
			$dt = new DateTime("now");  // convert UNIX timestamp to PHP DateTime
			echo "[TIME] " . $dt->format('d-m-Y H:i:s') . PHP_EOL; // output = 2017-01-01 00:00:00
			//$loop->stop();
			$discord->destroy();
			$discord = new \CharlotteDunois\Yasmin\Client(array(), $loop);
			$discord->login($token)->done(null, function ($error){
				echo "[LOGIN ERROR] $error".PHP_EOL; //Echo any errors
			});
			//$loop->run();
			echo "[LOOP RESTARTED]" . PHP_EOL;
		}
		if (substr($message_content_lower, 0, 6) == 'timer '){ //;timer
			echo "[TIMER]" . PHP_EOL;
			$filter = "timer ";
			$value = str_replace($filter, "", $message_content_lower);
			if(is_numeric($value)){
				$discord->addTimer($value, function() use ($author_channel) {
					return $author_channel->send("Timer");
				});
			}else return $message->reply("Invalid input! Please enter a valid number");
			return true;
		}
		if (substr($message_content_lower, 0, 10) == 'resolveid '){ //;timer
			echo "[RESOLVEID]" . PHP_EOL;
			$filter = "resolveid ";
			$value = str_replace($filter, "", $message_content_lower);
			if(is_numeric($value)){ //resolve with restcord
				$restcord_result = $restcord->user->getUser(['user.id' => (int)$value]);
				var_dump($restcord_result);
			}
		}
		if ($message_content_lower == 'xml'){
			include "xml.php";
		}
		if ($message_content_lower == 'backup'){ //;backup
			echo "[SAVEGLOBAL]" . PHP_EOL;
			$GLOBALS["RESCUE"] = true;
			$blacklist_globals = array (
				"GLOBALS",
				"loop",
				"discord",
				"restcord"
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
		}
		if ($message_content_lower == 'rescue'){ //;rescue
			echo "[RESCUE]" . PHP_EOL;
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
		}
		if ($message_content_lower == 'get unregistered'){ //;get unregistered
			echo "[GET UNREGISTERED START]" . PHP_EOL;
			$GLOBALS["UNREGISTERED"] = null;
			$author_guild->fetchMembers()->then(function($fetched_guild) use ($message, $author_guild){	//Promise
				$members = $fetched_guild->members->all(); //array
				foreach ($members as $target_member){ //GuildMember
					$target_skip = false;
					//get roles of member
					$target_guildmember_role_collection = $target_member->roles;
					foreach ($target_guildmember_role_collection as $role){
						if ($role->name == "Cadet") $target_skip = true;
						if ($role->name == "Bots") $target_skip = true;
					}
					if ($target_skip === false){
						//Query SQL for ss13 where discord =
						$mention_id = $target_member->id; //echo "mention_id: " . $mention_id . PHP_EOL;
						$active_member = $author_guild->members->get($mention_id);
						include "../connect.php";
						$sqlgettargetinfo = "
							SELECT
								`ss13`
							FROM
								`users`
							WHERE
								`discord` = '$mention_id'";
						$resultsqlgettargetinfo = mysqli_query($con, $sqlgettargetinfo);
						if($resultsqlgettargetinfo){
							$rowselect = mysqli_fetch_array($resultsqlgettargetinfo);
							$ckey = $rowselect['ss13'];
							if (!$ckey){
								//echo "$mention_id: No ckey found" . PHP_EOL;
								$GLOBALS["UNREGISTERED"][] = $mention_id;
							}else{
								//echo "$mention_id: $ckey" . PHP_EOL;
							}
						}else{
							//echo "$mention_id: No registration found" . PHP_EOL;
							$GLOBALS["UNREGISTERED"][] = $mention_id;
						}
					}
				}
				if($react) $message->react("👍");
				echo count($GLOBALS["UNREGISTERED"]) . " UNREGISTERED ACCOUNTS" . PHP_EOL;
				echo "[GET UNREGISTERED DONE]" . PHP_EOL;
				return true;
			});
		}
		if ($message_content_lower == 'unverify unregistered'){ //;unverify unregistered
			echo "[UNVERIFY UNREGISTERED START]" . PHP_EOL;
			if ($GLOBALS["UNREGISTERED"]){
				echo "UNREGISTERED 0: " . $GLOBALS["UNREGISTERED"][0] . PHP_EOL;
				$GLOBALS["UNREGISTERED_COUNT"] = count($GLOBALS["UNREGISTERED"]); echo "UNREGISTERED_COUNT: " . $GLOBALS["UNREGISTERED_COUNT"] . PHP_EOL;
				$GLOBALS["UNREGISTERED_X"] = 0;
				$GLOBALS['UNREGISTERED_TIMER'] = $loop->addPeriodicTimer(5, function() use ($discord, $loop, $author_guild_id){
					//FIX THIS
					if ($GLOBALS["UNREGISTERED_X"] < $GLOBALS["UNREGISTERED_COUNT"]){
						$target_id = $GLOBALS["UNREGISTERED"][$GLOBALS["UNREGISTERED_X"]]; //GuildMember
						//echo "author_guild_id: " . $author_guild_id;
						//echo "UNREGISTERED ID: $target_id" . PHP_EOL;
						if ($target_id){
							echo "UNVERIFYING $target_id" . PHP_EOL;
							$target_guild = $discord->guilds->resolve($author_guild_id); echo "target_guild: " . get_class($target_guild) . PHP_EOL;
							$target_member = $target_guild->members->get($target_id); echo "target_member: " . get_class($target_member) . PHP_EOL;
							$target_member->removeRole("468982790772228127");
							$target_member->removeRole("468983261708681216");
							$target_member->addRole("469312086766518272");
							$GLOBALS["UNREGISTERED_X"] = $GLOBALS["UNREGISTERED_X"] + 1;
							return true;
						}else{
							$loop->cancelTimer($GLOBALS['UNREGISTERED_TIMER']);
							$GLOBALS["UNREGISTERED_COUNT"] = null;
							$GLOBALS['UNREGISTERED_X'] = null;
							$GLOBALS['UNREGISTERED_TIMER'] = null;
							echo "[UNREGISTERED TIMER DONE]";
							return true;
						}
					}
				});
				if($react) $message->react("👍");
			}else{
				if($react) $message->react("👎");
			}
			echo "[CHECK UNREGISTERED DONE]" . PHP_EOL;
			return true;
		}
		if ($message_content_lower == 'get unverified'){ //;get unverified
			echo "[GET UNVERIFIED START]" . PHP_EOL;
			$GLOBALS["UNVERIFIED"] = null;
			$author_guild->fetchMembers()->then(function($fetched_guild) use ($message, $author_guild){	//Promise
				$members = $fetched_guild->members->all(); //array
				foreach ($members as $target_member){ //GuildMember
					$target_skip = false;
					//get roles of member
					$target_guildmember_role_collection = $target_member->roles;
					foreach ($target_guildmember_role_collection as $role){
						if ($role->name == "Cadet") $target_get = true;
						if ($role->name == "Private") $target_skip = true;
						if ($role->name == "Veteran") $target_skip = true;
						if ($role->name == "Bots") $target_skip = true;
						if ($role->name == "BANNED") $target_skip = true;
					}
					if ( ($target_skip === false) && ($target_get === true) ){
						$mention_id = $target_member->id; //echo "mention_id: " . $mention_id . PHP_EOL;
						$GLOBALS["UNVERIFIED"][] = $mention_id;
					}
				}
				if($react) $message->react("👍");
				echo count($GLOBALS["UNVERIFIED"]) . " UNVERIFIED ACCOUNTS" . PHP_EOL;
				echo "[GET UNVERIFIED DONE]" . PHP_EOL;
				return true;
			});
		}
		if ($message_content_lower == 'purge unverified'){ //;purge unverified
			echo "[PURGE UNVERIFIED START]" . PHP_EOL;
			if ($GLOBALS["UNVERIFIED"]){
				echo "UNVERIFIED 0: " . $GLOBALS["UNVERIFIED"][0] . PHP_EOL;
				$GLOBALS["UNVERIFIED_COUNT"] = count($GLOBALS["UNVERIFIED"]); echo "UNVERIFIED_COUNT: " . $GLOBALS["UNVERIFIED_COUNT"] . PHP_EOL;
				$GLOBALS["UNVERIFIED_X"] = 0;
				$GLOBALS['UNVERIFIED_TIMER'] = $loop->addPeriodicTimer(5, function() use ($discord, $loop, $author_guild_id){
					//FIX THIS
					if ($GLOBALS["UNVERIFIED_X"] < $GLOBALS["UNVERIFIED_COUNT"]){
						$target_id = $GLOBALS["UNVERIFIED"][$GLOBALS["UNVERIFIED_X"]]; //GuildMember
						//echo "author_guild_id: " . $author_guild_id;
						//echo "UNVERIFIED ID: $target_id" . PHP_EOL;
						if ($target_id){
							echo "PURGING $target_id" . PHP_EOL;
							$target_guild = $discord->guilds->resolve($author_guild_id); //echo "target_guild: " . get_class($target_guild) . PHP_EOL;
							$target_member = $target_guild->members->get($target_id); //echo "target_member: " . get_class($target_member) . PHP_EOL;
							$target_member->kick("unverified purge");
							$GLOBALS["UNVERIFIED_X"] = $GLOBALS["UNVERIFIED_X"] + 1;
							return true;
						}else{
							$loop->cancelTimer($GLOBALS['UNVERIFIED_TIMER']);
							$GLOBALS["UNVERIFIED_COUNT"] = null;
							$GLOBALS['UNVERIFIED_X'] = null;
							$GLOBALS['UNVERIFIED_TIMER'] = null;
							echo "[PURGE UNVERIFIED TIMER DONE]" . PHP_EOL;
							return true;
						}
					}
				});
				if($react) $message->react("👍");
			}else{
				if($react) $message->react("👎");
			}
			echo "[PURGE UNVERIFIED DONE]" . PHP_EOL;
			return true;
		}
	}

	if ( $creator || ($author_guild_id == "468979034571931648") || ($author_guild_id == "744022293021458464") ) { //These commands should only be relevant for use on this server
		switch($author_guild_id){
			case "468979034571931648":
				$staff_channel_id = "562715700360380434";
				$staff_bot_channel_id = "712685552155230278";
				break;
			case "744022293021458464":
				$staff_channel_id = "744022293533032541";
				$staff_bot_channel_id = "744022293533032542";
				break;
		}	
		//Don't let people use these in #general
		switch($message_content_lower){
			case 'status': //;status
				echo "[STATUS] $author_check" . PHP_EOL;
				$ch = curl_init(); //create curl resource
				curl_setopt($ch, CURLOPT_URL, "http://10.0.0.18:81/civ13/serverstate.txt"); // set url
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); //return the transfer as a string
				$message->reply(curl_exec($ch));
				return true;
				break;
			case "serverstatus":
				echo "[SERVER STATUS] $author_check" . PHP_EOL;
				//VirtualBox state
				$ch = curl_init(); //create curl resource
				curl_setopt($ch, CURLOPT_URL, "http://10.0.0.18:81/civ13/serverstate.txt"); // set url
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); //return the transfer as a string
				if (curl_exec($ch) != "playing"){ //Don't even try to process anything (including webhooks) if the persistence server is saving.
					$author_channel->send("Persistence is either saving or the webserver is down!");
				}
				include "../servers/getserverdata.php"; //Do this async?
				$sent = false; //No message has been sent yet.		
				$embed = new \CharlotteDunois\Yasmin\Models\MessageEmbed();
				$embed->setDescription("Civ13 Server Status");
				$image_path_array = array();
				if ($serverinfo[0]["age"]){ //We got data back for this server, so it must be online, right??
					//Round duration info
					$rd = explode (":",  urldecode($serverinfo[0]["roundduration"]) );
					$remainder = ($rd[0] % 24);
					$rd[0] = floor($rd[0] / 24);
					if( ($rd[0] != 0) || ($remainder != 0) || ($rd[1] != 0) ){ //Round is starting
						$rt = $rd[0] . "d " . $remainder . "h " . $rd[1] . "m";
					}else{
						$rt = "STARTING";
					}
						$alias = "<byond://" . $servers[0]["alias"] . ":" . $servers[0]["port"] . ">";
						$image_path = "http://www.valzargaming.com/servers/gamebanner.php?servernum=0&rand=" . rand(0,999999999);
						$image_path_array[] = $image_path;
						//echo "image_path: " . $image_path . PHP_EOL;
					//	Build the embed message
						$embed
					//		->setTitle("$author_check")														// Set a title
							->setColor("e1452d")																	// Set a color (the thing on the left side)
							->addField("Server", "$alias"  . "\n" . $servers[0]["servername"])									// Set a description (below title, above fields)
					//		->setDescription("$alias"  . "\n" . $servers[0]["servername"] /*. "\nRound time: " . $rd[1] . "d " . $remainder . "h " . $rd[1] . "m" . "\n Host: ". $serverinfo[1]["host"] ." \nPlayers: " . $serverinfo[1]["players"]*/)									// Set a description (below title, above fields)
					//		->addField("⠀", "$documentation")														// New line after this
							
					//		->setThumbnail("$author_avatar")														// Set a thumbnail (the image in the top right corner)
					//				->setImage('https://avatars1.githubusercontent.com/u/4529744?s=460&v=4')        // Set an image (below everything except footer)
							//->setImage("$image_path")             													// Set an image (below everything except footer)
							->setTimestamp()                                                                     	// Set a timestamp (gets shown next to footer)
					//		->setAuthor("$author_check", "$author_guild_avatar")  									// Set an author with icon
							->setFooter("Palace Bot by Valithor#5947")                             					// Set a footer without icon
							->setURL("");                             												// Set the URL
					//				Open a DM channel then send the rich embed message
						/*
						$author_user->createDM()->then(function($author_dmchannel) use ($message, $embed){	//Promise
							echo 'SEND GENIMAGE EMBED' . PHP_EOL;
							$author_dmchannel->send('', array('embed' => $embed))->done(null, function ($error){
								echo "[ERROR] $error".PHP_EOL; //Echo any errors
							});
						});
						*/
						if($rt) $embed->addField("Round Time", $rt, true);
						if ( ($serverinfo[0]["age"] != "unknown") && ($serverinfo[0]["age"] != NULL) ){
							$embed->addField("Epoch", urldecode($serverinfo[0]["age"]), true);
						}
						if ( ($serverinfo[0]["season"] != "unknown") && ($serverinfo[0]["season"] != NULL) ){
							$embed->addField("Season", urldecode($serverinfo[0]["season"]), true);
						}
						if ( ($serverinfo[0]["map"] != "unknown") && ($serverinfo[0]["map"] != NULL) ){
							$embed->addField("Map", urldecode($serverinfo[0]["map"]), true);
						}
						$sent = true;
				}
				
				if ($serverinfo[1]["age"]){ //We got data back for this server, so it must be online, right??
					//Round duration info
					$rd = explode (":",  urldecode($serverinfo[1]["roundduration"]) );
					$remainder = ($rd[0] % 24);
					$rd[0] = floor($rd[0] / 24);
					if( ($rd[0] != 0) || ($remainder != 0) || ($rd[1] != 0) ){ //Round is starting
						$rt = $rd[0] . "d " . $remainder . "h " . $rd[1] . "m";
					}else{
						$rt = "STARTING";
					}
						$alias = "<byond://" . $servers[1]["alias"] . ":" . $servers[1]["port"] . ">";
						$image_path = "http://www.valzargaming.com/servers/gamebanner.php?servernum=1&rand=" . rand(0,999999999);
						$image_path_array[] = $image_path;
						//echo "image_path: " . $image_path . PHP_EOL;
					//	Build the embed message
						$embed
					//		->setTitle("$author_check")														// Set a title
							->setColor("e1452d")																	// Set a color (the thing on the left side)
							->addField("Server", "$alias" . "\n" . $servers[1]["servername"] /*. "\nRound time: " . $rd[1] . "d " . $remainder . "h " . $rd[1] . "m" . "\n Host: ". $serverinfo[1]["host"] ." \nPlayers: " . $serverinfo[1]["players"]*/)									// Set a description (below title, above fields)
					//		->addField("⠀", "$documentation")														// New line after this
							
					//		->setThumbnail("$author_avatar")														// Set a thumbnail (the image in the top right corner)
					//				->setImage('https://avatars1.githubusercontent.com/u/4529744?s=460&v=4')        // Set an image (below everything except footer)
							//->setImage("$image_path")             													// Set an image (below everything except footer)
							->setTimestamp()                                                                     	// Set a timestamp (gets shown next to footer)
					//		->setAuthor("$author_check", "$author_guild_avatar")  									// Set an author with icon
							->setFooter("Palace Bot by Valithor#5947")                             					// Set a footer without icon
							->setURL("");                             												// Set the URL
					//				Open a DM channel then send the rich embed message
						/*
						$author_user->createDM()->then(function($author_dmchannel) use ($message, $embed){	//Promise
							echo 'SEND GENIMAGE EMBED' . PHP_EOL;
							$author_dmchannel->send('', array('embed' => $embed))->done(null, function ($error){
								echo "[ERROR] $error".PHP_EOL; //Echo any errors
							});
						});
						*/
						if($rt) $embed->addField("Round Time", $rt, true);
						if ( ($serverinfo[1]["age"] != "unknown") && ($serverinfo[1]["age"] != NULL) ){
							$embed->addField("Epoch", urldecode($serverinfo[1]["age"]), true);
						}
						if ( ($serverinfo[1]["season"] != "unknown") && ($serverinfo[1]["season"] != NULL) ){
							$embed->addField("Season", urldecode($serverinfo[1]["season"]), true);
						}
						if ( ($serverinfo[1]["map"] != "unknown") && ($serverinfo[1]["map"] != NULL) ){
							$embed->addField("Map", urldecode($serverinfo[1]["map"]), true);
						}
						$sent = true;
				}
				if ($serverinfo[2]["age"]){ //We got data back for this server, so it must be online, right??
					//Round duration info
					$rd = explode (":",  urldecode($serverinfo[2]["roundduration"]) );
					$remainder = ($rd[0] % 24);
					$rd[0] = floor($rd[0] / 24);
					if( ($rd[0] != 0) || ($remainder != 0) || ($rd[1] != 0) ){ //Round is starting
						$rt = $rd[0] . "d " . $remainder . "h " . $rd[1] . "m";
					}else{
						$rt = "STARTING";
					}
						$alias = "<byond://" . $servers[2]["alias"] . ":" . $servers[2]["port"] . ">";
						$image_path = "http://www.valzargaming.com/servers/gamebanner.php?servernum=2&rand=" . rand(0,999999999);
						$image_path_array[] = $image_path;
						//echo "image_path: " . $image_path . PHP_EOL;
					//	Build the embed message
						$embed
					//		->setTitle("$author_check")														// Set a title
							->setColor("e1452d")																	// Set a color (the thing on the left side)
							->addField("Server", "$alias"  . "\n" . $servers[2]["servername"] /*. "\nRound time: " . $rd[1] . "d " . $remainder . "h " . $rd[1] . "m" . "\n Host: ". $serverinfo[1]["host"] ." \nPlayers: " . $serverinfo[1]["players"]*/)									// Set a description (below title, above fields)
					//		->addField("⠀", "$documentation")														// New line after this
							
					//		->setThumbnail("$author_avatar")														// Set a thumbnail (the image in the top right corner)
					//				->setImage('https://avatars1.githubusercontent.com/u/4529744?s=460&v=4')        // Set an image (below everything except footer)
							//->setImage("$image_path")             													// Set an image (below everything except footer)
							->setTimestamp()                                                                     	// Set a timestamp (gets shown next to footer)
					//		->setAuthor("$author_check", "$author_guild_avatar")  									// Set an author with icon
							->setFooter("Palace Bot by Valithor#5947")                             					// Set a footer without icon
							->setURL("");                             												// Set the URL
					//				Open a DM channel then send the rich embed message
						/*
						$author_user->createDM()->then(function($author_dmchannel) use ($message, $embed){	//Promise
							echo 'SEND GENIMAGE EMBED' . PHP_EOL;
							$author_dmchannel->send('', array('embed' => $embed))->done(null, function ($error){
								echo "[ERROR] $error".PHP_EOL; //Echo any errors
							});
						});
						*/
						if($rt) $embed->addField("Round Time", $rt, true);
						if ( ($serverinfo[2]["age"] != "unknown") && ($serverinfo[2]["age"] != NULL) ){
							$embed->addField("Epoch", urldecode($serverinfo[2]["age"]), true);
						}
						if ( ($serverinfo[2]["season"] != "unknown") && ($serverinfo[2]["season"] != NULL) ){
							$embed->addField("Season", urldecode($serverinfo[2]["season"]), true);
						}
						if ( ($serverinfo[2]["map"] != "unknown") && ($serverinfo[2]["map"] != NULL) ){
							$embed->addField("Map", urldecode($serverinfo[2]["map"]), true);
						}
						$sent = true;
				}
				if ($sent === true){
					//Get the image
					$image_path = appendImages($image_path_array);
					echo "image_path: $image_path" . PHP_EOL;
					$embed->setImage("$image_path");
					
					//Send the message
					$author_channel->send('', array('embed' => $embed))->done(null, function ($error){
						echo "[ERROR] $error".PHP_EOL; //Echo any errors
					});
				}else{
					$author_channel->send("No servers online!");
				}
				
				return true;
				break;
			case "serverstate":
				//Sends a message containing data for each server we host as collected from serverinfo.json
				//This method does not have to be called locally, so it can be moved to VZG Verifier
				echo "[SERVER STATE] $author_check" . PHP_EOL;
				$data = array();
				//get json from website
				$ch = curl_init(); //create curl resource
				curl_setopt($ch, CURLOPT_URL, "http://www.valzargaming.com/servers/serverinfo.json"); // set url
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); //return the transfer as a string
				curl_setopt($ch, CURLOPT_POST, false);
				$ch_data = curl_exec($ch);
				curl_close($ch);
				
				$data_json = json_decode($ch_data, true); //iterable with $data_json["key"]
				$desc_string_array = array();
				$desc_string = "";
				foreach ($data_json as $varname => $varvalue){ //individual servers
					echo strlen($desc_string) . PHP_EOL;
					if(is_array($varvalue)){
						//$varvalue = json_encode($varvalue);
						foreach ($varvalue as $varname2 => $varvalue2){ //invalid
							$varvalue2 = json_encode($varvalue2);
							$desc_string = $desc_string . $varname2 . ": " . urldecode($varvalue2) . "\n";
						}
					}else{
						$desc_string = $desc_string . $varname . ": " . urldecode($varvalue) . "\n";
					}
					$desc_string_array[] = $desc_string ?? "null";
					$desc_string = "";
				}
				/*
				
				*/
				
				$server_index[] = "Persistence" . PHP_EOL;
				$server_index[] = "TDM" . PHP_EOL;
				$server_index[] = "Nomads" . PHP_EOL;
				$x=0;
				foreach ($desc_string_array as $output_string){
					if ($output_string != "" && $output_string != NULL){
						//Build the embed message
						if (strlen($output_string) <= 2042){
							$embed = new \CharlotteDunois\Yasmin\Models\MessageEmbed();
							$embed
								//->setTitle("$author_check")															// Set a title
								->setColor("e1452d")																	// Set a color (the thing on the left side)
								->setDescription($server_index[$x] . "```$output_string```")												// Set a description (below title, above fields)
								//->addField("Players (" .($serverinfo[0]["players"] ?? "Offline").")", urldecode($playerlist))		// New line after this
								
								//->setThumbnail("$author_avatar")														// Set a thumbnail (the image in the top right corner)
								//->setImage('https://avatars1.githubusercontent.com/u/4529744?s=460&v=4')             	// Set an image (below everything except footer)
								//->setImage("$image_path")             												// Set an image (below everything except footer)
								->setTimestamp()                                                                     	// Set a timestamp (gets shown next to footer)
								//->setAuthor("$author_check", "$author_avatar")  								// Set an author with icon
								->setFooter("Palace Bot by Valithor#5947")                             					// Set a footer without icon
								->setURL("");
							$message->channel->send('', array('embed' => $embed))->done(null, function ($error){
								echo "[ERROR] $error".PHP_EOL; //Echo any errors
							});
						}else{
							$author_channel->send($server_index[$x] . "```$output_string```")->done(null, function ($error){
								echo "[ERROR] $error".PHP_EOL; //Echo any errors
							});
						}
						$x++;
					}
				}
				return true;
				break;
			case "players":
				echo "[PLAYERS] $author_check" . PHP_EOL;
				include "../servers/getserverdata.php";
				
				$playerlist = " ";
				$alias = "<byond://" . $servers[0]["alias"] . ":" . $servers[0]["port"] . ">";
				$serverinfo0 = print_r($serverinfo[0], true); //json array
				foreach ($serverinfo[0] as $varname => $varvalue){
					if ( (substr($varname, 0, 6) == "player") && $varname != "players")
					$playerlist = $playerlist . "$varvalue, ";
				}
				if (trim(substr($playerlist, 0, -2)) == ""){
					$playerlist = "None";
				}else{
					$playerlist = trim(substr($playerlist, 0, -2));
				}
				$embed = new \CharlotteDunois\Yasmin\Models\MessageEmbed();
				$embed->setTitle("Civ13 Player List");
				//echo "image_path: " . $image_path . PHP_EOL;
				//$image_path = "http://www.valzargaming.com/servers/gamebanner.php?servernum=0";
			//	Build the embed message
				$embed
			//				->setTitle("$author_check")																// Set a title
					->setColor("e1452d")																	// Set a color (the thing on the left side)
					->addField("Server", "$alias\n" . $servers[0]["servername"])																// Set a description (below title, above fields)
					->addField("Players (" .($serverinfo[0]["players"] ?? "Offline").")", urldecode($playerlist))												// New line after this
					
			//		->setThumbnail("$author_avatar")														// Set a thumbnail (the image in the top right corner)
			//		->setImage('https://avatars1.githubusercontent.com/u/4529744?s=460&v=4')             	// Set an image (below everything except footer)
			//		->setImage("$image_path")             													// Set an image (below everything except footer)
					->setTimestamp()                                                                     	// Set a timestamp (gets shown next to footer)
			//		->setAuthor("$author_check", "$author_guild_avatar")  									// Set an author with icon
					->setFooter("Palace Bot by Valithor#5947")                             					// Set a footer without icon
					->setURL("");                             												// Set the URL
				
				if ($playerlist != "None"){
					$author_channel->send('', array('embed' => $embed))->done(null, function ($error){
						echo "[ERROR] $error".PHP_EOL; //Echo any errors
					});
					$sent = true;
				}
				
				$playerlist = " ";
				$alias = "<byond://" . $servers[1]["alias"] . ":" . $servers[1]["port"] . ">";
				$serverinfo1 = print_r($serverinfo[1], true); //json array
				foreach ($serverinfo[1] as $varname => $varvalue){
					if ( (substr($varname, 0, 6) == "player") && $varname != "players")
					$playerlist = $playerlist . "$varvalue, ";
				}
				if (trim(substr($playerlist, 0, -2)) == ""){
					$playerlist = "None";
				}else{
					$playerlist = trim(substr($playerlist, 0, -2));
				}
				//echo "image_path: " . $image_path . PHP_EOL;
			//	Build the embed message
				$embed
			//				->setTitle("$author_check")																// Set a title
					->setColor("e1452d")																	// Set a color (the thing on the left side)
					->addField("Server", "$alias\n" . $servers[1]["servername"])																// Set a description (below title, above fields)
					->addField("Players (" .($serverinfo[1]["players"] ?? "Offline").")", urldecode($playerlist))												// New line after this
					
			//		->setThumbnail("$author_avatar")														// Set a thumbnail (the image in the top right corner)
			//		->setImage('https://avatars1.githubusercontent.com/u/4529744?s=460&v=4')             	// Set an image (below everything except footer)
			//		->setImage("$image_path")             													// Set an image (below everything except footer)
					->setTimestamp()                                                                     	// Set a timestamp (gets shown next to footer)
			//		->setAuthor("$author_check", "$author_guild_avatar")  									// Set an author with icon
					->setFooter("Palace Bot by Valithor#5947")                             					// Set a footer without icon
					->setURL("");                             												// Set the URL
				
				if ($playerlist != "None"){
					$author_channel->send('', array('embed' => $embed))->done(null, function ($error){
						echo "[ERROR] $error".PHP_EOL; //Echo any errors
					});
					$sent = true;
				}
				
				$playerlist = " ";
				$alias = "<byond://" . $servers[2]["alias"] . ":" . $servers[2]["port"] . ">";
				$serverinfo2 = print_r($serverinfo[2], true); //json array
				foreach ($serverinfo[2] as $varname => $varvalue){
					if ( (substr($varname, 0, 6) == "player") && $varname != "players")
					$playerlist = $playerlist . "$varvalue, ";
				}
				if (trim(substr($playerlist, 0, -2)) == ""){
					$playerlist = "None";
				}else{
					$playerlist = trim(substr($playerlist, 0, -2));
				}
				//echo "image_path: " . $image_path . PHP_EOL;
			//	Build the embed message
				$embed
			//				->setTitle("$author_check")																// Set a title
					->setColor("e1452d")																	// Set a color (the thing on the left side)
					->addField("Server", "$alias\n" . $servers[2]["servername"])																// Set a description (below title, above fields)
					->addField("Players (" .($serverinfo[2]["players"] ?? "Offline").")", urldecode($playerlist))												// New line after this
					
			//		->setThumbnail("$author_avatar")														// Set a thumbnail (the image in the top right corner)
			//		->setImage('https://avatars1.githubusercontent.com/u/4529744?s=460&v=4')             	// Set an image (below everything except footer)
			//		->setImage("$image_path")             													// Set an image (below everything except footer)
					->setTimestamp()                                                                     	// Set a timestamp (gets shown next to footer)
			//		->setAuthor("$author_check", "$author_guild_avatar")  									// Set an author with icon
					->setFooter("Palace Bot by Valithor#5947")                             					// Set a footer without icon
					->setURL("");                             												// Set the URL
				
				if ($playerlist != "None"){
					$author_channel->send('', array('embed' => $embed))->done(null, function ($error){
						echo "[ERROR] $error".PHP_EOL; //Echo any errors
					});
					$sent = true;
				}
				if (!($sent)){
					$author_channel->send("No servers have any players!");
				}
				return true;
				break;
			case 'admins':
				echo "[ADMINS] $author_check" . PHP_EOL;
				include "../servers/getserverdata.php";
				$x=0;
				foreach ($serverinfo as $server){
					$admins = $serverinfo[$x]["admins"] ?? "N/A";
					$alias = "<byond://" . $servers[$x]["alias"] . ":$port>";
					$servername = $servers[$x]["servername"] ?? "None";
					//echo "image_path: " . $image_path . PHP_EOL;
				//	Build the embed message
					$embed = new \CharlotteDunois\Yasmin\Models\MessageEmbed();
					$embed
				//		->setTitle("$author_check")																// Set a title
						->setColor("e1452d")																	// Set a color (the thing on the left side)
						->setDescription($alias . "\n" . $servername )								// Set a description (below title, above fields)
						->addField("Admins", $admins)															// New line after this
						
				//		->setThumbnail("$author_avatar")														// Set a thumbnail (the image in the top right corner)
				//		->setImage('https://avatars1.githubusercontent.com/u/4529744?s=460&v=4')             	// Set an image (below everything except footer)
				//		->setImage("$image_path")             													// Set an image (below everything except footer)
						->setTimestamp()                                                                     	// Set a timestamp (gets shown next to footer)
				//		->setAuthor("$author_check", "$author_guild_avatar")  									// Set an author with icon
						->setFooter("Palace Bot by Valithor#5947")                             					// Set a footer without icon
						->setURL("");                             												// Set the URL
					
					$author_channel->send('', array('embed' => $embed))->done(null, function ($error){
						echo "[ERROR] $error".PHP_EOL; //Echo any errors
					});
					$x++;
				}
				return true;
				break;
		}
	
		if ($creator || $owner || $dev || $tech || $assistant) {
			switch($message_content_lower){
				case 'resume': //;resume
					echo "[RESUME] $author_check" .  PHP_EOL;
					//Trigger the php script remotely
					$ch = curl_init(); //create curl resource
					curl_setopt($ch, CURLOPT_URL, "http://10.0.0.18:81/civ13/resume.php"); // set url
					curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); //return the transfer as a string
					curl_setopt($ch, CURLOPT_POST, true);	  
					$message->reply(curl_exec($ch));
					return true;
					break;
				case 'save 1': //;save 1
					echo "[SAVE SLOT 1] $author_check" .  PHP_EOL;
					$manual_saving = VarLoad(NULL, "manual_saving.php");
					if ($manual_saving == true){
						if($react) $message->react("👎");
						$message->reply("A manual save is already in progress!");
					}else{
						if($react) $message->react("👍");
						VarSave(NULL, "manual_saving.php", true);
						$message->react("⏰")->then(function($author_channel) use ($message){	//Promise
							//Trigger the php script remotely
							$ch = curl_init(); //create curl resource
							curl_setopt($ch, CURLOPT_URL, "http://10.0.0.18:81/civ13/savemanual1.php"); // set url
							curl_setopt($ch, CURLOPT_POST, true);
							
							curl_setopt($ch, CURLOPT_USERAGENT, 'Palace Bot');
							
							curl_setopt($ch, CURLOPT_TIMEOUT, 1); 
							curl_setopt($ch, CURLOPT_HEADER, 0);
							curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
							curl_setopt($ch, CURLOPT_FORBID_REUSE, true);
							curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 1);
							curl_setopt($ch, CURLOPT_DNS_CACHE_TIMEOUT, 10);
							
							curl_setopt($ch, CURLOPT_FRESH_CONNECT, true);
							
							curl_exec($ch);
							curl_close($ch);
							
							
							$dt = new DateTime("now", new DateTimeZone('America/New_York'));  // convert UNIX timestamp to PHP DateTime
							$time = $dt->format('d-m-Y H:i:s'); // output = 2017-01-01 00:00:00
							$message->reply("$time EST");
							VarSave(NULL, "manual_saving.php", false);
							return true;
						});
					}
					return true;
					break;
				case 'save 2': //;save 2
					echo "[SAVE SLOT 2] $author_check" .  PHP_EOL;
					$manual_saving = VarLoad(NULL, "manual_saving.php");
					if ($manual_saving == true){
						if($react) $message->react("👎");
						$message->reply("A manual save is already in progress!");
					}else{
						if($react) $message->react("👍");
						VarSave(NULL, "manual_saving.php", true);
						$message->react("⏰")->then(function($author_channel) use ($message){	//Promise
							//Trigger the php script remotely
							$ch = curl_init(); //create curl resource
							curl_setopt($ch, CURLOPT_URL, "http://10.0.0.18:81/civ13/savemanual2.php"); // set url
							curl_setopt($ch, CURLOPT_POST, true);
							
							curl_setopt($ch, CURLOPT_USERAGENT, 'Palace Bot');
							
							curl_setopt($ch, CURLOPT_TIMEOUT, 1); 
							curl_setopt($ch, CURLOPT_HEADER, 0);
							curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
							curl_setopt($ch, CURLOPT_FORBID_REUSE, true);
							curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 1);
							curl_setopt($ch, CURLOPT_DNS_CACHE_TIMEOUT, 10);
							
							curl_setopt($ch, CURLOPT_FRESH_CONNECT, true);
							
							curl_exec($ch);
							curl_close($ch);
							
							$dt = new DateTime("now", new DateTimeZone('America/New_York'));  // convert UNIX timestamp to PHP DateTime
							$time = $dt->format('d-m-Y H:i:s'); // output = 2017-01-01 00:00:00
							$message->reply("$time EST");
							VarSave(NULL, "manual_saving.php", false);
							return true;
						});
					}
					return true;
					break;
				case 'save 3': //;save 3
					echo "[SAVE SLOT 3] $author_check" .  PHP_EOL;
					$manual_saving = VarLoad(NULL, "manual_saving.php");
					if ($manual_saving == true){
						if($react) $message->react("👎");
						$message->reply("A manual save is already in progress!");
					}else{
						if($react) $message->react("👍");
						VarSave(NULL, "manual_saving.php", true);
						$message->react("⏰")->then(function($author_channel) use ($message){	//Promise
							//Trigger the php script remotely
							$ch = curl_init(); //create curl resource
							curl_setopt($ch, CURLOPT_URL, "http://10.0.0.18:81/civ13/savemanual3.php"); // set url
							curl_setopt($ch, CURLOPT_POST, true);
							
							curl_setopt($ch, CURLOPT_USERAGENT, 'Palace Bot');
							
							curl_setopt($ch, CURLOPT_TIMEOUT, 1); 
							curl_setopt($ch, CURLOPT_HEADER, 0);
							curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
							curl_setopt($ch, CURLOPT_FORBID_REUSE, true);
							curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 1);
							curl_setopt($ch, CURLOPT_DNS_CACHE_TIMEOUT, 10);
							
							curl_setopt($ch, CURLOPT_FRESH_CONNECT, true);
							
							curl_exec($ch);
							curl_close($ch);
							
							$dt = new DateTime("now", new DateTimeZone('America/New_York'));  // convert UNIX timestamp to PHP DateTime
							$time = $dt->format('d-m-Y H:i:s'); // output = 2017-01-01 00:00:00
							$message->reply("$time EST");
							VarSave(NULL, "manual_saving.php", false);
							return true;
						});
					}
					return true;
					break;
				case 'delete 1': //;delete 1
					if( !($creator || $owner || $dev) ){
						return true;
						break;
					}
					echo "[DELETE SLOT 1] $author_check" . PHP_EOL;
					if($react) $message->react("👍");
					$message->react("⏰")->then(function($author_channel) use ($message){	//Promise
						//Trigger the php script remotely
						$ch = curl_init(); //create curl resource
						curl_setopt($ch, CURLOPT_URL, "http://10.0.0.18:81/civ13/deletemanual1.php"); // set url
						curl_setopt($ch, CURLOPT_POST, true);
						
						curl_setopt($ch, CURLOPT_USERAGENT, 'Palace Bot');
						
						curl_setopt($ch, CURLOPT_TIMEOUT, 1); 
						curl_setopt($ch, CURLOPT_HEADER, 0);
						curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
						curl_setopt($ch, CURLOPT_FORBID_REUSE, true);
						curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 1);
						curl_setopt($ch, CURLOPT_DNS_CACHE_TIMEOUT, 10);
						
						curl_setopt($ch, CURLOPT_FRESH_CONNECT, true);
						
						curl_exec($ch);
						curl_close($ch);
						
						$dt = new DateTime("now", new DateTimeZone('America/New_York'));  // convert UNIX timestamp to PHP DateTime
						$time = $dt->format('d-m-Y H:i:s'); // output = 2017-01-01 00:00:00
						$message->reply("$time EST");
						return true;
					});
					return true;
					break;
			}
		}
		if ($creator || $owner || $dev || $tech){
			switch($message_content_lower){
				case 'load 1': //;load 1
					echo "[LOAD SLOT 1] $author_check" . PHP_EOL;
					if($react) $message->react("👍");
					$message->react("⏰")->then(function($author_channel) use ($message){	//Promise
						//Trigger the php script remotely
						$ch = curl_init(); //create curl resource
						curl_setopt($ch, CURLOPT_URL, "http://10.0.0.18:81/civ13/loadmanual1.php"); // set url
						curl_setopt($ch, CURLOPT_POST, true);
							
						curl_setopt($ch, CURLOPT_USERAGENT, 'Palace Bot');
						
						curl_setopt($ch, CURLOPT_TIMEOUT, 1); 
						curl_setopt($ch, CURLOPT_HEADER, 0);
						curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
						curl_setopt($ch, CURLOPT_FORBID_REUSE, true);
						curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 1);
						curl_setopt($ch, CURLOPT_DNS_CACHE_TIMEOUT, 10);
						
						curl_setopt($ch, CURLOPT_FRESH_CONNECT, true);
						
						curl_exec($ch);
						curl_close($ch);
							
						$dt = new DateTime("now", new DateTimeZone('America/New_York'));  // convert UNIX timestamp to PHP DateTime
						$time = $dt->format('d-m-Y H:i:s'); // output = 2017-01-01 00:00:00
						$message->reply("$time EST");
						return true;
					});
					return true;
					break;
				case 'load 2': //;load 2 
					echo "[LOAD SLOT 2] $author_check" . PHP_EOL;
					if($react) $message->react("👍");
					$message->react("⏰")->then(function($author_channel) use ($message){	//Promise
						//Trigger the php script remotely
						$ch = curl_init(); //create curl resource
						curl_setopt($ch, CURLOPT_URL, "http://10.0.0.18:81/civ13/loadmanual2.php"); // set url
						curl_setopt($ch, CURLOPT_POST, true);
							
						curl_setopt($ch, CURLOPT_USERAGENT, 'Palace Bot');
						
						curl_setopt($ch, CURLOPT_TIMEOUT, 1); 
						curl_setopt($ch, CURLOPT_HEADER, 0);
						curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
						curl_setopt($ch, CURLOPT_FORBID_REUSE, true);
						curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 1);
						curl_setopt($ch, CURLOPT_DNS_CACHE_TIMEOUT, 10);
						
						curl_setopt($ch, CURLOPT_FRESH_CONNECT, true);
						
						curl_exec($ch);
						curl_close($ch);
						
						$dt = new DateTime("now", new DateTimeZone('America/New_York'));  // convert UNIX timestamp to PHP DateTime
						$time = $dt->format('d-m-Y H:i:s'); // output = 2017-01-01 00:00:00
						$message->reply("$time EST");
						return true;
					});
					return true;
					break;
				case 'load 3': //;load 3
					echo "[LOAD SLOT 3] $author_check" . PHP_EOL;
					if($react) $message->react("👍");
					$message->react("⏰")->then(function($author_channel) use ($message){	//Promise
						//Trigger the php script remotely
						$ch = curl_init(); //create curl resource
						curl_setopt($ch, CURLOPT_URL, "http://10.0.0.18:81/civ13/loadmanual3.php"); // set url
						curl_setopt($ch, CURLOPT_POST, true);
							
						curl_setopt($ch, CURLOPT_USERAGENT, 'Palace Bot');
						
						curl_setopt($ch, CURLOPT_TIMEOUT, 1); 
						curl_setopt($ch, CURLOPT_HEADER, 0);
						curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
						curl_setopt($ch, CURLOPT_FORBID_REUSE, true);
						curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 1);
						curl_setopt($ch, CURLOPT_DNS_CACHE_TIMEOUT, 10);
						
						curl_setopt($ch, CURLOPT_FRESH_CONNECT, true);
						
						curl_exec($ch);
						curl_close($ch);				
						
						$dt = new DateTime("now", new DateTimeZone('America/New_York'));  // convert UNIX timestamp to PHP DateTime
						$time = $dt->format('d-m-Y H:i:s'); // output = 2017-01-01 00:00:00
						$message->reply("$time EST");
						return true;
					});
					return true;
					break;
				case 'load1h': //;load1h
					echo "[LOAD 1H] $author_check" . PHP_EOL;
					if($react) $message->react("👍");
					$message->react("⏰")->then(function($author_channel) use ($message){	//Promise
						//Trigger the php script remotely
						$ch = curl_init(); //create curl resource
						curl_setopt($ch, CURLOPT_URL, "http://10.0.0.18:81/civ13/load1h.php"); // set url
						curl_setopt($ch, CURLOPT_POST, true);
							
						curl_setopt($ch, CURLOPT_USERAGENT, 'Palace Bot');
						
						curl_setopt($ch, CURLOPT_TIMEOUT, 1); 
						curl_setopt($ch, CURLOPT_HEADER, 0);
						curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
						curl_setopt($ch, CURLOPT_FORBID_REUSE, true);
						curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 1);
						curl_setopt($ch, CURLOPT_DNS_CACHE_TIMEOUT, 10);
						
						curl_setopt($ch, CURLOPT_FRESH_CONNECT, true);
						
						curl_exec($ch);
						curl_close($ch);
						
						$dt = new DateTime("now", new DateTimeZone('America/New_York'));  // convert UNIX timestamp to PHP DateTime
						$time = $dt->format('d-m-Y H:i:s'); // output = 2017-01-01 00:00:00
						$message->reply("$time EST");
						return true;
					});
					return true;
					break;
				case 'load2h': //;load2h
					echo "[LOAD 2H] $author_check" . PHP_EOL;
					if($react) $message->react("👍");
					$message->react("⏰")->then(function($author_channel) use ($message){	//Promise
						//Trigger the php script remotely
						$ch = curl_init(); //create curl resource
						curl_setopt($ch, CURLOPT_URL, "http://10.0.0.18:81/civ13/load2h.php"); // set url
						curl_setopt($ch, CURLOPT_POST, true);
							
						curl_setopt($ch, CURLOPT_USERAGENT, 'Palace Bot');
						
						curl_setopt($ch, CURLOPT_TIMEOUT, 1); 
						curl_setopt($ch, CURLOPT_HEADER, 0);
						curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
						curl_setopt($ch, CURLOPT_FORBID_REUSE, true);
						curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 1);
						curl_setopt($ch, CURLOPT_DNS_CACHE_TIMEOUT, 10);
						
						curl_setopt($ch, CURLOPT_FRESH_CONNECT, true);
						
						curl_exec($ch);
						curl_close($ch);
						
						$dt = new DateTime("now", new DateTimeZone('America/New_York'));  // convert UNIX timestamp to PHP DateTime
						$time = $dt->format('d-m-Y H:i:s'); // output = 2017-01-01 00:00:00
						$message->reply("$time EST");
						return true;
					});
					return true;
					break;
				case 'host persistence':
				case 'host pers':
					echo "[HOST PERSISTENCE] $author_check" . PHP_EOL;
					$message->react("⏰")->then(function($author_channel) use ($message){	//Promise
						//Trigger the php script remotely
						$ch = curl_init(); //create curl resource
						curl_setopt($ch, CURLOPT_URL, "http://10.0.0.18:81/civ13/host.php"); // set url
						curl_setopt($ch, CURLOPT_POST, true);
							
						curl_setopt($ch, CURLOPT_USERAGENT, 'Palace Bot');
						
						curl_setopt($ch, CURLOPT_TIMEOUT, 1); 
						curl_setopt($ch, CURLOPT_HEADER, 0);
						curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
						curl_setopt($ch, CURLOPT_FORBID_REUSE, true);
						curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 1);
						curl_setopt($ch, CURLOPT_DNS_CACHE_TIMEOUT, 10);
						
						curl_setopt($ch, CURLOPT_FRESH_CONNECT, true);
						
						curl_exec($ch);
						curl_close($ch);
						
						/*
						$dt = new DateTime("now", new DateTimeZone('America/New_York'));  // convert UNIX timestamp to PHP DateTime
						$time = $dt->format('d-m-Y H:i:s'); // output = 2017-01-01 00:00:00
						$message->reply("$time EST");
						*/
						if($react) $message->react("👍");
						return true;
					});
					return true;
					break;
				case 'kill persistence':
				case 'kill pers':
					echo "[HOST PERSISTENCE] $author_check" . PHP_EOL;
					$message->react("⏰")->then(function($author_channel) use ($message){	//Promise
						//Trigger the php script remotely
						$ch = curl_init(); //create curl resource
						curl_setopt($ch, CURLOPT_URL, "http://10.0.0.18:81/civ13/kill.php"); // set url
						curl_setopt($ch, CURLOPT_POST, true);
							
						curl_setopt($ch, CURLOPT_USERAGENT, 'Palace Bot');
						
						curl_setopt($ch, CURLOPT_TIMEOUT, 1); 
						curl_setopt($ch, CURLOPT_HEADER, 0);
						curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
						curl_setopt($ch, CURLOPT_FORBID_REUSE, true);
						curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 1);
						curl_setopt($ch, CURLOPT_DNS_CACHE_TIMEOUT, 10);
						
						curl_setopt($ch, CURLOPT_FRESH_CONNECT, true);
						
						curl_exec($ch);
						curl_close($ch);
						/*
						$dt = new DateTime("now", new DateTimeZone('America/New_York'));  // convert UNIX timestamp to PHP DateTime
						$time = $dt->format('d-m-Y H:i:s'); // output = 2017-01-01 00:00:00
						$message->reply("$time EST");
						*/
						if($react) $message->react("👍");
						return true;
					});
					return true;
					break;
				case 'update persistence':
				case 'update pers':
					echo "[HOST PERSISTENCE] $author_check" . PHP_EOL;
					/*
					$message->react("⏰")->then(function($author_channel) use ($message){	//Promise
						//Trigger the php script remotely
						$ch = curl_init(); //create curl resource
						curl_setopt($ch, CURLOPT_URL, "http://10.0.0.18:81/civ13/update.php"); // set url
						curl_setopt($ch, CURLOPT_POST, true);
							
						curl_setopt($ch, CURLOPT_USERAGENT, 'Palace Bot');
						
						curl_setopt($ch, CURLOPT_TIMEOUT, 1); 
						curl_setopt($ch, CURLOPT_HEADER, 0);
						curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
						curl_setopt($ch, CURLOPT_FORBID_REUSE, true);
						curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 1);
						curl_setopt($ch, CURLOPT_DNS_CACHE_TIMEOUT, 10);
						
						curl_setopt($ch, CURLOPT_FRESH_CONNECT, true);
						
						curl_exec($ch);
						curl_close($ch);
						
						$dt = new DateTime("now", new DateTimeZone('America/New_York'));  // convert UNIX timestamp to PHP DateTime
						$time = $dt->format('d-m-Y H:i:s'); // output = 2017-01-01 00:00:00
						$message->reply("$time EST");
						
						if($react) $message->react("👍");
						return true;
					});
					*/
					if($react) $message->react("👎");
					return true;
					break;
			}
		}
		if ($creator || $owner || $dev){
			switch($message_content_lower){
				case '?status': //;?status
					include "../servers/getserverdata.php";
					$debug = var_export($serverinfo, true);
					if ($debug) $author_channel->send(urldecode($debug));
					else $author_channel->send("No debug info found!");
					return true;
					break;
				case 'pause': //;pause
					//Trigger the php script remotely
					$ch = curl_init(); //create curl resource
					curl_setopt($ch, CURLOPT_URL, "http://10.0.0.18:81/civ13/pause.php"); // set url
					curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); //return the transfer as a string
					curl_setopt($ch, CURLOPT_POST, true);
					$message->reply(curl_exec($ch));
					return true;
					break;
				case 'loadnew': //;loadnew
					//Trigger the php script remotely
					$ch = curl_init(); //create curl resource
					curl_setopt($ch, CURLOPT_URL, "http://10.0.0.18:81/civ13/loadnew.php"); // set url
					curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); //return the transfer as a string
					curl_setopt($ch, CURLOPT_POST, true);
					$message->reply(curl_exec($ch));
					return true;
					break;
				case 'VM_restart': //;VM_restart
					if ( !($creator || $dev) ){
						return true;
						break;
					}
					//Trigger the php script remotely
					$ch = curl_init(); //create curl resource
					curl_setopt($ch, CURLOPT_URL, "http://10.0.0.18:81/civ13/VM_restart.php"); // set url
					curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); //return the transfer as a string
					curl_setopt($ch, CURLOPT_POST, true);
					$message->reply(curl_exec($ch));
					return true;
					break;
			}		}
		if ($creator){
			switch($message_content_lower){
				case 'crash': //;crash
					if($react) $message->react("☠️");
					throw new \CharlotteDunois\Events\UnhandledErrorException('Unhandled error event', 0, (($arguments[0] ?? null) instanceof \Throwable ? $arguments[0] : null));
					return true;
					break;
			}
		}
	}
	/*
	if ($author_id == "352898973578690561"){ //magmacreeper
		if ($message_content_lower == 'start'){ //;start
			echo "[START] $author_check" .  PHP_EOL;
			//Trigger the php script remotely
			$ch = curl_init(); //create curl resource
			curl_setopt($ch, CURLOPT_URL, "http://10.0.0.97/magmacreeper/start.php"); // set url
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); //return the transfer as a string
			curl_setopt($ch, CURLOPT_POST, true);	  
			$message->reply(curl_exec($ch));
			return true;
		}
		if ($message_content_lower == 'pull'){ //;pull
			echo "[START] $author_check" .  PHP_EOL;
			//Trigger the php script remotely
			$ch = curl_init(); //create curl resource
			curl_setopt($ch, CURLOPT_URL, "http://10.0.0.97/magmacreeper/pull.php"); // set url
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); //return the transfer as a string
			curl_setopt($ch, CURLOPT_POST, true);	  
			$message->reply(curl_exec($ch));
			return true;
		}
	}
	*/

	if ($creator || $owner || $dev || $admin || $mod){ //Only allow these roles to use this
		if (substr($message_content_lower, 0, 5) == 'kick '){ //;kick
			echo "[KICK]" . PHP_EOL;
			//Get an array of people mentioned
			$mentions_arr 	= $message->mentions->users; 									//echo "mentions_arr: " . PHP_EOL; var_dump ($mentions_arr); //Shows the collection object
			$GetMentionResult = GetMention([&$author_guild, $message_content_lower, "kick ", 1, &$restcord]);
			if (!(is_array($GetMentionResult))) return $message->reply("Invalid input! Please enter a valid ID or @mention the user");
			if ($GetMentionResult[1] == NULL){
				echo "GetMentionResult['restcord_user']: "; echo PHP_EOL; print_r($GetMentionResult['restcord_user']); echo PHP_EOL;
				if ($GetMentionResult['restcord_user_found'] === true){
					echo "[RESTCORD KICK]" . PHP_EOL;
					try{
						$restcord->guild->removeGuildMember(['guild.id' => intval($author_guild_id), 'user.id' => intval($GetMentionResult['restcord_user']->id)]);
						echo "[RESTCORD KICK DONE]" . PHP_EOL;
						if($react) $message->react("🥾");
					}catch (Throwable $e){
						if($react) $message->react("👎");
						echo "[RESTCORD] Unable to kick user!" . PHP_EOL;
					}
				} else return $message->reply("User not found in the guild!");
				return $message->reply("User not found in the guild!");
			}
			$mention_user = GetMentionResult[0];
			$mention_member = GetMentionResult[1];
			$mentions_arr = $mentions_arr ?? GetMentionResult[2];
			foreach ( $mentions_arr as $mention_param ){
				$mention_param_encode 									= json_encode($mention_param); 									//echo "mention_param_encode: " . $mention_param_encode . PHP_EOL;
				$mention_json 											= json_decode($mention_param_encode, true); 					//echo "mention_json: " . PHP_EOL; var_dump($mention_json);
				$mention_id 											= $mention_json['id']; 											//echo "mention_id: " . $mention_id . PHP_EOL; //Just the discord ID
				$mention_discriminator 									= $mention_json['discriminator']; 								//echo "mention_id: " . $mention_id . PHP_EOL; //Just the discord ID
				$mention_username 										= $mention_json['username']; 									//echo "mention_username: " . $mention_username . PHP_EOL; //Just the discord ID
				$mention_check 											= $mention_username ."#".$mention_discriminator;
				
				
				if ($author_id != $mention_id){ //Don't let anyone kick themselves
					//Get the roles of the mentioned user
					$target_guildmember 								= $message->guild->members->get($mention_id); 	//This is a GuildMember object
					$target_guildmember_role_collection 				= $target_guildmember->roles;					//This is the Role object for the GuildMember
					
		//  				Get the avatar URL of the mentioned user
		//					$target_guildmember_user							= $target_guildmember->user;									//echo "member_class: " . get_class($target_guildmember_user) . PHP_EOL;
		//					$mention_avatar 									= "{$target_guildmember_user->getAvatarURL()}";					//echo "mention_avatar: " . $mention_avatar . PHP_EOL;				//echo "target_guildmember_role_collection: " . (count($target_guildmember_role_collection)-1);
					
		//  				Populate arrays of the info we need
		//  				$target_guildmember_roles_names 					= array();
					$x=0;
					$target_dev = false;
					$target_owner = false;
					$target_admin = false;
					$target_mod = false;
					$target_vzgbot = false;
					$target_guildmember_roles_ids = array();
					foreach ($target_guildmember_role_collection as $role){
						if ($x!=0){ //0 is @everyone so skip it
							$target_guildmember_roles_ids[] 						= $role->id; 													//echo "role[$x] id: " . PHP_EOL; //var_dump($role->id);
							if ($role->id == $role_18_id)		$target_adult 		= true;							//Author has the 18+ role
							if ($role->id == $role_dev_id)    	$target_dev 		= true;							//Author has the dev role
							if ($role->id == $role_owner_id)    $target_owner	 	= true;							//Author has the owner role
							if ($role->id == $role_admin_id)	$target_admin 		= true;							//Author has the admin role
							if ($role->id == $role_mod_id)		$target_mod 		= true;							//Author has the mod role
							if ($role->id == $role_verified_id)	$target_verified 	= true;							//Author has the verified role
							if ($role->id == $role_bot_id)		$target_bot 		= true;							//Author has the bot role
							if ($role->id == $role_vzgbot_id)	$target_vzgbot 		= true;							//Author is this bot
							if ($role->id == $role_muted_id)	$target_muted 		= true;							//Author is this bot
						}
						$x++;
					}
					if( (!$target_dev && !$target_owner && !$target_admin && !$target_mod && !$target_vzg) || ($creator || $owner || $dev)){ //Guild owner and bot creator can kick anyone
						if ($mention_id == $creator_id) return true; //Don't kick the creator
						//Build the string to log
						$filter = "kick <@!$mention_id>";
						$warndate = date("m/d/Y");
						$reason = "**🥾Kicked:** <@$mention_id>
						**🗓️Date:** $warndate
						**📝Reason:** " . str_replace($filter, "", $message_content);
						//Kick the user
						$target_guildmember->kick($reason)->done(null, function ($error){
							echo "[ERROR] $error".PHP_EOL; //Echo any errors
						});
						if($react) $message->react("🥾"); //Boot
						//Build the embed message
						$embed = new \CharlotteDunois\Yasmin\Models\MessageEmbed();
						$embed
		//							->setTitle("Commands")																	// Set a title
							->setColor("e1452d")																	// Set a color (the thing on the left side)
							->setDescription("$reason")																// Set a description (below title, above fields)
		//							->addField("⠀", "$reason")																// New line after this
							
		//							->setThumbnail("$author_avatar")														// Set a thumbnail (the image in the top right corner)
		//							->setImage('https://avatars1.githubusercontent.com/u/4529744?s=460&v=4')             	// Set an image (below everything except footer)
							->setTimestamp()                                                                     	// Set a timestamp (gets shown next to footer)
							->setAuthor("$author_check ($author_id)", "$author_avatar")  									// Set an author with icon
							->setFooter("Palace Bot by Valithor#5947")                             					// Set a footer without icon
							->setURL("");                             												// Set the URL
		//						Send the message
						if($modlog_channel)$modlog_channel->send('', array('embed' => $embed))->done(null, function ($error){
							echo "[ERROR] $error".PHP_EOL; //Echo any errors
						});
						return true;
					}else{//Target is not allowed to be kicked
						$author_channel->send("<@$mention_id> cannot be kicked because of their roles!");
						return true;
					}
				}else{
					if($react) $message->react("👎");
					$author_channel->send("<@$author_id>, you can't kick yourself!");
					return true;
				}
			} //foreach method didn't return, so nobody was mentioned
			if($react) $message->react("👎");
			$author_channel->send("<@$author_id>, you need to mention someone!");
			return true;
		}
		if (substr($message_content_lower, 0, 5) == 'mute '){ //;mute
			echo "[MUTE]" . PHP_EOL;
	//			Get an array of people mentioned
			$mentions_arr 												= $message->mentions->users;
			if (!strpos($message_content_lower, "<")){ //String doesn't contain a mention
				$filter = "mute ";
				$value = str_replace($filter, "", $message_content_lower);
				$value = str_replace("<@!", "", $value); $value = str_replace("<@", "", $value);
				$value = str_replace(">", "", $value);//echo "value: " . $value . PHP_EOL;
				if(is_numeric($value)){
					$mention_member				= $author_guild->members->get($value);
					$mention_user				= $mention_member->user;
					$mentions_arr				= array($mention_user);
				}else return $message->reply("Invalid input! Please enter a valid ID or @mention the user");
				if ($mention_member == NULL) return $message->reply("Invalid input! Please enter an ID or @mention the user");
			}
			foreach ( $mentions_arr as $mention_param ){
				$mention_param_encode 									= json_encode($mention_param); 									//echo "mention_param_encode: " . $mention_param_encode . PHP_EOL;
				$mention_json 											= json_decode($mention_param_encode, true); 					//echo "mention_json: " . PHP_EOL; var_dump($mention_json);
				$mention_id 											= $mention_json['id']; 											//echo "mention_id: " . $mention_id . PHP_EOL; //Just the discord ID
				$mention_discriminator 									= $mention_json['discriminator']; 								//echo "mention_id: " . $mention_id . PHP_EOL; //Just the discord ID
				$mention_username 										= $mention_json['username']; 									//echo "mention_username: " . $mention_username . PHP_EOL; //Just the discord ID
				$mention_check 											= $mention_username ."#".$mention_discriminator;
				
				
				if ($author_id != $mention_id){ //Don't let anyone mute themselves
					//Get the roles of the mentioned user
					$target_guildmember 								= $message->guild->members->get($mention_id); 	//This is a GuildMember object
					$target_guildmember_role_collection 				= $target_guildmember->roles;					//This is the Role object for the GuildMember
					
	//  			Populate arrays of the info we need
	//	    		$target_guildmember_roles_names 					= array();
					$x=0;
					$target_dev = false;
					$target_owner = false;
					$target_admin = false;
					$target_mod = false;
					$target_vzgbot = false;
					$target_guildmember_roles_ids = array();
					$removed_roles = array();
					foreach ($target_guildmember_role_collection as $role){
						if ($x!=0){ //0 is @everyone so skip it
							$removed_roles[] = $role->id;
							$target_guildmember_roles_ids[] 						= $role->id; 													//echo "role[$x] id: " . PHP_EOL; //var_dump($role->id);
							if ($role->id == $role_dev_id)    	$target_dev 		= true;							//Author has the dev role
							if ($role->id == $role_owner_id)    $target_owner	 	= true;							//Author has the owner role
							if ($role->id == $role_admin_id)	$target_admin 		= true;							//Author has the admin role
							if ($role->id == $role_mod_id)		$target_mod 		= true;							//Author has the mod role
							if ($role->id == $role_vzgbot_id)	$target_vzgbot 		= true;							//Author is this bot
						}
						$x++;
					}
					if( (!$target_dev && !$target_owner && !$target_admin && !$target_mod && !$target_vzg) || ($creator || $owner || $dev)){ //Guild owner and bot creator can mute anyone
						if ($mention_id == $creator_id) return true; //Don't mute the creator
						//Save current roles in a file for the user
						VarSave($guild_folder."/".$mention_id, "removed_roles.php", $removed_roles);
						//Build the string to log
						$filter = "mute <@!$mention_id>";
						$warndate = date("m/d/Y");
						$reason = "**🥾Muted:** <@$mention_id>
						**🗓️Date:** $warndate
						**📝Reason:** " . str_replace($filter, "", $message_content);
						//Remove all roles and add the muted role (TODO: REMOVE ALL ROLES AND RE-ADD THEM UPON BEING UNMUTED)
						foreach ($removed_roles as $role){
							$target_guildmember->removeRole($role);
						}
						if($role_muted_id) $target_guildmember->addRole($role_muted_id);
						if($react) $message->react("🤐");
						//Build the embed message
						$embed = new \CharlotteDunois\Yasmin\Models\MessageEmbed();
						$embed
	//							->setTitle("Commands")																	// Set a title
							->setColor("e1452d")																	// Set a color (the thing on the left side)
							->setDescription("$reason")																// Set a description (below title, above fields)
	//							->addField("⠀", "$reason")																// New line after this
							
	//							->setThumbnail("$author_avatar")														// Set a thumbnail (the image in the top right corner)
	//							->setImage('https://avatars1.githubusercontent.com/u/4529744?s=460&v=4')             	// Set an image (below everything except footer)
							->setTimestamp()                                                                     	// Set a timestamp (gets shown next to footer)
							->setAuthor("$author_check ($author_id)", "$author_avatar")  									// Set an author with icon
							->setFooter("Palace Bot by Valithor#5947")                             					// Set a footer without icon
							->setURL("");                             												// Set the URL
	//						Send the message
						if($modlog_channel)$modlog_channel->send('', array('embed' => $embed))->done(null, function ($error){
							echo "[ERROR] $error".PHP_EOL; //Echo any errors
						});
						return true;
					}else{//Target is not allowed to be muted
						$author_channel->send("<@$mention_id> cannot be muted because of their roles!");
						return true;
					}
				}else{
					if($react) $message->react("👎");
					$author_channel->send("<@$author_id>, you can't mute yourself!");
					return true;
				}
			} //foreach method didn't return, so nobody was mentioned
			if($react) $message->react("👎");
			$author_channel->send("<@$author_id>, you need to mention someone!");
			return true;
		}
		if (substr($message_content_lower, 0, 7) == 'unmute '){ //;unmute
			echo "[UNMUTE]" . PHP_EOL;
	//			Get an array of people mentioned
			$mentions_arr 												= $message->mentions->users; 									//echo "mentions_arr: " . PHP_EOL; var_dump ($mentions_arr); //Shows the collection object
			if (!strpos($message_content_lower, "<")){ //String doesn't contain a mention
				$filter = "unmute ";
				$value = str_replace($filter, "", $message_content_lower);
				$value = str_replace("<@!", "", $value); $value = str_replace("<@", "", $value);
				$value = str_replace(">", "", $value);//echo "value: " . $value . PHP_EOL;
				if(is_numeric($value)){
					$mention_member				= $author_guild->members->get($value);
					$mention_user				= $mention_member->user;
					$mentions_arr				= array($mention_user);
				}else return $message->reply("Invalid input! Please enter a valid ID or @mention the user");
				if ($mention_member == NULL) return $message->reply("Invalid input! Please enter an ID or @mention the user");
			}
			foreach ( $mentions_arr as $mention_param ){
				$mention_param_encode 									= json_encode($mention_param); 									//echo "mention_param_encode: " . $mention_param_encode . PHP_EOL;
				$mention_json 											= json_decode($mention_param_encode, true); 					//echo "mention_json: " . PHP_EOL; var_dump($mention_json);
				$mention_id 											= $mention_json['id']; 											//echo "mention_id: " . $mention_id . PHP_EOL; //Just the discord ID
				$mention_discriminator 									= $mention_json['discriminator']; 								//echo "mention_id: " . $mention_id . PHP_EOL; //Just the discord ID
				$mention_username 										= $mention_json['username']; 									//echo "mention_username: " . $mention_username . PHP_EOL; //Just the discord ID
				$mention_check 											= $mention_username ."#".$mention_discriminator;
				
				
				if ($author_id != $mention_id){ //Don't let anyone mute themselves
					//Get the roles of the mentioned user
					$target_guildmember 								= $message->guild->members->get($mention_id);
					$target_guildmember_role_collection 				= $target_guildmember->roles;

	//				Get the roles of the mentioned user
					$target_dev = false;
					$target_owner = false;
					$target_admin = false;
					$target_mod = false;
					$target_vzgbot = false;
	//				Populate arrays of the info we need
					$target_guildmember_roles_ids = array();
					$x=0;
					foreach ($target_guildmember_role_collection as $role){
						if ($x!=0){ //0 is @everyone so skip it
							$target_guildmember_roles_ids[] 						= $role->id; 													//echo "role[$x] id: " . PHP_EOL; //var_dump($role->id);
							if ($role->id == $role_dev_id)    	$target_dev 		= true;							//Author has the dev role
							if ($role->id == $role_owner_id)    $target_owner	 	= true;							//Author has the owner role
							if ($role->id == $role_admin_id)	$target_admin 		= true;							//Author has the admin role
							if ($role->id == $role_mod_id)		$target_mod 		= true;							//Author has the mod role
							if ($role->id == $role_vzgbot_id)	$target_vzgbot 		= true;							//Author is this bot
							if ($role->name == "Palace Bot")	$target_vzgbot 		= true;							//Author is this bot
						}
						$x++;
					}
					if( (!$target_dev && !$target_owner && !$target_admin && !$target_mod && !$target_vzg) || ($creator || $owner || $dev)){
						if ($mention_id == $creator_id) return true; //Don't mute the creator
						//Build the string to log
						$filter = "unmute <@!$mention_id>";
						$warndate = date("m/d/Y");
						$reason = "**🥾Unmuted:** <@$mention_id>
						**🗓️Date:** $warndate
						**📝Reason:** " . str_replace($filter, "", $message_content);
						//Unmute the user and readd the verified role (TODO: READD REMOVED ROLES)
						//Save current roles in a file for the user
						$removed_roles = VarLoad($guild_folder."/".$mention_id, "removed_roles.php");
						foreach ($removed_roles as $role){
							$target_guildmember->addRole($role);
						}
						if($role_muted_id) $target_guildmember->removeRole($role_muted_id);
						if($react) $message->react("😩");
						//Build the embed message
						$embed = new \CharlotteDunois\Yasmin\Models\MessageEmbed();
						$embed
	//							->setTitle("Commands")																	// Set a title
							->setColor("e1452d")																	// Set a color (the thing on the left side)
							->setDescription("$reason")																// Set a description (below title, above fields)
	//							->addField("⠀", "$reason")																// New line after this
							
	//							->setThumbnail("$author_avatar")														// Set a thumbnail (the image in the top right corner)
	//							->setImage('https://avatars1.githubusercontent.com/u/4529744?s=460&v=4')             	// Set an image (below everything except footer)
							->setTimestamp()                                                                     	// Set a timestamp (gets shown next to footer)
							->setAuthor("$author_check ($author_id)", "$author_avatar")  							// Set an author with icon
							->setFooter("Palace Bot by Valithor#5947")                             					// Set a footer without icon
							->setURL("");                             												// Set the URL
	//						Send the message
						if($modlog_channel)$modlog_channel->send('', array('embed' => $embed))->done(null, function ($error){
							echo "[ERROR] $error".PHP_EOL; //Echo any errors
						});
						return true;
					}else{//Target is not allowed to be unmuted
						$author_channel->send("<@$mention_id> cannot be unmuted because of their roles!");
						return true;
					}
				}else{
					if($react) $message->react("👎");
					$author_channel->send("<@$author_id>, you can't mute yourself!");
					return true;
				}
			} //foreach method didn't return, so nobody was mentioned
			if($react) $message->react("👎");
			$author_channel->send("<@$author_id>, you need to mention someone!");
			return true;
		}
		if ( (substr($message_content_lower, 0, 2) == 'v ') || (substr(($message_content), 0, 7) == 'verify ') ){ //Verify ;v ;verify
			if ( ($role_verified_id != "") || ($role_verified_id != NULL) ){ //This command only works if the Verified Role is setup
				echo "[VERIFY] $author_check" . PHP_EOL;
			//	Get an array of people mentioned
				$mentions_arr 												= $message->mentions->users; 									//echo "mentions_arr: " . PHP_EOL; var_dump ($mentions_arr); //Shows the collection object
				$mention_role_name_queue_default							= "<@$author_id> verified the following users:" . PHP_EOL;
				$mention_role_name_queue_full 								= $mention_role_name_queue_default;
				
				$filter = "v ";
				$value = str_replace($filter, "", $message_content_lower);
				$filter = "verify ";
				$value = str_replace($filter, "", $value);
				$value = str_replace("<@!", "", $value); $value = str_replace("<@", "", $value); $value = str_replace("<@", "", $value); $value = str_replace(">", "", $value);
				
				if(is_numeric($value)){
					$mention_member				= $author_guild->members->get($value);
					$mention_user				= $mention_member->user;
					$mentions_arr				= array($mention_user);
				}else return $message->reply("Invalid input! Please enter a valid ID or @mention the user.");
				if ($mention_member == NULL) return $message->reply("Invalid ID or user not found! Are they in the server?"); //User not found
				
				foreach ( $mentions_arr as $mention_param ){																				//echo "mention_param: " . PHP_EOL; var_dump ($mention_param);
			//		id, username, discriminator, bot, avatar, email, mfaEnabled, verified, webhook, createdTimestamp
					$mention_param_encode 									= json_encode($mention_param); 									//echo "mention_param_encode: " . $mention_param_encode . PHP_EOL;
					$mention_json 											= json_decode($mention_param_encode, true); 					//echo "mention_json: " . PHP_EOL; var_dump($mention_json);
					$mention_id 											= $mention_json['id']; 											//echo "mention_id: " . $mention_id . PHP_EOL; //Just the discord ID
					
			//		$mention_discriminator 									= $mention_json['discriminator']; 								//echo "mention_discriminator: " . $mention_discriminator . PHP_EOL; //Just the discord ID
			//		$mention_check 											= $mention_username ."#".$mention_discriminator; 				//echo "mention_check: " . $mention_check . PHP_EOL; //Just the discord ID
					
			//		Get the roles of the mentioned user
					$target_guildmember 									= $message->guild->members->get($mention_id);
					$target_guildmember_role_collection 					= $target_guildmember->roles;									//echo "target_guildmember_role_collection: " . (count($author_guildmember_role_collection)-1);

			//		Get the avatar URL of the mentioned user
					$target_guildmember_user								= $target_guildmember->user;									//echo "member_class: " . get_class($target_guildmember_user) . PHP_EOL;
					$mention_avatar 										= "{$target_guildmember_user->getAvatarURL()}";					//echo "mention_avatar: " . $mention_avatar . PHP_EOL;				//echo "target_guildmember_role_collection: " . (count($target_guildmember_role_collection)-1);
					
					$target_verified										= false; //Default
					$x=0;
					foreach ($target_guildmember_role_collection as $role){
						if ($x!=0){ //0 is @everyone so skip it
							if ($role->id == $role_verified_id)
								$target_verified 							= true;
						}
						$x++;
					}
					if($target_verified == false){ //Add the verified role to the member
						$target_guildmember->addRole($role_verified_id)->done(
							function ($error) {
								echo "[ERROR] $error".PHP_EOL;
							}
						); //echo "Verify role added ($role_verified_id)" . PHP_EOL;
					
			//			Build the embed
						$embed = new \CharlotteDunois\Yasmin\Models\MessageEmbed();
						$embed
			//				->setTitle("Roles")																		// Set a title
							->setColor("e1452d")																	// Set a color (the thing on the left side)
			//				->setDescription("$author_guild_name")													// Set a description (below title, above fields)
							->addField("Verified", 		"<@$mention_id>")											// New line after this if ,true

							->setThumbnail("$mention_avatar")														// Set a thumbnail (the image in the top right corner)
			//				->setImage('https://avatars1.githubusercontent.com/u/4529744?s=460&v=4')             	// Set an image (below everything except footer)
							->setTimestamp()                                                                     	// Set a timestamp (gets shown next to footer)
							->setAuthor("$author_check", "$author_avatar")  									// Set an author with icon
							->setFooter("Palace Bot by Valithor#5947")                             					// Set a footer without icon
							->setURL("");                             												// Set the URL
			//			Send the message
						if($react) $message->react("👍");
						//Log the verification
						if($verifylog_channel){
							$verifylog_channel->send('', array('embed' => $embed))->done(null, function ($error){
								echo "[ERROR] $error".PHP_EOL; //Echo any errors
							});
						}elseif($modlog_channel){
							$modlog_channel->send('', array('embed' => $embed))->done(null, function ($error){
								echo "[ERROR] $error".PHP_EOL; //Echo any errors
							});
						}
						//Welcome the verified user
						if($general_channel){
							$msg = "Welcome to $author_guild_name, <@$mention_id>!";
							if($rolepicker_channel) $msg = $msg . " Feel free to pick out some roles in <#$rolepicker_channel_id>.";
							if($general_channel)$general_channel->send($msg);
						}
						return true;
					}else{
						if($react) $message->react("👎");
						$message->reply("$mention_check does not need to be verified!" . PHP_EOL);
						return true;
					}
				}
			}
		}
		if ( ($message_content_lower == 'cv') || ( $message_content_lower == 'clearv') ){ //;clearv ;cv Clear all messages in the get-verified channel
			if( ($getverified_channel_id != "") || ($getverified_channel_id != NULL)){ //This command only works if the Get Verified Channel is setup
				echo "[CV] $author_check" . PHP_EOL;
				if($getverified_channel){
					$getverified_channel->bulkDelete(100);
					//Delete any messages that aren't cached
					$getverified_channel->fetchMessages()->then(function($message_collection) use ($getverified_channel){
						foreach ($message_collection as $message){
							$getverified_channel->message->delete();
						}
					});
					$getverified_channel->send("Welcome to $author_guild_name! Please take a moment to read the rules and fill out the questions below:
					1. How did you find the server?
					2. How old are you?
					3. Do you understand the rules?
					4. Do you have any other questions?");
				}
				return true;
			}
		}
		if (substr($message_content_lower, 0, 5) == 'poll '){ //;poll
			echo "[POLL] $author_check" . PHP_EOL;
			$filter = "poll ";
			$poll = str_replace($filter, "", $message_content);
			$filter = "@";
			$poll = str_replace($filter, "@ ", $poll);
			$arr = explode(" ", $message_content);
			$duration = $arr[1];
			$poll = str_replace($duration, "", $poll);
			if( ($poll != "" && $poll != NULL) && is_numeric($duration) ){
				$author_channel->send("**VOTE TIME! ($duration seconds)**\n`".trim($poll)."`")->then(function($message) use ($discord, $author_channel, $duration){
					$message->react("👍");
					$message->react("👎");
					$discord->addTimer($duration, function() use ($message, $author_channel) {
						$reactions = $message->reactions;
						$yes_count = 0;
						$no_count = 0;
						foreach ($reactions as $reaction){
							$emoji = $reaction->emoji;
							$count = $reaction->count;
							if ($emoji == "👍")
								$yes_count = $count;
							if ($emoji == "👎")
								$no_count = $count;
						}
						//Count reacts
						$count = ($yes_count - $no_count);
						if ($count == 0){
								$author_channel->send("**Vote tied! ($yes_count:$no_count)**");
								return true;
						}
						if ($count > 0){
								$author_channel->send("**Vote passed! ($yes_count:$no_count)**");
								return true;
						}
						if ($count < 0){
								$author_channel->send("**Vote failed! ($yes_count:$no_count)**");
								return true;
						}
					});
					return true;
				});
			}else return $message->reply("Invalid input!");
		}
		if (substr($message_content_lower, 0, 6) == 'whois '){ //;whois
			echo "[WHOIS] $author_check" . PHP_EOL;			
			$filter = "whois ";
			$value = str_replace($filter, "", $message_content_lower);
			$value = str_replace("<@!", "", $value); $value = str_replace("<@", "", $value); $value = str_replace("<@", "", $value); 
			$value = str_replace(">", "", $value);
			$value = trim($value);
			if(is_numeric($value)){
				$mention_member	= $author_guild->members->get($value);
				if ($mention_member != NULL){ //$message->reply("Invalid input! Please enter an ID or @mention the user");
					$mention_user				= $mention_member->user;
					
					$mention_id					= $mention_member->id;
					$mention_check				= $mention_user->tag;
					$mention_nickname			= $mention_member->displayName;
					$mention_avatar 			= $mention_user->getAvatarURL();
					
					$mention_joined				= $mention_member->joinedAt; 
					$mention_joinedTimestamp	= $mention_member->joinedTimestamp;
					$mention_joinedDate			= date("D M j H:i:s Y", $mention_joinedTimestamp); //echo "Joined Server: " . $mention_joinedDate . PHP_EOL;
					$mention_joinedDateTime		= new DateTime('@' . $mention_joinedTimestamp);
					
					$mention_created			= $mention_user->createdAt;
					$mention_createdTimestamp	= $mention_user->createdTimestamp;
					$mention_createdDate		= date("D M j H:i:s Y", $mention_createdTimestamp);
					$mention_createdDateTime	= new DateTime('@' . $mention_createdTimestamp);
					
					$mention_joinedAge = $mention_joinedDateTime->diff($now)->days . " days";
					$mention_createdAge = $mention_createdDateTime->diff($now)->days . " days";
					
					//Load history
					$mention_folder = "\\users\\$mention_id";
					CheckDir($mention_folder);
					$mention_nicknames_array = VarLoad($mention_folder, "nicknames.php");
					$mention_nicknames = "";
					if(is_array($mention_nicknames_array)){
						$mention_nicknames_array = array_reverse($mention_nicknames_array);
						$x=0;
						foreach ($mention_nicknames_array as $nickname){
							if ($x<5)
								$mention_nicknames = $mention_nicknames . $nickname . "\n";
							$x++;
						}
					}
					if ($mention_nicknames == "") $mention_nicknames = "No nicknames tracked";
					//echo "mention_nicknames: " . $mention_nicknames . PHP_EOL;
					
					$mention_tags_array = VarLoad($mention_folder, "tags.php");
					$mention_tags = "";
					if (is_array($mention_tags_array)){
						$mention_tags_array = array_reverse($mention_tags_array);
						$x=0;
						foreach ($mention_tags_array as $tag){
							if ($x<5)
								$mention_tags = $mention_tags . $tag . "\n";
							$x++;
						}
					}
					if ($mention_tags == "") $mention_tags = "No tags tracked";
					 
					$embed = new \CharlotteDunois\Yasmin\Models\MessageEmbed();
					$embed
						->setTitle("$mention_check ($mention_nickname)")																// Set a title
						->setColor("e1452d")																	// Set a color (the thing on the left side)
			//					->setDescription("$author_guild_name")									// Set a description (below title, above fields)
						->addField("ID", "$mention_id", true)
						->addField("Avatar", "[Link]($mention_avatar)", true)
						->addField("Account Created", "$mention_createdDate", true)
						->addField("Account Age", "$mention_createdAge", true)
						->addField("Joined Server", "$mention_joinedDate", true)
						->addField("Server Age", "$mention_joinedAge", true)
						->addField("Tag history (last 5)", "`$mention_tags`")
						->addField("Nickname history (last 5)", "`$mention_nicknames`")

						->setThumbnail("$mention_avatar")														// Set a thumbnail (the image in the top right corner)
			//					->setImage('https://avatars1.githubusercontent.com/u/4529744?s=460&v=4')             	// Set an image (below everything except footer)
			//					->setImage("$image_path")             													// Set an image (below everything except footer)
						->setTimestamp()                                                                     	// Set a timestamp (gets shown next to footer)
			//					->setAuthor("$author_check", "$author_guild_avatar")  									// Set an author with icon
						->setFooter("Palace Bot by Valithor#5947")                             					// Set a footer without icon
						->setURL("");                             												// Set the URL
					$author_channel->send('', array('embed' => $embed))->done(null, function ($error){
						echo "[ERROR] $error".PHP_EOL; //Echo any errors
					});
				}else{
					$restcord_timestamp = snowflake_timestamp($value);
					$restcord_date = date("D M j H:i:s Y", $restcord_timestamp);
					$restcord_DateTime = new DateTime();
					$restcord_DateTime->setTimestamp($restcord_timestamp);
					$restcord_Age = $restcord_DateTime->diff($now)->days . " days"; //fails
					try{
						$restcord_user = $restcord->user->getUser(['user.id' => intval($value)]);
						$restcord_nick = $restcord_user->username;
						$restcord_discriminator = $restcord_user->discriminator;
						$restcord_avatar = $restcord_user->getAvatar();
						//$date = new DateTime("$restcord_timestamp"); 
						//echo "date: " . $date;
						//$restcord_result = "Discord ID is registered to $restcord_nick#$restcord_discriminator created on $restcord_timestamp";
					}catch (Exception $e){
						$restcord_result = "Unable to locate user for ID $value";
					}
					$embed = new \CharlotteDunois\Yasmin\Models\MessageEmbed();
					$embed
						->setTitle("$restcord_nick#$restcord_discriminator")																// Set a title
						->setColor("e1452d")																	// Set a color (the thing on the left side)
						//->setDescription("$author_guild_name")									// Set a description (below title, above fields)
						->addField("ID", "$value", true)
						->addField("Avatar", "[Link]($restcord_avatar)", true)
						->addField("Account Created", "$restcord_date", true)
						->addField("Account Age", "$restcord_Age", true)
						//->addField("Tag history (last 5)", "`$mention_tags`")
						//->addField("Nickname history (last 5)", "`$mention_nicknames`")

						->setThumbnail("$restcord_avatar")														// Set a thumbnail (the image in the top right corner)
						//->setImage('https://avatars1.githubusercontent.com/u/4529744?s=460&v=4')             	// Set an image (below everything except footer)
						//	->setImage("$image_path")             													// Set an image (below everything except footer)
						->setTimestamp()                                                                     	// Set a timestamp (gets shown next to footer)
						//->setAuthor("$author_check", "$author_guild_avatar")  									// Set an author with icon
						->setFooter("Palace Bot by Valithor#5947")                             					// Set a footer without icon
						->setURL("");                             												// Set the URL
					
					//Load history
					$mention_folder = "\\users\\$value";
					CheckDir($mention_folder);
					$mention_nicknames_array = VarLoad($mention_folder, "nicknames.php");
					
					if(is_array($mention_nicknames_array)){
						$mention_nicknames_array = array_reverse($mention_nicknames_array);
						$x=0;
						foreach ($mention_nicknames_array as $nickname){
							if ($x<5)
								$mention_nicknames = $mention_nicknames . $nickname . "\n";
							$x++;
						}
					}
					if ($mention_nicknames == "") $mention_nicknames = "No nicknames tracked";
					
					$mention_tags = "";
					$mention_tags_array = VarLoad($mention_folder, "tags.php");
					$x=0;
					if (is_array($mention_tags_array)){
						$mention_tags_array = array_reverse($mention_tags_array);
						foreach ($mention_tags_array as $tag){
							if ($x<5)
								$mention_tags = $mention_tags . $tag . "\n";
							$x++;
						}
					}
					if ($mention_tags == "") $mention_tags = "No tags tracked";
					
					$embed->addField("Tag history (last 5)", "`$mention_tags`");
					$embed->addField("Nickname history (last 5)", "`$mention_nicknames`");
					
					$restcord_result = $restcord_result ?? '';
					$message->channel->send($restcord_result, array('embed' => $embed))->done(null, function ($error){
						echo "[ERROR] $error".PHP_EOL; //Echo any errors
					});
				}
			}else $message->reply("Invalid input! Please enter an ID or @mention the user");
			return true;
		}
		if (substr($message_content_lower, 0, 7) == 'lookup '){ //;lookup
			echo "[WHOIS] $author_check" . PHP_EOL;			
			$filter = "lookup ";
			$value = str_replace($filter, "", $message_content_lower);
			$value = str_replace("<@!", "", $value); $value = str_replace("<@", "", $value); $value = str_replace("<@", "", $value); 
			$value = str_replace(">", "", $value);
			$value = trim($value);
			if(is_numeric($value)){
				try{
					$restcord_user = $restcord->user->getUser(['user.id' => intval($value)]);
					$restcord_nick = $restcord_user->username;
					$restcord_discriminator = $restcord_user->discriminator;
					$restcord_result = "Discord ID is registered to $restcord_nick#$restcord_discriminator (<@$value>)";
				}catch (Exception $e){
					$restcord_result = "Unable to locate user for ID $value";
				}
				$message->reply($restcord_result);
			}
		}
		if ($message_content_lower == 'clearall'){ //;clearall Clear as many messages in the author's channel at once as possible
			echo "[CLEARALL] $author_check" . PHP_EOL;
			$author_channel->bulkDelete(100);
			$author_channel->fetchMessages()->then(function($message_collection) use ($author_channel){
				foreach ($message_collection as $message){
					$author_channel->message->delete();
				}
			});
			return true;
		};
		if (substr($message_content_lower, 0, 6) == 'clear '){ //;clear #
			echo "[CLEAR #] $author_check" . PHP_EOL;
			$filter = "clear ";
			$value = str_replace($filter, "", $message_content_lower);
			if(is_numeric($value)){
				$author_channel->bulkDelete($value);
				/*$author_channel->fetchMessages()->then(function($message_collection) use ($author_channel){
					foreach ($message_collection as $message){
						$author_channel->message->delete();
					}
				});
	*/		}
			if($modlog_channel){
				$embed = new \CharlotteDunois\Yasmin\Models\MessageEmbed();
				$embed
	//				->setTitle("Commands")																	// Set a title
					->setColor("e1452d")																	// Set a color (the thing on the left side)
	//				->setDescription("Infractions for $mention_check")										// Set a description (below title, above fields)
					->addField("Clear", "Deleted $value messages in <#$author_channel_id>")			// New line after this
	//				->addField("⠀", "Use '" . "removeinfraction @mention #' to remove")	// New line after this
					
					->setThumbnail("$author_avatar")														// Set a thumbnail (the image in the top right corner)
	//				->setImage('https://avatars1.githubusercontent.com/u/4529744?s=460&v=4')             	// Set an image (below everything except footer)
	//				->setTimestamp()                                                                     	// Set a timestamp (gets shown next to footer)
					->setAuthor("$author_check", "$author_avatar;clea")  									// Set an author with icon
					->setFooter("Palace Bot by Valithor#5947")                             					// Set a footer without icon
					->setURL(""); 
				$modlog_channel->send('', array('embed' => $embed))->done(null, function ($error){
					echo "[ERROR] $error".PHP_EOL; //Echo any errors
				});
			}
			//Send message to channel confirming the message deletions
			$duration = 3;
			$author_channel->send("$author_check ($author_id) deleted $value messages!")->then(function($new_message) use ($discord, $message, $duration){
				$message->delete(); //Delete the original ;clear message
				$discord->addTimer($duration, function() use ($new_message) {
					$new_message->delete(); //Delete message confirming the deletion of messages
					return true;
				});
				return true;
			});
			return true;
		};
		if (substr($message_content_lower, 0, 6) == 'watch '){ //;watch @
			echo "[WATCH] $author_check" . PHP_EOL;
		//			Get an array of people mentioned
			$mentions_arr 												= $message->mentions->users; 									//echo "mentions_arr: " . PHP_EOL; var_dump ($mentions_arr); //Shows the collection object
			if ($watch_channel)	$mention_watch_name_mention_default		= "<@$author_id>";
			$mention_watch_name_queue_default							= $mention_watch_name_mention_default."is watching the following users:" . PHP_EOL;
			$mention_watch_name_queue_full 								= "";
			
			if (!strpos($message_content_lower, "<")){ //String doesn't contain a mention
				$filter = "watch ";
				$value = str_replace($filter, "", $message_content_lower);
				$value = str_replace("<@!", "", $value); $value = str_replace("<@", "", $value); $value = str_replace("<@", "", $value); 
				$value = str_replace(">", "", $value);
				if(is_numeric($value)){
					$mention_member				= $author_guild->members->get($value);
					$mention_user				= $mention_member->user;
					$mentions_arr				= array($mention_user);
				}else return $message->reply("Invalid input! Please enter a valid ID or @mention the user");
				if ($mention_member == NULL) return $message->reply("Invalid input! Please enter an ID or @mention the user");
			}
			
			foreach ( $mentions_arr as $mention_param ){																				//echo "mention_param: " . PHP_EOL; var_dump ($mention_param);
		//		id, username, discriminator, bot, avatar, email, mfaEnabled, verified, webhook, createdTimestamp
				$mention_param_encode 									= json_encode($mention_param); 									//echo "mention_param_encode: " . $mention_param_encode . PHP_EOL;
				$mention_json 											= json_decode($mention_param_encode, true); 					//echo "mention_json: " . PHP_EOL; var_dump($mention_json);
				$mention_id 											= $mention_json['id']; 											//echo "mention_id: " . $mention_id . PHP_EOL; //Just the discord ID
				
		//		Place watch info in target's folder
				$watchers[] = VarLoad($guild_folder."/".$mention_id, "$watchers.php");
				$watchers = array_unique($arr);
				$watchers[] = $author_id;
				VarSave($guild_folder."/".$mention_id, "watchers.php", $watchers);
				$mention_watch_name_queue 								= "**<@$mention_id>** ";
				$mention_watch_name_queue_full 							= $mention_watch_name_queue_full . PHP_EOL . $mention_watch_name_queue;
			}
		//	Send a message
			if ($mention_watch_name_queue != ""){
				if ($watch_channel)$watch_channel->send($mention_watch_name_queue_default . $mention_watch_name_queue_full . PHP_EOL);
				else $message->reply($mention_watch_name_queue_default . $mention_watch_name_queue_full . PHP_EOL);
		//		React to the original message
		//		if($react) $message->react("👀");
				if($react) $message->react("👁");		
				return true;
			}else{
				if($react) $message->react("👎");
				$message->reply("Nobody in the guild was mentioned!");
				return true;
			}
		//						
		}
		if (substr($message_content_lower, 0, 8) == 'unwatch '){ //;unwatch @
			echo "[UNWATCH] $author_check" . PHP_EOL;
		//	Get an array of people mentioned
			$mentions_arr 												= $message->mentions->users; 									//echo "mentions_arr: " . PHP_EOL; var_dump ($mentions_arr); //Shows the collection object
			$mention_watch_name_queue_default							= "<@$author_id> is no longer watching the following users:" . PHP_EOL;
			$mention_watch_name_queue_full 								= "";
			
			if (!strpos($message_content_lower, "<")){ //String doesn't contain a mention
				$filter = "unwatch ";
				$value = str_replace($filter, "", $message_content_lower);
				$value = str_replace("<@!", "", $value); $value = str_replace("<@", "", $value); $value = str_replace("<@", "", $value); 
				$value = str_replace(">", "", $value);
				if(is_numeric($value)){
					$mention_member				= $author_guild->members->get($value);
					$mention_user				= $mention_member->user;
					$mentions_arr				= array($mention_user);
				}else return $message->reply("Invalid input! Please enter a valid ID or @mention the user");
				if ($mention_member == NULL) return $message->reply("Invalid input! Please enter an ID or @mention the user");
			}
			
			foreach ( $mentions_arr as $mention_param ){																				//echo "mention_param: " . PHP_EOL; var_dump ($mention_param);
		//		id, username, discriminator, bot, avatar, email, mfaEnabled, verified, webhook, createdTimestamp
				$mention_param_encode 									= json_encode($mention_param); 									//echo "mention_param_encode: " . $mention_param_encode . PHP_EOL;
				$mention_json 											= json_decode($mention_param_encode, true); 					//echo "mention_json: " . PHP_EOL; var_dump($mention_json);
				$mention_id 											= $mention_json['id']; 											//echo "mention_id: " . $mention_id . PHP_EOL; //Just the discord ID
				
		//		Place watch info in target's folder
				$watchers[] = VarLoad($guild_folder."/".$mention_id, "$watchers.php");
				$watchers = array_value_remove($author_id, $watchers);
				VarSave($guild_folder."/".$mention_id, "watchers.php", $watchers);
				$mention_watch_name_queue 								= "**<@$mention_id>** ";
				$mention_watch_name_queue_full 							= $mention_watch_name_queue_full . PHP_EOL . $mention_watch_name_queue;
			}
		//	React to the original message
			if($react) $message->react("👍");
		//	Send the message
			if ($watch_channel)	$watch_channel->send($mention_watch_name_queue_default . $mention_watch_name_queue_full . PHP_EOL);
			else $author_channel->send($mention_watch_name_queue_default . $mention_watch_name_queue_full . PHP_EOL);
			return true;
		}
		if ( (substr($message_content_lower, 0, 7) == 'vwatch ') || (substr($message_content_lower, 0, 3) == 'vw ')){ //;vwatch @
			echo "[VWATCH] $author_check" . PHP_EOL;
	//		Get an array of people mentioned
			$mentions_arr 												= $message->mentions->users; 									//echo "mentions_arr: " . PHP_EOL; var_dump ($mentions_arr); //Shows the collection object
			if ($watch_channel)	$mention_watch_name_mention_default		= "<@$author_id>";
			$mention_watch_name_queue_default							= $mention_watch_name_mention_default."is watching the following users:" . PHP_EOL;
			$mention_watch_name_queue_full 								= "";
			
			if (!strpos($message_content_lower, "<")){ //String doesn't contain a mention
				$filter = "vwatch ";
				$value = str_replace($filter, "", $message_content_lower);
				$value = str_replace("<@!", "", $value); $value = str_replace("<@", "", $value); $value = str_replace("<@", "", $value); 
				$value = str_replace(">", "", $value);
				$filter = "vw ";
				$value = str_replace($filter, "", $value);
				$value = str_replace("<@!", "", $value); $value = str_replace("<@", "", $value); $value = str_replace("<@", "", $value); 
				$value = str_replace(">", "", $value);
				if(is_numeric($value)){
					$mention_member				= $author_guild->members->get($value);
					$mention_user				= $mention_member->user;
					$mentions_arr				= array($mention_user);
				}else return $message->reply("Invalid input! Please enter a valid ID or @mention the user");
				if ($mention_member == NULL) return $message->reply("Invalid input! Please enter an ID or @mention the user");
			}
			
			foreach ( $mentions_arr as $mention_param ){																				//echo "mention_param: " . PHP_EOL; var_dump ($mention_param);
		//				id, username, discriminator, bot, avatar, email, mfaEnabled, verified, webhook, createdTimestamp
				$mention_param_encode 									= json_encode($mention_param); 									//echo "mention_param_encode: " . $mention_param_encode . PHP_EOL;
				$mention_json 											= json_decode($mention_param_encode, true); 					//echo "mention_json: " . PHP_EOL; var_dump($mention_json);
				$mention_id 											= $mention_json['id']; 											//echo "mention_id: " . $mention_id . PHP_EOL; //Just the discord ID
				
		//				Place watch info in target's folder
				$watchers[] = VarLoad($guild_folder."/".$mention_id, "$watchers.php");
				$watchers = array_unique($arr);
				$watchers[] = $author_id;
				VarSave($guild_folder."/".$mention_id, "watchers.php", $watchers);
				$mention_watch_name_queue 								= "**<@$mention_id>** ";
				$mention_watch_name_queue_full 							= $mention_watch_name_queue_full . PHP_EOL . $mention_watch_name_queue;
				
				echo "mention_id: " . $mention_id . PHP_EOL;
				$target_guildmember 									= $message->guild->members->get($mention_id);
				$target_guildmember_role_collection 					= $target_guildmember->roles;									//echo "target_guildmember_role_collection: " . (count($author_guildmember_role_collection)-1);
				
		//				Populate arrays of the info we need
				$target_verified										= false; //Default
				$x=0;
				foreach ($target_guildmember_role_collection as $role){
					if ($x!=0){ //0 is @everyone so skip it
						if ($role->id == $role_verified_id)
							$target_verified 							= true;
					}
					$x++;
				}
				
				if($target_verified == false){
		//					Build the string for the reply
					$mention_role_name_queue 							= "**<@$mention_id>** ";
					$mention_role_name_queue_full 						= $mention_role_name_queue_full . PHP_EOL . $mention_role_name_queue;
		//					Add the verified role to the member
					$target_guildmember->addRole($role_verified_id)->done(
						function (){
							//if ($general_channel) $general_channel->send('Welcome to the Palace, <@$mention_id>! Feel free to pick out some roles in #role-picker!');
						},
						function ($error) {
							echo "[ERROR] $error".PHP_EOL;
						}
					);
					echo "Verify role added to $mention_id" . PHP_EOL;
				}
			}
		//			Send a message
			if ($mention_watch_name_queue != ""){
				if ($watch_channel)$watch_channel->send($mention_watch_name_queue_default . $mention_watch_name_queue_full . PHP_EOL);
				else $message->reply($mention_watch_name_queue_default . $mention_watch_name_queue_full . PHP_EOL);
		//				React to the original message
		//				if($react) $message->react("👀");
				if($react) $message->react("👁");
				if($general_channel){
					$msg = "Welcome to the Palace, <@$mention_id>!";
					if($rolepicker_channel) $msg = $msg . " Feel free to pick out some roles in <#$rolepicker_channel_id>.";
					if($general_channel)$general_channel->send($msg);
				}
				return true;
			}else{
				if($react) $message->react("👎");
				$message->reply("Nobody in the guild was mentioned!");
				return true;
			}
		}
		if (substr($message_content_lower, 0, 5) == 'warn '){ //;warn @
			echo "[WARN] $author_check" . PHP_EOL;
			//$message->reply("Not yet implemented!");
	//		Get an array of people mentioned
			$mentions_arr 												= $message->mentions->users; 									//echo "mentions_arr: " . PHP_EOL; var_dump ($mentions_arr); //Shows the collection object
			if ($modlog_channel)	$mention_warn_name_mention_default		= "<@$author_id>";
			$mention_warn_queue_default									= $mention_warn_name_mention_default." warned the following users:" . PHP_EOL;
			$mention_warn_queue_full 									= "";
			
			foreach ( $mentions_arr as $mention_param ){																				//echo "mention_param: " . PHP_EOL; var_dump ($mention_param);
	//			id, username, discriminator, bot, avatar, email, mfaEnabled, verified, webhook, createdTimestamp
				$mention_param_encode 									= json_encode($mention_param); 									//echo "mention_param_encode: " . $mention_param_encode . PHP_EOL;
				$mention_json 											= json_decode($mention_param_encode, true); 					//echo "mention_json: " . PHP_EOL; var_dump($mention_json);
				$mention_id 											= $mention_json['id']; 											//echo "mention_id: " . $mention_id . PHP_EOL; //Just the discord ID
				$mention_username 										= $mention_json['username']; 									//echo "mention_username: " . $mention_username . PHP_EOL; //Just the discord ID
				$mention_discriminator 									= $mention_json['discriminator']; 								//echo "mention_discriminator: " . $mention_discriminator . PHP_EOL; //Just the discord ID
				$mention_check 											= $mention_username ."#".$mention_discriminator; 				//echo "mention_check: " . $mention_check . PHP_EOL; //Just the discord ID
				
	//			Build the string to log
				$filter = "warn <@!$mention_id>";
				$warndate = date("m/d/Y");
				$mention_warn_queue = "**$mention_check was warned by $author_check on $warndate for reason: **" . str_replace($filter, "", $message_content);
				
	//			Place warn info in target's folder
				$infractions = VarLoad($guild_folder."/".$mention_id, "infractions.php");
				$infractions[] = $mention_warn_queue;
				VarSave($guild_folder."/".$mention_id, "infractions.php", $infractions);
				$mention_warn_queue_full = $mention_warn_queue_full . PHP_EOL . $mention_warn_queue;
			}
	//		Send a message
			if ($mention_warn_queue != ""){
				if ($watch_channel)$watch_channel->send($mention_warn_queue_default . $mention_warn_queue_full . PHP_EOL);
				else $message->channel->send($mention_warn_queue_default . $mention_warn_queue_full . PHP_EOL);
	//			React to the original message
	//			if($react) $message->react("👀");
				if($react) $message->react("👁");		
				return true;
			}else{
				if($react) $message->react("👎");
				$message->reply("Nobody in the guild was mentioned!");
				return true;
			}
		}
		if (substr($message_content_lower, 0, 12) == 'infractions '){ //;infractions @
			echo "[INFRACTIONS] $author_check" . PHP_EOL;
	//		Get an array of people mentioned
			$mentions_arr 													= $message->mentions->users; 									//echo "mentions_arr: " . PHP_EOL; var_dump ($mentions_arr); //Shows the collection object
			
			if (!strpos($message_content_lower, "<")){ //String doesn't contain a mention
				$filter = "infractions ";
				$value = str_replace($filter, "", $message_content_lower);
				$value = str_replace("<@!", "", $value); $value = str_replace("<@", "", $value); $value = str_replace("<@", "", $value); 
				$value = str_replace(">", "", $value);
				if(is_numeric($value)){
					$mention_member	= $author_guild->members->get($value);
					$mention_user = $mention_member->user;
					$mentions_arr = array($mention_user);
				}else return $message->reply("Invalid input! Please enter a valid ID or @mention the user");
				if ($mention_member == NULL) return $message->reply("Invalid input! Please enter an ID or @mention the user");
			}
			
			$x = 0;
			foreach ( $mentions_arr as $mention_param ){																				//echo "mention_param: " . PHP_EOL; var_dump ($mention_param);
				if ($x == 0){ //We only want the first person mentioned
	//				id, username, discriminator, bot, avatar, email, mfaEnabled, verified, webhook, createdTimestamp
					$mention_param_encode 									= json_encode($mention_param); 									//echo "mention_param_encode: " . $mention_param_encode . PHP_EOL;
					$mention_json 											= json_decode($mention_param_encode, true); 					//echo "mention_json: " . PHP_EOL; var_dump($mention_json);
					$mention_id 											= $mention_json['id']; 											//echo "mention_id: " . $mention_id . PHP_EOL; //Just the discord ID
					$mention_username 										= $mention_json['username']; 									//echo "mention_username: " . $mention_username . PHP_EOL; //Just the discord ID
					$mention_discriminator 									= $mention_json['discriminator']; 								//echo "mention_discriminator: " . $mention_discriminator . PHP_EOL; //Just the discord ID
					$mention_check 											= $mention_username ."#".$mention_discriminator; 				//echo "mention_check: " . $mention_check . PHP_EOL; //Just the discord ID
					
	//				Place infraction info in target's folder
					$infractions = VarLoad($guild_folder."/".$mention_id, "infractions.php");
					$y = 0;
					$mention_infraction_queue = "";
					$mention_infraction_queue_full = "";
					foreach ( $infractions as $infraction ){
						//Build a string
						$mention_infraction_queue = $mention_infraction_queue . "$y: " . $infraction . PHP_EOL;
						$y++;
					}
					$mention_infraction_queue_full 								= $mention_infraction_queue_full . PHP_EOL . $mention_infraction_queue;
				}
				$x++;
			}
	//			Send a message
			if ($mention_infraction_queue != ""){
				$length = strlen($mention_infraction_queue_full);
				if ($length < 1025){

				$embed = new \CharlotteDunois\Yasmin\Models\MessageEmbed();
				$embed
	//					->setTitle("Commands")																	// Set a title
					->setColor("e1452d")																	// Set a color (the thing on the left side)
	//					->setDescription("Infractions for $mention_check")										// Set a description (below title, above fields)
					->addField("Infractions for $mention_check", "$mention_infraction_queue_full")			// New line after this
	//					->addField("⠀", "Use '" . "removeinfraction @mention #' to remove")	// New line after this
					
	//					->setThumbnail("$author_avatar")														// Set a thumbnail (the image in the top right corner)
	//					->setImage('https://avatars1.githubusercontent.com/u/4529744?s=460&v=4')             	// Set an image (below everything except footer)
	//					->setTimestamp()                                                                     	// Set a timestamp (gets shown next to footer)
	//					->setAuthor("$author_check", "$author_guild_avatar")  									// Set an author with icon
					->setFooter("Palace Bot by Valithor#5947")                             					// Set a footer without icon
					->setURL("");                             												// Set the URL
	//					Send the embed to the author's channel
					$author_channel->send('', array('embed' => $embed))->done(null, function ($error){
						echo "[ERROR] $error".PHP_EOL; //Echo any errors
					});
					return true;
				}else{ //Too long, send reply instead of embed
					$message->reply($mention_infraction_queue_full . PHP_EOL);
	//				React to the original message
	//				if($react) $message->react("👀");
					if($react) $message->react("🗒️");		
					return true;
				}
			}else{
				//if($react) $message->react("👎");
				$message->reply("No infractions found!");
				return true;
			}
		}
	}
	if ($creator || $owner || $dev || $admin){
		if (substr($message_content_lower, 0, 4) == 'ban '){ //;ban
			echo "[BAN]" . PHP_EOL;
			//Get an array of people mentioned
			$mentions_arr 	= $message->mentions->users; //echo "mentions_arr: " . PHP_EOL; var_dump ($mentions_arr); //Shows the collection object
			
			$GetMentionResult = GetMention([&$author_guild,  substr($message_content_lower, 4, strlen($message_content_lower)), null, 1, &$restcord]);
			if ($GetMentionResult === false ) return $message->reply("Invalid input! Please enter a valid ID or @mention the user");
			$mention_id_array = array();
			$reason_text = null;
			$keys = array_keys($GetMentionResult);
			for($i = 0; $i < count($GetMentionResult); $i++) {
				if (is_numeric($keys[$i])){
					$mention_id_array[] = $keys[$i];
				}else{
					foreach($GetMentionResult[$keys[$i]] as $key => $value) {
						$reason_text = $value ?? "None";
					}
				}
			}
			
			/*
			if ($GetMentionResult[1] == NULL){
				if ($GetMentionResult['restcord_user_found'] === true){
					echo "[RESTCORD BAN]" . PHP_EOL;
					try{
						$restcord->guild->createGuildBan(['guild.id' => intval($author_guild_id), 'user.id' => intval($GetMentionResult['restcord_user']->id), 'delete-message-days?' => intval(0), 'reason?' => strval('null')]); //This seems to be failing no matter what
						if($react) $message->react("🔨");
					}catch (Throwable $e){
						if($react) $message->react("👎");
						echo "[RESTCORD] Unable to ban user!" . PHP_EOL;
					}
				} else return $message->reply("User not found in the guild!");
				return $message->reply("User not found in the guild!");
			}
			*/
			$mention_user = GetMentionResult[0];
			$mention_member = GetMentionResult[1];
			$mentions_arr = $mentions_arr ?? GetMentionResult[2];
			foreach ( $mentions_arr as $mention_param ){
				$mention_param_encode 									= json_encode($mention_param); 									//echo "mention_param_encode: " . $mention_param_encode . PHP_EOL;
				$mention_json 											= json_decode($mention_param_encode, true); 				//echo "mention_json: " . PHP_EOL; var_dump($mention_json);
				$mention_id 											= $mention_json['id']; 											//echo "mention_id: " . $mention_id . PHP_EOL; //Just the discord ID
				$mention_discriminator 									= $mention_json['discriminator']; 								//echo "mention_id: " . $mention_id . PHP_EOL; //Just the discord ID
				$mention_username 										= $mention_json['username']; 									//echo "mention_username: " . $mention_username . PHP_EOL; //Just the discord ID
				$mention_check 											= $mention_username ."#".$mention_discriminator;
				
				if ($author_id != $mention_id){ //Don't let anyone ban themselves
					//Get the roles of the mentioned user
					$target_guildmember 								= $message->guild->members->get($mention_id); 	//This is a GuildMember object
					$target_guildmember_role_collection 				= $target_guildmember->roles;					//This is the Role object for the GuildMember
					
		//  				Get the avatar URL of the mentioned user
		//					$target_guildmember_user							= $target_guildmember->user;									//echo "member_class: " . get_class($target_guildmember_user) . PHP_EOL;
		//					$mention_avatar 									= "{$target_guildmember_user->getAvatarURL()}";					//echo "mention_avatar: " . $mention_avatar . PHP_EOL;				//echo "target_guildmember_role_collection: " . (count($target_guildmember_role_collection)-1);

		//  				Populate arrays of the info we need
		//  				$target_guildmember_roles_names 					= array();
					$x=0;
					$target_dev = false;
					$target_owner = false;
					$target_admin = false;
					$target_mod = false;
					$target_vzgbot = false;
					$target_guildmember_roles_ids = array();
					foreach ($target_guildmember_role_collection as $role){
						if ($x!=0){ //0 is @everyone so skip it
							$target_guildmember_roles_ids[] 						= $role->id; 											//echo "role[$x] id: " . PHP_EOL; //var_dump($role->id);
							if ($role->id == $role_dev_id)    	$target_dev 		= true;							//Author has the dev role
							if ($role->id == $role_owner_id)    $target_owner	 	= true;							//Author has the owner role
							if ($role->id == $role_admin_id)	$target_admin 		= true;							//Author has the admin role
							if ($role->id == $role_mod_id)		$target_mod 		= true;							//Author has the mod role
							if ($role->id == $role_vzgbot_id)	$target_vzgbot 		= true;							//Author is this bot
							if ($role->name == "Palace Bot")	$target_vzgbot 		= true;							//Author is this bot
						}
						$x++;
					}
					if( (!$target_dev && !$target_owner && !$target_admin && !$target_vzg) || ($creator || $owner)){ //Guild owner and bot creator can ban anyone
						if ($mention_id == $creator_id) return true; //Don't ban the creator
						//Build the string to log
						$filter = "ban <@!$mention_id>";
						$warndate = date("m/d/Y");
						$reason = "**User:** <@$mention_id>
						**🗓️Date:** $warndate
						**📝Reason:** $reason_text";
						//Ban the user and clear 1 days worth of messages
						$target_guildmember->ban("1", $reason)->done(null, function ($error){
							echo "[ERROR] $error".PHP_EOL; //Echo any errors
						});
						//Build the embed message
						$embed = new \CharlotteDunois\Yasmin\Models\MessageEmbed();
						$embed
		//							->setTitle("Commands")																	// Set a title
							->setColor("e1452d")																	// Set a color (the thing on the left side)
							->setDescription("$reason")																// Set a description (below title, above fields)
		//							->addField("⠀", "$reason")																// New line after this
							
		//							->setThumbnail("$author_avatar")														// Set a thumbnail (the image in the top right corner)
		//							->setImage('https://avatars1.githubusercontent.com/u/4529744?s=460&v=4')             	// Set an image (below everything except footer)
							->setTimestamp()                                                                     	// Set a timestamp (gets shown next to footer)
							->setAuthor("$author_check ($author_id)", "$author_avatar")  							// Set an author with icon
							->setFooter("Palace Bot by Valithor#5947")                             					// Set a footer without icon
							->setURL("");                             												// Set the URL
		//						Send the message
						if($modlog_channel)$modlog_channel->send('', array('embed' => $embed))->done(null, function ($error){
							echo "[ERROR] $error".PHP_EOL; //Echo any errors
						});
						if($react) $message->react("🔨"); //Hammer
						return true; //No more processing, we only want to process the first person mentioned
					}else{//Target is not allowed to be banned
						$author_channel->send("<@$mention_id> cannot be banned because of their roles!");
						return true;
					}
				}else{
					if($react) $message->react("👎");
					$author_channel->send("<@$author_id>, you can't ban yourself!");
					return true;
				}
			} //foreach method didn't return, so nobody in the guild was mentioned
			//Try restcord
			$filter = "ban ";
			$value = str_replace($filter, "", $message_content_lower);
			$value = str_replace("<@!", "", $value); $value = str_replace("<@", "", $value);
			$value = str_replace(">", "", $value);//echo "value: " . $value . PHP_EOL;
			if(is_numeric($value)){ //resolve with restcord
				//$restcord->guild
				$restcord_param = ['guild.id' => (int)$author_guild_id, 'user.id' => (int)$value];
				try{
					//$restcord_result = $restcord->guild->createGuildBan($restcord_param);
				}catch (Exception $e){
					$restcord_result = "Unable to locate user for ID $value";
					echo $e . PHP_EOL;
				}
				//$message->reply($restcord_result);
			}else{
				if($react) $message->react("👎");
				$author_channel->send("<@$author_id>, you need to mention someone!");
			}
			return true;
		}
		if (substr($message_content_lower, 0, 17) == 'removeinfraction '){ //;removeinfractions @mention #
			echo "[REMOVE INFRACTION] $author_check" . PHP_EOL;
		//	Get an array of people mentioned
			$mentions_arr 													= $message->mentions->users; 									//echo "mentions_arr: " . PHP_EOL; var_dump ($mentions_arr); //Shows the collection object
			
			
			$filter = "removeinfraction ";
			$value = str_replace($filter, "", $message_content_lower);
			$value = str_replace("<@!", "", $value); $value = str_replace("<@", "", $value); $value = str_replace("<@", "", $value); $value = str_replace(">", "", $value);
			
				
			if(is_numeric($value)){
				$mention_member				= $author_guild->members->get($value);
				$mention_user				= $mention_member->user;
				$mentions_arr				= array($mention_user);
			}else return $message->reply("Invalid input! Please enter a valid ID or @mention the user");
			if ($mention_member == NULL) return $message->reply("Invalid input! Please enter an ID or @mention the user");
			
			$x = 0;
			foreach ( $mentions_arr as $mention_param ){																				//echo "mention_param: " . PHP_EOL; var_dump ($mention_param);
				if ($x == 0){ //We only want the first person mentioned
		//			id, username, discriminator, bot, avatar, email, mfaEnabled, verified, webhook, createdTimestamp
					$mention_param_encode 									= json_encode($mention_param); 									//echo "mention_param_encode: " . $mention_param_encode . PHP_EOL;
					$mention_json 											= json_decode($mention_param_encode, true); 					//echo "mention_json: " . PHP_EOL; var_dump($mention_json);
					$mention_id 											= $mention_json['id']; 											//echo "mention_id: " . $mention_id . PHP_EOL; //Just the discord ID
					$mention_username 										= $mention_json['username']; 									//echo "mention_username: " . $mention_username . PHP_EOL; //Just the discord ID
					$mention_discriminator 									= $mention_json['discriminator']; 								//echo "mention_discriminator: " . $mention_discriminator . PHP_EOL; //Just the discord ID
					$mention_check 											= $mention_username ."#".$mention_discriminator; 				//echo "mention_check: " . $mention_check . PHP_EOL; //Just the discord ID
					
		//			Get infraction info in target's folder
					$infractions = VarLoad($guild_folder."/".$mention_id, "infractions.php");
					$proper = "removeinfraction <@!$mention_id> ";
					$strlen = strlen("removeinfraction <@!$mention_id> ");
					$substr = substr($message_content_lower, $strlen);
					
		//			Check that message is formatted properly
					if ($proper != substr($message_content_lower, 0, $strlen)){
						$message->reply("Please format your command properly: " . $command_symbol . "warn @mention number");
						return true;
					}
					
		//			Check if $substr is a number
					if ( ($substr != "") && (is_numeric(intval($substr))) ){
		//				Remove array element and reindex
						//array_splice($infractions, $substr, 1);
						if ($infractions[$substr] != NULL){
							$infractions[$substr] = "Infraction removed by $author_check on " . date("m/d/Y"); // for arrays where key equals offset
		//					Save the new infraction log
							VarSave($guild_folder."/".$mention_id, "infractions.php", $infractions);
							
		//					Send a message
							if($react) $message->react("👍");
							$message->reply("Infraction $substr removed from $mention_check!");
							return true;
						}else{
							if($react) $message->react("👎");
							$message->reply("Infraction '$substr' not found!");
							return true;
						}
						
					}else{
						if($react) $message->react("👎");
						$message->reply("'$substr' is not a number");
						return true;
					}
					
				}
				$x++;
			}
		}
	}
