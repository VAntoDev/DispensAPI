<?php
class CategorieController {

    // Il model che usiamo è della classe Alimenti, quindi creo una variabile privata così che solo questa classe la possa usare
    private Categorie $model;

    public function __construct(PDO $db){
        //creo una istanza della classe alimenti, passandole la connessione al db (come richiesto dalla classe stessa)
        $this->model = new Categorie($db); // $db viene da config.php
    }

    public function select($id = null) { 
        //Sostituisco la cache messa inizialmente nell'index come no store perché questa risorsa è uguale per tutti gli utenti e non cambia quasi mai, così l'utente genera meno richieste per questa risorsa
        header('Cache-Control: public, max-age=3600'); // cacheabile per 1h
        

        if($id === null){ // senza id, funziona solo senza questa richiesta
            //richiesta tutte le categorie nella tabella
            $result = $this->model->read();

            if (empty($result)){
                http_response_code(404);
                //echo json_encode(['message' => 'Nessuna categoria trovata']);
                return;
            }

            http_response_code(200);
            echo json_encode(['data' => $result]);
        }
    }
}
?>