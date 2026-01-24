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
    private $id;
    private $nome;
    private $categoria_id;
    private $percorso_immagine;
    private $quantita;
    private $unita_id;
    private $utente_id;

    //costruttore con connessione db
    public function __construct($db){
        $this->conn = $db;
    }

    //legge i record del database
    public function read(){
        //creo la query, questa 
        $query = '
        SELECT 
            a.id,
            a.nome,
            a.categoria_id,
            c.nome AS categoria,
            a.percorso_immagine,
            a.quantita,
            a.unita_id,
            a.utente_id
        FROM alimenti a
        LEFT JOIN categorie c ON a.categoria_id = c.id
        ORDER BY a.id DESC';
        
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
            SELECT 
            a.id,
            a.nome,
            a.categoria_id,
            c.nome AS categoria,
            a.percorso_immagine,
            a.quantita,
            a.unita_id,
            u.simbolo AS unita,
            a.utente_id
            FROM alimenti a
            LEFT JOIN categorie c ON a.categoria_id = c.id
            LEFT JOIN unita_misura u ON a.unita_id = u.id
            WHERE a.utente_id = :utente_id
            ORDER BY a.id DESC
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
    public function create(){
        try {
            //query
            $query = 'INSERT INTO ' . $this->table . ' SET nome = :nome, categoria_id = :categoria_id, percorso_immagine = :percorso_immagine, quantita = :quantita, unita_id = :unita_id, utente_id = :utente_id';
            // preparazione query
            $stmt = $this->conn->prepare($query);
            
            //binding dei valori
            $stmt->bindValue(':nome', $this->nome);
            $stmt->bindValue(':categoria_id', $this->categoria_id);
            $stmt->bindValue(':percorso_immagine', $this->percorso_immagine);
            $stmt->bindValue(':quantita', $this->quantita);
            $stmt->bindValue(':unita_id', $this->unita_id);
            $stmt->bindValue('utente_id', $this->utente_id);

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

    //AGGIORNARE UPDATE E DELETE CHE FUNZIONANO ANCORA COME LA VECCHIA TEST-REST CHE HAI FATTO
        //la logica dietro create e update è molto simile, quindi possiamo usare il create come base
        //update alimento
    public function update(){
        //usiamo INSERT SET, così possiamo usare :nome e :categoria come parametri bindati nel prepared statement
        //vecchio update
        //$query = 'UPDATE ' . $this->table . ' SET nome = :nome, categoria_id = :categoria_id
        //WHERE id = :id';

        //limitazione: l'immagine non si può cambiare.
        $query = '
            UPDATE ' . $this->table . ' 
            SET 
                nome = :nome, 
                categoria_id = :categoria_id, 
                quantita = :quantita, 
                unita_id = :unita_id 
            WHERE 
                id = :id AND utente_id = :utente_id';

        //prepare statement
        $stmt = $this->conn->prepare($query);
        //pulisco i dati da caratteri speciali prima di inserirli nel db
        $this->nome = htmlspecialchars(strip_tags($this->nome));
        $this->categoria_id = htmlspecialchars(strip_tags($this->categoria_id));
        $this->quantita = htmlspecialchars(strip_tags($this->quantita));
        $this->unita_id = htmlspecialchars(strip_tags($this->unita_id));
        $this->id = htmlspecialchars(strip_tags($this->id));
        $this->utente_id = htmlspecialchars(strip_tags($this->utente_id));

        //binding dei parametri
        $stmt->bindValue(':nome', $this->nome);
        $stmt->bindValue(':categoria_id', $this->categoria_id);
        $stmt->bindValue(':quantita', $this->quantita);
        $stmt->bindValue(':unita_id', $this->unita_id);
        $stmt->bindValue(':id', $this->id);    // id dell'alimento scelto
        $stmt->bindValue(':utente_id', $this->utente_id); // id dell'utente che sta mandando la richiesta
    
        //eseguo la query
        if($stmt->execute()){

            //Nel caso in cui l'id non esista per quell'utente o l'utente manda la richiesta per un id diverso dal suo
            if($stmt->rowCount() < 1){
                return false;
            }

            return true;
        } else {
            echo json_encode(['error' => "Errore %s. \n", $stmt->error]);
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

    public function salvaImmagine(array $file): string {
        echo json_encode(['error' => 'sono arrivato dentro salvaImmagine']);
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

        $ext = match ($mime) {
            'image/jpeg' => '.jpg',
            'image/png'  => '.png',
            'image/webp' => '.webp',
            default      => throw new RuntimeException('Formato non valido'),
        };

        $filename = uniqid('img_', true) . $ext;

        if (!move_uploaded_file($file['tmp_name'], $uploadDir . $filename)) {
            throw new RuntimeException('Errore nel salvataggio file');
        }

        return '/uploads/alimenti/' . $filename;
    }
    // getter
    public function getId() { return $this->id; }
    public function getNome() { return $this->nome; }
    public function getCategoriaId() { return $this->categoria_id; }
    public function getPercorsoImmagine() { return $this->percorso_immagine; }
    public function getQuantita() { return $this->quantita; }
    public function getUnitaId() { return $this->unita_id; }
    public function getUtenteId() { return $this->utente_id; }

    // setter
    public function setId($id) { $this->id = $id; }
    public function setNome($nome) { $this->nome = $nome; }
    public function setCategoriaId($categoria_id) { $this->categoria_id = $categoria_id; }
    public function setPercorsoImmagine($percorso_immagine) { $this->percorso_immagine = $percorso_immagine; }
    public function setQuantita($quantita) { $this->quantita = $quantita; }
    public function setUnitaId($unita_id) { $this->unita_id = $unita_id; }
    public function setUtenteId($utente_id) { $this->utente_id = $utente_id; }
}
?>