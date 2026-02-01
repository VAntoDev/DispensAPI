<?php
class UnitamisuraController {

    // Il model che usiamo è della classe Alimenti, quindi creo una variabile privata così che solo questa classe la possa usare
    private Unita_Misura $model;

    public function __construct(PDO $db){
        //creo una istanza della classe alimenti, passandole la connessione al db (come richiesto dalla classe stessa)
        $this->model = new Unita_Misura($db); // $db viene da config.php
    }

    public function select($id = null) { 
        if($id === null){ // senza id, funziona solo senza questa richiesta
            //richiesta tutte le unità di misura nella tabella
            $result = $this->model->read();

            if (empty($result)){
                http_response_code(404);
                echo json_encode(['message' => 'Nessuna unita di misura trovata']);
                return;
            }

            echo json_encode(['data' => $result]);
        }
    }
}
?>