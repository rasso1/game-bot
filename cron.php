<?php
include 'config.php';
include 'Medoo.php';
use Medoo\Medoo;
$database = new medoo($config);

$database->update("lottery", [
	"expect[+]" => 1,
    "opencode" => 0
], [
	"id" => 1
]);
