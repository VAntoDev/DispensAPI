<?php
// Dispense.php : si occupa di gestire le dispense dell'utente. L'utente passa: nome della dispensa e il suo id.

class Dispense{
    //proprietà database
    private $conn;
    private $table = 'dispense'; //verranno letti dalla tabella dispense

    //proprietà dispensa
    private $id;
    private $utente_id;
    private $nome;

    //costruttore con connessione db
    public function __construct($db){
        $this->conn = $db;
    }

    //prende tutte le dispense di un utente dal database
    public function readByUserId(int $utente_id) {
        $query = 'SELECT id, nome, utente_id
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

    // Crea la dispensa nel db
    public function create(){
        try {
            //query
            $query = 'INSERT INTO ' . $this->table . ' SET utente_id = :utente_id, nome = :nome';
            // preparazione query
            $stmt = $this->conn->prepare($query);
            
            //binding dei valori
            $stmt->bindValue(':utente_id', $this->utente_id);
            $stmt->bindValue(':nome', $this->nome);

            //eseguo la query
            $stmt->execute();

            // Una volta eseguita, se è andato tutto bene, ritorno la riga con l'id appena inserito
            $stmt = $this->conn->prepare("
                SELECT id, nome
                FROM dispense
                WHERE id = :id
            ");

            $stmt->bindValue(':id', $this->conn->lastInsertId());
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        //se c'è un problema allora da errore
        } catch (PDOException $e) {
            return;
        }
    }

    //Aggiorna una dispensa dell'utente dal db
    public function update(){
        try{
            $query = '
                UPDATE ' . $this->table . ' 
                SET 
                    nome = :nome
                WHERE 
                    id = :id AND utente_id = :utente_id';

            //prepare statement
            $stmt = $this->conn->prepare($query);

            //pulisco i dati da caratteri speciali prima di inserirli nel db
            $this->id = htmlspecialchars(strip_tags($this->id));
            $this->nome = htmlspecialchars(strip_tags($this->nome));
            $this->utente_id = htmlspecialchars(strip_tags($this->utente_id));

            //binding dei parametri
            $stmt->bindValue(':id', $this->id);        
            $stmt->bindValue(':nome', $this->nome);
            $stmt->bindValue(':utente_id', $this->utente_id);
        
            //eseguo la query
            $stmt->execute();
                
            //Nel caso in cui l'id non esista per quell'utente o l'utente manda la richiesta per un id diverso dal suo
            if ($stmt->rowCount() < 1) {
                // Nessuna riga aggiornata: id inesistente o non appartiene all'utente
                http_response_code(404);
                return;
            }

            // Una volta eseguita, se è andato tutto bene, ritorno la riga con l'id appena inserito
            $stmt = $this->conn->prepare("
                SELECT id, nome
                FROM dispense
                WHERE id = :id
            ");
                
            $stmt->bindValue(':id', $this->id);
            $stmt->execute();
                
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        //Se c'è un problema non ritorna nulla, così il controller manda errore
        } catch (PDOException $e) {
            return;
        }
    }

    //Elimina una dispensa dell'utente dal db
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
        } else { //errore nello statement, ritorno false
            return false;
        }   
    }

    // getter
    public function getId() { return $this->id; }
    public function getUtenteId() { return $this->utente_id; }
    public function getNome() { return $this->nome; }

    // setter
    public function setId($id) { $this->id = $id; }
    public function setUtenteId($utente_id) { $this->utente_id = $utente_id; }
    public function setNome($nome) { $this->nome = $nome; }

}
?>