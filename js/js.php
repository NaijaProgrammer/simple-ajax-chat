<?php
require_once dirname(__DIR__). '/config.php';
header('Content-type:text/javascript');
echo 'function getChatDirectoryUrl(){ return "'. $dir_url. '"; }';