<?php
abstract class Landmark{
	// Properties
	protected $name; //string
	protected $alias; //alternative name?
	protected $cost; //int
	protected $img; //URL
	
	// Getters
	public function getName(){
		return $this->name;
	}
	public function getAlias(){
		return $this->alias;
	}
	public function getCost(){
		return $this->cost;
	}
	public function getImg(){
		return $this->img;
	}
}
abstract class CustomLandmark extends Landmark{	
	// Constructor
	public function __construct($param_name, $param_alias, $param_type, $param_activation_numbers, $param_cost, $param_income, $param_img){
		$this->name = $param_name;
		$this->alias = $param_alias;
		$this->type = $param_type;
		$this->activation_numbers = $param_activation_numbers;
		
		$this->cost = $param_cost;
		$this->income = $param_income;
		$this->img = $param_img;
	}
	
	abstract public function ActiveEffect($player, $game, $current_player);
}
class TrainStation extends Landmark{
	// Properties
	protected $name = "Train Station"; //string
	protected $alias = "Train"; //alternative name?
	protected $cost = 4; //int
	protected $img = "https://vignette.wikia.nocookie.net/machi-koro/images/3/37/Train_Station.svg"; //URL
	
	public function ActiveEffect($player, $game, $current_player){
		//This card has no active effect
	}
}
class ShoppingMall extends Landmark{	// Properties
	//Abstract Methods
	public function ActiveEffect($player, $game, $current_player){
		//
	}
}
class AmusementPark extends Landmark{	// Properties
	//Abstract Methods
	public function ActiveEffect($player, $game, $current_player){
		//
	}
}
class RadioTower extends Landmark{	// Properties
	//Abstract Methods
	public function ActiveEffect($player, $game, $current_player){
		//
	}
}



abstract class Card{
	// Properties
	protected $name; //string
	protected $alias; //alternative name?
	protected $type; //int
	protected $activation_numbers; //int array
	
	protected $cost; //int
	protected $income; //int
	protected $img; //URL

	// Getters
	public function getName(){
		return $this->name;
	}
	public function getAlias(){
		return $this->alias;
	}
	public function getCardType(){ //getType is a PHP function
		return $this->type;
	}
	public function getActivationNumbers(){
		return $this->activation_numbers;
	}
	public function getCost(){
		return $this->cost;
	}
	public function getIncome(){
		return $this->income;
	}
	public function getImg(){
		return $this->img;
	}
	
	//Methods
	abstract public function ActiveEffect($player, $game, $current_player);
}
class CustomCard extends Card{
	// Constructor
	public function __construct($param_name, $param_alias, $param_type, $param_activation_numbers, $param_cost, $param_income, $param_img){
		$this->name = $param_name;
		$this->alias = $param_alias;
		$this->type = $param_type;
		$this->activation_numbers = $param_activation_numbers;
		
		$this->cost = $param_cost;
		$this->income = $param_income;
		$this->img = $param_img;
	}
	
	public function ActiveEffect($player, $game, $current_player){
		//Refer the the active effect of a prefab card?
		return true;
	}
}

class WheatField extends Card{
	// Properties
	protected $name = "Wheat Field";
	protected $alias = "Wheat";
	protected $type = 1; //Primary Industry (Anyone's turn)
	protected $activation_numbers = [1];
	
	protected $cost = 1;
	protected $income = 1;
	protected $img = "https://www.yucata.de/Games/MachiKoro/images/est1_e.gif";
	
	public function ActiveEffect($player, $game, $current_player){
		$player->addCoins($this->income);
		return true;
	}
}
class Bakery extends Card{
	// Properties
	protected $name = "Bakery";
	protected $alias = "Bakery";
	protected $type = 2; //Secondary Industry (Only on player's turn)
	protected $activation_numbers = [2, 3];
	
	protected $cost = 1;
	protected $income = 1;
	protected $img = "https://www.yucata.de/Games/MachiKoro/images/est1_e.gif";	
	
