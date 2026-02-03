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
            http_response_code(400);
            //echo json_encode(['error' => 'Parametro non valido']);
        }

    }

    /* Crea: register e login */
    private function register(){
            $data = json_decode(file_get_contents("php://input"), true);

            $this->model->setEmail($data['email']);
            $this->model->setPassword($data['password']);
            $this->model->setNome($data['nome']);

            //richiesta di registrazione user
            $risultato = $this->model->register();

            if ($risultato === false) {
                http_response_code(400);
                //echo json_encode(['message' => 'Account non aggiunto a causa di un errore']);
                return;
            }
            
            http_response_code(200);
            //echo json_encode(['message' => 'Account aggiunto']);
    }

    private function login(){
            $data = json_decode(file_get_contents("php://input"), true);
            
            $this->model->setEmail($data['email']);
            $this->model->setPassword($data['password']);

            //richiesta di login user
            $risultato = $this->model->login();

            if (empty($risultato)){
                http_response_code(401); // non autorizzat / credenziali sbagliate
                //echo json_encode(['message' => 'Account non loginnato a causa di un errore']);
                return;
            }

            http_response_code(200);
            echo json_encode(['data' => $risultato]);
            //echo json_encode(['message' => 'Account loginnato correttamente']);
    }

    /* Implementata SENZA SICUREZZA con JWT */
    public function update($param){
        
        //leggo il body raw della richiesta PUT
        $data = json_decode(file_get_contents("php://input"), true);

        $this->model->setId($data['id'] ?? null);
        $this->model->setNome($data['nome'] ?? null);
        $this->model->setEmail($data['email'] ?? null);
        $this->model->setPassword($data['password'] ?? null);

        //richiesta update account inviato dall'user
        $risultato = $this->model->update();

        if(empty($risultato)){
            http_response_code(404);
            //echo json_encode(['message' => 'Account non aggiornato a causa di un errore / oppure la modifica è uguale al precedente alimento o utente non corretto o non esiste']);
            return;
        }

        http_response_code(200);
        echo json_encode(['data' => $risultato]);
        //echo json_encode(['message' => 'account aggiornato']);
    }
    

    /* Implementata SENZA SICUREZZA con JWT */
    public function delete($param){
        
        //leggo il body raw della richiesta PUT
        $data = json_decode(file_get_contents("php://input"), true);

        $this->model->setId($data['id'] ?? null);

        //richiesta eliminazione account inviata dall'user
        $risultato = $this->model->delete();

        if ($risultato === false) {
            http_response_code(404);
            //echo json_encode(['message' => 'Account non eliminato a causa di un errore / non esiste o non sei utente non corretto']);
            return;
        }

        http_response_code(204);
        //echo json_encode(['message' => 'Account eliminato']); 
    }
    
}
?>