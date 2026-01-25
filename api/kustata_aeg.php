<?php
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 0);

try {
    $jsonSisend = file_get_contents('php://input');
    $andmed = json_decode($jsonSisend, true);
    
    if (!$andmed) {
        throw new Exception("Vigased andmed!");
    }
    
    if (!isset($andmed['slotId']) || !isset($andmed['teacher'])) {
        throw new Exception("Vajalikud andmed puuduvad!");
    }
    
    $aegId = intval($andmed['slotId']);
    $opetaja = trim($andmed['teacher']);
    
    $ajadFail = __DIR__ . '/data/ajad.json';
    $konsultatsioonidFail = __DIR__ . '/data/konsultatsioonid.json';
    
    if (!file_exists($ajadFail)) {
        throw new Exception("Ühtegi konsultatsiooni pole veel loodud!");
    }
    
    $jsonAndmed = file_get_contents($ajadFail);
    $ajad = json_decode($jsonAndmed, true);
    
    if (!is_array($ajad)) {
        throw new Exception("Vigane andmefail!");
    }
    
    $leitud = false;
    $kustutatud = null;
    $uuedAjad = [];
    
    foreach ($ajad as $aeg) {
        if ($aeg['id'] === $aegId) {
            if ($aeg['teacher'] !== $opetaja) {
                throw new Exception("Sul pole õigust seda konsultatsiooni kustutada!");
            }
            
            if (isset($aeg['registrations']) && count($aeg['registrations']) > 0) {
                throw new Exception("Sellel konsultatsioonil on registreeringuid! Palun eemalda need enne kustutamist.");
            }
            
            $leitud = true;
            $kustutatud = $aeg;
        } else {
            $uuedAjad[] = $aeg;
        }
    }
    
    if (!$leitud) {
        throw new Exception("Konsultatsiooni ei leitud!");
    }
    
    if (file_exists($konsultatsioonidFail)) {
        $konsultAndmed = file_get_contents($konsultatsioonidFail);
        $konsultatsioonid = json_decode($konsultAndmed, true);
        
        if (is_array($konsultatsioonid)) {
            $konsultatsioonid = array_filter($konsultatsioonid, function($reg) use ($aegId) {
                return $reg['slotId'] !== $aegId;
            });
            
            $konsultatsioonid = array_values($konsultatsioonid);
            file_put_contents($konsultatsioonidFail, json_encode($konsultatsioonid, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        }
    }
    
    if (file_put_contents($ajadFail, json_encode($uuedAjad, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) === false) {
        throw new Exception("Andmete salvestamine ebaõnnestus!");
    }
    
    echo json_encode([
        'success' => true,
        'message' => "Konsultatsioon edukalt kustutatud!",
        'deleted' => $kustutatud
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
?>