	public function ActiveEffect($player, $game, $current_player){
		$player->addCoins($this->income);
		return true;
	}
}

class Ranch extends Card{	// Properties
	//Abstract Methods
	public function ActiveEffect($player, $game, $current_player){
		//
	}
}
class Forest extends Card{	// Properties
	//Abstract Methods
	public function ActiveEffect($player, $game, $current_player){
		//
	}
}
class Mine extends Card{	// Properties
	//Abstract Methods
	public function ActiveEffect($player, $game, $current_player){
		//
	}
}
class AppleOrchard extends Card{	// Properties
	//Abstract Methods
	public function ActiveEffect($player, $game, $current_player){
		//
	}
}
class ConvenienceStore extends Card{	// Properties
	//Abstract Methods
	public function ActiveEffect($player, $game, $current_player){
		//
	}
}
class CheeseFactory extends Card{	// Properties
	//Abstract Methods
	public function ActiveEffect($player, $game, $current_player){
		//
	}
}
class FurnitureFactory extends Card{	// Properties
	//Abstract Methods
	public function ActiveEffect($player, $game, $current_player){
		//
	}
}
class FruitandVegetableMarket extends Card{	// Properties
	//Abstract Methods
	public function ActiveEffect($player, $game, $current_player){
		//
	}
}
class Cafe extends Card{	// Properties
	// Properties
	protected $name = "Wheat Field";
	protected $alias = "Wheat";
	protected $type = 1; //Primary Industry (Anyone's turn)
	protected $activation_numbers = [1];
	
	protected $cost = 1;
	protected $income = 1;
	protected $img = "https://www.yucata.de/Games/MachiKoro/images/est1_e.gif";
	
	//Abstract Methods
	public function ActiveEffect($player, $game, $current_player){
		//remove coins from current player and add removed coins to player
		$removed_coins = $current_player->removeCoins($this->income);
		$total_coins = $player->addCoins($removed_coins);
		$results = array($removed_coins, $total_coins);
		return $results;
	}
}
class FamilyRestaurant extends Card{	// Properties
	//Abstract Methods
	public function ActiveEffect($player, $game, $current_player){
		//
	}
}
class Stadium extends Card{	// Properties
	//Abstract Methods
	public function ActiveEffect($player, $game, $current_player){
		//
	}
}
class TVStation extends Card{	// Properties
	//Abstract Methods
	public function ActiveEffect($player, $game, $current_player){
		//
	}
}
class BusinessCenter extends Card{	// Properties
	//Abstract Methods
	public function ActiveEffect($player, $game, $current_player){
		//
	}
}

class Board{ //Cards available for purchase
	// Properties
	private $primary;
	private $secondary;
	private $restaurant;
	private $major_establishment;
	private $landmark;
	
	public function __construct($param_board){ //Populate cards on the board from an prefabricated array
		$this->primary = $param_board[0];
		$this->secondary = $param_board[1];
		$this->restaurant = $param_board[2];
		$this->major_establishment = $param_board[3];
		$this->landmark = $param_board[4];
	}
	
