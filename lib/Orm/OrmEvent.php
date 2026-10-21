<?php
/**
 * Amed Framework
 * Orm Event Handler
 */

class OrmEvent
{
    private static $events = [];


    public static function register($name, $callback)
    {
        self::$events[$name][] = $callback;
    }

    public static function run($name)
    {
        print_r(self::$events[$name]);
        if (isset(self::$events[$name])) {
            foreach (self::$events[$name] as $callback) {
                if (is_callable($callback)) {
                    call_user_func($callback);
                }
            }
        }
    }
}
