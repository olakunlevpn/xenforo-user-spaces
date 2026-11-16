<?php

namespace Olakunlevpn\UserSpaces;

use XF\Container;
use XF\Http\Request;
use XF\Mvc\RouteMatch;
use XF\Mvc\Router;

class Listener
{
    public static function routerPublicSetup(Container $container, Router &$router): void
    {
        $router->addRoutePreProcessor('olakunlevpn_user_spaces_at', function(Router $router, $path, RouteMatch $match, ?Request $request = null)
        {
            if (preg_match('#^@([a-zA-Z0-9_\.\-]+)(?:/(.*))?$#', $path, $matches))
            {
                $username = $matches[1];
                $subAction = $matches[2] ?? '';
                $rewrite = 'u/' . $username . '/' . ($subAction ? $subAction . '/' : '');
                $match->setPathRewrite($rewrite);
                return $match;
            }

            return $match;
        });
    }
}
