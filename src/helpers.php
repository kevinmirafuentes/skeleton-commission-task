<?php 

if (!function_exists('config')) {
    function config($key) {
        $configs = include(__DIR__ . '/configs.php');
        return dot($configs)->get($key);
    }
}
