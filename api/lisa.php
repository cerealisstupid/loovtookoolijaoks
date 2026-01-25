<?php
header('Content-Type: application/json');

error_reporting(E_ALL);
ini_set('display_errors', 0);

try {
    if (!isset($_POST['name']) || empty(trim($_POST['name']))) {
        throw new Exception("Nimi on nõutud!");
    }
    
    $nimi = trim($_POST['name']);
    
    if (strlen($nimi) < 2) {
        throw new Exception("Nimi peab olema vähemalt 2 tähemärki pikk!");
    }
    
    if (strlen($nimi) > 50) {
        throw new Exception("Nimi on liiga pikk (max 50 tähemärki)!");
    }
    
    if (!preg_match("/^[a-zA-ZäöüõÄÖÜÕšžŠŽ\s'-]+$/u", $nimi)) {
        throw new Exception("Nimi sisaldab keelatud tähemärke!");
    }
    
    $andmeteFail = __DIR__ . '/data/pudru.json';
    
    if (!file_exists(__DIR__ . '/data')) {
        if (!mkdir(__DIR__ . '/data', 0755, true)) {
            throw new Exception("Andmekataloogi loomine ebaõnnestus!");
        }
    }
    
    $nimed = [];
    if (file_exists($andmeteFail)) {
        $jsonAndmed = file_get_contents($andmeteFail);
        $nimed = json_decode($jsonAndmed, true);
        
        if (!is_array($nimed)) {
            $nimed = [];
        }
    }
    
    foreach ($nimed as $olemasolevNimi) {
        if (mb_strtolower($olemasolevNimi) === mb_strtolower($nimi)) {
            throw new Exception("See nimi on juba registreeritud!");
        }
    }
    
    $nimed[] = $nimi;
    
    if (file_put_contents($andmeteFail, json_encode($nimed, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) === false) {
        throw new Exception("Andmete salvestamine ebaõnnestus!");
    }
    
    echo json_encode([
        'success' => true,
        'message' => "Registreerimine õnnestus! $nimi on nimekirja lisatud.",
        'count' => count($nimed)
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>
