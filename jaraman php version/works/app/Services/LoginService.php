<?php

namespace App\Services;

use Exception;
use Illuminate\Http\Request;
use App\Http\Resources\UserResource;
use App\Traits\AuthenticateUserTrait;


class LoginService
{
    use AuthenticateUserTrait;

    protected $loginField;

    public function __construct()
    {
        $this->loginField = $this->findLoginField();
    }

    protected function findLoginField()
    {
        $login = request()->email;
        $fieldType = filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';
        request()->merge([$fieldType => $login]);

        return $fieldType;
    }

    public function username()
    {
        return $this->loginField;
    }

    public function loginUser(Request $request)
    {
        $user = $this->authenticate($request);
        $user->update(['last_login' => now()]);
        return $this->mergeUserWithToken($user);
    }

    public function mergeUserWithToken($user)
    {
        $token = $user->createToken('auth_token')->plainTextToken;

        return array_merge(
            (new UserResource($user))->toArray(request()),
            [
                'token' => $token,
                'token_type' => 'Bearer',
                'expires_in' => 3600
            ]
        );
    }

}
