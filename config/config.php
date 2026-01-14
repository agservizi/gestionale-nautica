<?php
/**
 * NautikaPro
 * File di configurazione - Connessione Database
 * Anno: 2025+
 */

// Carica variabili da .env se presente
$envPath = __DIR__ . '/../.env';
if (file_exists($envPath)) {
    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        $parts = explode('=', $line, 2);
        if (count($parts) === 2) {
            $key = trim($parts[0]);
            $value = trim($parts[1]);
            $value = trim($value, " \t\n\r\0\x0B\"");
            if (!getenv($key)) {
                putenv("$key=$value");
                $_ENV[$key] = $value;
            }
        }
    }
}

// Configurazione Database
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');
define('DB_NAME', getenv('DB_NAME') ?: 'gestionale_nautica');
define('DB_CHARSET', getenv('DB_CHARSET') ?: 'utf8mb4');

// Configurazione Applicazione
define('APP_NAME', 'NautikaPro');
define('APP_VERSION', '1.0.0');
define('APP_YEAR_START', 2025);

// Timezone
date_default_timezone_set('Europe/Rome');

// Classe Database con singleton pattern
class Database {
    private static $instance = null;
    private $connection;
    
    private function __construct() {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            $this->connection = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch(PDOException $e) {
            die("Errore connessione database: " . $e->getMessage());
        }
    }
    
    public static function getInstance() {
        if(self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        return $this->connection;
    }
    
    // Previene clonazione
    private function __clone() {}
    
    // Previene unserialize
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
}

// Funzione helper per ottenere la connessione
function getDB() {
    return Database::getInstance()->getConnection();
}
