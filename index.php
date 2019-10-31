<?php

$wc = new Autosuggest("words.txt");
$wc->doCompletion('राम');
echo json_encode($wc->suggestions);
