<?php
header("Content-Type: application/json");
echo json_encode([
    "raw_input" => file_get_contents("php://input"),
    "post" => $_POST,
    "content_type" => $_SERVER["CONTENT_TYPE"] ?? "not set",
    "request_method" => $_SERVER["REQUEST_METHOD"] ?? "not set",
]);
