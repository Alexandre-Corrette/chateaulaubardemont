<?php
// Optionnel : lire un paramètre ?success=1 ou ?error=...
$success = isset($_GET['success']);
$error   = $_GET['error'] ?? null;

// Puis on sert le HTML généré par Hugo
readfile(__DIR__ . '/index.html');
