<?php

namespace App\Classes;

use App\Modules\Properties\Controllers\FileController;
use Moneta\Types\CreateProfileDocumentRequest;
use Moneta\Types\EditProfileDocumentRequest;
use Moneta\Types\FindProfileDocumentFilesRequest;
use Moneta\Types\FindProfileDocumentsRequest;
use Moneta\Types\GetProfileInfoRequest;

/**
 * Класс для работы с учётными записями в сервисе Moneta.ru
 * @package App\Classes
 */
class MonetaProfileDocument extends MonetaBase implements MonetaInterface
{
    public static function create($unitId = null, $type = null, $attribute = []) {
        $request = parent::PATR(new CreateProfileDocumentRequest(), ['unitId', 'attribute', 'type'], [
            'unitId'    => $unitId,
            'type'      => $type,
            'attribute' => $attribute
        ]);
        return parent::request('CreateProfileDocument', $request);
    }

    public static function update($unitId = null, $profileId = null, $docId = null, $type = null, $attribute = []) {
        $request = parent::PATR(new EditProfileDocumentRequest(), ['unitId', 'profileId', 'id', 'attribute', 'type'], [
            'id'        => $docId,
            'unitId'    => $unitId,
            'profileId' => $profileId,
            'type'      => $type,
            'attribute' => $attribute
        ]);
        return parent::request('EditProfileDocument', $request);
    }


    public static function get($unitId = null)
    {
        $request  = parent::PATR(new GetProfileInfoRequest(), ['unitId'], ['unitId' => $unitId]);
        $response = parent::request('GetProfileInfo', $request);
        if ($response && $response->attribute) $response = $response->attribute;
        return $response;
    }

    public static function find($unitId)
    {
        $request  = parent::PATR(new FindProfileDocumentsRequest(), ['unitId'], ['unitId' => $unitId]);
        $response = parent::request('FindProfileDocuments', $request);
        return $response;
    }

    public static function findFile($docFileId)
    {
        $name = 'file_' . time() . '_' . $docFileId . '.pdf';
        $path = storage_path('public') . '/' . $name;

        $request  = parent::PATR(new FindProfileDocumentFilesRequest(), ['documentId'], ['documentId' => $docFileId]);
        $response = parent::request('FindProfileDocumentFiles', $request);

        file_put_contents($path, $response);
        return FileController::call('uploadByPath', $path);
    }
}
