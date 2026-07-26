<?php

namespace App\Http\Controllers;

use App\Enums\PinTypeEnum;
use App\Http\Requests\PinRequest;
use App\Notifications\PinNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Http\Response;
use Exception;

class PinController extends Controller
{
    /**
     * Set or update transaction PIN
     */
    public function setPin(PinRequest $request)
    {
        try {
            $user = $request->user();
            if (!$user) {
                return response()->errorResponse('User not found.', Response::HTTP_NOT_FOUND);
            }

            $user->pin = Hash::make($request->pin);
            $user->save();

            $user->notify(new PinNotification(PinTypeEnum::SETUP()));

            return response()->success('Transaction PIN set successfully.', [], Response::HTTP_OK);
        } catch (Exception $e) {
            report($e);
            return response()->errorResponse('Failed to set PIN.', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Verify PIN
     */
    public function verifyPin(PinRequest $request)
    {
        try {
            $user = $request->user();
            if (!$user) {
                return response()->errorResponse('User not found.', Response::HTTP_NOT_FOUND);
            }

            if (!Hash::check($request->pin, $user->pin)) {
                return response()->errorResponse('Invalid PIN.', Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            if ($request->boolean('remember')) {
                $token = Str::random(60);
                $user->remember_pin_token = $token;
                $user->pin_token_expiry = now()->addDays(7);
                $user->save();         

                return response()->success('PIN verified successfully.', [
                    'pin_token'  => $token,
                    'expires_at' => $user->pin_token_expiry
                ], Response::HTTP_OK);
            }

            return response()->success('PIN verified successfully.', [], Response::HTTP_OK);
        } catch (Exception $e) {
            report($e);
            return response()->errorResponse('Failed to verify PIN.', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Validate remember PIN token
     */
    public function validatePinToken(Request $request)
    {
        try {
            $user = $request->user();
            if (!$user) {
                return response()->errorResponse('User not found.', Response::HTTP_NOT_FOUND);
            }

            $token = $request->header('X-PIN-TOKEN');

            if ($user->remember_pin_token === $token && now()->lessThan($user->pin_token_expiry)) {
                $user->notify(new PinNotification(PinTypeEnum::TOKEN_VALIDATED()));    
                return response()->success('PIN token is valid.', [], Response::HTTP_OK);
            }

            $user->notify(new PinNotification(PinTypeEnum::TOKEN_INVALID()));    

            return response()->errorResponse('Invalid or expired PIN token.', Response::HTTP_UNAUTHORIZED);
        } catch (Exception $e) {
            report($e);
            return response()->errorResponse('Failed to validate PIN token.', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Clear PIN token
     */
    public function clearPinToken(Request $request)
    {
        try {
            $user = $request->user();
            if (!$user) {
                return response()->errorResponse('User not found.', Response::HTTP_NOT_FOUND);
            }

            $user->remember_pin_token = null;
            $user->pin_token_expiry = null;
            $user->save();

            $user->notify(new PinNotification(PinTypeEnum::TOKEN_CLEARED()));

            return response()->success('PIN token cleared.', [], Response::HTTP_OK);
        } catch (Exception $e) {
            report($e);
            return response()->errorResponse('Failed to clear PIN token.', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Request PIN reset (generate reset token)
     */
    public function requestReset(Request $request)
    {
        try {
            $user = $request->user();
            if (!$user) {
                return response()->errorResponse('User not found.', Response::HTTP_NOT_FOUND);
            }

            $token = Str::random(6); // could be numeric OTP
            $user->pin_reset_token = $token;
            $user->pin_reset_expiry = now()->addMinutes(5);
            $user->save();

            $user->notify(new PinNotification(PinTypeEnum::RESET_REQUEST(),$token, $user->pin_reset_expiry));

            return response()->success('PIN reset token sent successfully.', [], Response::HTTP_OK);
        } catch (Exception $e) {
            report($e);
            return response()->errorResponse('Failed to request PIN reset.', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Reset PIN with token
     */
    public function resetPin(PinRequest $request)
    {
        try {
            $user = $request->user();
            if (!$user) {
                return response()->errorResponse('User not found.', Response::HTTP_NOT_FOUND);
            }

            if (
                $user->pin_reset_token !== $request->token ||
                now()->greaterThan($user->pin_reset_expiry)
            ) {
                return response()->errorResponse('Invalid or expired reset token.', Response::HTTP_UNAUTHORIZED);
            }

            $user->pin = Hash::make($request->pin);
            $user->pin_reset_token = null;
            $user->pin_reset_expiry = null;
            $user->save();

            $user->notify(new PinNotification(PinTypeEnum::REQUEST_CONFIRMED()));

            return response()->success('Transaction PIN reset successfully.', [], Response::HTTP_OK);
        } catch (Exception $e) {
            report($e);
            return response()->errorResponse('Failed to reset PIN.', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
