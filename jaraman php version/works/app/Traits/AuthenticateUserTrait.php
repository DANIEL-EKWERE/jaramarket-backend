<?php

namespace App\Traits;


use Exception;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

trait AuthenticateUserTrait
{
    /**
     * Attempt to authenticate the request's credentials.
     *
     * @return App\Models\User
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function authenticate(Request $request)
    {
        $this->ensureIsNotRateLimited($request);

        try {
            $this->loginWithCredentials($request);
        } catch (Exception $e) {
            throw new Exception('Error Generating Token '. $e->getMessage(), Response::HTTP_UNAUTHORIZED, $e);
        }
        $this->ensureAccountIsActive();
        
        RateLimiter::clear($this->throttleKey($request));

        $user = auth()->user();
        return $user;
    }

    private function loginWithCredentials(Request $request)
    {
        if (! auth()->attempt($this->credentials($request))) {
            RateLimiter::hit($this->throttleKey($request));

            throw ValidationException::withMessages([
                $this->username() => 'Either the '.$this->username().' or password provided does not match our record',
            ])->errorBag($this->username());
        }
    }

    public function ensureIsNotRateLimited(Request $request)
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey($request), 5)) {
            return;
        }

        event(new Lockout($request));

        $seconds = RateLimiter::availableIn($this->throttleKey($request));

        throw ValidationException::withMessages([
            'email' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    public function throttleKey(Request $request)
    {
        $username = $this->username();

        return Str::lower($request->$username).'|'.$request->ip();
    }

    public function credentials(Request $request, array $credentials = [])
    {
        if (empty($credentials)) {
            return $request->only($this->username(), 'password');
        }

        return $credentials;
    }

    public function ensureAccountIsActive()
    {
        if (! auth()->user()->is_active) {
            auth()->logout();
            throw new Exception('Your account is not active! Please contact admin', Response::HTTP_BAD_REQUEST);
        }
    }

    public function username()
    {
        return 'email';
    }

}