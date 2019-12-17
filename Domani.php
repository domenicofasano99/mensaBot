<?php
/**
 * Domani per gestire le richieste relative a domani.
 * @author Domenico Fasano
 */
class Domani {

	private $oggi = array();
    private $menu = array();
    private $nomescuola="";
    private $nomegrado="";
    private $tipo_query = ""; 
    private $connessione=NULL;
	private $invio;
    /// Class constructor
    /**


     */
    public function __construct($nomescuola,$nomegrado,$tipo_query,$connessione,$invio) {
        $this->nomescuola=$nomescuola;
        $this->nomegrado=$nomegrado;
        $this->tipo_query = strtolower($tipo_query);
		$this->connessione=$connessione;
        $this->invio=$invio;
        $this->oggi=$this->crea_array_oggi();
    }
    
    
    /// Class destructor
    /**
     * Distrugge l'istanza di domani
     */
    public function __destruct() {
        $this->connessione->close();
    	//unset($this->nomeoggi); 
    	/*unset($this->nomecomune);*/
        unset($this->nomescuola); 
    	unset($this->nomegrado);
    	unset($this->tipo_query);
    	unset($this->connessione);
        unset($this->invio);
        unset($this->oggi); 
    }
    
    
    /// Numero di elementi nell'array domani
    /**
     * Quanti sono i piatti che iniziano con la stringa specificata?
     * \return il numero richiesto
     */
    public function quanti() {
        return count($this->oggi);
    }
    
    
    /// Il metodo che costruisce la stringa di testo da inviare al richiedente.
    /**
     * E' il metodo principale, responsabile della costruzione della stringa  
     * da restituire al richiedente. Non ha parametri perchè opera sui dati
     * privati della classe.
     * Restituisce una stringa.
     * \return la risposta da inviare al chat_id che ha emesso richiesta
     */
    public function reply() {
    	
    	$r=""; // La stringa risultato da restituire al chiamante
    	
    	// Controllo sul numero delle classi
    	$quanti_oggi=$this->quanti();
    	if ($quanti_oggi==0) {
        	
			return "Non esiste alcuna scuola che inizi per: <b>$this->nomescuola</b>";
		}
		else {
			if ($quanti_oggi>1) {
				return "Ci sono diverse scuole che iniziano per: <b>$this->nomescuola</b>. \nScegli quella desiderata dalla lista seguente:\n\n";
            }
		} 
        $r= "Il menu di domani della scuola <b>$this->nomescuola</b>: \n\n".$this->stringaSQL();
       
  	 	return $r;
	}

    /// Per costruire la stringa SQL. 
    /**
     * \return la stringa che poi sarà la query da inviare al server SQL
     */
    public function stringaSQL() {
		$que='SELECT IDSTAGIONE FROM stagioni WHERE DATE(NOW())BETWEEN DATA_FROM and DATA_TO';
        $r=$this->connessione->query($que);
        //if (isset($this->connessione)) return "settata";
        $stagione=$r->fetch_array()[0];
        $quer="SELECT menusettimana.IDMENUSETTIMANA FROM menusettimana join scuole using(idscuola) 
        	where (menusettimana.settimana= IF(ceil(dayofmonth(now())/7)=5, 1, ceil(dayofmonth(now())/7)) AND scuole.NomeScuola like '$this->nomescuola%' AND menusettimana.IDStagione=$stagione AND menusettimana.grado like '$this->nomegrado%')";//$this->nomegrado $this->nomescuola
        $r=$this->connessione->query($quer);
        $menusettimana=$r->fetch_array()[0];
       	$query="select p.nome,p.indicazioni, if('$this->nomegrado'='INFANZIA' AND p.tipo='primo', SUM((ep.quantita-20)*e.kcalorieg),SUM(ep.quantita*e.kcalorieg)) as calorie
				from menu_pie mp JOIN pietanze p USING (idpietanza) 
	 				JOIN ele_pie ep USING (Idpietanza)
     				JOIN elementi e USING (idelemento)
                where mp.idmenu=$menusettimana and mp.giorno=IF(dayofweek(now())>6, 2, dayofweek(now())+1)
                GROUP BY p.nome
                order by p.tipo";
        	$risultato=$this->connessione->query($query);
        $r='';
        $cont=0;
        while($rs = $risultato->fetch_array()) { 
              if ($rs['indicazioni']!='') $rr=$rs['nome']."*";//
           else $rr=$rs['nome'];//
          $r.= $rr."  ".round($rs['calorie'], 1)." Kcal\n";
          $cont+=$rs['calorie'];
       }
	return $r."\nL' apporto calorico totale è di: ".$cont." Kcal\n\n * indica le pietanze contenenti glutine e/o lattosio e/o uova";
   
    }
    

    /// Costruisce l'array sulla base del quale verrà creata la tastiera personalizzata Telegram
    /**
     * Non ha niente in ingresso.
     * Ogni elemento dell'array è a sua volta un array che contiene i
     * comandi che compariranno nei pulsanti di una riga. In questo caso una 
     * riga sarà costituita da un solo pulsante, vista la lunghezza complessiva 
     * del comando seguito dall'identificativo della scuola
     * \return l'array con il testo  
     */
   public function opzioni() {
        $option1=array();
        $quanti_oggi=$this->quanti();
        foreach($this->oggi as $chiave => $valore) {
			$option1[]=array("/".$this->tipo_query." ".$valore["nomescuola"]." ".$valore["grado"]);
		}
		return $option1;
    }
    
  	private function crea_array_oggi() {
      	 $array_oggi=array();
      $query="select distinct scuole.nomescuola, menusettimana.grado from scuole join menusettimana using(idscuola) where scuole.nomescuola like '$this->nomescuola%' and menusettimana.grado like '$this->nomegrado%'"; // where scuole.nomescuola like "Spir%" and menusettimana.grado like "inf%"
		$risultato=$this->connessione->query($query);
       	while($rs = $risultato->fetch_array()) { 
      	 $array_oggi[] = $rs;
       }
       
		return $array_oggi;
	}

} //Chiude la classe

?>
