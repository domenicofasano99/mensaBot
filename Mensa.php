<?php

/**
 * Classe per gestire le richieste relative alla mensa.
 * @author Domenico Fasano
 */
include ("Oggi.php");
include ("Domani.php");
include ("Email.php");



session_start();


class mensa { //menu

    private $comando = "";
   	private $oggi=NULL;
    private $domani=NULL;
	private $nomescuola = "";
	private $nomegrado = "";
    private $pdf=NULL;
	private $email=NULL;
	private $abilita;
    private $chat_id;
    private $connessione;
    private $menu= array();
    /// Class constructor
   
    public function __construct($text,$connessione,$chat_id,$autenticazione) {//,$prefisso_tabelle
        $this->comando = strtoupper($text);
		//$this->prefisso_tabelle=$prefisso_tabelle;
        $this->connessione=$connessione;
        $this->chat_id=$chat_id;
		$this->abilita=$autenticazione; 
    }   


    /// Class destructor
    /**
     * Distrugge una istanza di Classe
     */
    public function __destruct() {
    	unset($this->comando);
       	unset($this->oggi);
        unset($this->domani);
		unset($this->pdf);
		unset($this->email);
    	unset($this->connessione);
        unset($this->chat_id);
		unset($this->abilita);
    }
    

    /// Restituisce la risposta al richiedente
    /**
     * \return la risposta formattata
     */
    public function reply() {
	
        // La variabile da restituire 
		$r="";
		
		// Estraiamo le parole del comando in maiuscolo...
        $parole=explode(" ",strtoupper($this->comando));
		if (substr($parole[0],0,1)=="/") {
        	$parole[0]=substr($parole[0],1,strlen($parole[0])-1);
        }
        
        //Se il comando digitato non è presente nell'elenco dei comandi
        $r="Il comando <b>'".$parole[0]."'</b> non è presente nell'elenco dei comandi. ".
        "Digita /help per conoscere tutti i comandi.";

		// Utilizziamo questo SWITCH per trattare ogni singolo comando
    	switch ($parole[0]) {
    	
    		case "AUTORE": {
		    	$r = "<b>Dipartimento di Informatica</b>\n<i>I.I.S.S. Majorana - Martina Franca</i>\n\n<b>Fasano Domenico</b>\nClasse: 5Ai - A.S. 2017/18";
	    		break;
            }
            
			case "HELP": {
	 			$r ="<b>/menuoggi</b> Selezionare la scuola di cui si vuole vedere il menu giornaliero (se sabato o domenica visualizza il menu di lunedì);\n\n".
                	"<b>/menudomani</b>Selezionare la scuola di cui si vuole vedere il menu di domani (se sabato o domenica visualizza il menu di martedì);\n\n".
                	"<b>/invia</b> &lt;email&gt;: inoltra via email il menu di una scuola in questa settimana di una scuola.\n\n".
                    "<b>/autore</b>: chi ha sviluppato questo bot\n\n".
					"<b>/help</b>: visualizza l'elenco dei comandi disponibili;\n\n";
					
                break;
            }
            
            case "START": {
		    	$r = "<b>Benvenuto!</b>\nDigita /help per conoscere tutti i comandi.";
	    		break;
            
            }
          case "SETTIMANA": {
            
				$nomescuola=(strlen($this->comando)>strlen($parole[0])+2 ? $parole[1] : "");
				$nomegrado=(strlen($this->comando)>strlen($parole[0])+strlen($nomescuola)+2 ? $parole[2] : "");
               	
                // Estraggo il Chat_ID 
				$q= "SELECT Autenticazione_Comando FROM utenti where Chat_ID = $this->chat_id";
                $risultato=$this->connessione->query($q);
                //Verifico se la query ha prodotto una riga
                if($this->connessione->affected_rows==1){
                	$linea = $risultato->fetch_array(MYSQLI_NUM);
                    //Verifico autenticazione
                    if ($linea[0]=='TRUE'){
                    
                    	// Creiamo un'istanza delle classe Oggi autenticata (true)
                    	$this->oggi=new Oggi($nomescuola,$nomegrado,$parole[0],$this->connessione,true); 
						$tabella=$this->oggi->reply();  
                        $r=$this->invia_email($tabella); 
                        }
                   if ($linea[0]=='FALSE'){  
   	 					$this->oggi=new Oggi($nomescuola,$nomegrado,$parole[0],$this->connessione,false); 
					$r=$this->oggi->reply();  //La risposta completa da restituire al richiedente 
                   }
               	}
                else
                {
	   				$this->oggi=new Oggi($nomescuola,$nomegrado,$parole[0],$this->connessione,false); 
					$r=$this->oggi->reply(); //La risposta completa da restituire al richiedente 
                }
				break;
            }
			
			case "MENUOGGI": {
                $nomescuola=(strlen($this->comando)>strlen($parole[0])+2 ? $parole[1] : "");
				$nomegrado=(strlen($this->comando)>strlen($parole[0])+strlen($nomescuola)+2 ? $parole[2] : "");
               	
                // Estraggo il Chat_ID 
                $q= "SELECT Autenticazione_Comando FROM utenti where Chat_ID = $this->chat_id";
                $risultato=$this->connessione->query($q);
                //Verifico se la query ha prodotto una riga
                if($this->connessione->affected_rows==1){
                	$linea = $risultato->fetch_array(MYSQLI_NUM);
                    //Verifico autenticazione
                    if ($linea[0]=='TRUE'){
                    
                    	// Creiamo un'istanza delle classe Comune autenticata (true)
                    	$this->oggi=new Oggi($nomescuola,$nomegrado,$parole[0],$this->connessione,false); 
						$r=$this->oggi->reply(); 
                          }
                   if ($linea[0]=='FALSE'){  
   	 					$this->oggi=new Oggi($nomescuola,$nomegrado,$parole[0],$this->connessione,false); 
					$r=$this->oggi->reply();  //La risposta completa da restituire al richiedente 
                   }
               	}
                else
                {
	   				$this->oggi=new Oggi($nomescuola,$nomegrado,$parole[0],$this->connessione,false); 
					$r=$this->oggi->reply(); //La risposta completa da restituire al richiedente 
                }
               	break;
            }
          case "INVIA": {
			   
				$email_destinatario=(strlen($this->comando)>strlen($parole[0])+2 ? substr($this->comando,strlen($parole[0])+2,strlen($this->comando)-strlen($parole[0])-2) : "");
				
                // Creiamo un'istanza delle classe Email
				$this->email=new Email($email_destinatario,$parole[0],$this->connessione);
				$r=$this->email->reply(); //La risposta completa da restituire al richiedente
                
                //Creazione della sessione per l'autenticazione dei comandi ORARIO_DOCENTE e ORARIO_CLASSE
                if($this->email->verifica()==1){
                    $_SESSION['autenticazione']=1;
                }
				break;
		   }
           case "MENU_SETTIMANA": {
            	$nomescuola=(strlen($this->comando)>strlen($parole[0])+2 ? $parole[1] : "");
				$nomegrado=(strlen($this->comando)>strlen($parole[0])+strlen($nomescuola)+2 ? $parole[2] : "");
               	
            	//Verifica se il comando ORARIO_DOCENTE è stato autenticato
				if(!$this->abilita)
					$r="Comando non formulato correttamente. E' necessario digitare il comando /invia seguito da un'email valida per poter utilizzare questo comando.".$this->abilita;
                else
                {
                	//Creiamo un'istanza della classe Docente
                      $this->oggi=new Oggi("","","SETTIMANA",$this->connessione,false);
                      $r=$this->oggi->reply();
                }
                    
                    //Distruggo la sessione
                    unset($_SESSION['autenticazione']);
                   	session_destroy();
				
                break;}
			 
     case "MENUDOMANI": {
                $nomescuola=(strlen($this->comando)>strlen($parole[0])+2 ? $parole[1] : "");
				$nomegrado=(strlen($this->comando)>strlen($parole[0])+strlen($nomescuola)+2 ? $parole[2] : "");
               	
                // Estraggo il Chat_ID 
                $q= "SELECT Autenticazione_Comando FROM utenti where Chat_ID = $this->chat_id";
                $risultato=$this->connessione->query($q);
                //Verifico se la query ha prodotto una riga
                if($this->connessione->affected_rows==1){
                	$linea = $risultato->fetch_array(MYSQLI_NUM);
                    //Verifico autenticazione
                    if ($linea[0]=='TRUE'){
                    
                    	// Creiamo un'istanza delle classe Comune autenticata (true)
                    $this->domani=new Domani($nomescuola,$nomegrado,$parole[0],$this->connessione,false); 
					$r=$this->domani->reply(); 
                        //$r=$this->invia_email($tabella); //La risposta completa da restituire al richiedente                       
                   }
                   if ($linea[0]=='FALSE'){  
   	 				$this->domani=new Domani($nomescuola,$nomegrado,$parole[0],$this->connessione,false); 
					$r=$this->domani->reply();  //La risposta completa da restituire al richiedente 
                   }
                else
                {
	   				$this->domani=new Domani($nomescuola,$nomegrado,$parole[0],$this->connessione,false); 
					$r=$this->domani->reply(); //La risposta completa da restituire al richiedente 
                }
                }
               break;
            }
        }
		return $r;
	}
         
           
           
		   
			
