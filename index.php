<?php
	
require("functions.inc.php");

// SSL is not required as the nginx load balancer has SSL termination
// after which point network traffic is sent via private networks only
//RequireSSL();

RequireAuthentication();
RequirePostRequest();

// TODO -- Add connectionstring here
$db = MongoConnect();

$appId = $_SERVER['PHP_AUTH_USER'];
$appToken = $_SERVER['PHP_AUTH_PW'];

PerformAuthentication($db, $appId, $appToken);

$newEvent = GetJson($appId);
SaveJson($db, $newEvent);

?>