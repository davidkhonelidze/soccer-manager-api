<?php

namespace App\Http\Controllers;

/**
 * @OA\Info(
 *     title="Soccer Manager API",
 *     version="1.0.0",
 *     description="Laravel API Documentation with Laravel Sanctum Authentication"
 * )
 *
 * @OA\SecurityScheme(
 *     securityScheme="sanctum",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="token",
 *     description="Enter your Sanctum token (without 'Bearer ' prefix)"
 * )
 *
 * @OA\Parameter(
 *     parameter="Accept-Language",
 *     name="Accept-Language",
 *     in="header",
 *     description="Language preference for API responses",
 *     required=false,
 *
 *     @OA\Schema(
 *         type="string",
 *         enum={"en", "ka"},
 *         default="en"
 *     )
 * )
 * */
abstract class Controller
{
    //
}
