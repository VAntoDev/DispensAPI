<?php
// Index.php : È l'endpoint, riceve la richiesta HTTP e genera un'istanza di Router che la andrà a gestire

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Authorization, Content-Type');

// Tutte le risposte, tranne due GET che sono pubblici, non devono essere mantenute dall'utente perché contengono dati privati o che cambiano di continuo
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

//gestione pre-flight cors, serve perché il browser può bloccare richieste per motivi di sicurezza se il server non risponde correttamente alla pre-flight 
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

//carico le classi di phpdotenv
require_once __DIR__ . '/../vendor/autoload.php';

// Richiedo i file, uso require_once perché da errore fatale se un file manca.
//Core, contiene Router per gestire le richieste e Authorization per gestire i token JWT
require_once __DIR__ . '/../app/core/Router.php';
require_once __DIR__ . '/../app/core/Authorization.php';

//Controllers, gestiscono le richieste HTTP di un determinato model: il loro obbiettivo è impostare i dati e mandare il risultato delle richieste
require_once __DIR__ . '/../app/controllers/AlimentiController.php';
require_once __DIR__ . '/../app/controllers/CategorieController.php';
require_once __DIR__ . '/../app/controllers/DispenseController.php';
require_once __DIR__ . '/../app/controllers/ScadenzeController.php';
require_once __DIR__ . '/../app/controllers/UnitamisuraController.php';
require_once __DIR__ . '/../app/controllers/UtentiController.php';

//Models, gestiscono le interazioni con le tabelle del database
require_once __DIR__ . '/../app/models/Alimenti.php';
require_once __DIR__ . '/../app/models/Categorie.php';
require_once __DIR__ . '/../app/models/Dispense.php';
require_once __DIR__ . '/../app/models/Scadenze.php';
require_once __DIR__ . '/../app/models/Unita_Misura.php';
require_once __DIR__ . '/../app/models/Utenti.php';

//Includes, stabilisce e gestisce la connessione con il database
require_once '../includes/Database.php';
if(!file_exists('../includes/Database.php')){
    echo "la config non è stata trovata (index.php)\n";
}

//carica le variabili d'ambiente dal .env, così che non siano salvate in chiaro nel codice
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

//genero un'istanza di Database che possano usare le altre classi
$db = (new Database())->connect();

//uso il metodo statico di Router per gestire la richiesta
Router::dispatch($db);
?>  