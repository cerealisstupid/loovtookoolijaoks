<?php
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 0);

try {
    $andmeteFail = __DIR__ . '/data/ajad.json';
    
    $ajad = [];
    if (file_exists($andmeteFail)) {
        $jsonAndmed = file_get_contents($andmeteFail);
        $ajad = json_decode($jsonAndmed, true);
        
        if (!is_array($ajad)) {
            $ajad = [];
        }
    }
    
    $tana = date('Y-m-d');
    $aktiivsedAjad = array_filter($ajad, function($aeg) use ($tana) {
        return $aeg['date'] >= $tana;
    });
    
    $aktiivsedAjad = array_values($aktiivsedAjad);
    
    usort($aktiivsedAjad, function($a, $b) {
        $kuupaevVordle = strcmp($a['date'], $b['date']);
        if ($kuupaevVordle !== 0) {
            return $kuupaevVordle;
        }
        
        $klassiVordle = strcmp($a['class'], $b['class']);
        if ($klassiVordle !== 0) {
            return $klassiVordle;
        }
        
        return strcmp($a['timeStart'], $b['timeStart']);
    });
    
    echo json_encode([
        'success' => true,
        'slots' => $aktiivsedAjad,
        'count' => count($aktiivsedAjad)
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
?>
