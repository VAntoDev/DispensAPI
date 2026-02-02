<?php
class ScadenzeController {

    // Il model che usiamo è della classe Utenti, quindi creo una variabile privata così che solo questa classe la possa usare
    private Scadenze $model;

    public function __construct(PDO $db){
        //creo una istanza della classe utenti, passandole la connessione al db (come richiesto dalla classe stessa)
        $this->model = new Scadenze($db); // $db viene da config.php
    }

    public function select($id = null) { 
        // l'utente non dovrebbe poterle leggere tutte, rimuovere successivamente
        if($id === null){ // senza id
            http_response_code(404);
            //echo json_encode(['error' => 'Richiesta non esistente per questo model']);
            return;

        } else { //con id
            //richiesta solo alimenti dell'user
            $result = $this->model->readByUserId($id);

            if (empty($result)) {
                http_response_code(404);
                //echo json_encode(['message' => 'Nessuna scadenza trovata']);
                return;
            }
            
            http_response_code(200);
            echo json_encode(['data' => $result]);
        }
    }
    
    // Crea scadenza
    public function create(){
            $data = json_decode(file_get_contents("php://input"), true);

            $this->model->setUtenteId($data['utente_id']);
            $this->model->setDispensaId($data['dispensa_id']);
            $this->model->setAlimentoId($data['alimento_id']);
            $this->model->setDataScadenza($data['data_scadenza']);

            //richiesta di creazione dispensa
            $risultato = $this->model->create();

            if (empty($risultato)){
                http_response_code(404);
                //echo json_encode(['message' => 'Scadenza non aggiunta a causa di un errore']);
                return;
            }
            http_response_code(200);
            //echo json_encode(['message' => 'Scadenza aggiunta']);
            echo json_encode(['data' => $risultato]);
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

    // elimina la scadenza tramite l'id della scadenza e l'utente che la possiede
    public function delete($param){
        //leggo il body raw della richiesta PUT
        $data = json_decode(file_get_contents("php://input"), true);

        $this->model->setId($data['id'] ?? null);
        $this->model->setUtenteId($data['utente_id'] ?? null);

        //richiesta eliminazione account inviata dall'user
        $risultato = $this->model->delete();

        if ($risultato === false) {
            http_response_code(404);
            //echo json_encode(['message' => 'Scadenza non eliminata a causa di un errore / non esiste o non sei utente non corretto']);
            return;
        }
        
        http_response_code(204);
        //echo json_encode(['message' => 'Scadenza eliminata']); 
    }
    
}
?>