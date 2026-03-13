<?php
require __DIR__."/../vendor/autoload.php";
$app = require __DIR__."/../bootstrap/app.php";
$kernel = $app->make(\Illuminate\Contracts\Http\Kernel::class);

$request = \Illuminate\Http\Request::capture();
echo json_encode([
    "input" => $request->all(),
    "content" => $request->getContent(),
    "isJson" => $request->isJson(),
    "contentType" => $request->getContentTypeFormat(),
]);
