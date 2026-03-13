<?php
// Same as index.php but with debug
error_reporting(E_ALL);
ini_set("display_errors", "1");

$rawInput = file_get_contents("php://input");
file_put_contents("/tmp/raw-input.txt", $rawInput);

require __DIR__."/../vendor/autoload.php";
$app = require __DIR__."/../bootstrap/app.php";
$kernel = $app->make(\Illuminate\Contracts\Http\Kernel::class);
$request = \Illuminate\Http\Request::capture();

file_put_contents("/tmp/request-debug.txt", json_encode([
    "content" => $request->getContent(),
    "all" => $request->all(),
    "isJson" => $request->isJson(),
    "uri" => $request->getRequestUri(),
    "method" => $request->method(),
]));

$response = $kernel->handle($request);
$response->send();
$kernel->terminate($request, $response);
