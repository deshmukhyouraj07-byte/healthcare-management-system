<?php
/**
 * switch_language.php
 * Call as: switch_language.php?lang=en  or  ?lang=mr
 * Sends the visitor back to the page they came from.
 */
session_start();

$lang = $_GET['lang'] ?? 'en';
$_SESSION['lang'] = in_array($lang, ['en', 'mr'], true) ? $lang : 'en';

$back = $_SERVER['HTTP_REFERER'] ?? 'index.php';
header('Location: ' . $back);
exit;
