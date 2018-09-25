<?php

namespace App\Modules\Hookah\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Hookah\Model\Line;
use App\Modules\Hookah\Model\Vendor;
use App\Modules\Logger\Controllers\LoggerController;
use App\Interfaces\ModuleInterface;
use App\Exceptions\CustomException;
use App\Modules\User\Model\User;
use Illuminate\Http\Request;

class LineController extends Controller implements ModuleInterface
{
    /**
     * Название модуля
     *
     * @var string
     */
    public $moduleName = 'Hookah';

    /**
     * Вернёт код модуля
     *
     * @return string
     */
    public function getModuleName()
    {
        return $this->moduleName;
    }

    /**
     * Добавление линейки табака
     *
     * @param Request $request
     *
     * @return mixed
     * @throws CustomException
     * @throws \App\Exceptions\CustomDBException
     */
    public function postLine(Request $request)
    {
        $Line = Line::post($request);
        if ($Line) {
            LoggerController::write(
                $this->getModuleName(), 'hookah_postline',
                null, 'line', $Line->id,
                ['data' => self::modelFilter($Line, Line::fields())]
            );
        }

        $Line = $this->getLineById($Line->id, false);
        return parent::response($request->all(), $Line, 200);
    }

    /**
     * Изменение линейки табака
     *
     * @param Request $request
     *
     * @return mixed
     * @throws CustomException
     */
    public function putLine(Request $request)
    {
        $Line = ['old' => false, 'new' => false];

        $Line = Line::put($request);

        if (isset($Line['old']) && isset($Line['new'])) {

            LoggerController::write(
                $this->getModuleName(), 'hookah_putline',
                null, 'line', $Line['new']->id,
                ['data' => self::modelFilter($Line['new'], Line::fields())],
                [$Line['old'], $Line['new']]
            );
        }

        return parent::response($request->all(), $Line['new'], 200);
    }

    /**
     * Получение списка линеек табака
     *
     * @param Request $request
     *
     * @return mixed
     * @throws \App\Exceptions\CustomDBException
     */
    public function getLines(Request $request)
    {
        User::can('hookah_viewline', true);
        return parent::response(
            $request->all(),
            parent::dbGet(new Line(), $request, [], ['vendor' => new Vendor()]),
            200
        );
    }

    /**
     * Получение линейки табака по ID
     *
     * @param      $id
     * @param bool $json
     *
     * @return mixed
     * @throws CustomException
     * @throws \App\Exceptions\CustomDBException
     */
    public function getLineById($id, $json = true)
    {
        User::can('hookah_viewline', true);
        $Line = Line::where('id', $id)->with(['vendor'])->first();
        if (!$Line) throw new CustomException(['id' => $id], [], 404, 'Производитель не найден');

        return ($json) ? parent::response(['id' => $id], $Line, 200) : $Line;
    }

    /**
     * Удаление линейки табака по ID
     *
     * @param $id
     *
     * @return mixed
     * @throws CustomException
     * @throws \App\Exceptions\CustomDBException
     */
    public function deleteLineById($id)
    {
        User::can('hookah_deleteline', true);

        $Line = $this->getLineById($id, false);

        LoggerController::write(
            $this->getModuleName(), 'hookah_deleteline',
            null, 'line', $Line->id,
            ['data' => self::modelFilter($Line, Line::fields())]
        );

        return parent::response(['id' => $id], $Line->delete(), 200);
    }
}