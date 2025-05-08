<?php

namespace App\Http\Controllers\Auth;

use App\Enums\Auth\UserStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\VerifyRequest;
use App\Repositories\Auth\RegisterRepository;
use Illuminate\Http\JsonResponse;

class RegisterController extends Controller
{
    protected $repo;

    /**
     * Instantiate a new controller instance
     * @param RegisterRepository $repo
     * @return void
     */
    public function __construct(RegisterRepository $repo)
    {
        $this->repo = $repo;
    }

    /**
     * User registration
     * @post ("/api/auth/register")
     * @param RegisterRequest $request
     * @return JsonResponse
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $userData = $request->validated();
        $user = $this->repo->register($userData);

        return $this->success([
            'message' => __('auth.register.' . $user->status . '_message'),
            'registration_status' => $user->status,
            'user_id' => $user->uuid
        ]);
    }

    /**
     * User verification
     * @post ("/api/auth/verify")
     * @param VerifyRequest $request
     * @return JsonResponse
     */
    public function verify(VerifyRequest $request): JsonResponse
    {
        $this->repo->verify($request->validated());

        return $this->success([
            'message' => __('auth.register.user_verified'),
            'verified' => true
        ]);
    }
}