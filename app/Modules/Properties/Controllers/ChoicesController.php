<?php

namespace App\Modules\Properties\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Logger\Controllers\LoggerController;
use App\Modules\Properties\Model\Properties;
use App\Modules\Properties\Model\PropertiesChoices;
use App\Modules\Properties\Model\PropertiesValues;
use App\Modules\Module\Model\Module;
use App\Modules\Module\Controllers\ModuleController as ModuleCTR;
use App\Modules\User\Controllers\UserController;
use App\Interfaces\ModuleInterface;
use App\Exceptions\CustomException;
use App\Exceptions\CustomDBException;
use App\Modules\User\Model\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;


/**
 * Класс для работы с вариантами выбьра свойств сущностей в модулях
 *
 * @category Laravel_Modules
 * @package  App\Modules\User\Controllers
 * @author   Kukanov Oleg <speed.live@mail.ru>
 * @license  http://opensource.org/licenses/gpl-license.php GNU Public License
 * @link     http://crm.lets-code.ru/
 */
class ChoicesController extends Controller implements ModuleInterface
{
    /**
     * Название модуля
     *
     * @var string
     */
    public $moduleName = 'Properties';

    /**
     * Вернёт код модуля
     *
     * @return string
     */
    public function getModuleName()
    {
        return $this->moduleName;
    }


    public function postPropertyChoicesArray(Request $request)
    {
        User::can('properties_add_property_choices', true);
        $data = $request->only('values', 'property_id');
        $result = [];

        //проверим, является ли свойство типом, у котрого есть возможность выбора
        if (!Properties::isSelecting($data['property_id'])) {
            throw new CustomException(
                $data, [], 400,
                'Property with id "'.$data['property_id'] . '" is not selecting.'
            );
        }

        foreach ($data['values'] as $value) {
            $dataTemp = $value;
            $dataTemp['property_id'] = $data['property_id'];
            $Choices = new PropertiesChoices();
            $issetChoices = false;
            if ($Choices->choicesValidator($dataTemp)) {
                if (array_key_exists('id', $dataTemp) && $dataTemp['id']) {
                    //если значение с таким кодом уже существует у указанного свйоства
                    $issetChoices = PropertiesChoices::where('id', $dataTemp['id'])
                        ->where('property_id', $data['property_id'])->first();
                }
                if ($issetChoices) {
                    $issetChoices->name = $dataTemp['name'];
                    $issetChoices->code = $dataTemp['code'];
                    $issetChoices->sort = $dataTemp['sort'];
                    $issetChoices->property()->associate($data['property_id']);
                    $issetChoices->save();
                    $result[] = $issetChoices;

                    //логируем действие
                    LoggerController::write(
                        $this->getModuleName(), 'add_property_choices',
                        null, 'choice', $issetChoices->id,
                        ['data' => self::modelFilter($issetChoices, PropertiesChoices::fields())]
                    );

                } else {
                    $Choices->name = $dataTemp['name'];
                    $Choices->code = $dataTemp['code'];
                    $Choices->sort = $dataTemp['sort'];
                    $Choices->property()->associate($data['property_id']);
                    $Choices->save();
                    $result[] = $Choices;

                    //логируем действие
                    LoggerController::write(
                        $this->getModuleName(), 'properties_add_property_choices',
                        null, 'choice', $Choices->id,
                        ['data' => self::modelFilter($Choices, PropertiesChoices::fields())]
                    );
                }
            }
        }


        return parent::response($data, $result, 200);
    }

    /**
     * Добавляет новый вариант выбора для свойтва
     * Параметры POST запроса:
     * string  name         - Значение (название) варианта
     * string  code         - Символьный код варианта
     * int     sort         - Сортировка
     * int     property_id  - id свойства, к которому относится вариант
     *
     * @param Request $request - экземпляр Request
     *
     * @return mixed
     * @throws CustomException
     */
    public function postPropertyChoices(Request $request)
    {
        User::can('properties_add_property_choices', true);
        $data = $request->only('name', 'code', 'sort', 'property_id');

        $Choices = new PropertiesChoices();
        $validate = $Choices->choicesValidator($data);
        if ($validate) {

            //проверим, является ли свойство типом, у котрого есть возможность выбора
            if (!Properties::isSelecting($data['property_id'])) {
                throw new CustomException(
                    'Choice already defined', [], 400,
                    'Property with id "'.$data['property_id'] . '" is not selecting.'
                );
            }

            //если значение с таким кодом уже существует у указанного свйоства
            $issetChoices = PropertiesChoices::where('code', $data['code'])
                ->where('property_id', $data['property_id'])
                ->first();
            if ($issetChoices) {
                //выбрасываем исключение
                throw new CustomException(
                    'Choice already defined', [], 400,
                    'Choice "'.$data['code'].'" is already defined on property ' .
                    ' with id "'.$data['property_id'] . '"'
                );
            }

            $Choices->name = $data['name'];
            $Choices->code = $data['code'];
            $Choices->sort = $data['sort'];
            $Choices->property()->associate($data['property_id']);
            $Choices->save();

            //логируем действие
            LoggerController::write(
                $this->getModuleName(), 'properties_add_property_choices',
                null, 'choice', $Choices->id,
                ['data' => self::modelFilter($Choices, PropertiesChoices::fields())]
            );
        }

        return parent::response($data, $Choices, 200);

    }

