<?php
class Database{
 
    public function getConnection(){
        $servername = "www.zenswimmer.com";
        $dbname = "****";
        $username = "****";
        $password = "****";

        $connection = null;
 
        try{
            $connection = new PDO("mysql:host=" . $servername . ";dbname=" . $dbname, $username, $password);
            $connection->exec("set names utf8");
        }catch(PDOException $exception){
            echo "Connection error: " . $exception->getMessage();
        }

        return $connection;
    }
}
?>