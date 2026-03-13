<?php
require __DIR__."/../vendor/autoload.php";
$app = require __DIR__."/../bootstrap/app.php";
$kernel = $app->make(\Illuminate\Contracts\Http\Kernel::class);

$request = \Illuminate\Http\Request::capture();

// Debug: what does the request look like before the kernel
$debug = [
    "before_kernel" => [
        "all" => $request->all(),
        "content" => substr($request->getContent(), 0, 200),
        "isJson" => $request->isJson(),
        "method" => $request->method(),
        "contentType" => $request->header("Content-Type"),
    ],
];

header("Content-Type: application/json");
echo json_encode($debug, JSON_PRETTY_PRINT);
