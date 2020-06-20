<?php


//have every card just have a single function called ActiveEffect, a single trigger that works for every card
class Landmark{
	// Properties
	
	// Methods
	
	// Internal Methods
	
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
	
	abstract public function ActiveEffect($player, $player2);
}
class CustomCard extends Card{
	//Can be used to create additional cards
	protected $name; //string
	protected $alias; //string
	protected $type; //int
	protected $activation_numbers; //int array
	
	protected $cost; //int
	protected $income; //int
	protected $img; //string URL
	
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
	
	public function ActiveEffect($player, $player2){
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
	
	public function ActiveEffect($player, $player2){
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
	
	public function ActiveEffect($player, $player2){
		$player->addCoins($this->income);
		return true;
	}
}

class Ranch extends Card{	// Properties
	//Abstract Methods
	public function ActiveEffect($player, $player2){
		//
	}
}
class Forest extends Card{	// Properties
	//Abstract Methods
	public function ActiveEffect($player, $player2){
		//
	}
}
class Mine extends Card{	// Properties
	//Abstract Methods
	public function ActiveEffect($player, $player2){
		//
	}
}
class AppleOrchard extends Card{	// Properties
	//Abstract Methods
	public function ActiveEffect($player, $player2){
		//
	}
}
class ConvenienceStore extends Card{	// Properties
	//Abstract Methods
	public function ActiveEffect($player, $player2){
		//
	}
}
class CheeseFactory extends Card{	// Properties
	//Abstract Methods
	public function ActiveEffect($player, $player2){
		//
	}
}
class FurnitureFactory extends Card{	// Properties
	//Abstract Methods
	public function ActiveEffect($player, $player2){
		//
	}
}
class FruitandVegetableMarket extends Card{	// Properties
	//Abstract Methods
	public function ActiveEffect($player, $player2){
		//
	}
}
class Cafe extends Card{	// Properties
	//Abstract Methods
	public function ActiveEffect($player, $player2){
		//
	}
}
class FamilyRestaurant extends Card{	// Properties
	//Abstract Methods
	public function ActiveEffect($player, $player2){
		//
	}
}
class Stadium extends Card{	// Properties
	//Abstract Methods
	public function ActiveEffect($player, $player2){
		//
	}
}
class TVStation extends Card{	// Properties
	//Abstract Methods
	public function ActiveEffect($player, $player2){
		//
	}
}
class BusinessCenter extends Card{	// Properties
	//Abstract Methods
	public function ActiveEffect($player, $player2){
		//
	}
}
class TrainStation extends Card{	// Properties
	//Abstract Methods
	public function ActiveEffect($player, $player2){
		//
	}
}
class ShoppingMall extends Card{	// Properties
	//Abstract Methods
	public function ActiveEffect($player, $player2){
		//
	}
}
class AmusementPark extends Card{	// Properties
	//Abstract Methods
	public function ActiveEffect($player, $player2){
		//
	}
}
class RadioTower extends Card{	// Properties
	//Abstract Methods
	public function ActiveEffect($player, $player2){
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
	
	
	// Internal Methods
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
	public function getPriamry(){
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
	public function setDiscord($discord){
		$this->discord = $discord;
	}
	public function setName($name){
		$this->name = $name;
	}
	public function addCoins($amount){
		$this->coins += $amount;
		return $this->coins;
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
	protected $phase; //string
	// Objects
	protected $board; //Board
	protected $players; //Player array
	protected $turn; //Player (might just use current($players)
	
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
	//Reference Getters
	public function getHost(){
		return $this->id;
	}
	public function getTurn(){
		return $this->turn;
	}
	//Search Getters
	public function getPlayer($param_discord){
		if ($this->players[$param_discord])
			return $this->players[$param_discord];
		return false;
	}
	
	
	// Setters
	public function addPlayer($param_discord){ //returns false if MKGame already has max number of players
		if ($this->locked !== true){
			if (array_sum < 4){
				$this->players[] = new Player($param_discord, 3, 0);
				return true;
			}
		}
		return false;
	}
	
	
	// Methods
	public function rollDie($num){
		$temp_array = array();
		for ($i = 0; $i < $num; $i++){
			$temp_array[] = rand(1,6);
		}
		$this->roll = $temp_array;
		return $this->roll;
	}
	private function diceActivation($roll){
		if (is_array($roll)) $temp_roll = array_sum($roll);
		else $temp_roll = $roll;
		foreach ($players as $player) { //Primary
		  foreach($player->Hand->primary as $card){
			if ( in_array( $temp_roll, $card->getActivationNumbers() ) ) {
			  // Trigger effect for all players
			}
		  }
		}
		foreach($turn->Hand->secondary as $card){ //Secondary
			if ( in_array( $temp_roll, $card->getActivationNumbers() ) ) {
			  // Trigger effect for current player
			}
		}
		return true;
	}
	public function start($author_id){
		if ($author_id != $this->id){
			if ($this->locked !== false){
				if (array_sum($this->players) > 1){
					$this->locked = true; //Prevent new players from joining
					//Set game phase
					return "**Machi Koro game $game_id has been started by the host!**";
				}else{
					return "Not enough players!";
				}
			}else{
				return "This game has already started!";
			}
		}else{
			return "You are not the host! Please ask <@$game_id> so start the game.";
		}
	}
	// Internal Methods
	public function nextTurn(){
		$this->turn = next($this->players);
	}
}

?>
