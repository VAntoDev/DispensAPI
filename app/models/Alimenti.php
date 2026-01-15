<?php
// Alimenti.php : essendo un Model, si occupa di gestire solo le interazioni con il DB

// questa è il tipo di alimento, non un oggetto specifico dell'alimento

//AGGIUNGI QUERY DINAMICHE E NON COME SONO ORA.
//CAMBIA LE VARIABILI IN PRIVATE E CREA SETTER E GETTER
class Alimenti{
    //cose database
    private $conn;
    private $table = 'alimenti'; //verranno letti dalla tabella alimenti

    //proprietà alimento
    public $id;
    public $nome;
    public $categoria_id;
    public $percorso_immagine;
    public $quantita;
    public $unita_id;
    public $utente_id;

    //costruttore con connessione db
    public function __construct($db){
        $this->conn = $db;
    }

    //legge i record del database
    public function read(){
        //creo la query, questa 
        $query = 'SELECT a.id, a.nome, c.nome AS categoria
                FROM alimenti a
                LEFT JOIN categorie c ON a.categoria_id = c.id
                ORDER BY a.id DESC;';
        //prepare statement
        //invia la query a mysql, così la analizza e prepara, poi ritorna un PDOStatement
        
        //try catch per la query
        try{
            $stmt = $this->conn->prepare($query);
            $stmt->execute();

        } catch (PDOException $e){
            echo "errore PDO: " . $e->getMessage();
        }

        //esegue la query tramite lo statement
        //sostituisce i parametri :id, ?, etc..
        //il result set viene creato e le righe vengono salvate dentro l'oggetto PDOStatement
        //ritorna true se va bene, false se da errore

        //return $stmt;
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    //prende tutti gli alimenti di un utente dal database
        public function readByUserId(int $id) {
        $query = '
            SELECT a.id, a.nome, c.nome AS categoria
            FROM alimenti a
            LEFT JOIN categorie c ON a.categoria_id = c.id
            WHERE a.utente_id = :utente_id
        ';

        //prepara la query
        $stmt = $this->conn->prepare($query);
        //mette il valore dell'utente in base a ciò che è stato passato alla funzione (lo mette qui e non nella query per evitare SQL injection)
        $stmt->bindValue(':utente_id', $id, PDO::PARAM_INT);
        //esegue la query
        $stmt->execute();

        //ritorna l'array dei risultati della query
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }


    //aggiunge uno o più record tramite l'API
    //qui uso categoria_id perché categoria l'ho sempre usato per il nome con il left join ma non avrei dovuto farlo...

    /*
    L'utente deve settare:
    public $nome;
    public $categoria_id;
    public $immagine;
    public $quantita;
    public $unita_id;
    public $utente_id;
    */
    public function create($nome, $categoria_id, $percorso_immagine, $quantita, $unita_id, $utente_id){
 

        //usiamo INSERT SET, così possiamo usare :nome e :categoria come parametri bindati nel prepared statement
        $query = 'INSERT INTO ' . $this->table . ' SET nome = :nome, categoria_id = :categoria_id';
        //prepare statement
        $stmt = $this->conn->prepare($query);
        //pulisco i dati da caratteri speciali prima di inserirli nel db
        $this->nome = htmlspecialchars(strip_tags($this->nome));
        $this->categoria_id = htmlspecialchars(strip_tags($this->categoria_id));
    
        //binding dei parametri
        $stmt->bindParam(':nome', $this->nome);
        $stmt->bindParam(':categoria_id', $this->categoria_id);
    
        //eseguo la query
        if($stmt->execute()){
            return true;
        } else {
            printf("Errore %s. \n", $stmt->error);
            return false;
        }
    }

        //la logica dietro create e update è molto simile, quindi possiamo usare il create come base
        //update alimento
    public function update(){
        //usiamo INSERT SET, così possiamo usare :nome e :categoria come parametri bindati nel prepared statement
        $query = 'UPDATE ' . $this->table . ' SET nome = :nome, categoria_id = :categoria_id
        WHERE id = :id';
        //prepare statement
        $stmt = $this->conn->prepare($query);
        //pulisco i dati da caratteri speciali prima di inserirli nel db
        $this->nome = htmlspecialchars(strip_tags($this->nome));
        $this->categoria_id = htmlspecialchars(strip_tags($this->categoria_id));
        $this->id = htmlspecialchars(strip_tags($this->id));
        //binding dei parametri
        $stmt->bindParam(':nome', $this->nome);
        $stmt->bindParam(':categoria_id', $this->categoria_id);
        $stmt->bindParam(':id', $this->id);
    
        //eseguo la query
        if($stmt->execute()){
            return true;
        } else {
            printf("Errore %s. \n", $stmt->error);
            return false;
        }
    }

    public function delete(){
        //usiamo INSERT SET, così possiamo usare :nome e :categoria come parametri bindati nel prepared statement
        $query = 'DELETE FROM ' . $this->table . ' WHERE id = :id';
        //prepare statement
        $stmt = $this->conn->prepare($query);
        //pulisco i dati da caratteri speciali prima di inserirli nel db
        $this->id = htmlspecialchars(strip_tags($this->id));
        //binding dei parametri
        $stmt->bindParam(':id', $this->id);
    
        //eseguo la query
        if($stmt->execute()){
            return true;
        } else {
            printf("Errore %s. \n", $stmt->error);
            return false;
        }   
    }
/*
    private function salvaImmagine(array $file): string {

        $uploadDir = __DIR__ . '/../../public/uploads/alimenti/';
        
        if (!is_dir($uploadDir) || !is_writable($uploadDir)) {
            http_response_code(500);
            echo json_encode([
                'error' => 'Directory upload non configurata'
            ]);
            exit;
        }

        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = uniqid('img_', true) . '.' . $ext;

        move_uploaded_file($file['tmp_name'], $uploadDir . $filename);

        return '/uploads/alimenti/' . $filename;
    }


*/
    private function salvaImmagine(array $file): string {

        $uploadDir = __DIR__ . '/../../public/uploads/alimenti/';

        //se la cartella in cui fare l'upload non esiste allora da errore
        if (!is_dir($uploadDir) || !is_writable($uploadDir)) {
            throw new RuntimeException('Upload dir non valida');
        }

        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Errore upload');
        }

        //controllo che l'immagine sia solo del tipo che voglio, altrimenti formato non valido
        $mime = mime_content_type($file['tmp_name']);
        if (!in_array($mime, ['image/jpeg', 'image/png'])) {
            throw new RuntimeException('Formato non valido');
        }

        $filename = uniqid('img_', true) . '.jpg';
        move_uploaded_file($file['tmp_name'], $uploadDir . $filename);

        return '/uploads/alimenti/' . $filename;
    }
}
?>