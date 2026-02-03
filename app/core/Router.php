<?php
// Router serve a parsare lo URI nella richiesta e scegliere il controller che la andrà a gestire in base alle informazioni estrapolate
// Inoltre, il router usa Authorization così che un utente possa usare una determinata operazione soltanto se ha un token JWT valido e non scaduto
class Router {
    public static function dispatch($db) {

        // creo token JWT per l'utente
        $auth = new Authorization($_ENV['JWT_SECRET_KEY']);

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
        //i parametri li uso per distinguere: login, register, o id dell'utente
        $param = $parts[1] ?? null;

        // costruisco il path del file del controller
        $controllerFile = __DIR__ . '/../controllers/' . $controllerName . '.php';
        
        //controllo se il file esiste, se non esiste non si va avanti
        if (!file_exists($controllerFile)) {
            http_response_code(404);
            echo json_encode(['error' => 'Richiesta non valida: Controller non trovato']);
            return;
        }
        
        //creo l'istanza del controller per poterne chiamare le funzioni
        $controller = new $controllerName($db);

        // Importante! Salvo il Metodo usato nella Richiesta, questo mi dirà quale funzione del Controller usare
        $httpMethod = $_SERVER['REQUEST_METHOD'];

        //creo due linee che corrispondano alle operazioni del controller Utenti: login e register
        $endpoint = $controllerName . '/' . $param;  // esempio "UtentiController/register"
        $login_register = ($endpoint === 'UtentiController/register' || $endpoint === 'UtentiController/login');

        //Protegge TUTTI gli endpoint tranne /login e /register
        //se la richiesta non è di login o di register
        if(!$login_register){
            // allora verifica che il token mandato dall'utente nell'header sia giusto
            $utente_id = $auth->protectEndpoint();
            // se il token è corretto allora assegni l'id dell'utente al parametro da passare ai vari metodi
            if($utente_id != false){
                $param = $utente_id; //assegna utente id, così che gli altri metodi possano usare utente_id mentre login/register usano come parameter proprio 'login' o 'register' che serve a capire quale delle due azioni l'utente vuole compiere
            } else {
                // se c'è stato un errore nel codice di verifica del token allora esci
                http_response_code(404);
                return;
            }
        }

        //$param corrispone sempre all'utente_id, tranne nel caso del login/register in cui indica il tipo di operazione
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
                //se il tipo di metodo HTTP non è supportato da errore 405 Method Not Allowed
                http_response_code(405);
        }
    }
}

?>