	// Methods
	public function remove($param_string){ //Takes an expected card name or alias
		$card = __search($param_string);
		if($card !== NULL){
			$remove_result = __remove($card[0], $card[1]);
		}else{
			//Card not found error
		}
		if($result !== false){
			//Successfully removed card message
		}else{
			//Card found but cannot remove error (if this triggers then something has gone terribly wrong)
		}
	}
	// Internal Methods
	public function __search($param_string){ //Returns a reference to a Card object and string (SLOW)
		$result = NULL;
		foreach ($this->primary as $card){
			$name = $card->getName();
			$alias = $card->getAlias();
			if ( (strtolower($name) == $param_string) || (strtolower($alias) == $param_string) ){
				return array($card, "primary");
			}
		}
		foreach ($this->secondary as $card){
			$name = $card->getName();
			$alias = $card->getAlias();
			if ( (strtolower($name) == $param_string) || (strtolower($alias) == $param_string) ){
				return array($card, "secondary");
			}
		}
		foreach ($this->restaurant as $card){
			$name = $card->getName();
			$alias = $card->getAlias();
			if ( (strtolower($name) == $param_string) || (strtolower($alias) == $param_string) ){
				return array($card, "restaurant");
			}
		}
		foreach ($this->major_establishment as $card){
			$name = $card->getName();
			$alias = $card->getAlias();
			if ( (strtolower($name) == $param_string) || (strtolower($alias) == $param_string) ){
				return array($card, "major_establishment");
			}
		}
		foreach ($this->landmark as $card){
			$name = $card->getName();
			$alias = $card->getAlias();
			if ( (strtolower($name) == $param_string) || (strtolower($alias) == $param_string) ){
				return array($card, "landmark");
			}
		}
		return $result; //NULL
	}
	public function __remove($param_card, $param_string){ //Remove an object from an array
		switch ($param_string): //FAST
			case "primary":
				foreach ($this->primary as $key => $card){
					if ($card == $param_card){
						unset($this->primary[$key]);
						return true;
					}
				}
				return false;
			case "secondary":
				foreach ($this->secondary as $key => $card){
					if ($card == $param_card){
						unset($this->secondary[$key]);
						return true;
					}
				}
				return false;
			case "restaurant":
				foreach ($this->restaurant as $key => $card){
					if ($card == $param_card){
						unset($this->restaurant[$key]);
						return true;
					}
				}
				return false;
			case "major_establishment":
				foreach ($this->major_establishment as $key => $card){
					if ($card == $param_card){
						unset($this->major_establishment[$key]);
						return true;
					}
				}
				return false;
			case "landmark":
				foreach ($this->landmark as $key => $card){
					if ($card == $param_card){
						unset($this->landmark[$key]);
						return true;
					}
				}
				return false;
			default: //SLOW
				foreach ($this->primary as $key => $card){
					if ($card == $param_card){
						unset($this->primary[$key]);
						return true;
					}
				}
				foreach ($this->secondary as $key => $card){
					if ($card == $param_card){
						unset($this->secondary[$key]);
						return true;
					}
				}
				foreach ($this->restaurant as $key => $card){
					if ($card == $param_card){
						unset($this->restaurant[$key]);
						return true;
					}
				}
				foreach ($this->major_establishment as $key => $card){
					if ($card == $param_card){
						unset($this->major_establishment[$key]);
						return true;
					}
				}
				foreach ($this->landmark as $key => $card){
					if ($card == $param_card){
						unset($this->landmark[$key]);
						return true;
					}
				}
				return false;
		endswitch;
    }
}
class Hand{
	// Properties
	protected $primary; //array
	protected $secondary; //array
	protected $restaurant; //array
	protected $major_establishment; //array
	protected $landmark; //array
	
	// Constructor
	public function __construct($param_primary, $param_secondary, $param_restaurant, $param_major_establishment, $param_landmark){
		$this->primary = $param_primary; //Trigger on any turn
		$this->secondary = $param_secondary; //Trigger only on player's turn
		$this->restaurant = $param_restaurant;
		$this->major_establishment = $param_major_establishment;
		$this->landmark = $param_landmark;
	}
	
	// Methods
	public function getPrimary(){
		return $this->primary;
	}
	public function getSecondary(){
		return $this->secondary;
	}
	public function getRestaurant(){
		return $this->restaurant;
	}
	public function getMajorEstablishment(){
		return $this->major_establishment;
	}
	public function getLandmark(){
		return $this->landmark;
	}
	public function addCard($param_card, $param_string){
		switch ($param_string): //FAST
			case "primary":
				$this->primary[] = $card;
				return true;
			case "secondary":
				$this->secondary[] = $card;
				return true;
			case "restaurant":
				$this->restaurant[] = $card;
				return true;
			case "major_establishment":
				$this->major_establishment[] = $card;
				return true;
			case "landmark":
				$this->landmark[] = $card;
				return true;
		endswitch;
	}
	// Internal Methods
	
}
class Player{
	// Properties
	private $discord; //Discord ID
	private $coins; //int
	private $gems; //int
	//Objects
	private $hand;
	
