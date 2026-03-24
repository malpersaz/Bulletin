<?php
/**
 * Created by JetBrains PhpStorm.
 * User: kral
 * Date: 4/18/11
 * Time: 8:59 PM
 * To change this template use File | Settings | File Templates.
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
