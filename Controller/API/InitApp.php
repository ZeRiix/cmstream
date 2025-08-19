<?php

namespace Controller\API\InitApp;

use Core\Controller;
use Core\Entity;
use Core\File;
use Core\Logger;
use Core\QueryBuilder;
use Core\Request;
use Core\Response;
use Services\Permissions;
use Entity\Role;
use Entity\User;
use PHPMailer\PHPMailer;

class getInit extends Controller
{
    public function checkers(Request $request): array
    {
        return [];
    }

    public function handler(Request $request, Response $response): void
    {
        $response->code(200)->render("init", "none");
    }
}

/**
 * @param Request $request
 * @return Response
 * 
 * This controller is used to check if the database is reachable.
 */
class tryDB extends Controller
{
    public function checkers(Request $request): array
    {
        return [
            ["type/string", $request->getBody()["DB_HOST"]],
            ["type/int", $request->getBody()["DB_PORT"]],
            ["type/string", $request->getBody()["DB_TYPE"]],
            ["type/string", $request->getBody()["DB_DATABASE"]],
            ["type/string", $request->getBody()["DB_USERNAME"]],
            ["type/string", $request->getBody()["DB_PASSWORD"]]
        ];
    }

    public function handler(Request $request, Response $response): void
    {
        $body = $request->getBody();
        QueryBuilder::dataBaseConnection($body);
        $response->code(204)->send();
    }
}

/**
 * @param Request $request
 * @return Response
 * 
 * This controller is used to check if the typeof config is correct.
 */
class tryAppConf extends Controller
{
    public function checkers(Request $request): array
    {
        return [
            ["type/string", $request->getBody()["HOST"], null, "host.error"],
            ["init/valideHost", $request->getBody()["HOST"], null, "host.error"],
            ["type/string", $request->getBody()["APP_NAME"], null, "app.name.error"],
            ["type/string", $request->getBody()["SECRET_KEY"], null, "secret.key.error"],
            ["type/int", $request->getBody()["TOKEN_DURATION"] ?? 3600, null, "token.duration.error"]
        ];
    }

    public function handler(Request $request, Response $response): void
    {
        $response->code(204)->send();
    }
}

/**
 * @param Request $request
 * @return Response
 * 
 * This controller is used to check if the mail config is correct.
 * And if the mail server is reachable.
 */
class tryEmail extends Controller
{
    public function checkers(Request $request): array
    {
        return [
            ["type/string", $request->getBody()["MAIL_HOST"], "host"],
            ["type/int", $request->getBody()["MAIL_PORT"], "port"],
            ["type/string", $request->getBody()["MAIL_FROM"], "mail"],
            ["type/sting", $request->getBody()["MAIL_PASSWORD"], "password"]
        ];
    }

    public function handler(Request $request, Response $response): void
    {
        $host = $this->floor->pickup("host");
        $port = $this->floor->pickup("port");
        $mail = $this->floor->pickup("mail");
        $password = $this->floor->pickup("password");

        $mailClient = new PHPMailer(true);
        $mailClient->SMTPDebug = 0;
        $mailClient->Host = $host;
        $mailClient->Port = $port;
        $mailClient->isSMTP();
        $mailClient->SMTPSecure = 'tls';
        $mailClient->SMTPAuth = true;

        $mailClient->setFrom($mail, "test");
        $mailClient->Username = !empty($password) ? $mail : '';
        $mailClient->Password = !empty($password) ? $password : '';

        $mailClient->addAddress($mail);
        $mailClient->isHTML(true);
        $mailClient->Subject = "test";
        $mailClient->Body = "test";

        $mailClient->send();

        $response->code(204)->send();
    }
}

/**
 * @param Request $request
 * @return Response
 * 
 * This controller is used to check if the first account is correct (Admin).
 */
class tryFirstAccount extends Controller
{
    public function checkers(Request $request): array
    {
        return [
            ["user/firstname", $request->getBody()["firstname"]],
            ["user/lastname", $request->getBody()["lastname"]],
            ["user/email", $request->getBody()["email"]],
            ["user/username", $request->getBody()["username"]],
            ["user/password", $request->getBody()["password"]]
        ];
    }

    public function handler(Request $request, Response $response): void
    {
        $response->code(204)->send();
    }
}

/*
{
    "DB_HOST" : "database",
    "DB_PORT" : "5432",
    "DB_TYPE" : "pgsql",
    "DB_DATABASE" : "esgi",
    "DB_USERNAME" : "esgi",
    "DB_PASSWORD" : "Test1234",

    "HOST" : "http://localhost:1506",
    "APP_NAME" : "cmStream",
    "SECRET_KEY" : "secretKey",
    "TOKEN_DURATION" : 3600,

    "MAIL_HOST" : "maildev",
    "MAIL_PORT" : 1025,
    "MAIL_FROM" : "no-reply-cmstream@mail.com",
    "MAIL_PASSWORD": "",

    "firstname": "Mathieu",
    "lastname": "Campani",
    "username": "mathcovax",
    "email": "campani.mathieu@gmail.com",
    "password": "!mlkit1234"
}
*/
/**
 * @param Request $request
 * @return Response
 * 
 * This controller is used to create the config file.
 * Re-check if data is correct.
 * Create the database.
 * Create the first account and define as admin (with all permissions).
 * Create the config file.
 * Init Route (reverse init route file with production route file)
 */
