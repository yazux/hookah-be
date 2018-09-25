<?php

namespace App\Classes;

use Moneta\Types\CheckProfileRequest;
use Moneta\Types\CreateProfileRequest;
use Moneta\Types\EditProfileRequest;
use Moneta\Types\FindAccountsListRequest;
use Moneta\Types\FindProfileInfoByAccountIdResponse;
use Moneta\Types\FindProfileInfoRequest;
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

}
