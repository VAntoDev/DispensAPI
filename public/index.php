<?php
// Index.php : Riceve la richiesta HTTP e genera un'istanza di Router che la andrà a gestire

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Authorization, Content-Type');

//carico le classi di phpdotenv
require_once __DIR__ . '/../vendor/autoload.php';

// Richiedo i file, uso require_once perché da errore fatale se un file manca.
//Core
require_once '../app/core/Router.php';

//Controller
require_once '../app/controllers/AlimentiController.php'; //controlla se lo stai facendo due volte nel router

//Models
require_once '../app/models/Alimenti.php'; 

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