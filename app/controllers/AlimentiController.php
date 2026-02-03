<?php
class AlimentiController {

    // Il model che usiamo è della classe Alimenti, quindi creo una variabile privata così che solo questa classe la possa usare
    private Alimenti $model;

    public function __construct(PDO $db){
        //creo una istanza della classe alimenti, passandole la connessione al db (come richiesto dalla classe stessa)
        $this->model = new Alimenti($db); // $db viene da config.php
    }

    public function select($id_utente = null) { 
        //uso il fatto che c'è l'id o no per distinguere il tipo di azione che vuole fare l'utente
        //se vuole avere tutti gli alimenti non mette nessun id (null), se vuole sapere i suoi alimenti mette il proprio id ($id)
        //richiesta di tutti gli alimenti (da rimuovere successivamente perché non serve)
        if($id_utente === null){ // senza id
            //ho deciso di non usare questo metodo read() per questa applicazione. La lascio in caso cambiassi idea
            //richiesta tutti gli alimenti nella tabella
            
            $alimenti = $this->model->read();

            if (empty($alimenti)) {
                http_response_code(404);
                //echo json_encode(['message' => 'Nessun alimento trovato']);
                return;
            }

            http_response_code(200);
            echo json_encode(['data' => $alimenti]);

        } else { //con id
            //richiesta solo alimenti dell'user

            $alimenti = $this->model->readByUserId($id_utente);

            if (empty($alimenti)) {
                http_response_code(404);
                //echo json_encode(['message' => 'Nessun alimento trovato']);
                return;
            }

            http_response_code(200);
            echo json_encode(['data' => $alimenti]);
        }
}

    public function create($id_utente){
            // Questa funzione prende $param per uniformarla alle altre e quelle degli altri controller. Ma non ha bisogno di usarlo.

            //Prendo i dati dalla POST request
            //$data = json_decode(file_get_contents("php://input"));

            //ATTENZIONE!!
            //I dati vanno presi da _POST in quanto c'è un immagine, quindi la richiesta è di tipo multipart/form-data
    
            //Assegno i dati alle variaibli
            //$this->model->nome = $data->nome;
            //$this->model->categoria_id = $data->categoria_id;

            //$this->model->percorso_immagine, questa cosa la fai dopo aver salvato l'immagine
            //salvo immagine sul server, così da avere il percorso da salvare sul db
            //echo json_encode(["Percorso" => $_FILES['immagine']]);
            /*
            try {
                if($_FILES['immagine'] != null){
                    $percorso = $this->model->salvaImmagine($_FILES['immagine']);
                    $this->model->setPercorsoImmagine($percorso);
                } else {
                    echo json_encode(["Percorso" => "Immagine null, la carico come NULL"]);
                }
            } catch (RuntimeException $e){
                http_response_code(400);
                //400 in questo caso è "errore del client nel mandare l'immagine"
                //echo json_encode(['error' => $e->getMessage()]);
                return;
            }
            */
            $this->model->setPercorsoImmagine($_FILES['immagine'] ?? null);
            $this->model->setNome($_POST['nome'] ?? null);
            $this->model->setId($_POST['id'] ?? null);
            $this->model->setCategoriaId($_POST['categoria_id'] ?? null);
            $this->model->setQuantita($_POST['quantita'] ?? null);
            $this->model->setUnitaId($_POST['unita_id'] ?? null);
            $this->model->setUtenteId($id_utente ?? null);

            //richiesta creazione alimento inviato dall'user
            $risultato = $this->model->create();

            if (empty($risultato)) {
                http_response_code(404);
                //echo json_encode(['message' => 'Alimento non aggiunto a causa di un errore']);
                return;
            }

            http_response_code(200);
            echo json_encode(['data' => $risultato]);
    }

    public function update($id_utente){
        //leggo il body raw della richiesta PUT
        $data = json_decode(file_get_contents("php://input"), true);

        $this->model->setNome($data['nome'] ?? null);
        $this->model->setId($data['id'] ?? null);
        $this->model->setCategoriaId($data['categoria_id'] ?? null);
        $this->model->setQuantita($data['quantita'] ?? null);
        $this->model->setUnitaId($data['unita_id'] ?? null);
        $this->model->setUtenteId($id_utente ?? null);

        //richiesta creazione alimento inviato dall'user
        $risultato = $this->model->update();

        if(empty($risultato)){
            http_response_code(404);
            //echo json_encode(['message' => 'Alimento non aggiornato a causa di un errore / oppure la modifica è uguale al precedente alimento o utente non corretto o non esiste']);
            return;
        }
            
        http_response_code(200);
        echo json_encode(['data' => $risultato]);
        //echo json_encode(['message' => 'Alimento aggiornato']);    
    }

    public function delete($id_utente){
        //leggo il body raw della richiesta PUT
        $data = json_decode(file_get_contents("php://input"), true);

        $this->model->setId($data['id'] ?? null);
        $this->model->setUtenteId($id_utente ?? null);

        //richiesta creazione alimento inviato dall'user
        $risultato = $this->model->delete();

        if ($risultato === false) {
            http_response_code(404);
            //echo json_encode(['message' => 'Alimento non eliminato a causa di un errore / non esiste o non sei utente non corretto']);
            return;
        }

        http_response_code(204);
        //echo json_encode(['message' => 'Alimento eliminato']);    
    }
}
?>