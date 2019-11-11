<?php

header("Access-Control-Allow-Origin: *");
header('Content-Type: application/json');


if(checkLimitation($_SERVER["REMOTE_ADDR"]))
{
   $limitMsg=['msg'=>'10 requests/1h  limit'];
   print json_encode($limitMsg,true);;
   return;
}

include_once 'database.php';
 
$connection = null;
$database = new Database();
$connection = $database->getConnection();

if(isset($_SERVER["CONTENT_TYPE"]))
{
 if($_SERVER["CONTENT_TYPE"]=='application/xml')
 {
    header('Content-Type: application/xml');
    $xml = new SimpleXMLElement('<data/>');
    array_walk_recursive(getDataFromDb($connection), array ($xml, 'addChild'));
    print $xml->asXML();
 }
 elseif($_SERVER["CONTENT_TYPE"]=='text/csv'){
    header("Content-type: text/csv");
    header("Content-Disposition: attachment; filename=file.csv");
    header("Pragma: no-cache");
    header("Expires: 0");
    print json_encode(getDataFromDb($connection),true);
 }
 elseif($_SERVER["CONTENT_TYPE"]=='application/vnd.api+json')
 {
     header('Content-Type: application/json');
     print json_encode(getDataFromDb($connection),true);
 }
 else
 {
     print json_encode(getDataFromDb($connection),true);
 }
}
else
{
     print json_encode(getDataFromDb($connection),true);
}


function checkLimitation($ip){
    $setLimit=0;
   session_start();

if (!isset($_SESSION['requests'])) {
    $_SESSION['requests'] = [];
}

$requests = &$_SESSION['requests'];


$enableIpLimiter=0;
    $now = new DateTime();
    $now->format('Y-m-d H:i:s');
    $nowTsp=$now->getTimestamp();
if(!isset($requests[$ip]))
{
    
    
    $requests[$ip]=[
    'time'=>$nowTsp,
    'count'=>1
    ];
}
if(isset($requests[$ip]))
{
    $timeDiff = $requests[$ip]['time'] - $nowTsp;
    if($timeDiff>3600)
    {
        $requests[$ip]['time']=$nowTsp;
        $requests[$ip]['count']=1;
    }
    else
    {
        if($requests[$ip]['count']<=10)
        {
            $requests[$ip]['count']=$requests[$ip]['count']+1;
        }
        else
        {
            $enableIpLimiter=1;
        }
    }
}
if($enableIpLimiter)
{
    $setLimit=1;
}
return $setLimit;
 
}

function getDataFromDb($connection){
    $mmsi=null;
$lonlat=null;
$timestamp=null;

if(isset($_GET["mmsi"]))
{
    $mmsi = $_GET["mmsi"];
}
if(isset($_GET["lonlat"]))
{
    $lonlat = $_GET["lonlat"];
    $lonlat=explode(",",$lonlat);
}
if(isset($_GET["timestamp"]))
    $timestamp = $_GET["timestamp"];
    
$whereParams=[];
if ($mmsi) {
    $where[] = " `mmsi` IN (:mmsi)";
    $whereParams['mmsi']=print_r( $mmsi , TRUE);
}
if ($lonlat) {
    $where[] = " `lon` = :lon and `lat`= :lat";
    $whereParams['lon']=$lonlat[0];
    $whereParams['lat']=$lonlat[1];
}
if ($timestamp) {
    $where[] = " `timestamp` = :timestamp ";
    $whereParams['timestamp']=$timestamp;
}


if(isset($where))
$where_clause = implode(' AND ', $where);

$sql_q = "SELECT * FROM `MARINE_TRAFFIC`";
if(isset($where))
$sql_q=$sql_q."WHERE ".$where_clause."";

//error_log(print_r( $sql_q , TRUE));

$sql_json = $connection->prepare($sql_q);
$sql_json->execute($whereParams);
$result = $sql_json->fetchAll();

$finalData=[];


$finalData['data'] = array();
 
   foreach ($result as $row) {
        $finalData['data'][] = array(
        "mmsi" => $row['mmsi'],
        "status" => $row['status'],
        "stationId" => $row['stationId'],
        "speed" => $row['speed'],
        "lon" => $row['lon'],
        "lat" => $row['lat'],
        "course" => $row['course'],
        "heading" => $row['heading'],
        "rot" => $row['rot'],
        "timestamp" => $row['timestamp']
    );
    }
    logParams($mmsi,$lonlat,$timestamp,$connection);
return $finalData;
}

function logParams($mmsi,$lonlat,$timestamp,$connection){
    $loglatlonV=null;
if ($lonlat) {
    $loglatlonV=implode(",",$lonlat);
}

$dataLog = [
    'ip' => $_SERVER['REMOTE_ADDR'],
    'time' => date("Y-m-d H:i:s"),
    'mmsi' => isset($mmsi) ? $mmsi : '',
    'lonlat' => isset($loglatlonV) ? $loglatlonV : '',
    'timestamp' => isset($timestamp) ? $timestamp : '',
];
$sqlLog = "INSERT INTO LOG (ip,time,mmsi,lonlat,timestamp ) VALUES (:ip, :time, :mmsi, :lonlat, :timestamp)";
//error_log(print_r( $dataLog , TRUE));
//error_log(print_r( $sqlLog , TRUE));
$stmtLog= $connection->prepare($sqlLog);
$stmtLog->execute($dataLog);

}



