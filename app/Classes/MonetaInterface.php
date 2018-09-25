<?php
namespace App\Classes;

interface MonetaInterface
{
    public static function getService();
    public static function gs();
    public static function PATR($requestObject = null, $requireArguments = [], $arguments = []);
    public static function pushArgumentsToRequest($requestObject = null, $requireArguments = [], $arguments = []);
    public static function request($method, $arguments);
}