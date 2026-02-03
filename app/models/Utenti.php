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
            //controllo base per sapere se l'mail è valida
            $email = strtolower(trim($this->email));

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                //echo json_encode(['error' => 'Email non valida']);
                return false;
            }

            //query
            $query = 'INSERT INTO ' . $this->table . ' SET email = :email, password_hash = :password_hash, nome = :nome'; //qui c'è scritto password hash ma l'utente ovviamente la manda non hashata nel json
            // preparazione query
            $stmt = $this->conn->prepare($query);
            
            //faccio hash della password prima di salvarlo
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

            if ($row){
                // verifica della password (hashata nel DB)
                //RIMUOVERE QUESTE LINEE!!!!!
                if (password_verify($this->password, $row['password_hash'])) {
                    // restituisco all'utente nome, email e token (che avrà il suo id)
                    // Una volta eseguita, se è andato tutto bene, ritorno la riga con l'id appena inserito
                    
                    //creazione del token
                    //creo l'oggetto passandogli la secret key che è in .env
                    $auth = new Authorization($_ENV['JWT_SECRET_KEY']);

                    // genero il token, mettendo nel payload l'id dell'utente
                    $token = $auth->generateToken($row['id']);

                    // errore di generazione token
                    if($token == null){
                        return;
                    }
                    // risposta che mando all'utente
                    $response = [
                        'success' => true,
                        'user' => [
                            'email' => $row['email'],
                            'nome' => $row['nome']
                        ],
                        'token' => $token
                    ];

                    //usare generate token passando $row['id'] e 2 (che è il numero di ore per cui rimane valido)
                    //mettere il token con set dentro la row così lo passa all'utente

                    // Rimuovo i dati sensibili dell'utente, mando solo email e nome e token per all'applicazione
                    //unset($row['id']);
                    //unset($row['password_hash']);

                    // ritorno il json da mandare all'utente
                    return $response;

                    //return true; //password corretta e login riuscito
                } else {
                    // rimuovere questo: problema di sicurezza, l'utente non ha bisogno di sapere se sbaglia la password o l'utente altrimenti un malintenzionato potrebbe sapere che esiste un account con quel nome
                    //echo json_encode(["password errata"]);
                    return; // password errata
                }
            } else {
                // rimuovere questo: problema di sicurezza, l'utente non ha bisogno di sapere se sbaglia la password o l'utente altrimenti un malintenzionato potrebbe sapere che esiste un account con quel nome
                //echo json_encode(["utente non trovato"]);
                return; // utente non trovato
            }

        } catch (PDOException $e) {
            echo json_encode([
                'step' => 'errore PDO login',
                'msg' => $e->getMessage()
            ]);
            return false;
        }
    }

    /* Implementata SENZA SICUREZZA con JWT */
    public function update(){
        try {
        /* SVILUPPARE DELETE CHE VERIFICA TRAMITE TOKEN JWT PRIMA DI FARE IL UPDATE
        */

        // Devo fare in modo che se la password è null questa non venga neanche aggiunta alla query, così aggiorna solo utente ed email senza dover rifare l'hash
        $query = "
            UPDATE utenti
            SET nome = :nome,
                email = :email
        ";

        if ($this->password !== null) {
            $query .= ", password_hash = :password_hash";
        }

        $query .= " WHERE id = :id";

        //prepare statement
        $stmt = $this->conn->prepare($query);

        //pulisco i dati da caratteri speciali prima di inserirli nel db
        $this->id = htmlspecialchars(strip_tags($this->id));
        $this->nome = htmlspecialchars(strip_tags($this->nome));
        $this->email = htmlspecialchars(strip_tags($this->email));
        // non serve proteggere la password con specialchars che nel db è già hashata

        //binding dei parametri
        $stmt->bindValue(':id', $this->id);
        $stmt->bindValue(':nome', $this->nome);
        $stmt->bindValue(':email', $this->email);

        // se l'utente chiede di cambiare la password la hasha, altrimenti tiene la stessa
        if (!empty($this->password)) {
            $password_hash = password_hash($this->password, PASSWORD_DEFAULT);
            $stmt->bindValue(':password_hash', $password_hash);
        }

        //eseguo la query
        $stmt->execute();

        //Nel caso in cui l'id non esista per quell'utente o l'utente manda la richiesta per un id diverso dal suo
        if($stmt->rowCount() < 1){
            return;
        }

        // Una volta eseguita, se è andato tutto bene, ritorno la riga con l'id appena inserito
        $stmt = $this->conn->prepare("
            SELECT nome, email
            FROM utenti
            WHERE id = :id
        ");
                
        $stmt->bindValue(':id', $this->id);
        $stmt->execute();
                
        //echo json_encode(['data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e){
            return;
        }
    }

    /* Implementata SENZA SICUREZZA con JWT */
    public function delete(){
        /* SVILUPPARE DELETE CHE VERIFICA TRAMITE TOKEN JWT PRIMA DI FARE IL DELETE
        */

        //usiamo INSERT SET, così possiamo usare :nome e :categoria come parametri bindati nel prepared statement
        $query = 'DELETE FROM ' . $this->table . ' WHERE id = :id';

        //prepare statement
        $stmt = $this->conn->prepare($query);

        //pulisco i dati da caratteri speciali prima di inserirli nel db
        $this->id = htmlspecialchars(strip_tags($this->id));

        //binding dei parametri
        $stmt->bindValue(':id', $this->id);

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
    public function getEmail() { return "$this->email"; }
    public function getPassword() { return $this->password; }
    public function getNome() { return $this->nome; }

    // setter
    public function setId($id) { $this->id = $id; }
    public function setEmail($email) { $this->email = $email; }
    public function setPassword($password) { $this->password = $password; } // lascia in chiaro, hash lo fai nel register()
    public function setNome($nome) { $this->nome = $nome; }
}
?>