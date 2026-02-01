<?php
// Index.php : Riceve la richiesta HTTP e genera un'istanza di Router che la andrà a gestire

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Authorization, Content-Type');
// Tutte le richieste, tranne due GET che sono pubblici, non devono essere mantenute dall'utente perché contengono dati privati o che cambiano di continuo
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

//carico le classi di phpdotenv
require_once __DIR__ . '/../vendor/autoload.php';

// Richiedo i file, uso require_once perché da errore fatale se un file manca.
//Core
require_once __DIR__ . '/../app/core/Router.php';
require_once __DIR__ . '/../app/core/Authorization.php';

//Controllers
require_once __DIR__ . '/../app/controllers/AlimentiController.php';
require_once __DIR__ . '/../app/controllers/UtentiController.php';

//Models
require_once __DIR__ . '/../app/models/Alimenti.php';
require_once __DIR__ . '/../app/models/Categorie.php';
require_once __DIR__ . '/../app/models/Dispense.php';
require_once __DIR__ . '/../app/models/Scadenze.php';
require_once __DIR__ . '/../app/models/Unita_Misura.php';
require_once __DIR__ . '/../app/models/Utenti.php';

//TEST
if(!file_exists('../app/models/Alimenti.php')){
    echo "la classe Alimenti è stata trovata (index.php)\n";
}

//Includes
require_once '../includes/Database.php';
if(!file_exists('../includes/Database.php')){
    echo "la config non è stata trovata (index.php)\n";
}

//carica le variabili d'ambiente dal .env
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

$db = (new Database())->connect();

Router::dispatch($db);
?>  