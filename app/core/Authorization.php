<?php
// Authorization: classe che gestisce i token JWT
class Authorization {
    private $secret_key;
    private $algorithm = 'sha256';
    //    $_ENV['JWT_SECRET_KEY']
    public function __construct($secret_key) {
        $this->secret_key = $secret_key;
    }
    
    // Genera token JWT per utente loggato
    public function generateToken($utente_id, $exp_hours = 1) {
        try{
            $payload = [
                'iss' => 'dispensAPI',      // issuer
                'iat' => time(),             // issued at
                'exp' => time() + ($exp_hours * 3600), // expiration
                'sub' => $utente_id,         // subject (utente_id)
                'jti' => bin2hex(random_bytes(16)) // JWT ID univoco
            ];
           
            $header = $this->base64url_encode(json_encode(['typ' => 'JWT', 'alg' => $this->algorithm]));
            echo json_encode("Sono nella funzione");
            $payload_enc = $this->base64url_encode(json_encode($payload));
            $signature = $this->createSignature($header, $payload_enc); // crea la signature tramite la chiave segreta
            
            return $header . '.' . $payload_enc . '.' . $signature;
        
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

    //verifica token e restituisce utente_id se valido
    public function validateToken($token) {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return false;
        }
        
        [$header_enc, $payload_enc, $signature_provided] = $parts;
        $signature_calculated = $this->createSignature($header_enc, $payload_enc);
        
        // Verifica signature
        if (!hash_equals($signature_calculated, $signature_provided)) {
            return false;
        }
        
        // Verifica scadenza
        $payload = json_decode($this->base64url_decode($payload_enc), true);
        if (!isset($payload['exp']) || $payload['exp'] < time()) {
            return false;
        }
        
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
    
    // Middleware per proteggere endpoint
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
    
    // NON SERVE?
    // Verifica login utente (da chiamare nel /login)
    /*
    public function verifyUser($email, $password, $pdo) {
        $stmt = $pdo->prepare("SELECT id, password FROM utenti WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user && password_verify($password, $user['password'])) {
            return $user['id'];
        }
        return false;
    }
    */
    private function createSignature($header, $payload) {
        $signature = hash_hmac($this->algorithm, "$header.$payload", $this->secret_key, true);
        return $this->base64url_encode($signature);
    }
    
    private function base64url_encode($data) {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
    
    private function base64url_decode($data) {
        return base64_decode(str_pad(strtr($data, '-_', '+/'), strlen($data) % 4, '=', STR_PAD_RIGHT));
    }
    
    private function sendUnauthorized($message) {
        http_response_code(401);
        echo json_encode(['error' => $message]);
        exit;
    }
}
?>
