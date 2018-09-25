<?php

namespace App\Modules\Hookah\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Hookah\Model\Vendor;
use App\Modules\Properties\Controllers\FileController;
use App\Modules\Logger\Controllers\LoggerController;
use App\Interfaces\ModuleInterface;
use App\Exceptions\CustomException;
use App\Modules\User\Model\User;
use Illuminate\Http\Request;
use App\Modules\Properties\Model\Files;

class VendorController extends Controller implements ModuleInterface
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
     * Добавление постащика
     *
     * @param Request $request
     *
     * @return mixed
     * @throws CustomException
     * @throws \App\Exceptions\CustomDBException
     */
    public function postVendor(Request $request)
    {

        if ($request->hasFile('hero_image')) {
            $file = self::upFile($request, 'hero_image');
            $request->merge(['hero_image_id' => $file['id']]);
        }

        $Vendor = Vendor::post($request);
        if ($Vendor) {
            LoggerController::write(
                $this->getModuleName(), 'hookah_postvendor',
                null, 'vendor', $Vendor->id,
                ['data' => self::modelFilter($Vendor, Vendor::fields())]
            );
        }

        $Vendor = $this->getVendorById($Vendor->id, false);
        return parent::response($request->all(), $Vendor, 200);
    }

    /**
     * Изменение постащика
     *
     * @param Request $request
     *
     * @return mixed
     * @throws CustomException
     */
    public function putVendor(Request $request)
    {
        $Vendor = ['old' => false, 'new' => false];


        if ($request->hasFile('hero_image')) {
            $file = self::upFile($request, 'hero_image');
            $request->merge(['hero_image_id' => $file['id']]);
        }

        $Vendor = Vendor::put($request);

        if (isset($Vendor['old']) && isset($Vendor['new'])) {

            LoggerController::write(
                $this->getModuleName(), 'hookah_putvendor',
                null, 'vendor', $Vendor['new']->id,
                ['data' => self::modelFilter($Vendor['new'], Vendor::fields())],
                [$Vendor['old'], $Vendor['new']]
            );
            if ($request->hasFile('hero_image')) FileController::call('deleteFileById', $Vendor['old']['hero_image_id'], false);

        }

        return parent::response($request->all(), $Vendor['new'], 200);
    }

    /**
     * Получение списка постащиков
     *
     * @param Request $request
     *
     * @return mixed
     * @throws \App\Exceptions\CustomDBException
     */
    public function getVendors(Request $request)
    {
        User::can('hookah_viewvendor', true);
        $result = parent::dbGet(new Vendor(), $request, [], ['heroImage' => new Files()]);
        return parent::response($request->all(), $result, 200);
    }

    /**
     * Получение поставщика по ID
     *
     * @param      $id
     * @param bool $json
     *
     * @return mixed
     * @throws CustomException
     * @throws \App\Exceptions\CustomDBException
     */
    public function getVendorById($id, $json = true)
    {
        User::can('hookah_viewvendor', true);
        $Vendor = Vendor::where('id', $id)->with(['heroImage'])->first();
        if (!$Vendor) throw new CustomException(['id' => $id], [], 404, 'Производитель не найден');

        return ($json) ? parent::response(['id' => $id], $Vendor, 200) : $Vendor;
    }

    /**
     * Удаление поставщика по ID
     *
     * @param $id
     *
     * @return mixed
     * @throws CustomException
     * @throws \App\Exceptions\CustomDBException
     */
    public function deleteVendorById($id)
    {
        User::can('hookah_deletevendor', true);

        $Vendor = $this->getVendorById($id, false);

        LoggerController::write(
            $this->getModuleName(), 'hookah_deletevendor',
            null, 'vendor', $Vendor->id,
            ['data' => self::modelFilter($Vendor, Vendor::fields())]
        );

        return parent::response(['id' => $id], $Vendor->delete(), 200);
    }
}