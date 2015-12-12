<?php

require("functions.inc.php");

// SSL is not required as the nginx load balancer has SSL termination
// after which point network traffic is sent via private networks only

// Only allow POST requests to the API
RequirePostRequest();

// Connect to mongo
$db = MongoConnect("10.131.174.61", "user", "pass"); // db.prybar.io
//$db = MongoConnect("localhost", "", ""); // Local development

// Perform authentication using the X-ApiKey HTTP Header
$appId = GetAppId($db, $_SERVER['HTTP_X_APIKEY']);

// Process the supplied json
$newEvent = GetJson($appId);

// Save the json to the db
SaveJson($db, $newEvent);

?>
