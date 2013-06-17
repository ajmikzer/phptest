<?php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', 'root');
define('DB_NAME', 'chat');


/**
 * Database class. Requires PHP 5.1 to work with PDO
 */
 
class Database extends PDO
{
	public function __construct()
	{
		try
		{
			$dsn = 'mysql:dbname=' . DB_NAME . ';host=' . DB_HOST;
			parent::__construct($dsn, DB_USER, DB_PASS);
		}
		catch(PDOException $e)
		{
			echo 'Connection failed. ' . $e->getMessage();
			exit;
		}
	}
}



/**
 * Chat class.
*/
 
 
class Chat 
{	

private $rooms = array();
private $db;

public function __construct()
	{

		$this->db = new Database;

	}


//creating a user		
public function createClient($username){
	
	$user = new User($username);
	
	$sql = 'INSERT INTO users ( username)
				VALUES (:un)';

		$st = $this->db->prepare($sql);
		$st->bindValue(':un', $username);

		$st->execute();
		return $user;
	
	}


//creating a room
public function createChatroom($name){
  
   $room = new Room($name);
    
   $this->rooms[] = $room;
   
   $sql = 'INSERT INTO rooms (name) VALUES (:name)';
   $st = $this->db->prepare($sql);

	$st->bindParam(':name', $name);


	$st->execute();
	return $room;


  }


}


/**
* Listener class
*/

class Listener {

	/**
	 * receive function.
	 * Get a message by sender, room and text
	 */

 function receive(User $user, Room $room, $message)
  {

  $sql = 'SELECT message_id,content,users.username,rooms.name
				FROM messages 
				INNER JOIN rooms ON messages.room = rooms.room_id 
				LEFT JOIN users ON messages.owner = users.user_id 
				WHERE rooms.name = :room
				ORDER BY message_id DESC LIMIT 1';

		$st = $this->db->prepare($sql);
		$st->bindValue(':room', $room);
  
  		if($st->execute())
			{
				return true;
			}
		else
			{
				return false;
			}
  }

}



/**
 * Room class.
 */
class Room
{
  
  
  private $room;
  private $occupants = array();
  private $db;
  
  public function  __construct($roomname){
  	$this->db = new Database;
    $this->room = $roomname;
  
  }

  public function addClient(User $username){

    $this->occupants[] = $username;

  }
  
  public function getRoom(){
  
    return $this->room;
  
  }
  
  public function getOccupants(){

    return $this->occupants;

  }
  
  public function send(User $username, $message){    

    foreach( $this->occupants as $occupant)
    {
      if($occupant != $username)
      {
        
		$sql = 'INSERT INTO messages ( content, room, ip, owner )
				VALUES ( :msg, :room, :ip, :owner )';

		$st = $this->db->prepare($sql);
		$st->bindParam(':msg', $msg);
		$st->bindValue(':room', $this->getRoom());
		$st->bindParam(':ip', $_SERVER['REMOTE_ADDR']);
		$st->bindParam(':owner', $username->getUsername());
		
		$st->execute();
		$occupant->showto($username,$this,$message);
	
		
      }
    }
    
  }

	
	
	
}




/**
 * User class.
 */
class User
{

  private $username;
  private $listener = NULL;

  public function  __construct($username){
  
    $this->username = $username;
  
  }
  
  
//Adding a listener
  public function  addListener(Listener $listener){
    
    $this->listener = $listener;
  
  }
  
//Getting username
  public function  getUsername(){
    
   return $this->username;
  
  }
 
//Show message
public function showto(User $username, Room $room, $message){
  
    if(!is_null($this->listener))
    {
      $this->listener->receive($username, $room, $message);
    }
    
  }

}

?>