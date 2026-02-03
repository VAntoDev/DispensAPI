<?php
// Categorie.php : si occupa di gestire le categorie, non è specifica di un utente perché queste sono condivise tra tutti gli utenti

class Categorie{
    //proprietà database
    private $conn;
    private $table = 'categorie'; //verranno letti dalla tabella categorie

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
            nome
        FROM categorie';

        //prepara ed esegue la query
        try{
            $stmt = $this->conn->prepare($query);
            $stmt->execute();

        } catch (PDOException $e){
            // se lo statement da errore non continuo le operazioni
            return;
        }

        //ritorno le righe della select tramite un array associativo
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>