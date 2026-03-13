<?php
error_reporting(E_ALL);
ini_set("display_errors", "1");

require __DIR__."/../vendor/autoload.php";
$app = require __DIR__."/../bootstrap/app.php";

$request = \Illuminate\Http\Request::capture();

$content = $request->getContent();
$decoded = json_decode($content, true);

header("Content-Type: application/json");
echo json_encode([
    "content_length" => strlen($content),
    "content" => $content,
    "decoded" => $decoded,
    "json_error" => json_last_error_msg(),
    "isJson" => $request->isJson(),
    "json_all" => $request->json() ? $request->json()->all() : "null",
    "all" => $request->all(),
]);
