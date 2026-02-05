<?php
// Utenti.php: si occupa di gestire le operazioni che riguardano l'utente tra cui login, registrazione, update e delete dei suoi dati

class Utenti{
    //proprietà database
    private $conn;
    private $table = 'utenti'; //verranno letti dalla tabella utenti

    //proprietà utente
    private $id;
    private $email;
    private $password; // nel db è salvata come "password_hash", ma l'utente la passerà non hashata perché la conosce solo in chiaro
    private $nome;

    //costruttore con connessione db
    public function __construct($db){
        $this->conn = $db;
    }

    // register (create, POST utenti/register)
    //Crea un record per l'account dell'utente nel db
    public function register(){
        try {
            //lowercase della mail per evitare problemi di caps
            $this->email = strtolower(trim($this->email));

            //controlla se l'utente ha inviato una mail valida, se non lo è non può aggiungere il record e da errore
            if (!filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
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

        //se c'è un problema allora da errore
        } catch (PDOException $e) {
            return false;
        }
    }

    // login (create, POST utenti/login)
    //Permette all'utente di fare il login: controlla se esistono le sue credenziali nel db, se sono corrette allora fornisce all'utente nome, email e il token
    //che userà per mandare le altre richieste, che richiedono un token
    public function login(){
        try {
            //identifico l'utente tramite la mail
            $query = 'SELECT id, email, password_hash, nome
                  FROM ' . $this->table . ' 
                  WHERE email = :email
                  LIMIT 1';

            //tutto in lowercase, così adatto anche se ci sono caratteri con caps
            $this->email = strtolower(trim($this->email));

            // preparazione statement
            $stmt = $this->conn->prepare($query);

            //Binding dei valori
            $stmt->bindValue(':email', $this->email);

            //esecuzione della query
            $stmt->execute();

            //prendo la riga che contiene email e password nel db, così posso usarla per verificare la password
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            //se la row esiste, quindi lo statment non ha dato errore
            if ($row){
                // verifica della password (hashata nel DB)
                if (password_verify($this->password, $row['password_hash'])) {
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
                    // restituisco all'utente nome, email e token (che avrà il suo id)
                    $response = [
                        'success' => true,
                        'user' => [
                            'email' => $row['email'],
                            'nome' => $row['nome']
                        ],
                        'token' => $token
                    ];

                    // password ed email corretta, login riuscito: ritorno il json da mandare all'utente
                    return $response;
                } else {
                    return; // password errata
                }
            } else {
                return; // utente non trovato
            }

        } catch (PDOException $e) { //se il try da un errore di PDO ritorno false
            return false;
        }
    }

    //Aggiorna le credenziali e/o password di un utente
    public function update(){
        try {
            // Devo fare in modo che se la password è null questa non venga neanche aggiunta alla query, così aggiorna solo utente ed email senza dover rifare l'hash
            $query = "
                UPDATE utenti
                SET nome = :nome,
                    email = :email
            ";

            //se l'utente ha passato una password nel json allora la vuole cambiare, aggiungo la password alla query
            if ($this->password !== null) {
                $query .= ", password_hash = :password_hash";
            }

            //finisco la query dicendo di prendere la riga dell'id dell'account dell'utente
            $query .= " WHERE id = :id";

            //prepare statement
            $stmt = $this->conn->prepare($query);

            //pulisco i dati da caratteri speciali prima di inserirli nel db
            $this->id = htmlspecialchars(strip_tags($this->id));
            $this->nome = htmlspecialchars(strip_tags($this->nome));
            $this->email = htmlspecialchars(strip_tags($this->email));
            // non serve proteggere la password con specialchars, nel db è già hashata

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
            
            //ritorna array associativo con nome ed email cambiate (ovviamente non manda id utente o password in chiaro)
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e){
            return;
        }
    }

    //Elimina l'account di un utente dal database
    public function delete(){
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
        } else { //errore nell'esecuzione dello statement
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
    public function setPassword($password) { $this->password = $password; }
    public function setNome($nome) { $this->nome = $nome; }
}
?>