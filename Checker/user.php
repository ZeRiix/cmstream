<?php

namespace checker\user;

use Core\Floor;
use Core\Response;
use Entity\User;

function id(int $id, Floor $floor, Response $response): int
{
    return $id;
}

function username(string $name, Floor $floor, Response $response): string
{
    $name = trim($name);
    $name = strtolower($name);
    if (strlen($name) < 4 || strlen($name) > 120) {
        $response->info("user.username")->code(400)->send();
    }
    return $name;
}

function lastname(string $lastname, Floor $floor, Response $response): string
{
    $lastname = trim($lastname);
    $lastname = strtoupper($lastname);
    if (strlen($lastname) < 1 || strlen($lastname) > 120) {
        $response->info("user.lastname")->code(400)->send();
    }
    return $lastname;
}

function firstname(string $firstname, Floor $floor, Response $response): string
{
    $firstname = trim($firstname);
    $firstname = ucfirst($firstname);
    if (strlen($firstname) < 4 || strlen($firstname) > 60) {
        $response->info("user.firstname")->code(400)->send();
    }
    return $firstname;
}

function password(string $pwd, Floor $floor, Response $response) : string {
    $pattern = '/[!@#$%^&*(),.?":{}|<>]/';
    $password = trim($pwd);
    if (strlen($password) < 4 || strlen($password) > 255) {
        $response->info("user.password.length")->code(400)->send();
    }
    if(!preg_match($pattern, $password)) {
        $response->info("user.password.pattern")->code(400)->send();
    }
    return $password;
}

function email(string $email, Floor $floor, Response $response): string
{
    $email = trim($email);
    $email = strtolower($email);
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $response->info("user.email")->code(400)->send();
    }
    return $email;
}

function existByMail(string $email, Floor $floor, Response $response): User
{
    /** @var User $user */
    $user = User::findFirst(["email" => $email]);
    if($user === null) $response->info("user.notfound.mail")->code(404)->send();
    return $user;
}

function mailUsed(string $email, Floor $floor, Response $response): bool
{
    /** @var User $user */
    $user = User::findFirst(["email" => $email]);
    if($user !== null) return true;
    return false;
}

function exist(int $userId, Floor $floor, Response $response): User
{
    /** @var User $user */
    $user = User::findFirst(["id" => $userId]);
    if($user === null) $response->info("user.notfound.id")->code(404)->send();
    return $user;
}
