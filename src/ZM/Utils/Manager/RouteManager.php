<?php

declare(strict_types=1);

namespace ZM\Utils\Manager;

use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use ZM\Annotation\Http\Controller;
use ZM\Annotation\Http\RequestMapping;
use ZM\Console\Console;
use ZM\Http\StaticFileHandler;

/**
 * 路由管理器，2.5版本更改了命名空间
 * Class RouteManager
 * @since 2.3.0
 */
class RouteManager
{
    /** @var null|RouteCollection */
    public static $routes;

    public static function importRouteByAnnotation(RequestMapping $vss, $method, $class, $methods_annotations)
    {
        if (self::$routes === null) {
            self::$routes = new RouteCollection();
        }

        // 拿到所属方法的类上面有没有控制器的注解
        $prefix = '';
        foreach ($methods_annotations as $annotation) {
            if ($annotation instanceof Controller) {
                $prefix = $annotation->prefix;
                break;
            }
        }
        $tail = trim($vss->route, '/');
        $route_name = $prefix . ($tail === '' ? '' : '/') . $tail;
        Console::debug('添加路由：' . $route_name);
        $route = new Route($route_name, ['_class' => $class, '_method' => $method]);
        $route->setMethods($vss->request_method);

        self::$routes->add(md5($route_name), $route);
    }

    public static function addStaticFileRoute($route, $path)
    {
        $tail = trim($route, '/');
        $route_name = ($tail === '' ? '' : '/') . $tail . '/{filename}';
        Console::debug('添加静态文件路由：' . $route_name);
        $route = new Route($route_name, ['_class' => __CLASS__, '_method' => 'onStaticRoute'], [], compact('path'));

        self::$routes->add(md5($route_name), $route);
    }

    public function onStaticRoute(array $params)
    {
        if (($path = self::$routes->get($params['_route'])->getOption('path')) === null) {
            ctx()->getResponse()->endWithStatus(404);
            return false;
        }
        unset($params['_route']);
        $obj = array_shift($params);
        return new StaticFileHandler($obj, $path);
    }
}
