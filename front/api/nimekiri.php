<?php
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 0);

try {
    $andmeteFail = __DIR__ . '/data/pudru.json';
    $nimed = [];
    
    if (file_exists($andmeteFail)) {
        $jsonAndmed = file_get_contents($andmeteFail);
        $nimed = json_decode($jsonAndmed, true);
        
        if (!is_array($nimed)) {
            $nimed = [];
        }
    }
    
    echo json_encode([
        'success' => true,
        'names' => $nimed,
        'count' => count($nimed)
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Viga andmete laadimisel: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
?>
