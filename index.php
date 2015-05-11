<?php
	
require("functions.inc.php");

//RequireSSL();
RequireAuthentication();
RequirePostRequest();

$db = MongoConnect();

$appId = $_SERVER['PHP_AUTH_USER'];
$appToken = $_SERVER['PHP_AUTH_PW'];

PerformAuthentication($db, $appId, $appToken);

$newEvent = GetJson($appId);
SaveJson($db, $newEvent);

?>