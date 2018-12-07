<?php

namespace App\Modules\Hookah\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Hookah\Model\Bookmark;
use App\Modules\Hookah\Model\Mix;
use App\Modules\Logger\Controllers\LoggerController;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use App\Interfaces\ModuleInterface;
use App\Exceptions\CustomException;
use App\Modules\User\Model\User;
use Illuminate\Http\Request;

class BookmarkController extends Controller implements ModuleInterface
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
     * Добавление закладки
     *
     * @param Request $request
     *
     * @return mixed
     * @throws CustomException
     * @throws \App\Exceptions\CustomDBException
     */
    public function postBookmark(Request $request)
    {
        $Bookmark = Bookmark::post($request);
        if ($Bookmark) {
            LoggerController::write(
                $this->getModuleName(), 'hookah_postbookmark',
                null, 'bookmark', $Bookmark->id,
                ['data' => self::modelFilter($Bookmark, Bookmark::fields())]
            );
        }

        $Bookmark = $this->getBookmarkById($Bookmark->id, false);
        return parent::response($request->all(), $Bookmark, 200);
    }

    /**
     * Изменение закладки
     *
     * @param Request $request
     *
     * @return mixed
     * @throws CustomException
     */
    public function putBookmark(Request $request)
    {
        $Bookmark = ['old' => false, 'new' => false];
        $Bookmark = Bookmark::put($request);
        if (isset($Bookmark['old']) && isset($Bookmark['new'])) {
            LoggerController::write(
                $this->getModuleName(), 'hookah_putbookmark',
                null, 'bookmark', $Bookmark['new']->id,
                ['data' => self::modelFilter($Bookmark['new'], Bookmark::fields())],
                [$Bookmark['old'], $Bookmark['new']]
            );
        }
        return parent::response($request->all(), $Bookmark['new'], 200);
    }

    /**
     * Получение списка закладок
     *
     * @param Request $request
     *
     * @return mixed
     * @throws \App\Exceptions\CustomDBException
     */
    public function getBookmarks(Request $request)
    {
        User::can('hookah_viewbookmark', true);
        $Bookmarks = parent::dbGet(new Bookmark(), $request, [], [
            //'user' => new User(),
            'mix' => ['model' => new Mix(), 'with' => ['category', 'tobacco']]
        ]);


        if ($Bookmarks instanceof LengthAwarePaginator) {
            $Bookmarks->getCollection()
                ->transform(function ($item) {
                    foreach ($item->mix->tobacco as $tobacco) {
                        $tobacco->vendor = $tobacco->vendor()->first();
                    }
                    //$item->mix->tobacco->vendor = $item->mix->tobacco->vendor->first();
                    return $item;
                });
        } elseif ($Bookmarks instanceof Collection) {
            $Bookmarks->transform(function ($item) {
                foreach ($item->mix->tobacco as $tobacco) {
                    $tobacco->vendor = $tobacco->vendor()->first();
                }
                //$item->mix->tobacco->vendor = $item->mix->tobacco->vendor->first();
                return $item;
            });
        }


        return parent::response($request->all(), $Bookmarks, 200);
    }

    /**
     * Получение закладки по ID
     *
     * @param      $id
     * @param bool $json
     *
     * @return mixed
     * @throws CustomException
     * @throws \App\Exceptions\CustomDBException
     */
    public function getBookmarkById($id, $json = true)
    {
        User::can('hookah_viewbookmark', true);
        $Bookmark = Bookmark::where('id', $id)->with(['mix'])->first();
        if (!$Bookmark) throw new CustomException(['id' => $id], [], 404, 'Закладка не найдена');

        return ($json) ? parent::response(['id' => $id], $Bookmark, 200) : $Bookmark;
    }

    /**
     * Удаление закладки по ID
     *
     * @param $id
     *
     * @return mixed
     * @throws CustomException
     * @throws \App\Exceptions\CustomDBException
     */
    public function deleteBookmarkById($id)
    {
        User::can('hookah_deletebookmark', true);

        $Bookmark = $this->getBookmarkById($id, false);

        LoggerController::write(
            $this->getModuleName(), 'hookah_deletebookmark',
            null, 'bookmark', $Bookmark->id,
            ['data' => self::modelFilter($Bookmark, Bookmark::fields())]
        );

        return parent::response(['id' => $id], $Bookmark->delete(), 200);
    }

    /**
     * Удаление закладки по id пользователя и id микса
     *
     * @param $id
     *
     * @return mixed
     * @throws CustomException
     * @throws \App\Exceptions\CustomDBException
     */
    public function deleteBookmark(Request $request)
    {
        User::can('hookah_deletebookmark', true);

        $Bookmark = Bookmark::where('user_id', $request->get('user_id'))
            ->where('mix_id', $request->get('mix_id'))->first();
        if (!$Bookmark) throw new CustomException($request->all(), [], 404, 'Закладка не найдена');

        LoggerController::write(
            $this->getModuleName(), 'hookah_deletebookmark',
            null, 'bookmark', $Bookmark->id,
            ['data' => self::modelFilter($Bookmark, Bookmark::fields())]
        );

        return parent::response($request->all(), $Bookmark->delete(), 200);
    }
}