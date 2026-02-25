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
    
    if (!isset($andmed['studentName'])) {
        throw new Exception("Õpilase nimi on nõutud!");
    }
    
    $opilaseNimi = trim($andmed['studentName']);
    
    $ajadFail = __DIR__ . '/data/ajad.json';
    $konsultatsioonidFail = __DIR__ . '/data/konsultatsioonid.json';
    
    if (!file_exists($konsultatsioonidFail)) {
        throw new Exception("Ühtegi registreeringut pole veel tehtud!");
    }
    
    $jsonAndmed = file_get_contents($konsultatsioonidFail);
    $konsultatsioonid = json_decode($jsonAndmed, true);
    
    if (!is_array($konsultatsioonid)) {
        throw new Exception("Vigane andmefail!");
    }
    
    $leitud = false;
    $uuedKonsultatsioonid = [];
    $eemaldatud = null;
    
    foreach ($konsultatsioonid as $reg) {
        if (mb_strtolower($reg['studentName']) === mb_strtolower($opilaseNimi)) {
            $leitud = true;
            $eemaldatud = $reg;
        } else {
            $uuedKonsultatsioonid[] = $reg;
        }
    }
    
    if (!$leitud) {
        throw new Exception("Selle nimega registreeringut ei leitud!");
    }
    
    if (file_exists($ajadFail)) {
        $ajadAndmed = file_get_contents($ajadFail);
        $ajad = json_decode($ajadAndmed, true);
        
        if (is_array($ajad)) {
            foreach ($ajad as $indeks => $aeg) {
                if ($aeg['id'] === $eemaldatud['slotId']) {
                    if (isset($aeg['registrations']) && is_array($aeg['registrations'])) {
                        $ajad[$indeks]['registrations'] = array_filter($aeg['registrations'], function($r) use ($opilaseNimi) {
                            return mb_strtolower($r['studentName']) !== mb_strtolower($opilaseNimi);
                        });
                        $ajad[$indeks]['registrations'] = array_values($ajad[$indeks]['registrations']);
                    }
                    break;
                }
            }
            
            file_put_contents($ajadFail, json_encode($ajad, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        }
    }
    
    if (file_put_contents($konsultatsioonidFail, json_encode($uuedKonsultatsioonid, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) === false) {
        throw new Exception("Andmete salvestamine ebaõnnestus!");
    }
    
    echo json_encode([
        'success' => true,
        'message' => "$opilaseNimi konsultatsioon on tühistatud ({$eemaldatud['teacher']}, {$eemaldatud['date']} {$eemaldatud['time']}).",
        'removed' => $eemaldatud
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
?>
