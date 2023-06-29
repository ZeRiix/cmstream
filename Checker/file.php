<?php

namespace checker\file;

use Core\Floor;
use Core\Response;

function checkPath(string $path, Floor $floor, Response $response)
{
    if (str_contains("..", $path)) {
        $response->code(400)->info("invalidePath")->send();
    }

    return __DIR__ . "/.." . $path;
}

function exist(string $path, Floor $floor, Response $response)
{
    if (file_exists($path) === false) {
        $response->code(404)->info("fileNotFound")->send();
    }
    return $path;
}

function sizeFile($file, Floor $floor, Response $response)
{
    if ($file["size"] > 5000000) {
        $response->info("file.size")->code(400)->send();
    }
    return $file;
}

function extensionFile($file, Floor $floor, Response $response)
{
    $allowed = 'png';
    $filename = $file['name'];
    $ext = pathinfo($filename, PATHINFO_EXTENSION);
    if (!in_array($ext, explode(',', $allowed))) {
        $response->info("file.extension")->code(400)->send();
    }
    return $file;
}
