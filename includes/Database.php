<?php

// Database : Gestisce la connessione con il database, prende variabili d'ambiente da un file separato per non salvare in chiaro
class Database {
    private $conn;

    public function connect() {
        if ($this->conn === null) {
            try {
                //Connessione al database con il PDO
                $this->conn = new PDO(
                    "mysql:host=" . $_ENV['DB_HOST'] . ";dbname=" . $_ENV['DB_NAME'] . ";charset=utf8",
                    $_ENV['DB_USER'],
                    $_ENV['DB_PASSWORD'],
                    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
                );

                //DB Attributes setting
                //Disattiva i prepare emulati, così da maggiore protezione dalle sql injection
                $this->conn->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
                
                //Così puoi fare rowCount(), in questo modo puoi fare più query sulla stessa connessione (i risultati vengono caricati sulla RAM), pesante con milioni di righe
                //Puoi anche scorrere i risultati avanti e indietro
                $this->conn->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);

                //Gli erorri diventano eccezioni così posso usare try/catch
                $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            } catch(PDOException $e) {
                die("Errore connessione DB: " . $e->getMessage());
            }
        }
        return $this->conn;
    }
}
?>
