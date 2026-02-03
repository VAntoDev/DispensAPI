<?php
// Unita_Misura.php : si occupa di gestire le unità di misura, non sono specifiche di un utente perché queste sono condivise tra tutti gli utenti

class Unita_Misura{
    //proprietà database
    private $conn;
    private $table = 'unita_misura'; //verranno letti dalla tabella alimenti

    //costruttore con connessione db
    public function __construct($db){
        $this->conn = $db;
    }

    //legge i record del database
    public function read(){

        //creo la query
        $query = '
        SELECT 
            id,
            nome,
            simbolo
        FROM unita_misura';

        //prepare ad esegue la query
        try{
            $stmt = $this->conn->prepare($query);
            $stmt->execute();

        } catch (PDOException $e){
            // se lo statement da errore non continuo le operazioni
            return;
        }

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>