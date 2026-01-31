<?php
// Mi serve che l'utente passi: utente_id, dispensa, alimento, data di scadenza
// Dispense.php : si occupa di gestire le dispense dell'utente. L'utente passa: nome della dispensa e il suo id.

class Scadenze{
    //cose database
    private $conn;
    private $table = 'scadenze'; //verranno letti dalla tabella alimenti

    //proprietà scadenza
    private $id;
    private $utente_id;
    private $dispensa_id;
    private $alimento_id;
    private $data_scadenza;

    //costruttore con connessione db
    public function __construct($db){
        $this->conn = $db;
    }

    //prende tutte le scadenze di un utente dal database
    public function readByUserId(int $utente_id) {
        $query = 'SELECT id, utente_id, dispensa_id, alimento_id, data_scadenza
                  FROM ' . $this->table . ' 
                  WHERE utente_id = :utente_id';

        //prepara la query
        $stmt = $this->conn->prepare($query);
        //mette il valore dell'utente in base a ciò che è stato passato alla funzione (lo mette qui e non nella query per evitare SQL injection)
        $stmt->bindValue(':utente_id', $utente_id, PDO::PARAM_INT);
        //esegue la query
        $stmt->execute();

        //ritorna l'array dei risultati della query
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /*
    L'utente deve settare:
    utente_id, dispensa, alimento, data di scadenza. della scadenza
    */
    public function create(){
        try {
            //query
            $query = 'INSERT INTO ' . $this->table . ' SET utente_id = :utente_id, dispensa_id = :dispensa_id, alimento_id = :alimento_id, data_scadenza = :data_scadenza';
            // preparazione query
            $stmt = $this->conn->prepare($query);
            
            //binding dei valori
            $stmt->bindValue(':utente_id', $this->utente_id);
            $stmt->bindValue(':dispensa_id', $this->dispensa_id);
            $stmt->bindValue(':alimento:id', $this->alimento_id);
            $stmt->bindValue(':data_scadenza', $this->data_scadenza);

            //eseguo la query
            $stmt->execute();

            return true;

        //se da un problema allora da errore e lo manda
        } catch (PDOException $e) {
            echo json_encode([
                'step' => 'errore PDO',
                'sql'  => $query,
                'msg'  => $e->getMessage()
        ]);
            return false;
        }
    }

    // per fare l'update prendo l'id della scadenza e l'utente id dal POST dell'utente
    // ho deciso che non c'è bisogno di poter modificare una scadenza, al massimo si può eliminare da parte dell'utente e poi ne crea lui un'altra
    /*
    public function update(){
        $query = '
            UPDATE ' . $this->table . ' 
            SET 
                dispensa_id = :dispensa_id,
                alimento_id = :alimento_id
            WHERE 
                id = :id AND utente_id = :utente_id';

        //prepare statement
        $stmt = $this->conn->prepare($query);
        //pulisco i dati da caratteri speciali prima di inserirli nel db
        $this->id = htmlspecialchars(strip_tags($this->id));
        $this->utente_id = htmlspecialchars(strip_tags($this->utente_id));
        $this->dispensa_id = htmlspecialchars(strip_tags($this->dispensa_id));
        $this->alimento_id = htmlspecialchars(strip_tags($this->alimento_id));
        $this->data_scadenza = htmlspecialchars(strip_tags($this->data_scadenza));


        //binding dei parametri
        $stmt->bindValue(':id', $this->id);
        $stmt->bindValue(':utente_id', $this->utente_id);
        $stmt->bindValue(':dispensa_id', $this->dispensa_id);
        $stmt->bindValue(':alimento:id', $this->alimento_id);
        $stmt->bindValue(':data_scadenza', $this->data_scadenza);
    
        //eseguo la query
        if($stmt->execute()){

            //Nel caso in cui l'id non esista per quell'utente o l'utente manda la richiesta per un id diverso dal suo, o non viene trovata la scadenza
            if($stmt->rowCount() < 1){
                return false;
            }

            return true;
        } else {
            echo json_encode(['error' => "Errore %s. \n", $stmt->error]);
            return false;
        }
    }
    */

    public function delete(){
        //usiamo INSERT SET, così possiamo usare :nome e :categoria come parametri bindati nel prepared statement
        $query = 'DELETE FROM ' . $this->table . ' WHERE id = :id AND utente_id = :utente_id';
        //prepare statement
        $stmt = $this->conn->prepare($query);
        //pulisco i dati da caratteri speciali prima di inserirli nel db
        $this->id = htmlspecialchars(strip_tags($this->id));
        $this->utente_id = htmlspecialchars(strip_tags($this->utente_id));
        //binding dei parametri
        $stmt->bindValue(':id', $this->id);
        $stmt->bindValue(':utente_id', $this->utente_id);

        //eseguo la query
        if($stmt->execute()){
            
            //Nel caso in cui l'id non esista per quell'utente o l'utente manda la richiesta per un id diverso dal suo
            if($stmt->rowCount() < 1){
                return false;
            }

            return true;
        } else {
            printf("Errore %s. \n", $stmt->error);
            return false;
        }   
    }

    // getter
    public function getId() { return $this->id; }
    public function getUtenteId() { return $this->utente_id; }
    public function getDispensaId() { return $this->dispensa_id; }
    public function getAlimentoId() { return $this->alimento_id; }
    public function getDataScadenza() { return $this->data_scadenza; }

    // setter
    public function setId($id) { $this->id = $id; }
    public function setUtenteId($utente_id) { $this->utente_id = $utente_id; }
    public function setDispensaId($dispensa_id) { $this->dispensa_id = $dispensa_id; }
    public function setAlimentoId($alimento_id) { $this->alimento_id = $alimento_id; }
    public function setDataScadenza($data_scadenza) { $this->data_scadenza = $data_scadenza; }

}
?>