<?php
class DispenseController {

    // Il model che usiamo è della classe Dispense, quindi creo una variabile privata così che solo questa classe la possa usare
    private Dispense $model;

    public function __construct(PDO $db){
        //creo una istanza della classe Dispense, passandole la connessione al db (come richiesto dalla classe stessa)
        $this->model = new Dispense($db); // $db viene da config.php
    }

    //Visualizza e manda le dispense di un utente tramite il suo id
    public function select($id_utente) { 
        //Se l'utente prova una select senza id darà errore
        if($id_utente === null){ // senza id
            http_response_code(404);
            return;

        } else { //con id
            //richiesta solo dispense dell'user
            $result = $this->model->readByUserId($id_utente);

            //se il risultato è un array vuoto, perché qualcosa è andato storto con la query / non ci sono righe, allora errore 404
            if(empty($result)) {
                http_response_code(404);
                return;
            }
            
            //query andata a buon fine, mando il risultato
            http_response_code(200);
            echo json_encode(['data' => $result]);
        }
    }
    
    //Crea dispensa, tramite utente_id e nome
    public function create($id_utente){
        //prendo dati raw dal json che ha mandato l'utente
        $data = json_decode(file_get_contents("php://input"), true);

        //assegno i dati alle proprietà del model
        $this->model->setUtenteId($id_utente);
        $this->model->setNome($data['nome']);

        //richiesta di creazione dispensa
        $risultato = $this->model->create();

        //se il risultato è un array vuoto, perché qualcosa è andato storto con la query / non ci sono righe, allora errore 404
        if(empty($risultato)){
            http_response_code(404);
            return;
        }
            
        //query andata a buon fine, mando il risultato
        http_response_code(200);
        echo json_encode(['data' => $risultato]);
    }

    //Update della dispensa, ne modifica il nome tramite id e utente_id
    public function update($id_utente){
        
        //leggo il body raw della richiesta PUT
        $data = json_decode(file_get_contents("php://input"), true);

        //assegno i dati alle proprietà del model
        $this->model->setId($data['id']);
        $this->model->setUtenteId($id_utente);
        $this->model->setNome($data['nome']);

        //richiesta update account inviato dall'user
        $risultato = $this->model->update();

        //se il risultato è un array vuoto, perché qualcosa è andato storto con la query / non ci sono righe, allora errore 404
        if(empty($risultato)){
            http_response_code(404);
            return;
        }
        
        //query andata a buon fine, mando il risultato
        http_response_code(200);
        echo json_encode(['data' => $risultato]);
    }

    // elimina la dispensa tramite id della dispensa e utente_id
    public function delete($id_utente){
        //leggo il body raw della richiesta PUT
        $data = json_decode(file_get_contents("php://input"), true);

        //assegno i dati alle proprietà del model
        $this->model->setId($data['id'] ?? null);
        $this->model->setUtenteId($id_utente ?? null);

        //richiesta eliminazione account inviata dall'user
        $risultato = $this->model->delete();

        //se il risultato è un array vuoto, perché qualcosa è andato storto con la query / non ci sono righe, allora errore 404
        if ($risultato === false) {
            http_response_code(404);
            return;
        }
        
        //query andata a buon fine, codice 204 per "No Content"
        http_response_code(204);
    }
    
}
?>