    ///La funzione che crea la tabella da inviare
    	/**
	 * Ha in ingresso l'array multivalore che contiene i dati per creare la tabella
     * Genera dinamicamente la tabella da inviare al chiamante
     	**/
    public function crea_tabella($tabella)
    {
    	// 1° Riga della tabella (statica)
    	$r="<table border=1 cellpadding=\"10\"><tr><th>Piatti</th><th>Luned&igrave;</th><th>Marted&igrave;</th><th>Mercoled&igrave;</th><th>Gioved&igrave;</th><th>Venerd&igrave;</th>";
     	$piatti = array("Primo", "Secondo", "Contorno", "Frutta", "Pane", "Tot_Calorie");
        $i=0;
       	
        // Creazione del resto della tabella (dinamica)
        foreach($tabella as $ora => $val_ora)
       	{
       		
       		$r.="<tr><td align=\"center\">".$piatti[$i]."</td>";
            $i++;
       		foreach($val_ora as $giorno => $val_giorno){
      			$r.="<td align=\"center\">".$val_giorno."</td>";	
      		}
      		$r.="</tr>";
     	}           
     	$r.="</table>";
        
    	return $r;
    }
    
    
    //La funzione che genera il file PDF e lo salva in locale
    	/**
	 * Ha in ingresso l'array multidimensionale e l'oggetto del messaggio utilizzato per il titolo del PDF
     * Restituisce il path del file PDF generato
     	**/
     public function crea_PDF($tabella,$oggetto)
    {
    	//Utilizzo la libreria FPDF 
        //require_once('lib/TCPDF-master/tcpdf_import.php');
    	require_once ('lib/tcpdf/tcpdf.php');
        
        //Creo un istanza della classe PDF
		/*$this->pdf = new TCPDF();*/
         $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, false, 'ISO-8859-1', false);
         
