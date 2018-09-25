<?php

namespace App\Interfaces;

/**
 * Интерфейс всех моделей модулей
 *
 * @category Laravel_Modules
 * @package  App\Modules\User\Controllers
 * @author   Kukanov Oleg <speed.live@mail.ru>
 * @license  http://opensource.org/licenses/gpl-license.php GNU Public License
 * @link     http://crm.lets-code.ru/
 */
interface ModuleModelInterface
{
    /**
     * Вернёт поля таблицы, доступные для выборки
     *
     * @return mixed
     */
    public static function fields();
}