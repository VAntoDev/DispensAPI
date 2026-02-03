<?php
class DispenseController {

    // Il model che usiamo è della classe Utenti, quindi creo una variabile privata così che solo questa classe la possa usare
    private Dispense $model;

    public function __construct(PDO $db){
        //creo una istanza della classe utenti, passandole la connessione al db (come richiesto dalla classe stessa)
        $this->model = new Dispense($db); // $db viene da config.php
    }

    public function select($id_utente) { 
        // l'utente non dovrebbe poterle leggere tutte, rimuovere successivamente
        if($id_utente === null){ // senza id
            http_response_code(404);
            //echo json_encode(['error' => 'Richiesta non esistente per questo model']);
            return;
        } else { //con id
            //richiesta solo alimenti dell'user
            $result = $this->model->readByUserId($id_utente);

            if(empty($result)) {
                http_response_code(404);
                // echo json_encode(['message' => 'Nessuna dispensa trovata']);
                return;
            }
            
            http_response_code(200);
            echo json_encode(['data' => $result]);
        }
    }
    
    // Crea dispensa
    public function create($id_utente){
            $data = json_decode(file_get_contents("php://input"), true);

            $this->model->setUtenteId($id_utente);
            $this->model->setNome($data['nome']);

            //richiesta di creazione dispensa
            $risultato = $this->model->create();

            if(empty($risultato)){
                http_response_code(404);
                // echo json_encode(['message' => 'Nessuna dispensa trovata']);
                return;
            }
            
            http_response_code(200);
            echo json_encode(['data' => $risultato]);
            //echo json_encode(['message' => 'Dispensa aggiunta']);
    }

    // Update della dispensa, ne modifica il nome
    public function update($id_utente){
        
        //leggo il body raw della richiesta PUT
        $data = json_decode(file_get_contents("php://input"), true);

        $this->model->setId($data['id']);
        $this->model->setUtenteId($id_utente);
        $this->model->setNome($data['nome']);

        //richiesta update account inviato dall'user
        $risultato = $this->model->update();

        if(empty($risultato)){
            http_response_code(404);
            //echo json_encode(['message' => 'Dispensa non aggiornata a causa di un errore / oppure la modifica è uguale al precedente alimento o utente non corretto o non esiste']);
            return;
        }
        
        http_response_code(200);
        echo json_encode(['data' => $risultato]);
        //echo json_encode(['message' => 'Dispensa aggiornata']);
    }

    // elimina la dispensa tramite l'id della dispensa e l'utente che la possiede
    public function delete($id_utente){
        //leggo il body raw della richiesta PUT
        $data = json_decode(file_get_contents("php://input"), true);

        $this->model->setId($data['id'] ?? null);
        $this->model->setUtenteId($id_utente ?? null);

        //richiesta eliminazione account inviata dall'user
        $risultato = $this->model->delete();

        if ($risultato === false) {
            http_response_code(404);
            //echo json_encode(['message' => 'Dispensa non eliminata a causa di un errore / non esiste o non sei utente non corretto']);
            return;
        }
        
        http_response_code(204);
        //echo json_encode(['message' => 'Dispensa eliminata']); 
    }
    
}
?>