<?php
if (!function_exists('app')) {
    function app(string $class = null)
    {
        global $app_container;

        if ($class === null) {
            return $app_container;
        }

        return $app_container->get($class);
    }
}