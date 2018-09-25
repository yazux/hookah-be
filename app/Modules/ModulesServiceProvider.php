<?php

namespace App\Modules;

use App\Http\Controllers\State;
use Illuminate\Support\ServiceProvider;
use App\Http\Controllers\Controller as MainController;
use App\Exceptions\CustomException;
use App\Modules\Module\Model\Module;
use App\Modules\User\Controllers\UserController;

/**
 * Класс - автозагрузчик модулей
 *
 * @category Laravel_Modules
 * @package  App\Modules\User\Controllers
 * @author   Kukanov Oleg <speed.live@mail.ru>
 * @license  http://opensource.org/licenses/gpl-license.php GNU Public License
 * @link     http://crm.lets-code.ru/
 */
class ModulesServiceProvider extends ServiceProvider
{
    /**
     * Запускает автозагрузку модулей
     *
     * @return null
     * @throws CustomException
     */
    public function boot()
    {
        //получаем список модулей, которые надо подгрузить
        $modules = config("module.modules");

        if (!$modules) {
            throw new CustomException(
                '', false, 500,
                'Modules config file "config/module.php" is not find'
            );
        }

        while (list(,$module) = each($modules)) {
            //преобразуем первый символ имени модуля к верхнему регистру
            $module = ucfirst($module);
            //Подключаем роуты для модуля
            if (file_exists(__DIR__.'/'.$module.'/Routes/routes.php')) {
                $this->loadRoutesFrom(__DIR__.'/'.$module.'/Routes/routes.php');
            }
            
            //Загружаем View, пример: view('Test::admin')
            if (is_dir(__DIR__.'/'.$module.'/Views')) {
                $this->loadViewsFrom(__DIR__.'/'.$module.'/Views', $module);
            }

            //Подгружаем миграции
            if (is_dir(__DIR__.'/'.$module.'/Migration')) {
                $this->loadMigrationsFrom(__DIR__.'/'.$module.'/Migration');
            }
                
            //Подгружаем переводы, пример: trans('Test::messages.welcome')
            if (is_dir(__DIR__.'/'.$module.'/Lang')) {
                $this->loadTranslationsFrom(
                    __DIR__.'/'.$module.'/Lang', $module
                );
            }
        }
        $this->checkModules();
    }

    /**
     * Проверяет модули на корректность
     * (проверяет активны ли обязательные модули и их
     * зависимости, активны ли зависимости активных модулей,
     * есть ли у текущего пользователя доступ к активным модулям)
     *
     * @return bool
     * @throws CustomException
     */
    public function checkModules()
    {
        //получаем список модулей, которые надо подгрузить
        $modules = config("module.modules");
        //получим список обязательных модулей
        $require_modules = config("module.require_modules");

        if (!$modules || !$require_modules) {
            throw new CustomException(
                '', false, 500,
                'Modules or require modules is not defined in config file'
            );
        }

        $Controller = new MainController();
        while (list(, $module) = each($modules)) {
            //метод проверит все ли модули внесены в БД
            //если есть новые, то добавит их как не активные
            //добавление происходит на основе данных ин файла config.json
            $Controller->checkModule($module);
        }

        //получим все модули из БД
        $DBModules = MainController::arrayToValueKey('code', Module::get());
        //проверим их
        foreach ($DBModules as &$DBModule) {

            $ModuleConfig['user_access'] = false;
            $ModuleConfig['load'] = false;

            //если модуль обязательныЙ, но при этом он не активен,
            //то выбрасываем исключение
            if (array_key_exists($DBModule['code'], $require_modules)
                && !$DBModule['active']
            ) {
                throw new CustomException(
                    '', false, 500,
                    'Module "'.$DBModule['code'].'" must be active.'
                );
            }

            //получим конфиги модуля
            $ModuleConfig = $Controller->getConfig($DBModule['code']);
            $DBModule = array_merge(
                $DBModule, $ModuleConfig
            );


            //если модуль активен и у него есть зависимости
            if ($DBModule['active'] && count($DBModule['dependencies']) > 0) {
                //проверям чтобы они все были активны
                foreach ($DBModule['dependencies'] as $depend) {
                    //если не находим модуль в списке модулей
                    if (!array_key_exists(strtolower($depend), $DBModules)) {
                        //выбрасываем исключение с сообщением о том, что
                        //модуль не найден в списке модулей
                        throw new CustomException(
                            '',
                            [
                                'finding_module' => strtolower($depend),
                                'active_modules' => array_keys($DBModules),
                            ],
                            500,
                            'Module "'.$depend.'" (dependence of "'.
                            $DBModule['code'].'") is not defined.'
                        );
                    }


                    //если модуль не активен, то выбрасываем исключение
                    if (!$DBModules[$depend]['active']) {
                        throw new CustomException(
                            '',
                            [
                              'module' => $DBModules[$depend]
                            ],
                            500,
                            'Module "'.$depend.'" must be active.'
                        );
                    }
                }
            }

            $User = new UserController();
            //проверим, имеет ли пользователь доступ к модулю
            //и установим флаги доступа и факта загрузки модуля
            if ($User->can($DBModule['code'].'_access')) {
                $ModuleConfig['user_access'] = true;
                $ModuleConfig['load'] = true;
            }

            //установим в состояние конфиг модуля с отмеченными доступами
            $State = State::getInstance();
            $State->setConfig($ModuleConfig, $DBModule['code']);

        } unset($DBModule);

        return true;
    }

    /**
     * Регистратор
     *
     * @return null
     */
    public function register()
    {
    }

}