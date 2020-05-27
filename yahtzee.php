<?php
include_once('yahtzee_funcs.php');
if ($message_content_lower == $command_symbol . 'yahtzee status'){
	if ($GLOBALS[$author_id . 'yahtzee']){
		$output_string = "\nRunning: " . $GLOBALS[$author_id . 'yahtzee'] . "\n";
	}else $output_string = "\nRunning: null\n";
	$output_string = $output_string . "Function: " . $GLOBALS["$author_id"."_yahtzee_function"] . "\n";
	$output_string = $output_string . "Subaction: " . $GLOBALS["$author_id"."_yahtzee_subaction"] . "\n";
	$output_string = $output_string . "Stage: " . $GLOBALS["$author_id"."_yahtzee_stage"] . "\n";
	$message->reply($output_string);
}

if ($GLOBALS[$author_id . 'yahtzee'] != true){
	if ($message_content_lower == $command_symbol . 'yahtzee start'){
		$GLOBALS[$author_id . 'yahtzee'] = true;
		yahtzee_setup($author_id);
		$GLOBALS["$author_id"."_yahtzee_stage"] = "initialroll";
	}
	elseif ($message_content_lower == $command_symbol . 'yahtzee resume'){
		$GLOBALS[$author_id . 'yahtzee'] = true;
	}
	else return true;
}
	/*
	***************************
	Player interrupt functions
	***************************
	*/
	
	if ($message_content_lower == $command_symbol . 'yahtzee stop'){
		$GLOBALS[$author_id . 'yahtzee'] = false;
		yahtzee_setup($author_id);
		$message->reply("Your game of yahtzee has been ended and your progress has been cleared!");
		return true;
	}
	if ($message_content_lower == $command_symbol . 'yahtzee pause'){
		$GLOBALS[$author_id . 'yahtzee'] = false;
		$message->reply("Your game of yahtzee has been paused! Use `". $command_symbol . "yahtzee resume` to pick up where you left off.");
		return true;
	}
	if ($message_content_lower == $command_symbol . 'yahtzee rolls'){
		$rolled_string = implode (", ", $GLOBALS[$author_id . "_rolled"]);
		$face_string = "";
		foreach ($GLOBALS[$author_id . "_rolled"] as $rolled){
			$face_string = $face_string . $GLOBALS["yahtzeew"][$rolled] . "\n";
		}
		$output_string = $rolled_string . "\n" . $face_string;
		$message->reply("Your current rolls are : $output_string");
		return true;
	}
	
	/*
	***************************
	System interrupt functions
	***************************
	*/
	
	
	
	if ($GLOBALS["$author_id"."_yahtzee_function"] == "GET_YN"){
		if (strtoupper($message_content) == "Y" || strtoupper($message_content) == "N"){
			$GLOBALS["$author_id"."_yn"] = strtoupper($message_content);
			$GLOBALS["$author_id"."_yahtzee_function"] == null;
		}else{ //append prev_question to output
			echo "Not a valid answer! (Y/N)" . PHP_EOL;
			$message->reply("Not a valid answer! (Y/N)");
			return true;
		}
	}
	if ($GLOBALS["$author_id"."_yahtzee_function"] == "GET_INT"){
		if(is_numeric($message_content)){
			$GLOBALS["$author_id"."_int"] = $message_content;
			$GLOBALS["$author_id"."_yahtzee_function"] == null;
		}else{ //append prev_question to output
			echo "Not a valid integer!" . PHP_EOL;
			$message->reply("Invalid entry! Input must be an integer");
			return true;
		}
	}
	
	
	
	/*
	***************************
	Game Stages
	***************************
	*/
	if ($GLOBALS[$author_id."_yahtzee_stage"] == "initialroll"){
		//These are init in yahtzee_setup but we want to clear them for every new roll
		$GLOBALS[$author_id . '_rolled'] = array();
		$GLOBALS[$author_id . '_faces'] = array();
	
		for ($x = 0; $x <= 5; $x++) {
			$die = DIE_ROLL();
			$GLOBALS[$author_id . "_rolled"][] = $die;
			$GLOBALS[$author_id . "_faces"][] = $GLOBALS["yahtzee_FACES"][($die)];
		}
		$rolled_string = implode (", ", $GLOBALS[$author_id . "_rolled"]);
		//$face_string = implode (" \n", $GLOBALS[$author_id . "_faces"]);
		//$output_string = $rolled_string . "\n" . $face_string;
		
		$message->reply("Your initial roll is : $rolled_string");
		
		$GLOBALS[$author_id . '_rerollCounter'] = 0;
		$GLOBALS[$author_id ."_yahtzee_stage"] = "rerolls";
	}
	if ($GLOBALS[$author_id."_yahtzee_stage"] == "rerolls"){
		#offer rerolling of each die
		$skip = false;
		if($GLOBALS[$author_id."_yahtzee_subaction"] == "HOLDCHECK"){
			//
			if($GLOBALS["$author_id"."_yn"] == "Y"){
				$skip = false;
			}
			if($GLOBALS["$author_id"."_yn"] == "N"){
				$skip = true;
			}
		}
		if ($skip != true){
			if($GLOBALS["$author_id"."_yn"] == "Y"){
				#reroll die
				$die = DIE_ROLL();
				$GLOBALS[$author_id . "_rolled"][$GLOBALS[$author_id . '_rerollCounter']] = $die;
				$GLOBALS[$author_id . '_rerollCounter']+=1;
				$GLOBALS["$author_id"."_yn"] = null;
				
				$die_string = "Your new roll is now : $die";
				$face_num = ($GLOBALS[$author_id . "_rolled"][$GLOBALS[$author_id . '_rerollCounter']]);
				$face_string = $GLOBALS['yahtzee_FACES'][$die];
				$output_string = "$die_string \n $face_string";
				$message->reply($output_string);
				#do the next one
			}
			if($GLOBALS["$author_id"."_yn"] == "N"){
				$GLOBALS[$author_id . '_rerollCounter']+=1;
				$GLOBALS["$author_id"."_yn"] = null;
			}
			if( $GLOBALS[$author_id . "_rolled"][$GLOBALS[$author_id . '_rerollCounter']] ){
				$die_string = "Die #" . ($GLOBALS[$author_id . '_rerollCounter']+1) . "=" . $GLOBALS[$author_id . "_rolled"][$GLOBALS[$author_id . '_rerollCounter']];
				//$facenum = ($GLOBALS[$author_id . "_rolled"][$GLOBALS[$author_id . '_rerollCounter']]);
				$face_string = $GLOBALS['yahtzee_FACES'][($GLOBALS[$author_id . "_rolled"][$GLOBALS[$author_id . '_rerollCounter']])];
				
				$output_string = "$die_string \n $face_string \n Do you want to reroll?";
				$message->reply($output_string);
				$GLOBALS["$author_id"."_yahtzee_function"] = "GET_YN";
				return true;
			}else{
				#all die have been confirmed, offer to reroll a maximum of two times
				$GLOBALS[$author_id . '_rerollCounter']=0;
				$GLOBALS[$author_id . '_rerollTurn']+=1;
				if ($GLOBALS[$author_id . '_rerollTurn'] == 4){
					$GLOBALS["$author_id"."_yahtzee_stage"] = "countRolls";
				}else{
					#display current rolls
					$rolled_string = implode (", ", $GLOBALS[$author_id . "_rolled"]);
					//$face_string = implode (" \n", $GLOBALS[$author_id . "_faces"]);
					//$output_string = $rolled_string . "\n" . $face_string;
					
					if ($GLOBALS[$author_id . '_rerollTurn'] != 3){
						$message->reply("Your current roll is : $rolled_string \n Do you want to reroll again?");
						$GLOBALS["$author_id"."_yahtzee_function"] = "GET_YN";
						$GLOBALS[$author_id."_yahtzee_subaction"] = "HOLDCHECK";
						//this prompt is acutally for the first die, needs a subaction check
						return true;
					}
					else{
						$message->reply("Your final roll is : $rolled_string");
						$GLOBALS["$author_id"."_yahtzee_function"] = null;
						$GLOBALS["$author_id"."_yahtzee_stage"] = "countRolls";
						//Program hangs and asks for y/n
					}
				}
			}
		}else{
			//break
			$message->reply("Your final roll is : $rolled_string");
			$GLOBALS["$author_id"."_yahtzee_function"] = null;
			$GLOBALS["$author_id"."_yahtzee_stage"] = "countRolls";
		}
	}
	
	if ($GLOBALS["$author_id"."_yahtzee_stage"] == "countRolls"){
		#Count how many times a number was rolled
		foreach($GLOBALS[$author_id . '_rolled'] as $a){
			switch($a){
				case 1:
					$GLOBALS[$author_id . '_UPPER'][0]+=$a;
					$GLOBALS[$author_id . '_$num0']+=1;
					break;
				case 2:
					$GLOBALS[$author_id . '_UPPER'][1]+=$a;
					$GLOBALS[$author_id . '_$num1']+=1;
					break;
				case 3:
					$GLOBALS[$author_id . '_UPPER'][2]+=$a;
					$GLOBALS[$author_id . '_$num2']+=1;
					break;
				case 4:
					$GLOBALS[$author_id . '_UPPER'][3]+=$a;
					$GLOBALS[$author_id . '_$num3']+=1;
					break;
				case 5:
					$GLOBALS[$author_id . '_UPPER'][4]+=$a;
					$GLOBALS[$author_id . '_$num4']+=1;
					break;
				case 6:
					$GLOBALS[$author_id . '_UPPER'][5]+=$a;
					$GLOBALS[$author_id . '_$num5']+=1;
					break;
				default:
					echo "DEFAULT CASE ON LINE " . __LINE__ . ", was something not declared?";
			}
		}
		$GLOBALS["$author_id"."_yahtzee_stage"] = "calculateScore";
		$GLOBALS["$author_id"."_yahtzee_subaction"] = "addBonus";
		$GLOBALS["$author_id"."_yahtzee_function"] = null;
		//add Yahtzee?
	}
	
	if ($GLOBALS["$author_id"."_yahtzee_stage"] == "calculateScore"){
		#Count how many times a number was rolled

		if ($GLOBALS["$author_id"."_yahtzee_subaction"] == "addBonus"){
			if($GLOBALS[$author_id . '_bonusCount'] == 0){
				if(array_sum($GLOBALS[$author_id . '_UPPER'])>62){
					$GLOBALS[$author_id . '_bonus'] = 35;
					$GLOBALS[$author_id . '_bonusCount']+=1;
				}
			}
			$message->reply("Add Yahtzee?");
			$GLOBALS["$author_id"."_yahtzee_function"] = "GET_YN";
			$GLOBALS["$author_id"."_yahtzee_subaction"] = "addYahtzee";
			return true;
		}
		if ($GLOBALS["$author_id"."_yahtzee_subaction"] == "addYahtzee"){
			if($GLOBALS["$author_id"."_yn"] == "Y"){
				if($GLOBALS[$author_id . '_yahtzeeCounter'] == 0){
					$GLOBALS["$author_id"."_LOWER"][5]+=50;
					$GLOBALS[$author_id . '_yahtzeeCounter'] = 1;
				}else{
					$GLOBALS["$author_id"."_LOWER"][5]+=50;
					$GLOBALS[$author_id . '_yahtzeeScore']+=100;
					$GLOBALS[$author_id . '_yahtzeeCounter']+=1;
					$GLOBALS[$author_id . '_yahtzeeBonusCounter']+=1;
				}
			}
			if($GLOBALS["$author_id"."_LOWER"][4] == 0){
				$message->reply("Add large straight?");
				$GLOBALS["$author_id"."_yahtzee_function"] = "GET_YN";
				$GLOBALS["$author_id"."_yahtzee_subaction"] = "addLargeStraight";
				return true;
			}else $GLOBALS["$author_id"."_yahtzee_subaction"] = "addLargeStraight";
		}
		if ($GLOBALS["$author_id"."_yahtzee_subaction"] == "addLargeStraight"){
			if($GLOBALS["$author_id"."_LOWER"][4] == 0){
				if($GLOBALS["$author_id"."_yn"] == "Y")
					$GLOBALS["$author_id"."_LOWER"][4]+=1;
				$GLOBALS["$author_id"."_yn"] = null;
			}
			if($GLOBALS["$author_id"."_LOWER"][3] == 0){
				$message->reply("Add full house?");
				$GLOBALS["$author_id"."_yahtzee_function"] = "GET_YN";
				$GLOBALS["$author_id"."_yahtzee_subaction"] = "addFullHouse";
				return true;
			}else $GLOBALS["$author_id"."_yahtzee_subaction"] = "addFullHouse";
		}
		if ($GLOBALS["$author_id"."_yahtzee_subaction"] == "addSmallStraight"){
			if($GLOBALS["$author_id"."_LOWER"][3] == 0){
				if($GLOBALS["$author_id"."_yn"] == "Y")
					$GLOBALS["$author_id"."_LOWER"][3]+=1;
				$GLOBALS["$author_id"."_yn"] = null;
			}
			if($GLOBALS["$author_id"."_LOWER"][2] == 0){
				$message->reply("Add full house?");
				$GLOBALS["$author_id"."_yahtzee_function"] = "GET_YN";
				$GLOBALS["$author_id"."_yahtzee_subaction"] = "addFullHouse";
				return true;
			}else $GLOBALS["$author_id"."_yahtzee_subaction"] = "addFullHouse";
		}
		if ($GLOBALS["$author_id"."_yahtzee_subaction"] == "addFullHouse"){
			if($GLOBALS["$author_id"."_LOWER"][2] == 0){
				if($GLOBALS["$author_id"."_yn"] == "Y")
					$GLOBALS["$author_id"."_LOWER"][2]+=1;
				$GLOBALS["$author_id"."_yn"] = null;
			}
			if($GLOBALS["$author_id"."_LOWER"][6] == 0){
				$message->reply("Add chance?");
				$GLOBALS["$author_id"."_yahtzee_function"] = "GET_YN";
				$GLOBALS["$author_id"."_yahtzee_subaction"] = "addChance";
				return true;
			}else $GLOBALS["$author_id"."_yahtzee_subaction"] = "addChance";
		}
		if ($GLOBALS["$author_id"."_yahtzee_subaction"] == "addChance"){
			if($GLOBALS["$author_id"."_LOWER"][6] == 0){
				if($GLOBALS["$author_id"."_yn"] == "Y")
					$GLOBALS["$author_id"."_LOWER"][6]+=array_sum($GLOBALS["$author_id"."_rolled"]);
				$GLOBALS["$author_id"."_yn"] = null;
			}
			if($GLOBALS["$author_id"."_LOWER"][1] == 0){
				$message->reply("Add 4 of a kind?");
				$GLOBALS["$author_id"."_yahtzee_function"] = "GET_YN";
				$GLOBALS["$author_id"."_yahtzee_subaction"] = "add4kind";
				return true;
			}else $GLOBALS["$author_id"."_yahtzee_subaction"] = "add4kind";
		}
		if ($GLOBALS["$author_id"."_yahtzee_subaction"] == "add4kind"){
			if($GLOBALS["$author_id"."_LOWER"][1] == 0){
				if($GLOBALS["$author_id"."_yn"] == "Y"){
					if (is_numeric($GLOBALS["$author_id"."_int"])){
						$GLOBALS["$author_id"."_LOWER"][1]=$GLOBALS["$author_id"."_int"];
					}else{
						$GLOBALS["$author_id"."_yahtzee_function"] = "GET_INT";
						$message->reply("Enter an integer.");
						return true;
					}
					$GLOBALS["$author_id"."_yn"] = null;
					$GLOBALS["$author_id"."_int"] = null;
				}
			}
			if($GLOBALS["$author_id"."_LOWER"][0] == 0){
				$message->reply("Add 3 of a kind?");
				$GLOBALS["$author_id"."_yahtzee_function"] = "GET_YN";
				$GLOBALS["$author_id"."_yahtzee_subaction"] = "add3kind";
				return true;
			}else $GLOBALS["$author_id"."_yahtzee_subaction"] = "add3kind";
		}
		if ($GLOBALS["$author_id"."_yahtzee_subaction"] == "add3kind"){
			if($GLOBALS["$author_id"."_LOWER"][0] == 0){
				if($GLOBALS["$author_id"."_yn"] == "Y"){
					if (is_numeric($GLOBALS["$author_id"."_int"])){
						$GLOBALS["$author_id"."_LOWER"][0]=$GLOBALS["$author_id"."_int"];
					}else{
						$GLOBALS["$author_id"."_yahtzee_function"] = "GET_INT";
						$message->reply("Enter an integer.");
						return true;
					}
				}
				$GLOBALS["$author_id"."_yn"] = null;
				$GLOBALS["$author_id"."_int"] = null;
			}
			$GLOBALS["$author_id"."_yahtzee_subaction"] = "display_score";
		}
		if ($GLOBALS["$author_id"."_yahtzee_subaction"] == "display_score"){
			$current_core = display_score($author_id); //Nothing happens?
			$message->reply("\n$current_score");
			$GLOBALS["$author_id"."_scoreTurn"]+=1;
			if ($GLOBALS["$author_id"."_scoreTurn"] != 14){
				//Start the next turn
				$GLOBALS["$author_id"."_yahtzee_subaction"] = null;
				$GLOBALS["$author_id"."_yahtzee_stage"] = "initialroll";
			}else{
				$message->reply("Game over!");
				$GLOBALS[$author_id . 'yahtzee'] = false;
			}
		}
	}
	

?>
