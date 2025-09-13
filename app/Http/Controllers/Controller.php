<?php

namespace App\Http\Controllers;

/**
 * @OA\Info(
 *     title="My Laravel API",
 *     version="1.0.0",
 *     description="Laravel API Documentation with Laravel Sanctum Authentication"
 * )
 *
 *
 * @OA\SecurityScheme(
 *     securityScheme="sanctum",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="token",
 *     description="Enter your Sanctum token (without 'Bearer ' prefix)"
 * )
 * */
abstract class Controller
{
    //
}
