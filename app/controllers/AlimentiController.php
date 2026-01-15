<?php
class AlimentiController {

    // Il model che usiamo è della classe Alimenti, quindi creo una variabile privata così che solo questa classe la possa usare
    private Alimenti $model;

    public function __construct(PDO $db){
        //creo una istanza della classe alimenti, passandole la connessione al db (come richiesto dalla classe stessa)
        $this->model = new Alimenti($db); // $db viene da config.php
    }

    public function select($id = null) {
        //richiesta di tutti gli alimenti (da rimuovere successivamente perché non serve)
        if($id === null){
            
            $alimenti = $this->model->read();

            if (empty($alimenti)) {
                http_response_code(404);
                echo json_encode(['message' => 'Nessun alimento trovato']);
                return;
            }

            echo json_encode(['data' => $alimenti]);
        } else {
            //richiesta solo alimenti dell'user
            $alimenti = $this->model->readByUserId($id);

            if (empty($alimenti)) {
                http_response_code(404);
                echo json_encode(['message' => 'Nessun alimento trovato']);
                return;
            }
            
            echo json_encode(['data' => $alimenti]);
        }
}

    public function create(){
            //Prendo i dati dalla POST request
            //$data = json_decode(file_get_contents("php://input"));

            //ATTENZIONE!!
            //I dati vanno presi da _POST in quanto c'è un immagine, quindi la richiesta è di tipo multipart/form-data

            /*
            L'utente deve settare:
            public $nome;
            public $categoria_id;
            public $immagine;
            public $quantita;
            public $unita_id;
            public $utente_id;
            */

            //Assegno i dati alle variaibli
            //$this->model->nome = $data->nome;
            //$this->model->categoria_id = $data->categoria_id;

            //$this->model->percorso_immagine, questa cosa la fai dopo aver salvato l'immagine
            //salvo immagine sul server, così da avere il percorso da salvare sul db
            try {
                $this->model->percorso_immagine = $this->saveImage($_FILES['immagine']);
            } catch (RuntimeException $e) {
                http_response_code(400);
                echo json_encode(['error' => $e->getMessage()]);
                return;
            }    

            //$this->model->quantita = $data->quantita;
            //$this->model->unita_id = $data->unita_id;
            //$this->model->utente_id = $data->utente_id;

            $this->model->nome = $_POST['nome'] ?? null;
            $this->model->categoria_id = $_POST['categoria_id'] ?? null;
            $this->model->quantita = $_POST['quantita'] ?? null;
            $this->model->unita_id = $_POST['unita_id'] ?? null;
            $this->model->utente_id = $_POST['utente_id'] ?? null;

            //richiesta creazione alimento inviato dall'user
            $risultato = $this->model->create();

            if ($risultato === false) {
                http_response_code(404);
                echo json_encode(['message' => 'Alimento non aggiunto a causa di un errore']);
                return;
            }
            
            echo json_encode(['message' => 'Alimento aggiunto']);
    }

    public function update($id){
        echo "Shh non sono una vera update";
    }

    public function delete($id){
        echo "Shh non sono una vera delete";
    }
}
?>