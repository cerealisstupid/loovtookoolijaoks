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
    
    if (!isset($andmed['slotId']) || !isset($andmed['studentName'])) {
        throw new Exception("Vajalikud andmed puuduvad!");
    }
    
    $aegId = intval($andmed['slotId']);
    $opilaseNimi = trim($andmed['studentName']);
    
    if (strlen($opilaseNimi) < 2) {
        throw new Exception("Nimi peab olema vähemalt 2 tähemärki pikk!");
    }
    
    if (strlen($opilaseNimi) > 50) {
        throw new Exception("Nimi on liiga pikk!");
    }
    
    if (!preg_match("/^[a-zA-ZäöüõÄÖÜÕšžŠŽ\s'-]+$/u", $opilaseNimi)) {
        throw new Exception("Nimi sisaldab keelatud tähemärke!");
    }
    
    $ajadFail = __DIR__ . '/data/ajad.json';
    $konsultatsioonidFail = __DIR__ . '/data/konsultatsioonid.json';
    
    if (!file_exists($ajadFail)) {
        throw new Exception("Konsultatsioone pole veel loodud!");
    }
    
    $ajadAndmed = file_get_contents($ajadFail);
    $ajad = json_decode($ajadAndmed, true);
    
    if (!is_array($ajad)) {
        throw new Exception("Vigane andmefail!");
    }
    
    $aeg = null;
    $aegIndeks = null;
    foreach ($ajad as $indeks => $a) {
        if ($a['id'] === $aegId) {
            $aeg = $a;
            $aegIndeks = $indeks;
            break;
        }
    }
    
    if (!$aeg) {
        throw new Exception("Vale konsultatsiooni ID!");
    }
    
    $hetkeRegistreeringud = isset($aeg['registrations']) ? count($aeg['registrations']) : 0;
    if ($hetkeRegistreeringud >= $aeg['maxStudents']) {
        throw new Exception("See konsultatsioon on juba täis!");
    }
    
    $konsultatsioonid = [];
    if (file_exists($konsultatsioonidFail)) {
        $jsonAndmed = file_get_contents($konsultatsioonidFail);
        $konsultatsioonid = json_decode($jsonAndmed, true);
        
        if (!is_array($konsultatsioonid)) {
            $konsultatsioonid = [];
        }
    }
    
    foreach ($konsultatsioonid as $reg) {
        if ($reg['slotId'] === $aegId && mb_strtolower($reg['studentName']) === mb_strtolower($opilaseNimi)) {
            throw new Exception("Sa oled juba sellele konsultatsioonile registreerunud!");
        }
    }
    
    foreach ($konsultatsioonid as $reg) {
        foreach ($ajad as $a) {
            if ($a['id'] === $reg['slotId']) {
                if ($a['date'] === $aeg['date'] && 
                    $a['timeStart'] === $aeg['timeStart'] && 
                    mb_strtolower($reg['studentName']) === mb_strtolower($opilaseNimi)) {
                    throw new Exception("Sa oled juba sellel ajal teisele konsultatsioonile registreerunud!");
                }
                break;
            }
        }
    }
    
    $uusRegistreering = [
        'slotId' => $aegId,
        'studentName' => $opilaseNimi,
        'teacher' => $aeg['teacher'],
        'subject' => $aeg['subject'],
        'time' => $aeg['timeStart'] . '-' . $aeg['timeEnd'],
        'date' => $aeg['date'],
        'class' => $aeg['class'],
        'registeredAt' => date('Y-m-d H:i:s')
    ];
    
    $konsultatsioonid[] = $uusRegistreering;
    
    if (!isset($ajad[$aegIndeks]['registrations'])) {
        $ajad[$aegIndeks]['registrations'] = [];
    }
    $ajad[$aegIndeks]['registrations'][] = [
        'studentName' => $opilaseNimi,
        'registeredAt' => date('Y-m-d H:i:s')
    ];
    
    if (file_put_contents($konsultatsioonidFail, json_encode($konsultatsioonid, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) === false) {
        throw new Exception("Andmete salvestamine ebaõnnestus!");
    }
    
    if (file_put_contents($ajadFail, json_encode($ajad, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) === false) {
        throw new Exception("Pesa andmete salvestamine ebaõnnestus!");
    }
    
    echo json_encode([
        'success' => true,
        'message' => "Konsultatsioonile registreerimine õnnestus! {$aeg['teacher']} ({$aeg['subject']}) {$aeg['date']} kell {$aeg['timeStart']}-{$aeg['timeEnd']}",
        'registration' => $uusRegistreering
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
?>
