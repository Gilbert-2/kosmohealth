<?php

namespace App\Http\Resources;

use App\Helpers\ArrHelper;
use App\Helpers\CalHelper;
use Illuminate\Http\Resources\Json\JsonResource;

class AuthUser extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $gender = ArrHelper::searchByKey(ArrHelper::getTransList('genders'), 'uuid', $this->gender);

        return [
            'uuid'        => $this->uuid,
            'username'    => $this->username,
            'email'       => $this->email,
            'mobile'      => $this->mobile,
            'phone_number' => $this->phone_number,
            'role'        => $this->hasRole('admin') ? 'admin' : ($this->hasRole('host') ? 'host' : (optional($this->getRoleNames())->first() ?? 'user')),
            'roles'       => $this->roles()->pluck('name')->all(),
            'permissions' => $this->getAllPermissions()->pluck('name')->all(),
            'profile'     => array(
                'name'         => $this->name,
                'first_name'   => $this->first_name,
                'last_name'    => $this->last_name,
                'avatar'       => $this->avatar,
                'cover'        => $this->getMeta('cover_image'),
                'gender'       => $gender,
                'birth_date'   => $this->birth_date ? $this->birth_date->format('Y-m-d') : '',
                'date_of_birth' => $this->birth_date ? $this->birth_date->format('Y-m-d') : '', // Alias for frontend consistency
                'age'          => CalHelper::getAge($this->birth_date),
                'location'     => $this->location
            ),
            'membership_expiry_date'  => CalHelper::toDate($this->membership_expiry_date),
            'has_active_membership'   => $this->hasActiveMembership(),
            'has_lifetime_membership' => $this->getMeta('lifetime') ? true : false,
            'preference'              => $this->user_preference
        ];
    }
}
