<?php

$host="localhost";
$user="root";
$password="";
$dbname= "soe-portfolio";
$conn = new mysqli($host,$user,$password,$dbname);


if ($conn->connect_error) {
    echo ("Connection failed: X " .$conn->connect_error);
}

?>