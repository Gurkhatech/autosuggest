<?php
header('Content-Type: text/html; charset=utf-8');
ini_set('default_charset', 'utf-8');

require 'Autosuggest.php';
$wc = new Autosuggest("words.txt");
$wc->doCompletion('रा');
echo json_encode($wc->suggestions,true);
