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
    
    $vajalikud = ['teacher', 'subject', 'class', 'date', 'timeStart', 'timeEnd', 'maxStudents'];
    foreach ($vajalikud as $vali) {
        if (!isset($andmed[$vali]) || (is_string($andmed[$vali]) && empty(trim($andmed[$vali])))) {
            throw new Exception("Väli '$vali' on nõutud!");
        }
    }
    
    $opetaja = trim($andmed['teacher']);
    $aine = trim($andmed['subject']);
    $klass = trim($andmed['class']);
    $kuupaev = trim($andmed['date']);
    $algusaeg = trim($andmed['timeStart']);
    $loppaeg = trim($andmed['timeEnd']);
    $maksOpilasi = intval($andmed['maxStudents']);
    
    if (strlen($aine) < 2 || strlen($aine) > 100) {
        throw new Exception("Aine nimi peab olema 2-100 tähemärki!");
    }
    
    if (!in_array($klass, ['10', '11', '12'])) {
        throw new Exception("Vale klass!");
    }
    
    if ($maksOpilasi < 1 || $maksOpilasi > 10) {
        throw new Exception("Max õpilaste arv peab olema 1-10!");
    }
    
    $kuupaevObj = DateTime::createFromFormat('Y-m-d', $kuupaev);
    if (!$kuupaevObj) {
        throw new Exception("Vale kuupäeva formaat!");
    }
    
    $tana = new DateTime();
    $tana->setTime(0, 0, 0);
    if ($kuupaevObj < $tana) {
        throw new Exception("Kuupäev ei saa olla minevikus!");
    }
    
    if (!preg_match('/^([0-1][0-9]|2[0-3]):[0-5][0-9]$/', $algusaeg) || 
        !preg_match('/^([0-1][0-9]|2[0-3]):[0-5][0-9]$/', $loppaeg)) {
        throw new Exception("Vale aja formaat!");
    }
    
    if (strtotime($loppaeg) <= strtotime($algusaeg)) {
        throw new Exception("Lõppaeg peab olema hiljem kui algusaeg!");
    }
    
    $andmeteFail = __DIR__ . '/data/ajad.json';
    
    if (!file_exists(__DIR__ . '/data')) {
        if (!mkdir(__DIR__ . '/data', 0755, true)) {
            throw new Exception("Andmekataloogi loomine ebaõnnestus!");
        }
    }
    
    $ajad = [];
    if (file_exists($andmeteFail)) {
        $jsonAndmed = file_get_contents($andmeteFail);
        $ajad = json_decode($jsonAndmed, true);
        
        if (!is_array($ajad)) {
            $ajad = [];
        }
    }
    
    foreach ($ajad as $aeg) {
        if ($aeg['teacher'] === $opetaja && 
            $aeg['date'] === $kuupaev && 
            $aeg['timeStart'] === $algusaeg) {
            throw new Exception("Sul on juba sellel ajal konsultatsioon!");
        }
    }
    
    $maksId = 0;
    foreach ($ajad as $aeg) {
        if (isset($aeg['id']) && $aeg['id'] > $maksId) {
            $maksId = $aeg['id'];
        }
    }
    $uusId = $maksId + 1;
    
    $uusAeg = [
        'id' => $uusId,
        'teacher' => $opetaja,
        'subject' => $aine,
        'class' => $klass,
        'date' => $kuupaev,
        'timeStart' => $algusaeg,
        'timeEnd' => $loppaeg,
        'maxStudents' => $maksOpilasi,
        'registrations' => [],
        'createdAt' => date('Y-m-d H:i:s')
    ];
    
    $ajad[] = $uusAeg;
    
    if (file_put_contents($andmeteFail, json_encode($ajad, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) === false) {
        throw new Exception("Andmete salvestamine ebaõnnestus!");
    }
    
    echo json_encode([
        'success' => true,
        'message' => "Konsultatsioon edukalt loodud!",
        'slot' => $uusAeg
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
?>
