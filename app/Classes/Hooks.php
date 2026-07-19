<?php

namespace App\Classes;

class Hooks
{
    protected static array $actions = [];
    protected static array $filters = [];

    public static function do_action(string $tag, mixed ...$args): void
    {
        if (!isset(self::$actions[$tag])) {
            return;
        }

        $callbacks = self::$actions[$tag];
        ksort($callbacks);

        foreach ($callbacks as $priorityCallbacks) {
            foreach ($priorityCallbacks as $callback) {
                call_user_func_array($callback, $args);
            }
        }
    }

    public static function add_action(string $tag, callable $callback, int $priority = 10): void
    {
        self::$actions[$tag][$priority][] = $callback;
    }

    public static function apply_filters(string $tag, mixed $value, mixed ...$args): mixed
    {
        if (!isset(self::$filters[$tag])) {
            return $value;
        }

        $callbacks = self::$filters[$tag];
        ksort($callbacks);

        foreach ($callbacks as $priorityCallbacks) {
            foreach ($priorityCallbacks as $callback) {
                $value = call_user_func_array($callback, array_merge([$value], $args));
            }
        }

        return $value;
    }

    public static function add_filter(string $tag, callable $callback, int $priority = 10, int $acceptedArgs = 1): void
    {
        self::$filters[$tag][$priority][] = $callback;
    }

    public static function has_action(string $tag): bool
    {
        return isset(self::$actions[$tag]) && !empty(self::$actions[$tag]);
    }

    public static function has_filter(string $tag): bool
    {
        return isset(self::$filters[$tag]) && !empty(self::$filters[$tag]);
    }

    public static function remove_action(string $tag, callable $callback, int $priority = 10): bool
    {
        if (!isset(self::$actions[$tag][$priority])) {
            return false;
        }

        foreach (self::$actions[$tag][$priority] as $index => $registeredCallback) {
            if ($registeredCallback === $callback) {
                unset(self::$actions[$tag][$priority][$index]);
                return true;
            }
        }

        return false;
    }

    public static function remove_filter(string $tag, callable $callback, int $priority = 10): bool
    {
        if (!isset(self::$filters[$tag][$priority])) {
            return false;
        }

        foreach (self::$filters[$tag][$priority] as $index => $registeredCallback) {
            if ($registeredCallback === $callback) {
                unset(self::$filters[$tag][$priority][$index]);
                return true;
            }
        }

        return false;
    }
}
