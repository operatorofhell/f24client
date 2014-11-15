<?php

error_reporting(E_ALL);	

require("F24Client.php");

$f24 = new F24Client('UserName842014', 'zyBC!123');

$f24->sendAlarm('1000',"Dies ist ein Test des F24Clients auf php");
?>