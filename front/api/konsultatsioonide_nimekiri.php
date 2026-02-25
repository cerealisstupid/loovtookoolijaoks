<?php
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 0);

try {
    $andmeteFail = __DIR__ . '/data/konsultatsioonid.json';
    
    $registreeringud = [];
    
    if (file_exists($andmeteFail)) {
        $jsonAndmed = file_get_contents($andmeteFail);
        $registreeringud = json_decode($jsonAndmed, true);
        
        if (!is_array($registreeringud)) {
            $registreeringud = [];
        }
    }
    
    echo json_encode([
        'success' => true,
        'registrations' => $registreeringud,
        'count' => count($registreeringud)
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Viga andmete laadimisel: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
?>
