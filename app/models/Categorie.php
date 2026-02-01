<?php
class Categorie{
    //cose database
    private $conn;
    private $table = 'categorie'; //verranno letti dalla tabella alimenti

    //costruttore con connessione db
    public function __construct($db){
        $this->conn = $db;
    }

    //legge i record del database
    public function read(){
        //creo la query, questa 
        $query = '
        SELECT 
            id,
            nome
        FROM categorie';

        //try catch per la query
        try{
            $stmt = $this->conn->prepare($query);
            $stmt->execute();

        } catch (PDOException $e){
            echo "errore PDO: " . $e->getMessage();
        }

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>