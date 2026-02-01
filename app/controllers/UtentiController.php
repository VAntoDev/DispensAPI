<?php
class UtentiController {

    // Il model che usiamo è della classe Utenti, quindi creo una variabile privata così che solo questa classe la possa usare
    private Utenti $model;

    public function __construct(PDO $db){
        //creo una istanza della classe utenti, passandole la connessione al db (come richiesto dalla classe stessa)
        $this->model = new Utenti($db); // $db viene da config.php
    }

    // Distinguo in base ai parametri se l'utente vuole fare il login o il register
    public function create($param){
        if($param == "login"){
            $this->login();
        } else if($param == "register"){
            $this->register();
        } else {
            echo json_encode(['error' => 'Parametro non valido']);
        }

    }

    /* Crea: register e login */
    private function register(){
            $data = json_decode(file_get_contents("php://input"), true);

            $this->model->setEmail($data['email']);
            $this->model->setPassword($$data['password']);
            $this->model->setNome($data['nome']);

            //richiesta di registrazione user
            $risultato = $this->model->register();

            if ($risultato === false) {
                http_response_code(404);
                echo json_encode(['message' => 'Account non aggiunto a causa di un errore']);
                return;
            }
            
            echo json_encode(['message' => 'Account aggiunto']);
    }

    private function login(){
            $data = json_decode(file_get_contents("php://input"), true);

            $this->model->setEmail($data['email']);
            $this->model->setPassword($$data['password']);

            //richiesta di login user
            $risultato = $this->model->login();

            if ($risultato === false) {
                http_response_code(404);
                echo json_encode(['message' => 'Account non loginnato a causa di un errore']);
                return;
            }
            
            echo json_encode(['message' => 'Account loginnato correttamente']);
    }

    public function update(){
        /*
        //leggo il body raw della richiesta PUT
        $data = json_decode(file_get_contents("php://input"), true);

        $this->model->setNome($data['nome'] ?? null);
        $this->model->setId($data['id'] ?? null);
        $this->model->setCategoriaId($data['categoria_id'] ?? null);
        $this->model->setQuantita($data['quantita'] ?? null);
        $this->model->setUnitaId($data['unita_id'] ?? null);
        $this->model->setUtenteId($data['utente_id'] ?? null);

        echo json_encode(['error' => "Sono prima di update"]);

        //richiesta creazione alimento inviato dall'user
        $risultato = $this->model->update();

        if ($risultato === false) {
            http_response_code(404);
            echo json_encode(['message' => 'Alimento non aggiornato a causa di un errore / oppure la modifica è uguale al precedente alimento o utente non corretto o non esiste']);
            return;
        }
            
        echo json_encode(['message' => 'Alimento aggiornato']);
        */    
        echo json_encode(['message' => 'Funzione non implementata']);
    }

    /* Non ancora implementata */
    public function delete(){
        /*
        //leggo il body raw della richiesta PUT
        $data = json_decode(file_get_contents("php://input"), true);

        $this->model->setId($data['id'] ?? null);
        $this->model->setUtenteId($data['utente_id'] ?? null);

        echo json_encode(['error' => "Sono prima di delete"]);

        //richiesta creazione alimento inviato dall'user
        $risultato = $this->model->delete();

        if ($risultato === false) {
            http_response_code(404);
            echo json_encode(['message' => 'Alimento non eliminato a causa di un errore / non esiste o non sei utente non corretto']);
            return;
        }
            
        echo json_encode(['message' => 'Alimento eliminato']); 
        */
        echo json_encode(['message' => 'Funzione non implementata']);
    }
    
}
?>