    /**
     * Обновляет существующий вариант выбора для свойтва
     * Параметры POST запроса:
     * string  name         - Значение (название) варианта
     * string  code         - Символьный код варианта
     * int     sort         - Сортировка
     * int     property_id  - id свойства, к которому относится вариант
     * int     id           - id варианта, который нужно обновить
     *
     * @param Request $request - экземпляр Request
     *
     * @return mixed
     * @throws CustomException
     */
    public function putPropertyChoices(Request $request)
    {
        User::can('properties_put_property_choices', true);
        $data = $request->only('name', 'code', 'sort', 'property_id', 'id');
        $data['update_id'] = array_key_exists('id', $data) && false;

        $Choices = new PropertiesChoices();
        $validate = $Choices->choicesValidator($data);
        if ($validate) {

            //проверим, является ли свойство типом, у котрого есть возможность выбора
            if (!Properties::isSelecting($data['property_id'])) {
                throw new CustomException(
                    'Choice already defined', [], 400,
                    'Property with id "'.$data['property_id'] . '" is not selecting.'
                );
            }

            //если значение с таким кодом уже существует у указанного свйоства
            //за исключением того, что мы изменяем
            $issetChoices = PropertiesChoices::where('code', $data['code'])
                ->where('property_id', $data['property_id'])
                ->where('id', '!=', $data['id'])
                ->first();
            if ($issetChoices) {
                //выбрасываем исключение
                throw new CustomException(
                    'Choice already defined', [], 400,
                    'Choice "'.$data['code'].'" is already defined on property ' .
                    ' with id "'.$data['property_id'] . '"'
                );
            }


            //проверим, существует ли такое свойство
            $Choices = PropertiesChoices::where('id', $data['id'])->first();
            //если свойства нет выбрасываем исключение
            if (!$Choices) {
                throw new CustomException(
                    'Choice is not defined', [], 400,
                    'Choice with id "'.$data['id'].'" is not defined'
                );
            }
            $oldChoices = clone $Choices;
            $Choices->name = $data['name'];
            $Choices->code = $data['code'];
            $Choices->sort = $data['sort'];
            $Choices->property()->associate($data['property_id']);
            $Choices->save();

            //логируем действие
            LoggerController::write(
                $this->getModuleName(), 'properties_put_property_choices',
                null, 'choice', $Choices->id,
                ['data' => self::modelFilter($Choices, PropertiesChoices::fields())],
                [$oldChoices, $Choices]
            );
        }

        return parent::response($data, $Choices, 200);

    }

    /**
     * Удаляет вариант свйоства по переданному ID
     *
     * @param integer $prop_id - Идентификатор свойства
     * @param integer $id      - Идентификатор варианта свойства
     *
     * @return mixed
     * @throws CustomException
     */
    public function deletePropertyChoices($prop_id, $id)
    {
        User::can('properties_delete_property_choices', true);

        //проверим, существует ли такое свойство
        $Choices = PropertiesChoices::where('property_id', $prop_id)
            ->where('id', $id)
            ->first();
        //если свойства нет выбрасываем исключение
        if (!$Choices) {
            throw new CustomException(
                'Choices is not defined', [], 400,
                'Choices with id "'.$id.'" is not defined'
            );
        }

        //логируем действие
        LoggerController::write(
            $this->getModuleName(), 'properties_delete_property_choices',
            null, 'choice', $Choices->id,
            ['data' => self::modelFilter($Choices, PropertiesChoices::fields())]
        );

        return parent::response(['id' => $id], $Choices->delete(), 200);

    }

    /**
     * Возвращает список вариантов выбора для свойтсва
     *
     * @param integer $id - ID свойства, для которого нужны варианты
     *
     * @return mixed
     * @throws CustomDBException
     * @throws CustomException
     */
    public function getPropertyChoices($id)
    {
        User::can('properties_view_property_choices', true);

        $result = PropertiesChoices::where('property_id', $id)->get();
        return parent::response(['id' => $id], $result, 200);
    }


    /**
     * Возвращает список вариантов выбора для свойтсва
     *
     * @param integer $prop_id - ID свойства, к которому принадлежит
     * @param integer $id      - ID варианта, который надо получить
     *
     * @return mixed
     * @throws CustomDBException
     * @throws CustomException
     */
    public function getPropertyChoice($prop_id, $id)
    {
        User::can('properties_view_property_choices', true);

        $result = PropertiesChoices::where('property_id', $prop_id)
            ->where('id', $id)
            ->get();

        if (!count($result)) {
            throw new CustomDBException(
                'Choices not found', [], 404,
                'Choices on Property width id "'.$id.'" is not found'
            );
        }

        return parent::response(
            ['id' => $id, 'property_id' => $prop_id], $result, 200
        );
    }
}