<?php
/**
 * Materia per gestire le richieste relative ad oggi.
 * @author Domenico Fasano
 */
class Oggi {

	private $oggi = array();
    private $menu = array();
    private $nomescuola="";
    private $nomegrado="";
    private $tipo_query = ""; // al momento, non ha alcun utilizzo; per un uso futuro
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
     * Distrugge l'istanza di oggi
     */
    public function __destruct() {
        $this->connessione->close();
        unset($this->nomescuola); 
    	unset($this->nomegrado);
    	unset($this->tipo_query);
    	unset($this->connessione);
        unset($this->invio);
        unset($this->oggi); 
    }
    
    
    /// Numero di elementi nell'array oggi
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
    	//return $nomescuola." ".$nomegrado;
		//Giorno in italiano
		$giorni = array("Lunedi'", "Martedi'", "Mercoledi'", "Giovedi'", "Venerdi'");
		$piatti = array("Primo", "Secondo", "Contorno", "Frutta", "Pane", "TotKCal");

    	$r=""; // La stringa risultato da restituire al chiamante
    	
    	// Controllo sul numero delle classi
    	$quanti_oggi=$this->quanti();
    	if ($quanti_oggi==0) {
        
    		
			return "Non esiste alcuna scuola che inizia per: <b>$this->nomescuola</b> ".$this->nomegrado;
		}
		else {
			if ($quanti_oggi>1) {
				return "Ci sono diverse scuole che comunciato per : <b>$this->nomescuola</b>".$this->nomegrado.", \nScegli quella desiderata dalla lista seguente:\n\n";
            }
		} 
        reset($this->oggi);
        if(!$this->invio)
           	$r= "Il menu odierno della scuola <b> $this->nomescuola </b>: \n\n".$this->stringaSQL();
        else
        	$q="";
	switch ($this->tipo_query) {
       case "settimana": {
            if(!$this->invio)
						$r="Comando già eseguito se si vuole ricevere nuovamente il menu si prega di reinserire il comando /invia .";	
					
                	//Creazione tabella vuota
             else 
             {
             
            for ($i=1;$i<7;$i++){
                 for($j=1;$j<=5;$j++){
                  $tabella[$i][$j]="";
                  }
                 }
                   
                    //Creo l'array multidimesionale (array di array) con all'interno le informazioni della mensa della scuola selezionata
                  
               for($giorno=1; $giorno<6; $giorno++){
                 //Utilizzo il metodo stringaSQLemail che mi restituisce la query contente le informazioni relative al giorno e al menu
                 $q=$this->stringaSQLemail($giorno+1);
                 $risultato=$this->connessione->query($q);
                 $piatto=1;
                 $cont=0;
                while($rs = $risultato->fetch_array(MYSQLI_ASSOC)){
                   $tabella[$piatto][$giorno].= $rs['nome'].", ".round($rs['calorie'], 1)."Kcal";
                   $cont+=$rs['calorie'];
                   $piatto++;
                 }
                  $tabella[$piatto][$giorno]=$cont;
               }//Chiude il for    
                        
                    $r= $tabella;
             } //Chiude l'else
          }
       }
   return $r;
}

    /// Per costruire la stringa SQL. 
    /**
     * \return la stringa che poi sarà la query da inviare al server SQL
     */
    public function stringaSQL() {
		$que='SELECT IDSTAGIONE FROM stagioni WHERE DATE(NOW())BETWEEN DATA_FROM and DATA_TO';
        $r=$this->connessione->query($que);
        $stagione=$r->fetch_array()[0];
        
        $quer="SELECT menusettimana.IDMENUSETTIMANA FROM menusettimana join scuole using(idscuola) 
        	where (menusettimana.settimana= IF(ceil(dayofmonth(now())/7)=5, 1, ceil(dayofmonth(now())/7)) AND scuole.NomeScuola like '$this->nomescuola%' AND menusettimana.IDStagione=$stagione AND menusettimana.grado like '$this->nomegrado%')";//$this->nomegrado $this->nomescuola
        $r=$this->connessione->query($quer);
        $menusettimana=$r->fetch_array()[0]; 
        
        $query="select p.nome,p.indicazioni, if('$this->nomegrado'='INFANZIA' AND p.tipo='primo', SUM((ep.quantita-20)*e.kcalorieg),SUM(ep.quantita*e.kcalorieg)) as calorie 
				from menu_pie mp JOIN pietanze p USING (idpietanza) 
	 				JOIN ele_pie ep USING (Idpietanza)
     				JOIN elementi e USING (idelemento)
                where mp.idmenu=$menusettimana and mp.giorno=IF(dayofweek(now())>6 or dayofweek(now())=1, 2, dayofweek(now()))
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
    public function stringaSQLemail($giorno){
    	$que='SELECT IDSTAGIONE FROM stagioni WHERE DATE(NOW())BETWEEN DATA_FROM and DATA_TO';
        $r=$this->connessione->query($que);
        $stagione=$r->fetch_array()[0];
        
        $quer="SELECT menusettimana.IDMENUSETTIMANA FROM menusettimana join scuole using(idscuola) 
        	where (menusettimana.settimana= IF(ceil(dayofmonth(now())/7)=5, 1, ceil(dayofmonth(now())/7)) AND scuole.NomeScuola like '$this->nomescuola%' AND menusettimana.IDStagione=$stagione AND menusettimana.grado like '$this->nomegrado%')";//$this->nomegrado $this->nomescuola
        $r=$this->connessione->query($quer);
        $menusettimana=$r->fetch_array()[0];//if('$this->nomegrado'='INFANZIA' AND p.tipo='primo', SUM((ep.quantita-20)*e.kcalorieg),
       	
        $query="select p.nome, if('$this->nomegrado'='INFANZIA' AND p.tipo='primo', SUM((ep.quantita-20)*e.kcalorieg),SUM(ep.quantita*e.kcalorieg)) as calorie 
				from menu_pie mp JOIN pietanze p USING (idpietanza) 
	 				JOIN ele_pie ep USING (Idpietanza)
     				JOIN elementi e USING (idelemento)
                where mp.idmenu=$menusettimana and mp.giorno=$giorno
                GROUP BY p.nome
                order by p.tipo";
       return $query;         
    }

    /// Costruisce l'array sulla base del quale verrà creata la tastiera personalizzata Telegram
    /**
     * Non ha niente in ingresso.
     * \return l'array con il testo  
     */
   public function opzioni() {
        $option1=array();
        $quanti_oggi=$this->quanti();
		/*for($i = 0; $i<$quanti_oggi; $i++) {
			$option1[]=array("/menuoggi".$this->oggi["nomescuola"]["grado"]);
		}*/
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