        $pdf->SetDocInfoUnicode(true);

        // set document information
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor('DomenicoFasano');
        $pdf->SetTitle('MensaFB_bot');
        $pdf->SetSubject('Menu');
        $pdf->SetKeywords('TCPDF, PDF, mensa, menu, settimana');


        // set default header data
        $pdf->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, PDF_HEADER_TITLE.' 019', PDF_HEADER_STRING);

        // set header and footer fonts
        $pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
        $pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));

        // set default monospaced fontreturn 'ciao';
        $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

        // set margins
        $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
        $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
        $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

        // set auto page breaks
        $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
        $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

        // set some language dependent data:
        $lg = Array();
        $lg['a_meta_charset'] = 'ISO-8859-1';
        $lg['a_meta_dir'] = 'ltr';
        $lg['a_meta_language'] = 'en';
        $lg['w_page'] = 'page';
        
        // set some language-dependent strings (optional)
        $pdf->setLanguageArray($lg);

       //Il parametro 'L' in AddPage() sta per Landscape, cioè la pagina viene visualizzata in orizzontale
		$pdf->AddPage('L');
          
         $pdf->SetFont('Arial', 'B', 16);
       
		$pdf->Cell(40, 10, $oggetto);
        
		$pdf->Ln();
        
        //Creo staticamente la prima riga
        $pdf->Ln(6);
		$pdf->SetFont('Helvetica', 'B', 10);
        $pdf->Cell(10, 10, 'Piatto',1,0,'C');
        $pdf->Cell(40, 10, 'Lunedi\'',1,0,'C');
        $pdf->Cell(40, 10, 'Martedi\'',1,0,'C');
        $pdf->Cell(40, 10, 'Mercoledi\'',1,0,'C');
        $pdf->Cell(40, 10, 'Giovedi\'',1,0,'C');
        $pdf->Cell(40, 10, 'Venerdi\'',1,0,'C');
		$pdf->Ln();
        
		
        //Creo dinamicamente la tabella in base all'array multidimensionale ($tabella) passato al metodo       
        $pdf->SetFont('Courier', '', 9);
        $c=1;
        $altezza=21; //Altezza della cella
		foreach($tabella as $row){
            
           	$y=$pdf->GetY();  // $y rappresenta l'ordinata della cella
            $pdf->Cell(10,$altezza, $c, 1,0,'C');
            $pdf->SetXY(20,$y);
            $riga=$this->numero_righe($row['1']);
            $pdf->MultiCell(40,($riga==1)?$altezza:($altezza/(intval($riga))),$row['1'], 1, 'C'); //1° Colonna 
            $pdf->SetXY(60,$y);
            $riga=$this->numero_righe($row['2']);
  			$pdf->MultiCell(40,($riga==1)?$altezza:($altezza/(intval($riga))),$row['2'], 1, 'C'); //2° Colonna
            $pdf->SetXY(100,$y);
            $riga=$this->numero_righe($row['3']);
  			$pdf->MultiCell(40,($riga==1)?$altezza:($altezza/(intval($riga))),$row['3'], 1, 'C'); //3° Colonna
            $pdf->SetXY(140,$y);
            $riga=$this->numero_righe($row['4']);
  			$pdf->MultiCell(40,($riga==1)?$altezza:($altezza/(intval($riga))),$row['4'], 1, 'C'); //4° Colonna
            $pdf->SetXY(180,$y);
            $riga=$this->numero_righe($row['5']);
            $pdf->MultiCell(40,($riga==1)?$altezza:($altezza/(intval($riga))),$row['5'], 1, 'C'); //5° Colonna
           	$pdf->SetXY(220,$y);
            $riga=$this->numero_righe($row['6']);
  			$pdf->MultiCell(40,($riga==1)?$altezza:($altezza/(intval($riga))),$row['6'], 1, 'C'); //6° Colonna
           	$c++;  
      		}     
            
 	    //Salvo il file e specifico il percorso
        //$pdf->Output("F","MensaFB_Bot.pdf");
       
      $pdf->Output("MensaFB_Bot.pdf","D");
         //return 'miao';
         
        $r="MensaFB_Bot.pdf";
        


        return $r;
    }
    
    
    //La funzione calcola il numero di righe necessarie per scrivere il contenuto di ogni cella
    	/**
	 * Ha in ingresso la stringa relativa al contenuto della cella
     * Restituisce il numero di righe necessarie
     	**/       
    public function numero_righe($contenuto){
    	
        //Suddivido la stringa ($contenuto) utilizzando lo spazio come separatore e inserisco i valori all'interno di un array
    	$contenuto =explode(' ', $contenuto); 
        $stringa="";
        $riga=1;
        $c=0;
        foreach($contenuto as $parola){
			if($c==0){
            	$stringa.=$parola;
                $c++;
            }
            else
            	$stringa.=" ".$parola;
            
            //Verifico se la stringa ottenuta supera il limite di lunghezza della riga della cella
            if (strlen($stringa)>20*$riga) 
            {
            	$spazi=strlen($parola);
            	$stringa=substr($stringa,0,strlen($stringa)-$spazi-1); //Elimino dalla stringa l'ultima parola
            	$riga++;
                $lunghezza=strlen($stringa);
                
                //Inserisco n spazi per raggiungere il limite della riga della cella
                for($i=0;$i<((20*($riga-1))-$lunghezza);$i=$i+1){
                	$stringa.=" "; 
                }
                $stringa.=$parola;
            }
        }
    	return $riga;
    }
    
     
    ///La funzione utilizzata per l'elaborazione e l'invio dell'email
    	/**
	 * Ha in ingresso l'array multidimensionale ($tabella)
     * Invia l'email al chiamante utilizzando creando un'istanza della classe PHPMailer()
     * Restituisce il messaggio di avvenuto invio dell'email
     	**/
    public function invia_email($tabella){
    	
        /* Aggiorno Autenticazione_Comando a False per non permettere che l'utente possa eseguire ancora una volta quel 
        * comando senza prima aver inserito nuovamente un email corretta */
		
        $q= "UPDATE utenti SET Autenticazione_Comando = 'FALSE' WHERE Chat_ID = $this->chat_id";
        $this->connessione->query($q);
       
        //Estraggo l'email dal database
        $q= "SELECT Email From utenti WHERE Chat_ID = $this->chat_id";
        $risultato = $this->connessione->query($q);
       	$dove=$risultato->fetch_array(MYSQLI_ASSOC);
        
        //La risposta completa da restituire al richiedente        
		$r="L'email è stata inviata correttamente a <b>".$dove['Email']."</b>"; 
        $oggetto = substr($this->comando,1,strlen($this->comando));
        
        //Creazione della tabella MENU SETTIMANA da inviare al richiedente richiamando il metodo crea_tabella()
        $messaggio=$this->crea_tabella($tabella);
        
        //Creazione del file PDF da inoltrare come allegato dell'email
        $allegato=$this->crea_PDF($tabella,$oggetto);
		//return $allegato;
        //Invio Email con la classe PHPMailer()
		require_once("lib/phpmailer/lib/class.phpmailer.php");
		$email=new PHPMailer();
 		$email->AddAddress($dove['Email']);
		$email->IsHTML(true);
		$email->Subject = $oggetto;
        $email->Body = $messaggio;
        $email->AddAttachment($allegato);
		$email->Send();
        
        return $r;
    
    }


	/// Il metodo che restituisce al chiamante il riferimento all'oggetto $this->OGGI.
    /**
	 * Non ha nulla in ingresso
     * \return il riferimento al dato privato $this->oggi
     */
    public function getOggi() {
        return $this->oggi;
    }
   	/// Il metodo che restituisce al chiamante il riferimento all'oggetto $this->domani.
    /**
	 * Non ha nulla in ingresso
     * \return il riferimento al dato privato $this->domani
     */ 
  public function getDomani() {
        return $this->domani;
    }
	/// Il metodo che restituisce al chiamante il riferimento all'oggetto $this->email.
    /**
	 * Non ha nulla in ingresso
     * \return il riferimento al dato privato $this->email
     */	
    public function getEmail() {
        return $this->email;
    }
    
} //Chiude la classe

?>
