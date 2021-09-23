<?php 
namespace App\CommissionTask\Repositories;

trait SingletonRepository 
{
    public static $instance;

    public static function getInstance()
    {
        if (!self::$instance) {
            self::$instance = new self;
        }
        return self::$instance;
    }
}