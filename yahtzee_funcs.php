<?php
function yahtzee_setup($author_id){ //THIS FUNCTION MUST BE CALLED PRIOR TO STARTING THE GAME
	$GLOBALS[$author_id."_yahtzee_function"] = null;
	$GLOBALS[$author_id."_yahtzee_subaction"] = null;
	$GLOBALS[$author_id."_yahtzee_stage"] = null;
	$GLOBALS[$author_id."_yn"] = null;
	$GLOBALS[$author_id."_int"] = null;
	
	$GLOBALS[$author_id . '_UPPER']=[0,0,0,0,0,0];   #ALL THE UPPER CATEGORIES                                   
	$GLOBALS[$author_id . '_LOWER']=[0,0,0,0,0,0,0]; #ALL THE LOWER CATEGORIES
	$GLOBALS[$author_id . '_num0'] = 0;
	$GLOBALS[$author_id . '_num1'] = 0;
	$GLOBALS[$author_id . '_num2'] = 0;
	$GLOBALS[$author_id . '_num3'] = 0;
	$GLOBALS[$author_id . '_num4'] = 0;
	$GLOBALS[$author_id . '_num5'] = 0;
	
	$GLOBALS[$author_id . '_yahtzeeCounter']=0;
	$GLOBALS[$author_id . '_yahtzeeBonusCounter']=0;
	$GLOBALS[$author_id . '_yahtzeeScore']=0;
	$GLOBALS[$author_id . '_bonusCount']=0;
	$GLOBALS[$author_id . '_bonus']=0;
	$GLOBALS[$author_id . '_scoreTurn']=1;
	
	$GLOBALS[$author_id . '_rerollCounter'] = 0;
	$GLOBALS[$author_id . '_rolled']=array();
	$GLOBALS[$author_id . '_faces']=array();
	$GLOBALS[$author_id . '_rerollTurn']=1;
	
	//holy fuck this is horrible
	$GLOBALS["yahtzee_FACES"]=array("",
	"https://game-icons.net/icons/ffffff/000000/1x1/skoll/inverted-dice-1.png",
	"https://game-icons.net/icons/ffffff/000000/1x1/skoll/inverted-dice-2.png",
	"https://game-icons.net/icons/ffffff/000000/1x1/skoll/inverted-dice-3.png",
	"https://game-icons.net/icons/ffffff/000000/1x1/skoll/inverted-dice-4.png",
	"https://game-icons.net/icons/ffffff/000000/1x1/skoll/inverted-dice-5.png",
	"https://game-icons.net/icons/ffffff/000000/1x1/skoll/inverted-dice-6.png",
	);
}
//game functions
function DIE_ROLL(){
	$number = rand(1, 6);
	return $number;
}
function display_score($author_id){
	$score_out = "";
	$score_out = $score_out . "TURN" . $GLOBALS[$author_id . '_scoreTurn'] . "
";
	$score_out = $score_out . " ONES: " . $GLOBALS[$author_id . '_UPPER'][0] . "
";
	$score_out = $score_out . " TWOS: " . $GLOBALS[$author_id . '_UPPER'][1] . "
";
	$score_out = $score_out . " THREES: " . $GLOBALS[$author_id . '_UPPER'][2] . "
";
	$score_out = $score_out . " FOURS: " . $GLOBALS[$author_id . '_UPPER'][3] . "
";
	$score_out = $score_out . " FIVES: " . $GLOBALS[$author_id . '_UPPER'][4] . "
";
	$score_out = $score_out . " SIXES: " . $GLOBALS[$author_id . '_UPPER'][5] . "
";
	$score_out = $score_out . " Total Score: ".  array_sum($GLOBALS[$author_id . '_UPPER']) . "
";
	$score_out = $score_out . " BONUS: " . $GLOBALS[$author_id . '_bonus'] . "
";
	$score_out = $score_out . " Total of Upper Section: " . (array_sum($GLOBALS['_UPPER'])+$GLOBALS[$author_id . '_bonus']) . "
";
	
	$score_out = $score_out . "=========================" . "
";
	$score_out = $score_out . " 3 OF A KIND: " . $GLOBALS[$author_id . "_LOWER"][0] . "
";
	$score_out = $score_out . " 4 OF A KIND: " . $GLOBALS[$author_id . "_LOWER"][1] . "
";
	$score_out = $score_out . " FULL: HOUSE: " . ($GLOBALS[$author_id . "_LOWER"][2]*25) . "
";
	$score_out = $score_out . " SMALL STRAIGHT: " . ($GLOBALS[$author_id . "_LOWER"][3]*30) . "
";
	$score_out = $score_out . " LARGE STRAIGHT: " . ($GLOBALS[$author_id . "_LOWER"][4]*40) . "
";
	$score_out = $score_out . " YAHTZEE: " . $GLOBALS[$author_id . "_LOWER"][5] . "
";
	$score_out = $score_out . " CHANCE: " . $GLOBALS[$author_id . "_LOWER"][6] . "
";
	$score_out = $score_out . " YAHTZEE BONUS:" . "
";
	$score_out = $score_out . " # NUMBER OF BONUS: " . $GLOBALS[$author_id . '_yahtzeeBonusCounter'] . "
";
	$score_out = $score_out . " SCORE 100 PER BONUS: " . $GLOBALS[$author_id . '_yahtzeeScore'] . "
";
	$GLOBALS[$author_id . 'totalLower'] = ($GLOBALS[$author_id . "_LOWER"][0]+$GLOBALS[$author_id . "_LOWER"][1]+($GLOBALS[$author_id . "_LOWER"][2]*25)+($GLOBALS[$author_id . "_LOWER"][3]*30)+($GLOBALS[$author_id . "_LOWER"][4]*40)+$GLOBALS[$author_id . "_LOWER"][5]+$GLOBALS[$author_id . "_LOWER"][6]+$GLOBALS[$author_id . 'yahtzeeScore']);
	$GLOBALS[$author_id . 'totalUpper'] = (array_sum($GLOBALS[$author_id . '_UPPER'])+$GLOBALS[$author_id . '_bonus']);
	$GLOBALS[$author_id . 'grandTotal'] = ($GLOBALS[$author_id . 'totalLower']+$GLOBALS[$author_id . 'totalUpper']);
	$score_out = $score_out . (" TOTAL LOWER: " . $GLOBALS[$author_id . 'totalLower'] . "\n");
	$score_out = $score_out . (" TOTAL UPPER: " . $GLOBALS[$author_id . 'totalUpper'] . "\n");
	$score_out = $score_out . (" GRAND TOTAL: " . $GLOBALS[$author_id . 'grandTotal']) . "\n";
	return $score_out;
}
?>
