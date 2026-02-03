<?php
// Authorization: classe che gestisce i token JWT
class Authorization {
    private $secret_key;            //chiave segreta per cifrare il token
    private $algorithm = 'sha256';  //algoritmo di cifratura
    
    public function __construct($secret_key) {
        //imposto la chiave segreta
        $this->secret_key = $secret_key;
    }
    
    // Genera token JWT per utente loggato
    // metto 48 ore al token per il testing
    public function generateToken($utente_id, $exp_hours = 48) {
        try{
            //creo il payload del token
            $payload = [
                'iss' => 'dispensAPI',      // creatore
                'iat' => time(),             // creato all'ora attuale
                'exp' => time() + ($exp_hours * 3600), // scadenza del token
                'sub' => $utente_id,         // subject, contenuto (utente_id)
                'jti' => bin2hex(random_bytes(16)) // JWT ID univoco
            ];
            
            //creo stringa per header, payload, signature tramite i metodi
            $header = $this->base64url_encode(json_encode(['typ' => 'JWT', 'alg' => $this->algorithm]));
            $payload_enc = $this->base64url_encode(json_encode($payload));
            $signature = $this->createSignature($header, $payload_enc); // crea la signature tramite la chiave segreta
            
            //ritorno il token intero concatenando le componenti
            return $header . '.' . $payload_enc . '.' . $signature;
        } catch (Error $e) {
            //random_bytes() fallito
            //echo json_encode("JWT: random_bytes fallito: " . $e->getMessage());
            return false;
        } catch (JsonException $e) {
            //JSON encoding fallito
            //echo json_encode("JWT: JSON error: " . $e->getMessage());
            return false;
        } catch (Exception $e) {
            //altri problemi
            //echo json_encode("JWT generico: " . $e->getMessage());
            return false;
        }
    }

    //verifica token e restituisce utente_id se valido
    public function validateToken($token) {
        //scompone il token in 3 parti tramite il .
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return false;
        }
        
        //prendo singolarmente la parti e creo la signature
        [$header_enc, $payload_enc, $signature_provided] = $parts;
        $signature_calculated = $this->createSignature($header_enc, $payload_enc);
        
        //verifica della signature, se fallisce -> false
        if (!hash_equals($signature_calculated, $signature_provided)) {
            return false;
        }
        
        //verifica della scadenza, se fallisce -> false
        $payload = json_decode($this->base64url_decode($payload_enc), true);
        if (!isset($payload['exp']) || $payload['exp'] < time()) {
            return false;
        }
        
        //restituisco id all'API per poterla usare nei metodi che la richiedono
        return $payload['sub']; // Restituisce utente_id
    }
    
    //Estrae token da header Authorization, così lo potrò usare per verificarlo
    public function getBearerToken() {
        $headers = getallheaders();
        if (isset($headers['Authorization'])) {
            $auth_header = $headers['Authorization'];
            if (preg_match('/Bearer\s(\S+)/', $auth_header, $matches)) {
                return $matches[1];
            }
        }
        return null;
    }
    
    //middleware per proteggere endpoint, in questo caso usato solo in Router così proteggo 
    //tutte le operazioni che hanno bisogno di un token per essere usate
    public function protectEndpoint() {
        try {
            $token = $this->getBearerToken(); //estrae il token dall'header
            if (!$token) { //se manca il token da errore
                $this->sendUnauthorized('Token mancante');
            }
            
            $utente_id = $this->validateToken($token); // verifica se il token e valido, ne restituisce l'utente_id salvato all'interno del token
            if (!$utente_id) { //se il token non è valido o scaduto manda errore
                $this->sendUnauthorized('Token non valido o scaduto');
            }
            
            return $utente_id;
        } catch (Error $e) {
            // random_bytes() fallito
            echo json_encode("JWT: random_bytes fallito: " . $e->getMessage());
            return false;
        } catch (JsonException $e) {
            // JSON encoding fallito
            echo json_encode("JWT: JSON error: " . $e->getMessage());
            return false;
        } catch (Exception $e) {
            // Altro
            echo json_encode("JWT generico: " . $e->getMessage());
            return false;
        }
    }
    
    //crea la signatura tramite hash_hmac
    private function createSignature($header, $payload) {
        $signature = hash_hmac($this->algorithm, "$header.$payload", $this->secret_key, true);
        return $this->base64url_encode($signature);
    }
    
    //codifica il token per usarlo negli header http o URL
    private function base64url_encode($data) {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
    
    //decodifica il token
    private function base64url_decode($data) {
        return base64_decode(str_pad(strtr($data, '-_', '+/'), strlen($data) % 4, '=', STR_PAD_RIGHT));
    }
    
    //Utente non autorizzato, manda il messaggio
    private function sendUnauthorized($message) {
        http_response_code(401);
        echo json_encode(['error' => $message]);
        exit;
    }
}
?>
