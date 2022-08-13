<?php

declare(strict_types=1);

use OneBot\V12\Object\MessageSegment;
use Psr\Log\LoggerInterface;
use ZM\Container\Container;
use ZM\Container\ContainerInterface;
use ZM\Context\Context;
use ZM\Logger\ConsoleLogger;
use ZM\Middleware\MiddlewareHandler;

// 防止重复引用引发报错
if (function_exists('zm_internal_errcode')) {
    return;
}

/**
 * 根据具体操作系统替换目录分隔符
 *
 * @param string $dir 目录
 */
function zm_dir(string $dir): string
{
    if (strpos($dir, 'phar://') === 0) {
        return $dir;
    }
    return str_replace('/', DIRECTORY_SEPARATOR, $dir);
}

/**
 * 获取内部错误码
 *
 * @param int|string $code
 */
function zm_internal_errcode($code): string
{
    return "[ErrCode:{$code}] ";
}

function zm_instance_id(): string
{
    if (defined('ZM_INSTANCE_ID')) {
        return ZM_INSTANCE_ID;
    }
    if (!defined('ZM_START_TIME')) {
        define('ZM_START_TIME', microtime(true));
    }
    $instance_id = dechex(crc32(strval(ZM_START_TIME)));
    define('ZM_INSTANCE_ID', $instance_id);
    return ZM_INSTANCE_ID;
}

/**
 * 助手方法，返回一个 Logger 实例
 */
function logger(): LoggerInterface
{
    global $ob_logger;
    if ($ob_logger === null) {
        return new ConsoleLogger();
    }
    return $ob_logger;
}

/**
 * 判断传入的数组是否为关联数组
 */
function is_assoc_array(array $array): bool
{
    return !empty($array) && array_keys($array) !== range(0, count($array) - 1);
}

function ctx(): Context
{
    return \container()->get('ctx');
}

/**
 * 构建消息段的助手函数
 *
 * @param string $type 类型
 * @param array  $data 字段
 */
function segment(string $type, array $data = []): MessageSegment
{
    return new MessageSegment($type, $data);
}

/**
 * 中间件操作类的助手函数
 */
function middleware(): MiddlewareHandler
{
    return MiddlewareHandler::getInstance();
}

// ////////////////// 容器部分 //////////////////////

/**
 * 获取容器（请求级）实例
 */
function container(): ContainerInterface
{
    return Container::getInstance();
}

/**
 * 解析类实例（使用容器）
 *
 * @template T
 * @param  class-string<T> $abstract
 * @return Closure|mixed|T
 * @noinspection PhpDocMissingThrowsInspection
 */
function resolve(string $abstract, array $parameters = [])
{
    /* @noinspection PhpUnhandledExceptionInspection */
    return Container::getInstance()->make($abstract, $parameters);
}

/**
 * 获取容器实例
 *
 * @template T
 * @param  null|class-string<T>               $abstract
 * @return Closure|ContainerInterface|mixed|T
 */
function app(string $abstract = null, array $parameters = [])
{
    if (is_null($abstract)) {
        return container();
    }

    return resolve($abstract, $parameters);
}