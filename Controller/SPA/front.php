<?php
namespace Controller\SPA\front;

use Services\IndexHandler;
use Core\Request;

/**
 * @GET{/}
 * @GET{/catalog}
 */
class index extends IndexHandler{}

/**
 * @GET{/signin}
 * @GET{/signup}
 * @GET{/validate}
 * @GET{/admin} // TODO: move to connected class when admin panel will be ready
 */
class guest extends IndexHandler{
    public function checkers(Request $request): array
    {
        return [
            ["page/onlyGuest", $request->getCookie("token") ?? null]
        ];
    }
}

/**
 * 
 */
class connected extends IndexHandler{
    public function checkers(Request $request): array
    {
        return [
            ["page/onlyConnected", $request->getCookie("token") ?? ""]
        ];
    }
}