class postInit extends Controller
{

    public function checkers(Request $request): array
    {
        return [
            ["type/string", $request->getBody()["DB_HOST"]],
            ["type/int", $request->getBody()["DB_PORT"]],
            ["type/string", $request->getBody()["DB_TYPE"]],
            ["type/string", $request->getBody()["DB_DATABASE"]],
            ["type/string", $request->getBody()["DB_USERNAME"]],
            ["type/string", $request->getBody()["DB_PASSWORD"]],

            ["type/string", $request->getBody()["HOST"]],
            ["type/string", $request->getBody()["APP_NAME"]],
            ["type/string", $request->getBody()["SECRET_KEY"]],
            ["type/int", $request->getBody()["TOKEN_DURATION"] ?? 3600],

            ["type/string", $request->getBody()["MAIL_HOST"]],
            ["type/int", $request->getBody()["MAIL_PORT"]],
            ["type/string", $request->getBody()["MAIL_FROM"]],

            ["user/firstname", $request->getBody()["firstname"], "firstname"],
            ["user/lastname", $request->getBody()["lastname"], "lastname"],
            ["user/email", $request->getBody()["email"], "email"],
            ["user/username", $request->getBody()["username"], "username"],
            ["user/password", $request->getBody()["password"], "password"]
        ];
    }
    public function handler(Request $request, Response $response): void
    {
        $body = $request->getBody();

        QueryBuilder::dataBaseConnection($body);

        try {
            $defaultConfigPath = __DIR__ . "/../../Core/config.example";

            $file = fopen($defaultConfigPath, "a+");
            if ($file) {
                $configFile = fread($file, filesize($defaultConfigPath));
                preg_match_all("/{(.*)}/", $configFile, $groups);
                foreach ($groups[1] as $key => $value) {
                    $configFile = str_replace($groups[0][$key], $body[$value], $configFile);
                }
                file_put_contents(__DIR__ . "/../../config.php", $configFile);
            }
            fclose($file);

            $file = fopen(__DIR__ . "/../../html/index.php", "r");
            $fileContent = fread($file, filesize(__DIR__ . "/../../html/index.php"));
            rename(__DIR__ . "/../../html/index.tmp.php", "index.php");
            file_put_contents(__DIR__ . "/../../html/index.tmp.php", $fileContent);

            exec("php " . __DIR__ . "/../../bin/makeMigration.php", $output, $retval);
            if($retval === 1 || count($output) !== 0)throw new \Exception(implode("\n", $output));
            exec("php " . __DIR__ . "/../../bin/doMigration.php", $output, $retval);
            if($retval === 1 || count($output) !== 0)throw new \Exception(implode("\n", $output));
            exec("php " . __DIR__ . "/../../bin/makeRoute.php", $output, $retval);
            if($retval === 1 || count($output) !== 0)throw new \Exception(implode("\n", $output));

            $role = Role::insertOne(
                fn (Role $role) => $role
                    ->setName("admin") 
            );

            $role->addPermission(Permissions::AccessDashboard);
            $role->addPermission(Permissions::RoleEditor);
            $role->addPermission(Permissions::CommentsManager);
            $role->addPermission(Permissions::ContentsManager);
            $role->addPermission(Permissions::StatsViewer);
            $role->addPermission(Permissions::UserEditor);
            $role->addPermission(Permissions::ConfigEditor);
            
            User::insertOne(
                fn (User $user) => $user
                    ->setEmail($this->floor->pickup("email"))
                    ->setFirstname($this->floor->pickup("firstname"))
                    ->setLastname($this->floor->pickup("lastname"))
                    ->setUsername($this->floor->pickup("username"))
                    ->setRole($role)
                    ->setPassword(password_hash($this->floor->pickup("password"), PASSWORD_DEFAULT))
            );

            $file = new File(__DIR__ . "/../../public/cuteVue/pages.json");
            $file->write('[{"name":"home","rows":[]}]');

            $response->code(204)->info("config.create")->send();
        } 
        catch (\Throwable $th) {
            exec("php " . __DIR__ . "/../../bin/reset.php", $output, $retval);

            $data = [
                "info" => "init error",
                "message" => $th->getMessage(),
                "file" => $th->getFile(),
                "line" => $th->getLine(),
            ];

            $response->code(500)->info("config.uncreated")->send($data);
        }
    }
}
