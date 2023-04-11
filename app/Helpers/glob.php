<?php 

function jsonResponse(bool $status, string $message = '', mixed $data = [], int $statusCode = 200)
{
    $response = [
        'status' => $status,
        'message' => $message,
        'data' => $data,
    ];

    return response()->json($response, $statusCode);
}