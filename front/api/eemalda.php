<?php
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 0);

try {
    if (!isset($_POST['name']) || empty(trim($_POST['name']))) {
        throw new Exception("Nimi on nõutud!");
    }
    
    $nimi = trim($_POST['name']);
    $andmeteFail = __DIR__ . '/data/pudru.json';
    
    if (!file_exists($andmeteFail)) {
        throw new Exception("Nimekirja pole veel loodud!");
    }
    
    $jsonAndmed = file_get_contents($andmeteFail);
    $nimed = json_decode($jsonAndmed, true);
    
    if (!is_array($nimed)) {
        throw new Exception("Vigane andmefail!");
    }
    
    $leitud = false;
    $uuedNimed = [];
    
    foreach ($nimed as $olemasolevNimi) {
        if (mb_strtolower($olemasolevNimi) === mb_strtolower($nimi)) {
            $leitud = true;
        } else {
            $uuedNimed[] = $olemasolevNimi;
        }
    }
    
    if (!$leitud) {
        throw new Exception("Seda nime ei leitud nimekirjast!");
    }
    
    if (file_put_contents($andmeteFail, json_encode($uuedNimed, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) === false) {
        throw new Exception("Andmete salvestamine ebaõnnestus!");
    }
    
    echo json_encode([
        'success' => true,
        'message' => "$nimi on nimekirjast eemaldatud.",
        'count' => count($uuedNimed)
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
?>
