<?php
namespace Mint\Service\Repositories;

use App\Helpers\CalHelper;
use App\Helpers\IpHelper;
use App\Helpers\SysHelper;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

class InitRepository
{
    public function __construct()
    {
        config(['app.item' => '201005']);
        config(['app.verifier' => 'https://auth.kodemint.in']);
    }

    public function check() : void
    {
        $last_verified_on = SysHelper::getApp('ACCESS_LOG');

        if (CalHelper::validateDate($last_verified_on) && $last_verified_on === today()) {
            return;
        }

        $this->validate('verify', false);
    }

    private function validate($action = 'verify', $throw = true)
    {

            SysHelper::setApp(['ACCESS_LOG' => Carbon::now()->toDateString()]);

        return '';
    }

    public function info()
    {

            $product['purchase_code'] = config('default.private_mask');
            $product['email'] = config('default.private_mask');
            $product['access_code'] = config('default.private_mask');
            $product['checksum'] = config('default.private_mask');

            $is_downloaded = 0;


        return compact('product', 'is_downloaded');
    }

    public function licenseValidate()
    {
        $status = 1;

        return compact('status');
    }
}
