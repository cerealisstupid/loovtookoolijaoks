<?php
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 0);

try {
    if (!isset($_GET['teacher']) || empty(trim($_GET['teacher']))) {
        throw new Exception("Õpetaja nimi on nõutud!");
    }
    
    $opetaja = trim($_GET['teacher']);
    $andmeteFail = __DIR__ . '/data/ajad.json';
    
    $ajad = [];
    if (file_exists($andmeteFail)) {
        $jsonAndmed = file_get_contents($andmeteFail);
        $ajad = json_decode($jsonAndmed, true);
        
        if (!is_array($ajad)) {
            $ajad = [];
        }
    }
    
    $opetajaAjad = array_filter($ajad, function($aeg) use ($opetaja) {
        return $aeg['teacher'] === $opetaja;
    });
    
    $opetajaAjad = array_values($opetajaAjad);
    
    usort($opetajaAjad, function($a, $b) {
        $kuupaevVordle = strcmp($a['date'], $b['date']);
        if ($kuupaevVordle !== 0) {
            return $kuupaevVordle;
        }
        return strcmp($a['timeStart'], $b['timeStart']);
    });
    
    echo json_encode([
        'success' => true,
        'slots' => $opetajaAjad,
        'count' => count($opetajaAjad)
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
?>
