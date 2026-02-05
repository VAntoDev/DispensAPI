<?php
class UtentiController {

    // Il model che usiamo è della classe Utenti, quindi creo una variabile privata così che solo questa classe la possa usare
    private Utenti $model;

    public function __construct(PDO $db){
        //creo una istanza della classe utenti, passandole la connessione al db (come richiesto dalla classe stessa)
        $this->model = new Utenti($db); // $db viene da config.php
    }

    // Distinguo in base al parametro se l'utente vuole fare il login o il register
    public function create($param){

        if($param == "login"){
            $this->login();

        } else if($param == "register"){
            $this->register();

        } else {
            //nel caso in cui il parametro non sia uno di questi due mando codice per Bad Request 400
            http_response_code(400);
        }
    }

    //Registra l'utente nell'applicazione
    //Questa operazioe non ha bisogno del token, al contrario delle altre
    private function register(){
        //prendo dati raw dal json che ha mandato l'utente    
        $data = json_decode(file_get_contents("php://input"), true);
        
        //assegno i dati alle proprietà del model
        $this->model->setEmail($data['email']);
        $this->model->setPassword($data['password']);
        $this->model->setNome($data['nome']);

        //richiesta di registrazione user
        $risultato = $this->model->register();

        //se il risultato è false allora l'user non è stato creato a causa di un errore nei dati o della query
        if ($risultato === false) {
            http_response_code(400);
            echo json_encode(['error' => 'Registrazione fallita']);
            return;
        }
        
        //se la registrazione è andata a buon fine mando OK 200
        http_response_code(200);
        echo json_encode(['success' => true, 'message' => 'Utente registrato con successo']);
    }

    //Permette il login dell'utente nella applicazione. Manda all'utente nome, email e token
    //Tramite la risposta questa funzione fornisce il token all'utente, lo dovrà usare per ogni altre operazione diversa da login/register
    private function login(){
        //prendo dati raw dal json che ha mandato l'utente  
        $data = json_decode(file_get_contents("php://input"), true);
            
        //assegno i dati alle proprietà del model
        $this->model->setEmail($data['email']);
        $this->model->setPassword($data['password']);

        //richiesta di login user
        $risultato = $this->model->login();
        
        //se il risultato è un array vuoto, perché qualcosa è andato storto con la query / non ci sono righe / il login era sbagliato, allora errore 404
        if (empty($risultato)){
            http_response_code(401); // non autorizzato / credenziali sbagliate
            echo json_encode(['error' => 'Login fallito']);
            return;
        }

        //query andata a buon fine, mando il risultato
        http_response_code(200);
        echo json_encode(['data' => $risultato]);
    }

    //Permette di aggiornare email e nome dell'utente, se c'è anche la password nel json aggiorna anche quella
    //Richiede il token dell'utente
    public function update($id_utente){
        
        //leggo il body raw della richiesta PUT
        $data = json_decode(file_get_contents("php://input"), true);

        //assegno i dati alle proprietà del model
        $this->model->setId($id_utente ?? null);
        $this->model->setNome($data['nome'] ?? null);
        $this->model->setEmail($data['email'] ?? null);
        $this->model->setPassword($data['password'] ?? null);

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
    
    //Elimina l'account dal database
    //Richiede il token dell'utente
    public function delete($id_utente){
        
        //leggo il body raw della richiesta DEL
        $data = json_decode(file_get_contents("php://input"), true);

        //assegno i dati alle proprietà del model
        $this->model->setId($id_utente ?? null);

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