	// Constructor
	public function __construct($param_discord, $param_coins, $param_gems){
		$temp_hand = new Hand(
			array( new WheatField() ),
			array( new Bakery() ),
			array( null ),
			array( null ),
			array( null )
		); //arrays of cards owned at start of game
		$this->discord = $param_discord; //discord ID
		$this->coins = $param_coins; //should default to 3
		$this->gems = $param_gems; //0
		$this->hand = $temp_hand;
	}
	
	// Getter Methods
	public function getDiscordID(){
		return $this->discord;
	}
	public function getName(){
		//Get nickname from Discord
		//return name;
	}
	public function getCoins(){
		return $this->coins;
	}
	public function getGems(){
		return $this->gems;
	}
	public function getHand(){
		return $this->hand;
	}
	//Setter Methods
	public function setDiscord($param_discord){
		$this->discord = $param_discord;
	}
	public function setName($name){
		$this->name = $name;
	}
	public function addCoins($amount){
		$this->coins += $amount;
		return $this->coins;
	}
	public function removeCoins($amount){
		$removed_coins = 0;
		while ($amount != 0){
			$this->coins--;
			$removed_coins++;
			$amount--;
		}
		return $removed_coins;
	}
}
class MKGame{
	protected $default_primary;
	protected $default_secondary;
	protected $default_restaurant;
	protected $default_major_establishment;
	protected $default_landmarks;
	protected $default_board;

	// Properties
	protected $id; //discord id, created the MKGame and should have Gamemaster privileges
	protected $legacy; //boolean
	protected $harbor; //boolean
	protected $roll; //int array, probably generated by rollDie($num)
	protected $locked; //boolean
	protected $phase; //string (ROLL/INCOME/CONSTRUCTION)
	// Objects
	protected $board; //Board
	protected $players; //Player array
	protected $turn; //Discord ID
	//Calculated
	private $can_reroll; //Assumed false, calculated on die roll
	private $can_swap; //Assumed false, calculated on die roll
	
	
	public function __construct($param_discord){ //Discord ID of person who started the MKGame
		$this->id = $param_discord;
		$this->turn = $param_discord;
		$this->phase = "SETUP";
		
		$temp_player = new Player($param_discord, 3, 0);
		$this->players[$param_discord] = $temp_player;
		
		$this->legacy = false; //Not yet implemented
		$this->harbor = false; //Not yet implemented, expansion
		
		$this->default_primary = array(
			new WheatField(), new WheatField(), new WheatField(), new WheatField(), new WheatField(), new WheatField(), 
			new Ranch(), new Ranch(), new Ranch(), new Ranch(), new Ranch(), new Ranch(), 
			new Forest(), new Forest(), new Forest(), new Forest(), new Forest(), new Forest(), 
			new Mine(), new Mine(), new Mine(), new Mine(), new Mine(), new Mine(), 
			new AppleOrchard(), new AppleOrchard(), new AppleOrchard(), new AppleOrchard(), new AppleOrchard(), new AppleOrchard()
		); //Primary Industry Cards
		$this->default_secondary = array(
			new Bakery(), new Bakery(), new Bakery(), new Bakery(), new Bakery(), new Bakery(), 
			new ConvenienceStore(), new ConvenienceStore(), new ConvenienceStore(), new ConvenienceStore(), new ConvenienceStore(), new ConvenienceStore(), 
			new CheeseFactory(), new CheeseFactory(), new CheeseFactory(), new CheeseFactory(), new CheeseFactory(), new CheeseFactory(), 
			new FurnitureFactory(), new FurnitureFactory(), new FurnitureFactory(), new FurnitureFactory(), new FurnitureFactory(), new FurnitureFactory(), 
			new FruitandVegetableMarket(), new FruitandVegetableMarket(), new FruitandVegetableMarket(), new FruitandVegetableMarket(), new FruitandVegetableMarket(), new FruitandVegetableMarket()
		); //Secondary Industry Cards
		$this->default_restaurant = array(
			new Cafe(), new Cafe(), new Cafe(), new Cafe(), new Cafe(), new Cafe(), 
			new FamilyRestaurant(), new FamilyRestaurant(), new FamilyRestaurant(), new FamilyRestaurant(), new FamilyRestaurant(), new FamilyRestaurant()
		); //Restaurant Cards
		$this->default_major_establishment = array(
			new Stadium(), new Stadium(), new Stadium(), new Stadium(), new Stadium(), new Stadium(), 
			new TVStation(), new TVStation(), new TVStation(), new TVStation(), new TVStation(), new TVStation(), 
			new BusinessCenter(), new BusinessCenter(), new BusinessCenter(), new BusinessCenter(), new BusinessCenter(), new BusinessCenter()
		); // Major Establishment Cards
		$this->default_landmarks = array(
			new TrainStation(), new TrainStation(), new TrainStation(), new TrainStation(), 
			new ShoppingMall(), new ShoppingMall(), new ShoppingMall(), new ShoppingMall(), 
			new AmusementPark(), new AmusementPark(), new AmusementPark(), new AmusementPark(), 
			new RadioTower(), new RadioTower(), new RadioTower(), new RadioTower()
		); //Landmark Cards
		$this->default_board = array($this->default_primary, $this->default_secondary, $this->default_restaurant, $this->default_major_establishment, $this->default_landmark);
		$this->board = new Board($this->default_board);
	}
	
