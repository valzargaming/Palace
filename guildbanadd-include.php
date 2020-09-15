<?php
include_once "custom_functions.php";
$author_guild = $guild;
$author_guild_id = $guild->id;
$author_guild_name = $guild->name;
$author_guild_avatar = $guild->getIconURL();
$author_user = $user;
$author_username = $author_user->username;
$author_discriminator = $author_user->discriminator;
$author_id = $author_user->id;
$author_avatar = $author_user->getAvatarURL();
$author_check = "$author_username#$author_discriminator";
echo "[guildBanAdd] ($author_guild_id)" . PHP_EOL;

$user_folder = "\\users\\$member_id";
CheckDir($user_folder);
$guild_folder = "\\guilds\\$author_guild_id";
if(!CheckDir($guild_folder)){
	if(!CheckFile($guild_folder, "guild_owner_id.php")){
		VarSave($guild_folder, "guild_owner_id.php", $guild_owner_id);
	}else $guild_owner_id	= VarLoad($guild_folder, "guild_owner_id.php");
}

//Load config variables for the guild
$guild_config_path = __DIR__  . "$guild_folder\\guild_config.php"; //echo "guild_config_path: " . $guild_config_path . PHP_EOL;
if(!include "$guild_config_path"){
	echo "CONFIG CATCH!" . PHP_EOL;
	$counter = $GLOBALS[$author_guild_id."_config_counter"] ?? 0;
	if ($counter <= 10){
		$GLOBALS[$author_guild_id."_config_counter"]++;
	}else{
		$author_guild->leave($author_guild_id)->done(null, function ($error){
			echo $error.PHP_EOL; //Echo any errors
		});
		rmdir(__DIR__  . $guild_folder);
		echo "GUILD DIR REMOVED" . PHP_EOL;
	}
}

$modlog_channel	= $author_guild->channels->get($modlog_channel_id);

//Build the embed message
$embed = new \CharlotteDunois\Yasmin\Models\MessageEmbed();
$embed
//	->setTitle("Commands")																	// Set a title
	->setColor("e1452d")																	// Set a color (the thing on the left side)
	->setDescription("$author_guild_name")																// Set a description (below title, above fields)
	->addField("Banned", "<@$author_id>")																// New line after this
	
	->setThumbnail("$author_avatar")														// Set a thumbnail (the image in the top right corner)
//	->setImage('https://avatars1.githubusercontent.com/u/4529744?s=460&v=4')             	// Set an image (below everything except footer)
	->setTimestamp()                                                                     	// Set a timestamp (gets shown next to footer)
	->setAuthor("$author_check ($author_id)", "$author_avatar")  							// Set an author with icon
	->setFooter("Palace Bot by Valithor#5947")                             					// Set a footer without icon
	->setURL("");                             												// Set the URL
//						Send the message
if($modlog_channel)$modlog_channel->send('', array('embed' => $embed))->done(null, function ($error){
	echo "[ERROR] $error".PHP_EOL; //Echo any errors
});
?>
