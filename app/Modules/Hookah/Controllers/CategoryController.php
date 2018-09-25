<?php

namespace App\Modules\Hookah\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Hookah\Model\Category;
use App\Modules\Logger\Controllers\LoggerController;
use App\Interfaces\ModuleInterface;
use App\Exceptions\CustomException;
use App\Modules\User\Model\User;
use Illuminate\Http\Request;

class CategoryController extends Controller implements ModuleInterface
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
     * Добавление категории
     *
     * @param Request $request
     *
     * @return mixed
     * @throws CustomException
     * @throws \App\Exceptions\CustomDBException
     */
    public function postCategory(Request $request)
    {
        $Category = Category::post($request);
        if ($Category) {
            LoggerController::write(
                $this->getModuleName(), 'hookah_postcategory',
                null, 'category', $Category->id,
                ['data' => self::modelFilter($Category, Category::fields())]
            );
        }

        $Category = $this->getCategoryById($Category->id, false);
        return parent::response($request->all(), $Category, 200);
    }

    /**
     * Изменение категории
     *
     * @param Request $request
     *
     * @return mixed
     * @throws CustomException
     */
    public function putCategory(Request $request)
    {
        $Category = ['old' => false, 'new' => false];

        $Category = Category::put($request);

        if (isset($Category['old']) && isset($Category['new'])) {

            LoggerController::write(
                $this->getModuleName(), 'hookah_putcategory',
                null, 'category', $Category['new']->id,
                ['data' => self::modelFilter($Category['new'], Category::fields())],
                [$Category['old'], $Category['new']]
            );
        }

        return parent::response($request->all(), $Category['new'], 200);
    }

    /**
     * Получение списка категорий
     *
     * @param Request $request
     *
     * @return mixed
     * @throws \App\Exceptions\CustomDBException
     */
    public function getCategories(Request $request)
    {
        User::can('hookah_viewcategory', true);
        return parent::response(
            $request->all(),
            parent::dbGet(new Category(), $request, [], []),
            200
        );
    }

    /**
     * Получение категории по ID
     *
     * @param      $id
     * @param bool $json
     *
     * @return mixed
     * @throws CustomException
     * @throws \App\Exceptions\CustomDBException
     */
    public function getCategoryById($id, $json = true)
    {
        User::can('hookah_viewcategory', true);
        $Category = Category::where('id', $id)->first();
        if (!$Category) throw new CustomException(['id' => $id], [], 404, 'Категория не найден');

        return ($json) ? parent::response(['id' => $id], $Category, 200) : $Category;
    }

    /**
     * Удаление категории по ID
     *
     * @param $id
     *
     * @return mixed
     * @throws CustomException
     * @throws \App\Exceptions\CustomDBException
     */
    public function deleteCategoryById($id)
    {
        User::can('hookah_deletecategory', true);

        $Category = $this->getCategoryById($id, false);

        LoggerController::write(
            $this->getModuleName(), 'hookah_deletecategory',
            null, 'category', $Category->id,
            ['data' => self::modelFilter($Category, Category::fields())]
        );

        return parent::response(['id' => $id], $Category->delete(), 200);
    }
}