	//Variable Getters
	public function getID(){
		return $this->id;
	}
	public function getHost(){
		return $this->id;
	}
	//Reference getters
	public function getTurn(){
		return $this->turn;
	}
	public function getCanReroll(){
		return $this->can_reroll;
	}
	public function getCanSwap(){
		return $this->can_swap;
	}
	
	public function getLegacy(){
		return $this->legacy;
	}
	public function getHarbor(){
		return $this->harbor;
	}
	public function getRoll(){
		return $this->roll;
	}
	public function getLocked(){
		return $this->roll;
	}
	public function getPhase(){
		return $this->phase;
	}
	//Object Getters
	public function getBoard(){
		return $this->board;
	}
	public function getPlayers(){
		return $this->players;
	}
	//Search Object Getters
	public function getPlayer($param_discord){
		if ($this->players[$param_discord])
			return $this->players[$param_discord];
		return false;
	}
	
	// Setters
	public function addPlayer($param_discord){ //returns false if MKGame already has max number of players
		if ($this->locked !== true){
			if (count($this->players) < 4){
				$temp_player = new Player($param_discord, 3, 0);
				$this->players[$param_discord] = $temp_player;
				return true;
			}
		}
		return false;
	}
	public function canReroll($bool){
		$this->can_reroll = $bool;
		return true;
	}
	public function canSwap($bool){
		$this->can_swap = $bool;
		return true;
	}
	public function setPhase($string){
		$this->phase = $string;
		return true;
	}
	// Methods
	public function start($author_id){
		echo "author_id: $author_id" . PHP_EOL;
		echo "this->id: " . $this->id . PHP_EOL;
		if ($author_id == $this->id){
			if ($this->locked !== false){
				$player_count = count($this->players); echo "player_count: $player_count" . PHP_EOL;
				if ($player_count > 1){
					$this->locked = true; //Prevent new players from joining
					//Set game phase
					return "**Machi Koro game $author_id has been started by the host!**";
					
				}else{
					return "Not enough players!";
				}
			}else{
				return "This game has already started!";
			}
		}else{
			return "You are not the host! Please ask <@$author_id> so start the game.";
		}
	}
	public function rollDie($num){
		$temp_array = array();
		for ($i = 0; $i < $num; $i++){
			$temp_array[] = rand(1,6);
		}
		$this->roll = $temp_array;
		return $this->roll;
	}
	public function diceActivation(){
		$temp_array = $this->roll;
		$temp_roll = array_sum($temp_array);
		$current_player = $this->getPlayer($this->turn);
		
		//Use internal cursor and a while loops to itterate through arrays because we must start from the current player and work backwards.
		/*
		next($this->players);
		while($temp_player = each($this->players)) {
			
		}
		*/
		
		//Restaurant
		$target_player = reset($this->players);
		while($target_player->getDiscordID() != $this->turn){ //start with current player
			$target_player = next($this->players);
			if ($target_player === FALSE) $target_player = reset($this->players);
		}
		do{ //process for current player and work backwards
			$hand = $target_player->getHand();
			$restaurant = $hand->getRestaurant();
			foreach($restaurant as $card){
				if ($card){
					if ( in_array( $temp_roll, $card->getActivationNumbers() ) ) {
						$temp_result = $card->ActiveEffect($player, $this, $current_player); //Steal coins from the current turn's player
					}
				}
			}
			//Build an array using the temp_result arrays and assign it to a foreach(players->getDiscordID)=>temp_array to be output later, perhaps as a rich embed showing totals changed?
			$target_player = prev($this->players);
			if ($target_player === FALSE) $target_player = end($this->players);
		}while ($target_player->getDiscordID() != $this->turn);
		//Secondary
		$current_hand = $current_player->getHand();
		$secondary = $current_hand->getSecondary();
		foreach($secondary as $card){
			if ($card){
				$temp_array = $card->getActivationNumbers();
				if ( in_array( $temp_roll, $card->getActivationNumbers() ) ) {
				  // Trigger effect for current player
				  $card->ActiveEffect($current_player, $this, null);
				}
			}
		}
		//Primary
		foreach ($players as $player) {
			$hand = $player->getHand();
			$primary = $hand->getPrimary();
			foreach($primary as $card){
				if ($card){
					$temp_array = $card->getActivationNumbers();
					if ( in_array($temp_roll, $temp_array) ) {
						// Trigger effect for all players
						$card->ActiveEffect($player, $this, null);
					}
				}
			}
		}
		/*//Major Establishments
		$major_establishment = 
		foreach($turn->Hand->major_establishment as $card){
			if ( in_array( $temp_roll, $card->getActivationNumbers() ) ) {
			  // Trigger effect for current player
			}
		}
		*/
		return true;
	}
	public function construct($param_string){
		$current_player = $this->getPlayer($this->turn);
		$current_hand = $current_player->getHand();
		//Search board to check if card is available on the board
		$card = $this->board->__search($param_string);
		if ($card !== NULL){
			$card_name = $card[0]->getName();
			$cost = $card[0]->getCost();
			$coins = $current_player->getCoins();
			if ($coins >= $cost){ //Add the card to the player's hand and remove it from the board
				$add_result = $current_hand->addCard($card[0], $card[1]);
			}else{
				return "$card_name costs $cost coins and you only own $coins.";
			}
			if ($add_result === true){
				$remove_result = $this->board->__remove($card[0], $card[1]);
			}
			if ($remove_result === true){
				return "You bought a $card_name for $cost coins! You have $coins coins remaining.";
			}
		}
		return "Unable to locate card matching the name '$param_string!'";
	}
	public function nextTurn(){
		$current_player = $this->getPlayer($this->turn);
		$current_player_pos = array_search($current_player, $this->players); //Variable is not used, but it sets the internal pointer
		$next_player = next($this->players);
		if ($next_player === FALSE) $next_player = reset($this->players);
		$this->turn = $next_player->getDiscordID();
	}
}

?>
