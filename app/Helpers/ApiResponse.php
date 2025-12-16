<?php 

namespace App\Helpers;

class ApiResponse
{
    public static function success($message = "Success", $data = null, $error = null, $status = 200)
    {
        return response()->json([
            'message' => $message,
            'data' => $data,
            'error' => $error,
        ], $status);
    }

    public static function error($message = "Error", $error = null, $status = 400)
    {
        return response()->json([
            'message' => $message,
            'errors' => $error,
        ], $status);
    }

    public static function noContent()
    {
        return response()->noContent();
    }
}



?>