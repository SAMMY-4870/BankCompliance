<?php

$host = "sqlXXX.byetcluster.com";
$user = "if0_42226829";
$password = "DbyPBzo6ao";
$database = "if0_42226829_banktracker";

$conn = mysqli_connect($host,$user,$password,$database);

if(!$conn){
    die("Database Connection Failed: " . mysqli_connect_error());
}
?>