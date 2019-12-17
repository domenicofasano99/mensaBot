<?php

/**
 * Email per gestire le richieste relative ad all'email.
 * Interagisce con Oggi
 * @author Fasano Domenico
 */
class Email{
	
	private $email_destinatario=""; 
    private $tipo_query = ""; // al momento, non ha alcun utilizzo; per un uso futuro
    private $connessione=NULL;
	
    
    /// Class constructor
    /**
     * Crea una istanza di Email 
     * \param $email_destinatario -> l'email (utente@dominio)
     * \param $prefisso_tabelle specifica il prefisso delle tabelle (set tabelle)
     * \return una istanza della classe
     */
    public function __construct($email_destinatario,$tipo_query,$connessione) {
		$this->email_destinatario=$email_destinatario;
        $this->tipo_query = strtolower($tipo_query);
		$this->prefisso_tabelle=$prefisso_tabelle;
		$this->connessione=$connessione;
    }
	
    
    /// Class destructor
    /**
     * Distrugge una istanza di Email
     */
    public function __destruct() {
        $this->connessione->close();
    	unset($this->email_destinatario);
    	unset($this->tipo_query);
    	unset($this->connessione);
    }
    
	
    /// Numero di elementi nell'array materie
    /**
     * Quanti sono le materie che iniziano con la stringa specificata?
     * \return il numero richiesto
     */
    public function verifica() {
        if ($this->email_destinatario==""){
			return 2;
		}
		else {	
        
		//Controllo Email
			$dati=explode("@",$this->email_destinatario);
			list($userName, $domainName)=$dati;
			if (checkdnsrr(strtolower($domainName), 'MX'))  //checkdnsrr verifica l'esistenza del dominio
				return 1; //Email corretta
			else 
				return 0; //Dominio non corretto	
		}
    }		


    /// Il metodo che costruisce la stringa di testo da inviare al richiedente.
    /**
     * E' il metodo principale, responsabile della costruzione della stringa  
     * da restituire al richiedente. Non ha parametri perchè opera sui dati
     * privati della classe.
     * Restituisce una stringa.
     * \return la risposta da inviare al chat_id che ha emesso richiesta
     */	
    public function reply(){
		
		$r=""; // La stringa risultato da restituire al chiamante
		$esito=$this->verifica(); 
		
		/** 
		il metodo 'verifica' restituisce 
		- 1 se l'email è verificata (solo nel dominio dell'email)
		- 0 se l'email non è verificata (dominio inesistente).
		- 2 se l'email non è stata inserita come parametro del comando
		*/
		
    	if ($esito==0) {
    		// L'email non è valida
			return "L'email <b>".strtolower($this->email_destinatario)."</b> non è valida";		
		}
		else {
			if ($esito==2) {
				return "Devi necessariamente inserire un email valida per poter utilizzare questo comando.";				
			}
		}
		if($esito==1){
			return "Seleziona cosa vuoi inviare";
		}	
	}
    
    
	/// Costruisce l'array sulla base del quale verrà creata la tastiera personalizzata Telegram
    /**
     * Ogni elemento dell'array è a sua volta un array che contiene i
     * comandi che compariranno nei pulsanti di una riga. In questo caso una 
     * riga sarà costituita da entrambi i pulsanti,
     * \return l'array con il testo  
     */
	public function opzioni() {
		$option1=array();
        $option1[]=array("menu_settimana");
		return $option1;
    }
    
    ///Ritorna l'email digitata dall'utente in minuscolo (strtolower)
    public function get_Email() {
    	return strtolower($this->email_destinatario);
    }
	
} //Chiude la classe

?>
