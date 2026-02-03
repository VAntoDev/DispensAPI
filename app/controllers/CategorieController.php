<?php
class CategorieController {

    // Il model che usiamo è della classe Categorie, quindi creo una variabile privata così che solo questa classe la possa usare
    private Categorie $model;

    public function __construct(PDO $db){
        //creo una istanza della classe Categorie, passandole la connessione al db (come richiesto dalla classe stessa)
        $this->model = new Categorie($db); // $db viene da config.php
    }

    //Visualizza e manda le categorie salvate nel db, esse non appartegono a nessun utente in particolare
    public function select() { 
        //Sostituisco la cache messa inizialmente nell'index come no store perché questa risorsa è uguale per tutti gli utenti e non cambia quasi mai, così l'utente genera meno richieste per questa risorsa
        header('Cache-Control: public, max-age=3600'); // cacheabile per 1h
        
        $id = null; // L'id è null perché questa richiesta funziona solo in questo modo, lascio l'if in caso di cambiamenti futuri 
        if($id === null){ // senza id, funziona solo senza questa richiesta
            //richiesta tutte le categorie nella tabella
            $result = $this->model->read();

            //se il risultato è un array vuoto, perché qualcosa è andato storto con la query / non ci sono righe, allora errore 404
            if (empty($result)){
                http_response_code(404);
                //echo json_encode(['message' => 'Nessuna categoria trovata']);
                return;
            }

            //query andata a buon fine, mando il risultato
            http_response_code(200);
            echo json_encode(['data' => $result]);
        }
    }
}
?>