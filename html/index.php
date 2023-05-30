<?php

use Core\Route;

require "../Core/Route.php";

Route::match([
    "method" => "GET",
    "path" => "/register",
    "controller" => "API/UserController/register",
]);

Route::match([
    "method" => "POST",
    "path" => "/login",
    "controller" => "API/UserController/loginUser",
]);

Route::match([
    "method" => "POST",
    "path" => "/checkToken",
    "controller" => "API/TestTokenController/checkToken",
]);

Route::match([
    "method" => "GET",
    "path" => "/",
    "controller" => "SPA/front/index",
]);

Route::match([
    "method" => "POST",
    "path" => "/addComment",
    "controller" => "API/CommentController/addComment",
]);

Route::match([
    "method" => "GET",
    "path" => "/getComments",
    "controller" => "API/CommentController/getComments",
]);

Route::match([
    "method" => "DELETE",
    "path" => "/deleteComment",
    "controller" => "API/CommentController/deleteComment",
]);

Route::match([
    "method" => "PUT",
    "path" => "/modifyComment",
    "controller" => "API/CommentController/modifyComment",
]);

Route::match([
    "method" => "*",
    "path" => "/public/.*",
    "controller" => "handlers/assets",
]);

Route::match([
    "method" => "*",
    "path" => ".*",
    "controller" => "handlers/notfound",
]);
