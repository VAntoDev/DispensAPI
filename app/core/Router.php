<?php
// Router serve a parsare lo URI nella richiesta e scegliere il controller che la andrà a gestire in base alle informazioni estrapolate
// Ricorda: il body della richiesta non è in $_SERVER, ci possono accedere anche le altre classi perché andrà su un flusso specifico (controlla appunti)
class Router {
    public static function dispatch($db) {

        $basePath = '/dispensAPI/public';  // cartella base del progetto

        //la richiesta è in $_SERVER['REQUEST_URI']
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

        // rimuovo la parte di base con dispensAPI/public che non serve
        if (strpos($uri, $basePath) === 0) {
            $uri = substr($uri, strlen($basePath));
        }

        $uri = trim($uri, '/'); // rimuove slash iniziali/finali

        $parts = explode('/', $uri); //suddivido la stringa in più parti in un array (es: alimenti/select/2 diventano 3 celle dell'array)
        
        //trovo il nome del controller, se esiste allora è nome + "controller", senno è null
        //uso ucfirst per mettere maiscuolo il nome, così corrisponde al nome della classe in Controllers
        $controllerName = $parts[0] ? ucfirst($parts[0]) . 'Controller' : null; // metto Controller dopo il nome così trovo i file Controller quando li cerco

        // Se manca il nome del controller allora da errore
        if (!$controllerName) {
            http_response_code(404);
            echo json_encode(['error' => 'Controller o metodo mancante da URI usato']);
            return;
        }

        //Salvo il parametro da dare al controller,se  non c'è un parametro allora è null
        //i parametri li uso per distinguere: login, register, o id
        $param = $parts[1] ?? null;


        // costruisco il path del file del controller
        $controllerFile = __DIR__ . '/../controllers/' . $controllerName . '.php';
        
        //controllo se il file esiste, se non esiste non si va avanti
        if (!file_exists($controllerFile)) {
            http_response_code(404);
            echo json_encode(['error' => 'Controller non trovato']);
            return;
        }
        
        // Include sul nome del file per poterlo usare
        //forse non serve più?
        require_once $controllerFile;
        
        $controller = new $controllerName($db);

        // Importante! Salvo il Metodo usato nella Richiesta, questo mi dirà quale funzione del Controller usare
        $httpMethod = $_SERVER['REQUEST_METHOD'];
        echo json_encode(['message' => 'sono qua']);

        //DEVI MODIFICARE QUESTO PER FARE IN MODO CHE FUNZIONI CON users/42/alimenti (da controlleralimenti) che per ora non fai per sbrigarti.
        //Ora funziona con: http://localhost/dispensAPI/public/alimenti/1 però non va bene.
        switch ($httpMethod) {
            case 'GET':
                $controller->select($param);
                break;

            case 'POST':
                $controller->create($param);
                break;

            case 'PUT':
                $controller->update($param);
                break;

            case 'DELETE':
                $controller->delete($param);
                break;

            default:
                http_response_code(405);
                echo json_encode(['error' => 'Metodo HTTP non supportato']);
        }
    }
}

?>