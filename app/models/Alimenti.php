<?php
// Alimenti.php : essendo un Model, si occupa di gestire solo le interazioni con il DB

// Questo è il tipo di alimento, non un oggetto specifico dell'alimento (quella sarebba una scadenza)
class Alimenti{
    //proprietà database
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
        //Ho deciso di non usare questa funzione per Alimenti poiché non la userei nell'applicazione
        return;
        //creo la query
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
        
        //invia la query a mysql, così la analizza e prepara, poi ritorna un PDOStatement
        //prepare ed esegue la queyry
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

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    //prende tutti gli alimenti di un utente dal database
        public function readByUserId($id_utente) {
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
        //mette il valore dell'utente in base a ciò che è stato passato al metodo (lo mette qui e non nella query per evitare SQL injection)
        $stmt->bindValue(':utente_id', $id_utente, PDO::PARAM_INT);
        //esegue la query
        $stmt->execute();

        //ritorna l'array dei risultati della query
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    //Crea un alimento ad un utente sul db
    public function create(){
        try {
            //query
            $query = 'INSERT INTO ' . $this->table . ' SET nome = :nome, categoria_id = :categoria_id, quantita = :quantita, unita_id = :unita_id, utente_id = :utente_id';
            // preparazione query
            $stmt = $this->conn->prepare($query);
            
            //binding dei valori
            $stmt->bindValue(':nome', $this->nome);
            $stmt->bindValue(':categoria_id', $this->categoria_id);
            $stmt->bindValue(':quantita', $this->quantita);
            $stmt->bindValue(':unita_id', $this->unita_id);
            $stmt->bindValue(':utente_id', $this->utente_id);

            //eseguo la query
            $stmt->execute();

            //prendo l'id dell'alimento appena aggiunto
            $alimentoId = $this->conn->lastInsertId();

            //Salvo l'immagine
            try {
                if($this->percorso_immagine != null){
                    $percorso = $this->salvaImmagine($_FILES['immagine']);
                    $this->percorso_immagine = $percorso;
                } else {
                    //immagine null
                }
            } catch (RuntimeException $e){
                //400 in questo caso è "errore del client nel mandare l'immagine"
                return;
            }
            
            //Update che aggiunge il percorso dell'immagine, perché va fatto solo dopo che è stato creato sul pc e la query che aggiunge l'alimento è andata a buon fine
            $query = "UPDATE alimenti SET percorso_immagine = :percorso_immagine WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindValue(':percorso_immagine', $this->percorso_immagine);
            $stmt->bindValue(':id', $alimentoId);
            $stmt->execute();

            // Una volta eseguita, se è andato tutto bene, ritorno la riga con l'id appena inserito
            $stmt = $this->conn->prepare("
                SELECT id, nome, categoria_id, percorso_immagine, unita_id, quantita
                FROM alimenti
                WHERE id = :id
            ");

            $stmt->bindValue(':id', $alimentoId);
            $stmt->execute();

            //ritorno le righe ricavate dalla select tramite un array associativo
            return $stmt->fetchAll(PDO::FETCH_ASSOC);            

        //se da un problema allora errore, non continua le operazioni
        } catch (PDOException $e) {
            return;
        }
    }

    //Aggiorna un alimento ad un utente sul db
    public function update(){
        //usiamo INSERT SET, così possiamo usare :nome e :categoria come parametri bindati nel prepared statement
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
                return;
            }

            // Una volta eseguita, se è andato tutto bene, ritorno la riga con l'id appena inserito
            $stmt = $this->conn->prepare("
                SELECT id, nome, categoria_id, percorso_immagine, unita_id, quantita
                FROM alimenti
                WHERE id = :id
            ");

            $stmt->bindValue(':id', $this->id);
            $stmt->execute();

            //ritorno le righe della select tramite un array associativo
            return $stmt->fetchAll(PDO::FETCH_ASSOC);  
        } else { // se da errore lo statement non continua le operazioni
            return;
        }
    }

    //Elimina un alimento di un utente dal db
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

            return true; //statement andato a buon fine, elemento eliminato
        } else { //se da errore il lo statement non continuo le operazioni
            return false;
        }   
    }

    //Salva le immagini all'interno del server, ritornando il percorso relativo a cui sono state salvate
    public function salvaImmagine(array $file): string {
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

        //definisco i tipi di formati accettati
        $ext = match ($mime) {
            'image/jpeg' => '.jpg',
            'image/png'  => '.png',
            'image/webp' => '.webp',
            default      => throw new RuntimeException('Formato non valido'),
        };

        //genero un id unico per ogni file
        $filename = uniqid('img_', true) . $ext;

        //muovo il file all'interno della cartella uploads in cui sono salvate le immagini dell'utente
        if (!move_uploaded_file($file['tmp_name'], $uploadDir . $filename)) {
            throw new RuntimeException('Errore nel salvataggio file');
        }

        //ritorno il percorso relativo dell'immagine
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