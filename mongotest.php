<?php
    echo extension_loaded("mongo") ? "loaded\n" : "not loaded\n";
$m = new MongoClient();

?>