<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Repositories\Auth\LoginRepository;
use Illuminate\Http\JsonResponse;

class LoginController extends Controller
{
    protected $repo;

    /**
     * Instantiate a new controller instance
     * @param LoginRepository $repo
     * @return void
     */
    public function __construct(LoginRepository $repo)
    {
        $this->repo = $repo;
    }

    /**
     * User login
     * @post ("/api/auth/login")
     * @param LoginRequest $request
     * @return JsonResponse
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $loginData = $request->validated();
        $remember = $request->boolean('remember', false);

        $result = $this->repo->login($loginData, $remember);

        return $this->success($result);
    }

    /**
     * User logout
     * @post ("/api/auth/logout")
     * @return JsonResponse
     */
    public function logout(): JsonResponse
    {
        $this->repo->logout();

        return $this->ok(['message' => __('auth.login.logged_out')]);
    }
}
