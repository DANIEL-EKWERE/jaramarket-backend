<?php

namespace App\Http\Controllers\API;


use Exception;
use App\Models\User;
use App\Http\Controllers\Controller;
use App\Services\UserRegistrationService;
use App\Http\Requests\ForgotPasswordLinkRequest;

class ForgotPasswordController extends Controller
{
    public function __construct(public UserRegistrationService $userService)
    { }

    public function sendResetLink(ForgotPasswordLinkRequest $request)
    {
        try {
            $user = User::where('email', $request->email)->first();

            if (! $user || ! $user->is_active) {
                return response()->json(['status' => false, 'message' => 'User account not found or in active'], 500);
            }

            $this->userService->sendOtp($user->email);

            return response()->json([
                'status' => true,
                'message' => 'An OTP has been sent to your email address. It expires after 15 minutes.',
                'data' => []
            ], 201);
        } catch (Exception $e) {
            report($e);

            return response()->json([
                'status' => true,
                'message' => $e->getMessage(),
                'data' => []
            ], 201);
        }
    }
}
