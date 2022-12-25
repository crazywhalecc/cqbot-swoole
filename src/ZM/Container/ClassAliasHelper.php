<?php

declare(strict_types=1);

namespace ZM\Container;

/**
 * 旨在帮助识别 class_alias 定义的类别名
 */
class ClassAliasHelper
{
    /**
     * @var array<string, class-string>
     */
    private static array $aliases = [];

    /**
     * 添加一个类别名
     *
     * @param string $class 类名
     * @param string $alias 别名
     */
    public static function addAlias(string $class, string $alias): void
    {
        class_alias($class, $alias);
        self::$aliases[$alias] = $class;
    }

    /**
     * 判断一个类是否是别名
     *
     * @param string $alias 别名
     */
    public static function isAlias(string $alias): bool
    {
        return isset(self::$aliases[$alias]);
    }

    /**
     * 获取别名定义信息
     *
     * @param  string            $alias 别名
     * @return null|class-string 如果没有定义则返回 null
     */
    public static function getAlias(string $alias): ?string
    {
        return self::$aliases[$alias] ?? null;
    }

    /**
     * 根据类名获取别名
     *
     * @param  string            $class 类名
     * @return null|class-string 如果没有定义则返回 null
     */
    public static function getAliasByClass(string $class): ?string
    {
        return array_search($class, self::$aliases, true) ?: null;
    }

    /**
     * 根据别名获取类名
     *
     * @param  string $alias 别名
     * @return string 类名
     */
    public static function getClass(string $alias): string
    {
        return self::$aliases[$alias] ?? $alias;
    }

    /**
     * 获取所有别名定义信息
     */
    public static function getAllAlias(): array
    {
        return self::$aliases;
    }
}
