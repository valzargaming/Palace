<?php
//Game logic
$machikoro_symbol = ";mk";

if (substr($message_content_lower, 0, 4) == "$machikoro_symbol "){
	$message_filtered = trim(str_replace($machikoro_symbol, "", $message_content_lower));
	
	//Initialize Machi Koro Global array if it doesn't already exist, which might happen after the bot restarts
	if(!$GLOBALS['MachiKoro_Games']){
		$GLOBALS['MachiKoro_Games'] = array();
		echo "MKGames array initialized" . PHP_EOL;
	}
	
	//Get relevant variables for the discord user
	$game = NULL; //MKGame object
	$game_id = NULL;
	$game_key = NULL;
	$turn = NULL; //Discord ID
	$phase = NULL; //String
	foreach ($GLOBALS['MachiKoro_Games'] as $mkgame){ //Array of Game objects
		$mkgame_class = get_class($mkgame);
		if ($mkgame_class == "MKGame"){
			$mkplayers = $mkgame->getPlayers(); //Array of Player objects
			$game_id = $mkgame->getID;
			foreach ($mkplayers as $key => $mkplayer){
				$mkplayer_id = $mkplayer->getDiscordID();
				if ($author_id == $mkplayer_id){
					$turn = $mkgame->getTurn();
					$phase = $mkgame->getPhase();
					$game = $mkgame;
					$game_key = $key;
					break;
				}
			}
		}
	}
	if ($message_filtered == "host"){
		if ($game === NULL){
			$temp_game = new MKGame($author_id);
			$GLOBALS['MachiKoro_Games'][] = $temp_game;
			$mkgame_id = $temp_game->getID();
			$message->reply("You successfully started a Machi Koro game with ID $mkgame_id!");
			return true;
		}else{			
			$mkgame_id = $game->getID();
			$message->reply("You are already part of Machi Koro game $mkgame_id!");
			return true;
		}
	}
	if ($message_filtered == "start"){
		if ($game !== NULL){
			$gamestate = $game->start();
			if ($gamestate === true){
				$message->reply("Machi Koro game $game_id has been started by the host!");
			}else{
				//Get reason
			}
		}
	}
	if ($message_filtered == "end"){
		if ($game !== NULL){
			$game_host = $game->getHost();
			$game_id = $game->getID();
			$game_host_id = $game_host->getDiscordID();
			if ($author_id == $game_host_id){
				if ($game_key !== NULL){
					unset($GLOBALS['MachiKoro_Games'][$game_key]);
				}else echo "No game_key found!" . PHP_EOL;
				$message->reply("You ended the Machi Koro game $game_id!");
				return true;
			}else{
				$message->reply("You are not the host of your Machi Koro game! Ask <@$game_host_id>");
				return true;
			}
		}else{
			$message->reply("You are not part of a Machi Koro game!");
			return true;
		}
	}
	if (substr($message_filtered, 0, 4) == "join"){
		if ($game === NULL){ //Can't join a game if you're already part of one!
			$message_filtered = trim(str_replace("join", "", $message_filtered));
			if (is_numeric($message_filtered)){
				$valid = true;
				$joined = false;
				foreach ($GLOBALS['MachiKoro_Games'] as $mkgame){
					$mkgame_id = $mkgame->getID;
					if ($message_filtered == $mkgame_id){
						$valid = true;
						$result = $mkgame->addPlayer($author_id); //returns false if game is lockeds
						if ( $result === true ){
							$joined = true;
						}
					}
				}
				if($joined === true){
					$message->reply("Successfully joined game $message_filtered!");
					return true;
				}else{
					$message->reply("Unable to locate game with ID $message_filtered!");
					return true;
				}
			}else{
				$message->reply("You need to include the game's ID!");
				return true;
			}
		}else{
			$message->reply("You are already part of a Machi Koro game $game_id!");
			return true;
		}
	}
	
	
	
	if ($game !== NULL){ //Commands that only work when part of a game
		
		
		//Commands that only work on the player's turn
		if ($author_id == $turn){
			
			//TODO
			if ($phase == "BUY"){
				//Commands that only work during a certain phase
				if (substr($message_filtered, 0, 3) == "buy"){
					$message_filtered = trim(str_replace("buy", "", $message_filtered));
					
				}
			}
		}
	}
	
	if ($message_filtered == "help"){ //TODO
		//Generate documentation based on what the player is currently allwewd to do
		$documentation = "Available Commands:\n";
		return true;
	}
	echo "Uncaught ;mk command: `$message_filtered`" . PHP_EOL;
	return true;
}
?>