<?php
/**
 * Telegram Bot mensa.
 * @author Fasano Domenico
 */
 
include("Telegram.php");
include("Mensa.php");
include("config.inc.php");

session_start();

// Imposta il bot TOKEN (API Key)
$bot_id = "588630066:AAFHgaiY8Uzm2CmTfIta3Zttivi9wGLJWt0"; //Bot @mensaFB_bot

// Instanze delle classi Telegram e mysqli
$telegram = new Telegram($bot_id);
$connessione = new mysqli($host,$utente,$password,$database);


/* check della connessione */
if ($connessione->connect_errno) 
    exit();


// Prende il testo e il chat id dal messaggio
$text = $telegram->Text();
$chat_id = $telegram->ChatID();
$username= $telegram -> Username();

/*$query="select nome from comuni";
$risultato=$this->connessione->query($query);
$content = array('chat_id' => $chat_id, 'reply_markup' => $keyb, 'text' => $risultato);
$telegram->sendMessage($content);*/
$keyb = $telegram->buildKeyBoardHide($selective=true); 
// Si tratta di un comando non vuoto?
if(!is_null($text) && !is_null($chat_id)){

	//Per eliminare tastiera personalizzata
    $keyb = $telegram->buildKeyBoardHide($selective=true); 
    
  //  $mensa=new mensa($text,$connessione,$chat_id,true); //da cambiare
	/**
    * Creiamo un'istanza della classe Mensa, passando al costruttore
	* il testo giunto dal bot
    * prima però andiamo a fare il controllo sull'autenticazione andando a verificare
    * se nel database il campo Autenticazione è TRUE o FALSE 
    **/
  
   	$q= "SELECT Autenticazione FROM utenti where Chat_ID = $chat_id"; //{$prefisso_tabelle}//Estraggo il Chat_ID dal database
    $risultato=$connessione->query($q);
    if($connessione->affected_rows==1){
        $linea = $risultato->fetch_array(MYSQLI_NUM);
		if ($linea[0]=='TRUE'){//qui
    		$mensa=new mensa($text,$connessione,$chat_id,true); //$prefisso_tabelle,  
            $q= "UPDATE utenti SET Autenticazione = 'FALSE' WHERE Chat_ID = $chat_id";//{$prefisso_tabelle}
            $connessione->query($q);
        }
        else
  		{
   	 		$mensa=new mensa($text,$connessione,$chat_id,false);//,$prefisso_tabelle
        }
            
  	}
    else
	   	$mensa=new mensa($text,$connessione,$chat_id,false);//,$prefisso_tabelle

  	
	
	// Facciamoci restituire il testo di risposta da trasmettere al richiedente...
	$reply=$mensa->reply();
    
    //Verifichiamo se è stato digitato il comando invia che ha aperto la SESSIONE Autenticazione
   	if($_SESSION['autenticazione']==1){
        $email_destinatario= $mensa->getEmail()->get_Email();
		$q= "SELECT Chat_ID FROM utenti where Chat_ID = $chat_id"; //{$prefisso_tabelle}
		$risultato=$connessione->query($q);
        
        /**
        * Verichiamo se il chat_id estratto nella query precedente è gia presente nel database in 
        * modo tale da aggiornare (UPDATE) la riga con le informazioni aggiornate dell'utente o inserirne 
        * una nuova (INSERT INTO) nel caso in cui quel chat_id non fosse già memorizzato nel database.
        **/
		if($connessione->affected_rows==1){	
			$q= "UPDATE utenti SET Email = '$email_destinatario', Username = '$username', Autenticazione= 'TRUE', Autenticazione_Comando = 'TRUE' WHERE Chat_ID = $chat_id"; //{$prefisso_tabelle}
			$risultato=$connessione->query($q);			
		}	
		else {//{$prefisso_tabelle}
			$q= "INSERT INTO utenti (Chat_ID, Username, Email, Autenticazione, Autenticazione_Comando) VALUES ($chat_id, '$username','$email_destinatario', 'TRUE', 'TRUE')";
			$risultato=$connessione->query($q);
		}
       
    }
  
	
	
	if ($mensa->getOggi()!=NULL) {
		if ($mensa->getOggi()->quanti()>1) {
			// Per preparare la tastiera personalizzata con tutte le classi omonime
			$keyb = $telegram->buildKeyBoard($mensa->getOggi()->opzioni(), $onetime=true, $resize=true, $selective=true);
		}
    }
	if ($mensa->getDomani()!=NULL) {
		if ($mensa->getDomani()->quanti()>1) {
			// Per preparare la tastiera personalizzata con tutte le classi omonime
			$keyb = $telegram->buildKeyBoard($mensa->getDomani()->opzioni(), $onetime=true, $resize=true, $selective=true);
		}	
	}	
	if ($mensa->getEmail()!=NULL) {
		if ($mensa->getEmail()->verifica()==1) {
			// Per preparare la tastiera personalizzata con tutte le classi omonime
			$keyb = $telegram->buildKeyBoard($mensa->getEmail()->opzioni(), $onetime=true, $resize=true, $selective=true);//modifica
		}	
	
	}

	$content = array('chat_id' => $chat_id, 'reply_markup' => $keyb, 'text' => $reply, 'parse_mode' => 'HTML');
   	$telegram->sendMessage($content);
 
} //Chiude la classe

?>


