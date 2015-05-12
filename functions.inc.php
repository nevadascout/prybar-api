<?php

/// <summary>
/// Require the current HTTP request to be made over HTTPS / SSL
/// </summary>
function RequireSSL()
{
    if(empty($_SERVER["HTTPS"]) || $_SERVER["HTTPS"] != "on")
    {
        header('HTTP/1.1 400 Bad Request');
        die("API requests must be made over HTTPS");
    }
}

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
function MongoConnect($connectionString)
{
    if(!isset($connectionString))
    {
        $m = new MongoClient($connectionString);   
    }
    else
    {
        // Connect locally
        $m = new MongoClient();
    }
    
    $db = $m->PrybarDev;
    
    if(!$db)
    {
        ReturnServerError();
    }
    
    return $db;
}

/// <summary>
/// Require the current HTTP request to be using HTTP Basic Authentication
/// </summary>
function RequireAuthentication()
{
    if (!isset($_SERVER['PHP_AUTH_USER']))
    {
        header('WWW-Authenticate: Basic realm="Prybar API"');
        header('HTTP/1.1 401 Unauthorized');
        exit();
    }
}

/// <summary>
/// Perform authentication for the current request against Mongo
/// </summary>
function PerformAuthentication($db, $appId, $apiToken)
{    
    $authenticated = false;
    
    $collection = $db->Apps;        
    $app = $collection->findOne(array('_id' => new MongoId($appId)), array('ApiKeys'));
    
    if($app != null && $app["ApiKeys"] != null)
    {
        foreach($app["ApiKeys"] as $apiKey)
        {
            // Prevent unnecessary loop iterations
            if(!$authenticated)
            {
                if($apiKey["Key"] == $apiToken && $apiKey["IsActive"])
                {
                    $authenticated = true;
                }
            }
        }
    }
    
    if(!$authenticated)
    {
        header('WWW-Authenticate: Basic realm="Prybar API"');
        header('HTTP/1.1 401 Unauthorized');
        exit();
    }
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
        
        // Replace spaces with dashes within tags
        foreach ($input["tags"] as $tag)
        {
            array_push($cleanTags, str_replace(" ", "-", $tag));
        }
        
        // Check the type is set correctly
        if($event->type == "Error") { $event->type = "error"; }        
        if($event->type == "Context") { $event->type = "context"; }
        
        if($event->type != "error" || $event->type != "context")
        {
            ReturnIncorrectJson();
        }
                        
        // Perform this manually to avoid saving unwanted fields
        $event->appId = $appId;
        $event->type = $input["type"];
        $event->summary = $input["summary"];
        $event->detail = $input["detail"];
        $event->trace = $input["trace"];
        $event->machine = $input["machine"];
        $event->user = $input["user"];
        $event->timestamp = $input["timestamp"];
        $event->tags = $cleanTags;
        
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
    public $trace;
    public $machine;
    public $user;
    public $timestamp;
    public $tags;
}

?>