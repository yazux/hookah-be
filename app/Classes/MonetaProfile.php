<?php

namespace App\Classes;

use Moneta\Types\FindProfileInfoRequest;
use Moneta\Types\CheckProfileRequest;
use Moneta\Types\CreateProfileRequest;
use Moneta\Types\EditProfileRequest;
use Moneta\Types\GetProfileInfoRequest;

/**
 * Класс для работы с учётными записями в сервисе Moneta.ru
 * @package App\Classes
 */
class MonetaProfile extends MonetaBase implements MonetaInterface
{
    public static function create(
        $unitId = null, $profileId = null, $profileType = 'organization', $profile = []
    ) {
        $request = parent::PATR(new CreateProfileRequest(), ['profileType', 'profile'], [
            'unitId'      => ($unitId) ? $unitId : env('PROFILE_UNIT_ID', 45316),
            'profileId'   => $profileId,
            'profileType' => $profileType,
            'profile'     => $profile,
            'parentId'    => env('GROUP_REGISTERED_UNIT_ID', 45316)
        ]);
        return parent::request('CreateProfile', $request);
    }

    public static function update(
        $unitId = null, $profileId = null, $profileType = 'organization', $profile = []
    ) {
        $request = parent::PATR(new EditProfileRequest(), ['profileType', 'profile', 'parentId'], [
            'unitId'      => ($unitId) ? $unitId : env('PROFILE_UNIT_ID', 45316),
            'profileId'   => $profileId,
            'profileType' => $profileType,
            'profile'     => $profile,
            'parentId'    => env('GROUP_REGISTERED_UNIT_ID', 45316)
        ]);
        return parent::request('EditProfile', $request);
    }

    public static function get($unitId = null)
    {
        $request  = parent::PATR(new GetProfileInfoRequest(), ['unitId'], ['unitId' => $unitId]);
        $response = parent::request('GetProfileInfo', $request);
        if ($response && $response->attribute) $response = $response->attribute;
        return $response;
    }

    public static function check($unitId = null, $profileId = null)
    {
        $request  = parent::PATR(new CheckProfileRequest(), ['unitId'], ['unitId' => $unitId]);
        $response = parent::request('CheckProfile', $request);
        //if ($response && $response->attribute) $response = $response->attribute;
        return $response;
    }

    public static function find($unitId = null, $profileId = null)
    {

        $filter = ['unitId' => $unitId];
        if ($profileId) $filter['profileId'] = $profileId;

        $request  = parent::PATR(new FindProfileInfoRequest(), ['filter', 'pager'], [
            'filter' => $filter,
            'pager'  => [
                'pageNumber' => 1,
                'pageSize'   => 100
            ]
        ]);
        $response = parent::request('FindProfileInfo', $request);
        //if ($response && $response->attribute) $response = $response->attribute;
        return $response;
    }

}
