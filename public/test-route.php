<?php
// Simulate a request to /api/v1/auth/login through the full Laravel kernel
$_SERVER["REQUEST_URI"] = "/api/v1/auth/login";
$_SERVER["SCRIPT_NAME"] = "/index.php";
$_SERVER["SCRIPT_FILENAME"] = "/var/www/html/public/index.php";
$_SERVER["DOCUMENT_ROOT"] = "/var/www/html/public";

require __DIR__."/../vendor/autoload.php";
$app = require __DIR__."/../bootstrap/app.php";
$kernel = $app->make(\Illuminate\Contracts\Http\Kernel::class);
$request = \Illuminate\Http\Request::capture();

// Debug request data at this point
file_put_contents("/tmp/debug-request.txt", json_encode([
    "all" => $request->all(),
    "content" => $request->getContent(),
    "isJson" => $request->isJson(),
    "method" => $request->method(),
]));

$response = $kernel->handle($request);
$response->send();
$kernel->terminate($request, $response);
