<?php

use JsonRPC\Client;

require_once __DIR__.'/../../app/common.php';

abstract class BaseApiTest extends PHPUnit_Framework_TestCase
{
    protected $adminUser = array();

    public static function setUpBeforeClass()
    {
        if (DB_DRIVER === 'postgres') {
            $pdo = new PDO('pgsql:host='.DB_HOSTNAME, DB_USERNAME, DB_PASSWORD);
            $pdo->exec("SELECT pg_terminate_backend(pg_stat_activity.pid) FROM pg_stat_activity WHERE pg_stat_activity.datname = '".DB_NAME."' AND pid <> pg_backend_pid()");
            $pdo->exec('DROP DATABASE '.DB_NAME);
            $pdo->exec('CREATE DATABASE '.DB_NAME.' WITH OWNER '.DB_USERNAME);
            $pdo = null;
        } else if (DB_DRIVER === 'mysql') {
            $pdo = new PDO('mysql:host='.DB_HOSTNAME, DB_USERNAME, DB_PASSWORD);
            $stmt = $pdo->query("SELECT information_schema.processlist.id FROM information_schema.processlist WHERE information_schema.processlist.DB = '".DB_NAME."' AND id <> connection_id()");
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $pdo->exec('KILL '.$row['id']);
            }
            $pdo->exec('DROP DATABASE '.DB_NAME);
            $pdo->exec('CREATE DATABASE '.DB_NAME);
            $pdo = null;
        } else if (file_exists(DB_FILENAME)) {
            unlink(DB_FILENAME);
        }
    }

    public function setUp()
    {
        $db = Miniflux\Database\get_connection();
        $this->adminUser = $db->table(Miniflux\Model\User\TABLE)->eq('username', 'admin')->findOne();
        
        // Debug: Log server connectivity info
        if (defined('DEBUG_MODE') && DEBUG_MODE) {
            echo "\n[DEBUG] Connecting to API: " . API_URL . "\n";
            echo "[DEBUG] Admin user: " . $this->adminUser['username'] . "\n";
            
            // Test basic connectivity
            $ch = curl_init(API_URL);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 5);
            curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
            curl_setopt($ch, CURLOPT_USERPWD, $this->adminUser['username'] . ':' . $this->adminUser['api_token']);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
                'jsonrpc' => '2.0',
                'method' => 'getVersion',
                'params' => [],
                'id' => 1
            ]));
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            
            $response = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curl_error = curl_error($ch);
            curl_close($ch);
            
            echo "[DEBUG] HTTP Status: " . $http_code . "\n";
            echo "[DEBUG] Response: " . $response . "\n";
            if ($curl_error) {
                echo "[DEBUG] cURL Error: " . $curl_error . "\n";
            }
        }
    }

    protected function getApiClient(array $user = array())
    {
        if (empty($user)) {
            $user = $this->adminUser;
        }

        try {
            $apiUserClient = new Client(API_URL);
            $apiUserClient->authentication($user['username'], $user['api_token']);
            return $apiUserClient;
        } catch (Exception $e) {
            echo "\n[ERROR] Failed to create API client for " . API_URL . "\n";
            echo "[ERROR] Exception: " . $e->getMessage() . "\n";
            echo "[ERROR] Code: " . $e->getCode() . "\n";
            echo "[ERROR] Trace: " . $e->getTraceAsString() . "\n";
            throw $e;
        }
    }
}