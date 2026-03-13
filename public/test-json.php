<?php
require __DIR__."/../vendor/autoload.php";
$app = require __DIR__."/../bootstrap/app.php";
$kernel = $app->make(\Illuminate\Contracts\Http\Kernel::class);

$request = \Illuminate\Http\Request::capture();

header("Content-Type: application/json");
echo json_encode([
    "content" => $request->getContent(),
    "json_method" => $request->json()->all(),
    "input_source" => $request->getInputSource()->all(),
    "all" => $request->all(),
    "isJson" => $request->isJson(),
    "format" => $request->getContentTypeFormat(),
    "query" => $request->query->all(),
    "request" => $request->request->all(),
], JSON_PRETTY_PRINT);
