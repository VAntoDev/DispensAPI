<?php
// Utenti.php: è un model. Si occupa di gestire solo azioni con il db

class Utenti{
    //cose database
    private $conn;
    private $table = 'utenti'; //verranno letti dalla tabella utenti

    //proprietà utente
    //id email password_hash nome created_at
    private $id;
    private $email;
    private $password; // ho salvato come password_hash, considera di cambiarlo in "password" e poi fallo anche nel db.
    private $nome;

    //costruttore con connessione db
    public function __construct($db){
        $this->conn = $db;
    }

    //legge i record del database
    public function read(){
        // Non trovo utile read() per gli Utenti in questa app.
        /*
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
        
        try{
            $stmt = $this->conn->prepare($query);
            $stmt->execute();

        } catch (PDOException $e){
            echo "errore PDO: " . $e->getMessage();
        }

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
        */
    }

    //id email password_hash nome
    // in register l'utente usa: email, nome, password
    // in login l'utente usa: email, password

    // register (create, POST utenti/register)
    public function register(){
        try {
            //query
            $query = 'INSERT INTO ' . $this->table . ' SET email = :email, password_hash = :password_hash, nome = :nome'; //qui c'è scritto password hash ma l'utente ovviamente la manda non hashata nel json
            // preparazione query
            $stmt = $this->conn->prepare($query);
            
            //hash della password prima di salvarlo
            $password_hash = password_hash($this->password, PASSWORD_DEFAULT);

            //binding dei valori
            $stmt->bindValue(':email', $this->email);
            $stmt->bindValue(':password_hash', $password_hash);
            $stmt->bindValue(':nome', $this->nome);

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

    // login (create, POST utenti/login)
    public function login(){
        // obbiettivo: prende dal db i dati da verificare, li controlla con i dati che ha mandato l'utente nel json + password_verify(), ritorna true se login successo sennò false
        try {
            //identifico l'utente tramite la mail
            $query = 'SELECT id, email, password_hash, nome
                  FROM ' . $this->table . ' 
                  WHERE email = :email
                  LIMIT 1';

            // preparazione statement
            $stmt = $this->conn->prepare($query);

            //Binding dei valori
            $stmt->bindValue(':email', $this->email);

            //esecuzione della query
            $stmt->execute();

            //prendo la riga che contiene email e password nel db, così posso usarla per verificare la password
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($row) {
                // verifica della password (hashata nel DB)
                if (password_verify($this->password, $row['password_hash'])) {
                    // AGGIUNGERE QUI GENERAZIONE TOKEN JWT, INVECE DI RITORNARE TRUE GLI METTI IL TOKEN E POI IL CONTROLLER LO MANDA CON JSON-ENCODE
                    return true; //password corretta e login riuscito
                } else {
                    return false; // password errata
                }
            } else {
                return false; // utente non trovato
            }

        } catch (PDOException $e) {
            echo json_encode([
                'step' => 'errore PDO login',
                'msg' => $e->getMessage()
            ]);
            return false;
        }
    }

    //AGGIORNARE UPDATE E DELETE CHE FUNZIONANO ANCORA COME LA VECCHIA TEST-REST CHE HAI FATTO
        //la logica dietro create e update è molto simile, quindi possiamo usare il create come base
        //update alimento
    public function update(){
        /* SVILUPPARE DELETE CHE VERIFICA TRAMITE TOKEN JWT PRIMA DI FARE IL UPDATE

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
        */
    }

    public function delete(){
        /* SVILUPPARE DELETE CHE VERIFICA TRAMITE TOKEN JWT PRIMA DI FARE IL DELETE

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
        */
    }

    // getter
    public function getId() { return $this->id; }
    public function getEmail() { return $this->email; }
    public function getPassword() { return $this->password; }
    public function getNome() { return $this->nome; }

    // setter
    public function setId($id) { $this->id = $id; }
    public function setEmail($email) { $this->email = $email; }
    public function setPassword($password) { $this->password = $password; } // lascia in chiaro, hash lo fai nel register()
    public function setNome($nome) { $this->nome = $nome; }
}
?>