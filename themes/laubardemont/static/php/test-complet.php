<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Test complet</h1>";

// 1. Test écriture fichier
$writeTest = file_put_contents(__DIR__ . '/debug.txt', date('Y-m-d H:i:s') . " - TEST\n", FILE_APPEND);
echo "<p>1. Écriture fichier: " . ($writeTest ? "OK" : "ÉCHEC") . "</p>";

// 2. Test mail basique
$mailTest = mail(
    'alexandrecorrette@gmail.com',
    'Test complet ' . date('H:i:s'),
    'Ceci est un test',
    'From: contact@chateau-laubardemont.com'
);
echo "<p>2. Mail: " . ($mailTest ? "OK (accepté)" : "ÉCHEC") . "</p>";

// 3. Affiche les erreurs
echo "<p>3. Dernière erreur: " . (error_get_last()['message'] ?? 'Aucune') . "</p>";