<?php
class ScadenzeController {
    // Il model che usiamo è della classe Scadenze, quindi creo una variabile privata così che solo questa classe la possa usare
    private Scadenze $model;
    
    public function __construct(PDO $db){
        //creo una istanza della classe scadenze, passandole la connessione al db (come richiesto dalla classe stessa)
        $this->model = new Scadenze($db); // $db viene da config.php
    }

    //Visualizza e manda le scadenze di un utente tramite il suo id
    public function select($id_utente = null) { 
        //Se l'utente prova una select senza id darà errore
        if($id_utente === null){ // senza id
            http_response_code(404);
            return;

        } else { //con id
            //richiesta solo scadenze dell'user
            $result = $this->model->readByUserId($id_utente);
            
            //se da errore allora 404, scadenza non trovata
            if (!$result) {
                http_response_code(404);
                echo json_encode(['error' => 'Scadenza non trovata']);
                return;
            }

            //se il risultato è un array vuoto, ritorno comunque che è stato 200 OK perchè è solo l'utente che non ha ancora dati
            if (empty($result)) {
                http_response_code(200);
                echo json_encode(['data' => []]);
                return;
            }
            
            //query andata a buon fine, mando il risultato            
            http_response_code(200);
            echo json_encode(['data' => $result]);
        }
    }
    
    // Crea scadenza, tramite id_utente, dispensa_id, alimento_id e data_scadenza
    public function create($id_utente){
        //prendo dati raw dal json che ha mandato l'utente            
        $data = json_decode(file_get_contents("php://input"), true);

        //assegno i dati alle proprietà del model
        $this->model->setUtenteId($id_utente);
        $this->model->setDispensaId($data['dispensa_id']);
        $this->model->setAlimentoId($data['alimento_id']);
        $this->model->setDataScadenza($data['data_scadenza']);

        //richiesta di creazione dispensa
        $risultato = $this->model->create();

        //se da errore allora 404, scadenza non trovata
        if (!$risultato) {
            http_response_code(404);
            echo json_encode(['error' => 'Scadenza non trovata']);
            return;
        }

        //se il risultato è un array vuoto, ritorno comunque che è stato 200 OK perchè è solo l'utente che non ha ancora dati
        if (empty($risultato)) {
            http_response_code(200);
            echo json_encode(['data' => []]);
            return;
        }

        //query andata a buon fine, mando il risultato            
        http_response_code(200);
        echo json_encode(['data' => $risultato]);
    }

    // elimina la scadenza tramite id della scadenza e utente_id
    public function delete($id_utente){
        //leggo il body raw della richiesta DEL
        $data = json_decode(file_get_contents("php://input"), true);

        //assegno i dati alle proprietà del model
        $this->model->setId($data['id']);
        $this->model->setUtenteId($id_utente);

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

    // Update della scadenza, ho deciso che le scadenze non hanno un update perché se l'utente non le vuole basta eliminare e mettere una nuova scadenza
    /*
    public function update(){
        
        //leggo il body raw della richiesta PUT
        $data = json_decode(file_get_contents("php://input"), true);

        $this->model->setId($data['id']);
        $this->model->setUtenteId($data['utente_id']);
        $this->model->setNome($data['nome']);

        //richiesta update account inviato dall'user
        $risultato = $this->model->update();

        if ($risultato === false) {
            http_response_code(404);
            echo json_encode(['message' => 'Scadenza non aggiornata a causa di un errore / oppure la modifica è uguale al precedente alimento o utente non corretto o non esiste']);
            return;
        }
            
        echo json_encode(['message' => 'Scadenza aggiornata']);
    }
    */
}
?>