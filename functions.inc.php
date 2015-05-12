<?php

date_default_timezone_set('UTC');

/// <summary>
/// Require requests made to the API to use POST
/// </summary>
function RequirePostRequest()
{
    if($_SERVER['REQUEST_METHOD'] !== 'POST')
    {
        header('HTTP/1.1 400 Bad Request');
        die("The Prybar API currently only accepts POST requests");
    }
}

/// <summary>
/// Connect to the mongo server
/// </summary>
function MongoConnect($mongoServer, $mongoUser, $mongoPass)
{
    if($mongoServer == "localhost")
    {
        // Connect locally
        $m = new MongoClient();
    }
    else
    {
        $m = new MongoClient('mongodb://' . $mongoServer, [
            'username' => $mongoUser,
            'password' => $mongoPass,
            'db'       => 'Prybar'
        ]);
    }

    $db = $m->Prybar;
    
    if(!$db)
    {
        ReturnServerError();
    }
    
    return $db;
}

/// <summary>
/// Perform authentication for the current request against Mongo
/// </summary>
function GetAppId($db, $apiKey)
{    
    $collection = $db->AppApiKeyPairs;

    $key = $collection->findOne(array('apiKey' => $apiKey));
        
    if($key != null && $key["enabled"] == true)
    {
        return $key["appId"];
    }
    
    header('HTTP/1.1 401 Unauthorized');
    exit();
}

/// <summary>
/// Checks that the provided json data is in the correct format for Prybar to store
/// </summary>
function GetJson($appId)
{
    $valid = false;
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Check the json was parsed properly by json_decode() 
    // Getting null here normally means that the json was invalid
    if($input != null)
    {
        $event = new Event();
        $cleanTags = [];
        
        // Format provided date time        
        $timestamp = new MongoDate(strtotime($input["timestamp"]));
        
        // Replace spaces with dashes within tags
        foreach ($input["tags"] as $tag)
        {
            array_push($cleanTags, str_replace(" ", "-", $tag));
        }
        
        // Check the type is set correctly
        $type = $input["type"];
        
        if($type == "Error") { $type = "error"; }
        if($type == "Context") { $type = "context"; }
        
        if($type != "error")
        {
            var_dump($type);
            ReturnIncorrectJson();
        }
        
        // Clean machine value if set
        if($input["machine"] != null)
        {
            $machine = str_replace(" ", "-", $input["machine"]);
        }
        
        // Perform the assignments manually to avoid saving unwanted fields
        $event->appId = $appId;
        $event->type = $type;
        $event->summary = $input["summary"];
        $event->detail = $input["detail"];
        $event->stackTrace = $input["stackTrace"];
        $event->machine = $machine;
        $event->user = $input["user"];
        $event->timestamp = $timestamp;
        $event->tags = $cleanTags;
        
        // Check for the minimum required values
        if(isset($event->type) &&
           isset($event->summary) &&
           isset($event->timestamp))
        {
            return $event; 
        }
    }
    
    if(!$valid)
    {
        ReturnIncorrectJson();
    }
}

function ReturnIncorrectJson()
{
    header('HTTP/1.1 400 Bad Request');
    die("The json data you have supplied is not in the correct format for the Prybar API to understand.\r\nPlease consult the API documentation at http://docs.prybar.io");
}

/// <summary>
/// Save the supplied json data to the Mongo database
/// </summary>
function SaveJson($db, $data)
{ 
    $collection = $db->Events;
    
    if(!$collection)
    {
        ReturnServerError();
    }
    
    $collection->insert($data);
    
    header('HTTP/1.1 201 Created');
    exit();
}

/// <summary>
/// Return a HTTP header error code 500
/// </summary>
function ReturnServerError()
{
    header('HTTP/1.1 500 Internal Server Error');
    die("A server error occured while saving your request.\r\nPlease check our API status page at http://status.prybar.io and try again later");
}



class Event {
    public $appId;
    public $type;
    public $summary;
    public $detail;
    public $stackTrace;
    public $machine;
    public $user;
    public $timestamp;
    public $tags;
}

?>
