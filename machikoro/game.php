<?php
/*
///////////////////////////////
Game logic
///////////////////////////////
*/
$machikoro_symbol = ";mk";
if(substr($message_content_lower, 0, 4) == "$machikoro_symbol "){
	$message_filtered = trim(str_replace($machikoro_symbol, "", $message_content_lower));
	
	//Initialize Machi Koro Global array if it doesn't already exist, which might happen after the bot restarts
	if(!$GLOBALS['MachiKoro_Games']){
		$GLOBALS['MachiKoro_Games'] = array();
		echo "MKGames array initialized" . PHP_EOL;
	}
	
	/*
	///////////////////////////////
	Get relevant vars and objs
	///////////////////////////////
	*/
	
	//Game variables
	$game = NULL; //MKGame object
	$game_id = NULL; //Host Discord ID
	$turn = NULL; //Discord ID of current turn's player
	$phase = NULL; //String
	$can_reroll = NULL; //Boolean
	$can_swap = NULL; //Boolean
	//Player variables
	$player = NULL; //Player object
	$hand = NULL; //Hand object
	$gems = NULL;
	$coins = NULL;
	foreach ($GLOBALS['MachiKoro_Games'] as $mkgame){ //Array of Game objects
		$mkgame_class = get_class($mkgame);
		if($mkgame_class == "MKGame"){
			$mkplayers = $mkgame->getPlayers(); //Array of Player objects
			$game_id = $mkgame->getID();
			foreach ($mkplayers as $key => $mkplayer){
				$mkplayer_id = $mkplayer->getDiscordID();
				if($author_id == $mkplayer_id){
					$game = $mkgame;
					$turn = $mkgame->getTurn();
					$phase = $mkgame->getPhase();
					$can_reroll = $mkgame->getCanReroll();
					$can_swap = $mkgame->getCanSwap();
					
					$player = $mkgame->getPlayer($author_id);
					if ($player !== FALSE){
						$hand = $player->getHand();
						$gems = $player->getGems();
						$coins = $player->getCoins();
					}else $message->reply("Error getting Player variables for <@author_id>!");
					break;
				}
			}
		}
	}
	
	/*
	///////////////////////////////
	General commands
	///////////////////////////////
	*/
	
	if($message_filtered == "host"){
		if($game === NULL){
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
	if($message_filtered == "start"){
		if($game !== NULL){
			$gamestate = $game->start($author_id);
			//set phase
			$phase = "ROLL";
			$game->setPhase("ROLL");
			if($gamestate) $message->reply("$gamestate"); //Sending null strings is a fatal error, so obligatory if
		}
	}
	if($message_filtered == "end"){
		if($game !== NULL){
			$game_id = $game->getID();
			if($author_id == $game_id){ //Must be the host to end the game
				unset($GLOBALS['MachiKoro_Games'][$game_id]);
				$message->reply("Machi Koro game $game_id has been ended by the host!");
				return true;
			}else{
				$message->reply("You are not the host of your Machi Koro game! Ask <@$game_id>.");
				return true;
			}
		}else{
			$message->reply("You are not part of a Machi Koro game!");
			return true;
		}
	}
	if(substr($message_filtered, 0, 4) == "join"){
		if($game === NULL){ //Can't join a game if you're already part of one!
			$message_filtered = trim(str_replace("join", "", $message_filtered));
			$message_filtered = str_replace("<@!", "", $message_filtered); $message_filtered = str_replace("<@", "", $message_filtered);
			$message_filtered = str_replace(">", "", $message_filtered);
			if(is_numeric($message_filtered)){
				$valid = true;
				$joined = false;
				foreach ($GLOBALS['MachiKoro_Games'] as $mkgame){
					if ($mkgame){
						$mkgame_id = $mkgame->getID();
						if($message_filtered == $mkgame_id){
							$valid = true;
							$result = $mkgame->addPlayer($author_id); //returns false if game is lockeds
							if( $result === true ){
								$message->reply("Successfully joined game $message_filtered!");
								$game_found = true;
								return true;
							}
						}
					}
				}
				if ($game_found !== true){
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
	
	/*
	///////////////////////////////
	Game mechanics
	///////////////////////////////
	*/
	if($game !== NULL){ //Commands that only work when part of a game
		/*
		///////////////////////////////
		Game-related commands that work on any turn
		///////////////////////////////
		*/
		
		
		/*
		///////////////////////////////
		Game-related commands that only work on the player's turn
		///////////////////////////////
		*/
		if($author_id == $turn){
			/*
			///////////////////////////////
			Get situational variables
			///////////////////////////////
			*/
			//Check player's hand's landmarks to see if they built the Train Station
			$tworolls = false;
			$landmarks = $hand->getLandmark();
			foreach ($landmarks as $landmark){
				if ($landmark) $landmark_name = $landmark->getName();
				if($landmark_name == "Train Station"){
					$tworolls = true;
				}
			}
			
			/*
			///////////////////////////////
			Situational commands (Offered)
			///////////////////////////////
			*/
			if($can_reroll === true){ //Player was offered to reroll
				//Offer 
				if(substr($message_filtered, 0, 6) == "reroll"){
					$message_filtered = trim(str_replace("reroll", "", $message_filtered));
					if(is_numeric($message_filtered)){
						if($message_filtered == 0){
							//Skip reroll
							$rolls = $game->getRoll();
						}
						if($message_filtered == 1){
							$roll = $game->rollDie(1);
							$rolls = $game->getRoll();
						}
						if($message_filtered == 2){
							$roll = $game->rollDie(2);
							$rolls = $game->getRoll();
						}
						$sum_rolls = array_sum($rolls);
						if ($roll[0]) $roll_string = "" . $roll[0];
						if ($roll[1]) $roll_string = " and a " . $roll[1];
						if($roll) $reroll_string = "You rolled $message_filtered die and got a $roll_string. ";
						$reroll_string = $reroll_string . "Your current roll is $sum_rolls";
						$message->reply($reroll_string);
						
						$phase = "INCOME";
						$game->setPhase("INCOME");
						$game->diceActivation(); //Trigger card effects
						//It's safe to move straight to the INCOME phase
					}else{
						$message->reply("Please provide the number of die you would like to roll!");
						return true;
					}
				}
			}
			if($can_swap === true){ //Player made a roll that allows them to swap another person's card with one of their own
				//Name a player
					//Check if player exists
						//Get Player object reference
						//Assign to game object
					//Else
						//Prompt to select a player in this game
				//Name any card that isnt a major establish card that another player owns
					//Check if player owns card
						//Get Card object reference
						//Assign to game object
				//Name any card that isnt a major establish card that current player owns
					//Check if player owns card
						//Get Card object reference
						//Assign to game object
				//Check if all 3 are assigned
					//Tell the game to process the swap
				//Else
					//Error message and restart prompt
			}
			/*
			///////////////////////////////
			Phase-specific comamnds
			///////////////////////////////
			*/
			
			/*
			Commands that only work during a certain phase, of which there are three
			1) Roll Dice
			2) Earn Income
			3) Construction
			*/
			if($phase == "ROLL"){
				if(substr($message_filtered, 0, 4) == "roll"){
					$message_filtered = trim(str_replace("roll", "", $message_filtered));
					$rollarray = NULL;
					if($tworolls === true){ //Player owns the train station
						if( ($message_filtered == "2") || ($message_filtered == "two") ){
							$rollarray = $game->rollDie(2);
						}else{
							$rollarray = $game->rollDie(1);
						}
					}else{
						//Roll once
						$rollarray = $game->rollDie(1);
					}
					if($gems > 0){
						$game->canReroll(true);
						$reroll_question = "Would you like to reroll your die? If so, how many?";
						return true; //Situational command needed
					}
					//When rolling two dice, the dice are always summed together.
					if($rollarray[1]){ //Rolled twice
						$message->channel->send("<@$author_id> rolled a " . $rollarray[0] . " and a " . $rollarray[1] . " for a final roll of " . array_sum($rollarray) . "." . $reroll_question);
					}else{
						$message->channel->send("<@$author_id> rolled a " . $rollarray[0] . "."  . $reroll_question);
						
						$phase = "INCOME";
						$game->setPhase("INCOME");
						//It's safe to move straight to the INCOME phase
					}
				}
			}
			
			if($phase == "INCOME"){ //Card effects happen here
				$game->diceActivation(); //Trigger card effects
				$phase = "CONSTRUCTION";
				$game->setPhase("CONSTRUCTION");
				//Send message with buy options
			}
			
			if($phase == "CONSTRUCTION"){
				if(substr($message_filtered, 0, 9) == "construct" || substr($message_filtered, 0, 3) == "buy"){
					$message_filtered = trim(str_replace("construct", "", $message_filtered));
					$message_filtered = trim(str_replace("buy", "", $message_filtered));
					$result = $game->construct($message_filtered); //Name of card being built
					if ($result !== null){
						$message->reply($result); //string response from game class
						$game->nextTurn();
						$phase = "ROLL";
						$game->setPhase("ROLL");
						return true;
					}else{
						$message->reply("Something went wrong! Please use the format `construct buildingname` or `buy buildingname`");
					}
				}
			}
		}else{
			/*
			///////////////////////////////
			Commands that work only on another player's turn
			///////////////////////////////
			*/
			
			/*
			///////////////////////////////
			Commands that work only during a certain phase of someone elses turn
			///////////////////////////////
			*/
			
		}
	}
	
	if($message_filtered == "help"){ //TODO
		//Generate documentation based on what the player is currently allowed to do?
		$documentation = "Available Commands:\n";
		return true;
	}
	echo "Uncaught ;mk command: `$message_filtered` by `<@$author_id>` for phase $phase on <@$turn>'s turn" . PHP_EOL;
	return true;
}
?>
