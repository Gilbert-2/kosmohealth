<?php
namespace Mint\Service\Repositories;

use App\Helpers\SysHelper;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

class LicenseRepository
{
    public function verify()
    {

        SysHelper::setApp(['INSTALLED' => true]);
        SysHelper::setApp(['ACCESS_CODE' => request('access_code')]);
        SysHelper::setApp(['EMAIL' => request('envato_email')]);
    }
}
