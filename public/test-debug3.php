<?php
error_reporting(E_ALL);
ini_set("display_errors", "1");

require __DIR__."/../vendor/autoload.php";
$app = require __DIR__."/../bootstrap/app.php";
$kernel = $app->make(\Illuminate\Contracts\Http\Kernel::class);
$request = \Illuminate\Http\Request::capture();

// Let the kernel process the request and intercept
$response = $kernel->handle($request);

header("Content-Type: application/json");
echo json_encode([
    "status" => $response->getStatusCode(),
    "request_all" => $request->all(),
    "request_content" => $request->getContent(),
    "request_isJson" => $request->isJson(),
    "request_uri" => $request->getRequestUri(),
    "response_content" => substr($response->getContent(), 0, 500),
]);
exit;
