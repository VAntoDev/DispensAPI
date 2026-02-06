<?php
class AlimentiController {

    // Il model che usiamo è della classe Alimenti, quindi creo una variabile privata così che solo questa classe la possa usare
    private Alimenti $model;

    public function __construct(PDO $db){
        //creo una istanza della classe Alimenti, passandole la connessione al db (come richiesto dalla classe stessa)
        $this->model = new Alimenti($db); // $db viene da config.php
    }

    //Visualizza e manda gli alimenti di un utente tramite il suo id
    public function select($id_utente = null) { 
        //uso il fatto che c'è l'id o no per distinguere il tipo di azione che vuole fare l'utente
        //se vuole avere tutti gli alimenti non mette nessun id (null), se vuole sapere i suoi alimenti mette il proprio id ($id)
        if($id_utente === null){ // senza id
            //ho deciso di non usare questo metodo read() per questa applicazione. La lascio in caso cambiassi idea, c'è un return nel model che fa fallire l'operazione

            //richiesta tutti gli alimenti nella tabella
            $alimenti = $this->model->read();

            //se il risultato è un array vuoto, perché qualcosa è andato storto con la query / non ci sono righe, allora errore 404
            if (empty($alimenti)) {
                http_response_code(404);
                return;
            }

            //query andata a buon fine, mando il risultato
            http_response_code(200);
            echo json_encode(['data' => $alimenti]);

        } else { //con id
            //richiesta solo alimenti dell'user
            $alimenti = $this->model->readByUserId($id_utente);

            //se il risultato è un array vuoto, perché qualcosa è andato storto con la query / non ci sono righe, allora errore 404
            if (empty($alimenti)) {
                http_response_code(404);
                echo json_encode(['error' => 'GET Alimenti fallita']);
                return;
            }

            //query andata a buon fine, mando il risultato
            http_response_code(200);
            echo json_encode(['data' => $alimenti]);
        }
    }
    //Crea alimento, tramite percorso_immagine, nome, categoria, quantita, unita
    public function create($id_utente){
            //Prendo i dati dalla POST request
            //I dati vanno presi da _POST in quanto c'è un immagine, quindi la richiesta è di tipo multipart/form-data
    
            //Assegno i dati alle proprietà del model
            $this->model->setPercorsoImmagine($_FILES['immagine'] ?? null);
            $this->model->setNome($_POST['nome'] ?? null);
            $this->model->setCategoriaId($_POST['categoria_id'] ?? null);
            $this->model->setQuantita($_POST['quantita'] ?? null);
            $this->model->setUnitaId($_POST['unita_id'] ?? null);
            $this->model->setUtenteId($id_utente ?? null);

            //richiesta creazione alimento inviato dall'user
            $risultato = $this->model->create();
            
            //se il risultato è un array vuoto, perché qualcosa è andato storto con la query / non ci sono righe, allora errore 404
            if (empty($risultato)) {
                http_response_code(404);
                echo json_encode(['error' => "POST Alimenti fallita"]);
                return;
            }

            //query andata a buon fine, mando il risultato
            http_response_code(200);
            echo json_encode(['data' => $risultato]);
    }

    //Update dell'alimento, ne modifica il nome, categoria, quantità, unità tramite id e utente_id
    public function update($id_utente){
        //leggo il body raw della richiesta PUT
        $data = json_decode(file_get_contents("php://input"), true);
        
        //assegno i dati alle proprietà del model
        $this->model->setNome($data['nome'] ?? null);
        $this->model->setId($data['id'] ?? null);
        $this->model->setCategoriaId($data['categoria_id'] ?? null);
        $this->model->setQuantita($data['quantita'] ?? null);
        $this->model->setUnitaId($data['unita_id'] ?? null);
        $this->model->setUtenteId($id_utente ?? null);

        //richiesta creazione alimento inviato dall'user
        $risultato = $this->model->update();
        
        //se il risultato è un array vuoto, perché qualcosa è andato storto con la query / non ci sono righe, allora errore 404
        if(empty($risultato)){
            http_response_code(404);
            echo json_encode(['error' => "UPDATE Alimenti fallita"]);
            return;
        }

        //query andata a buon fine, mando il risultato
        http_response_code(200);
        echo json_encode(['data' => $risultato]);
    }

    // elimina l'alimento tramite id dell'alimento e utente_id
    public function delete($id_utente){
        //leggo il body raw della richiesta DEL
        $data = json_decode(file_get_contents("php://input"), true);

        //assegno i dati alle proprietà del model
        $this->model->setId($data['id'] ?? null);
        $this->model->setUtenteId($id_utente ?? null);

        //richiesta creazione alimento inviato dall'user
        $risultato = $this->model->delete();

        //se il risultato è un array vuoto, perché qualcosa è andato storto con la query / non ci sono righe, allora errore 404
        if ($risultato === false) {
            http_response_code(404);
            echo json_encode(['error' => "DELETE Alimenti fallita"]);
            return;
        }

        //query andata a buon fine, codice 204 per "No Content"
        http_response_code(204);
        echo json_encode(['data' => "Alimenti Eliminato"]);
    }
}
?>