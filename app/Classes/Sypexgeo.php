<?php

namespace App\Classes;

use GuzzleHttp\Client as HttpClient;
use League\Flysystem\Exception;

/**
 * Класс для определения города пользователя по ip
 *
 * @package App\Classes
 */
class Sypexgeo {

    /**
     * URL для запросов
     *
     * @var string
     */
    private static $_url = 'http://api.sypexgeo.net/json';

    /**
     * Локация по дефолту
     *
     * @var array
     */
    public static $default_location = [
        "ip"   => "188.168.170.96",
        "city" => [
            "id"         => 2119441,
            "lat"        => 46.954070000000002,
            "lon"        => 142.73603,
            "name_ru"    => "Южно-Сахалинск",
            "name_en"    => "Yuzhno-Sakhalinsk",
            "name_de"    => "Juschno-Sachalinsk",
            "name_fr"    => "Ioujno-Sakhalinsk",
            "name_it"    => "Južno-Sachalinsk",
            "name_es"    => "Yuzhno-Sajalinsk",
            "name_pt"    => "Iujno-Sakhalinsk",
            "okato"      => "64401",
            "vk"         => 167,
            "population" => 192734
        ],
        "region" => [
            "id"       => 2121529,
            "lat"      => 50,
            "lon"      => 143,
            "name_ru"  => "Сахалинская область",
            "name_en"  => "Sakhalinskaya Oblast'",
            "name_de"  => "Oblast Sachalin",
            "name_fr"  => "Oblast de Sakhaline",
            "name_it"  => "Oblast' di Sachalin",
            "name_es"  => "Óblast de Sajalín",
            "name_pt"  => "Oblast de Sacalina",
            "iso"      => "RU-SAK",
            "timezone" => "Asia/Sakhalin",
            "okato"    => "64",
            "auto"     => "65",
            "vk"       => 1153840,
            "utc"      => 11
        ],
        "country" => [
            "id"         => 185,
            "iso"        => "RU",
            "continent"  => "EU",
            "lat"        => 60,
            "lon"        => 100,
            "name_ru"    => "Россия",
            "name_en"    => "Russia",
            "name_de"    => "Russland",
            "name_fr"    => "Russie",
            "name_it"    => "Russia",
            "name_es"    => "Rusia",
            "name_pt"    => "Rússia",
            "timezone"   => "Europe/Moscow",
            "area"       => 17100000,
            "population" => 140702000,
            "capital_id" => 524901,
            "capital_ru" => "Москва",
            "capital_en" => "Moscow",
            "cur_code"   => "RUB",
            "phone"      => "7",
            "neighbours" => "GE,CN,BY,UA,KZ,LV,PL,EE,LT,FI,MN,NO,AZ,KP",
            "vk"         => 1,
            "utc"        => 3
        ],
        "error"     => "",
        "request"   => -1,
        "created"   => "2018.06.30",
        "timestamp" => 1530387484
    ];

    /**
     * Функция для определеня позиции по ip
     *
     * @param string $ip - ip пользователя
     *
     * @return mixed|\Psr\Http\Message\StreamInterface
     */
    public static function get($ip = '188.168.170.96')
    {
        try {
            $client = new HttpClient();
            $res = $client->request('GET', self::$_url . '/' . $ip);
            $result = $res->getBody();
            if (!is_array($result)) {
                $result = json_decode($result, true);
            }
        } catch (Exception $e) {
            $result = self::$default_location;
        }

        return $result;
    }
}