<?php

namespace App\Http\Controllers\API;

use Exception;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Services\LoginService;
use App\Http\Requests\OtpRequest;
use App\Http\Requests\LoginRequest;
use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\ResendOtpRequest;
use App\Http\Requests\UserProfileRequest;
use App\Services\UserRegistrationService;
use App\Http\Requests\ChangePasswordRequest;
use App\Http\Resources\ReferralResource;
use App\Http\Resources\WalletResource;



class UserController extends Controller
{
    public function __construct(public UserRegistrationService $userService, public LoginService $loginService)
    { }

    public function registerUser(RegisterRequest $request)
    {
        try {
            $user = $this->userService->register($request->validated());

            return response()->json([
                'status' => true,
                'message' => 'An OTP has been sent to your email address. It expires after 15 minutes.',
                'data' => new UserResource($user)
            ], 201);
        } catch (Exception $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function resendOtp(ResendOtpRequest $request)
    {
        try {
            $this->userService->sendOtp($request->email);
            return response()->json([
                'status' => true,
                'message' => 'An OTP has been sent to your email address. It expires after 15 minutes.',
                'data' => null
            ], 201);
        } catch (Exception $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()], 500);
        }
    }
    
    public function validateUserRegisterOTP(OtpRequest $request)
    {
        try {

            $data = $request->validated();
            $user = $this->userService->validateOTP($data['email'], $data['otp']);
    
            return response()->json([
                'status' => true,
                'message' => 'OTP validated successfully',
                'data' => $user
            ], 201);
    
        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], $e->getCode() ?: 400);
        }
    }

    public function verifyEmailWithOTP(OtpRequest $request)
    {
        $user = $this->userService->validateOTP($request->email, $request->otp);

        return $this->validateEmail($user);
    }

    public function validateEmail(User $user)
    {
        try {

            $this->userService->validateEmail($user);
    
            return response()->json([
                'status' => true,
                'message' => 'Email verified successfully and registration complete',
                'data' => new UserResource($user)
            ], 201);
    
        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], $e->getCode() ?: 400);
        }
    }

    public function login(LoginRequest $request)
    {
        try {
            $data = $this->loginService->loginUser($request);
           
            return response()->json([
                'status'  => true,
                'message' => 'User Authenticated successfully',
                'data'    => $data
            ], 201);
        } catch (Exception $e) {
            report($e);

            return response()->json([
                'status'  => false,
                'message' => 'User Authenticated failed',
                'data'    => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function logout(Request $request)
    {
        if ($request->user()) {
            // Revoke current token
            $request->user()->currentAccessToken()->delete();

            return response()->json([
                'status' => true,
                'message' => 'Session ended! Logout was successful.'
            ], 200);
        }

        return response()->json([
            'status' => false,
            'message' => 'Not authenticated.'
        ], 401);
    }

    public function fetchUserProfile()
    {
        try {
            $user = auth()->user();
            return response()->json([
                'status' => true,
                'message' => 'User Profile retrieved successfully',
                'data' => new UserResource($user)
            ], 201);
        } catch (Exception $e) {
            return response()->json(['status' => false, 'message' => 'User Profile not found'], 404);
        }
    }

    public function myRefferals()
    {
        try {
            $user = auth()->user();
            return response()->json([
                'status'  => true,
                'message' => 'Refferals retrieved successfully',
                'data'    => ReferralResource::collection($user->referrals()->latest()->get())
            ], 201);
        } catch (Exception $e) {
            return response()->json(['status' => false, 'message' => 'User refferals not found'], 404);
        }
    }

    public function fetchUserWallet()
    {
        try {
            $user = auth()->user();
            return response()->json([
                'status' => true,
                'message' => 'User Wallet retrieved successfully',
                'data' => new WalletResource($user->wallet)
            ], 201);
        } catch (Exception $e) {
            return response()->json(['status' => false, 'message' => 'User Profile not found'], 404);
        }
    }

    public function editUserProfile(UserProfileRequest $request)
    {
        try {       
          $user = $this->userService->updateProfile($request);
        return response()->json(['status' => true, 'message' => 'Profile Updated Successfully', 'data' => new UserResource($user)], 200);
        } catch (Exception $e) {
            return response()->json(['status' => false, 'message' => 'Problem Updating Profile'], 400);
        }
    }

    public function updateProfile(UserProfileRequest $request, $email)
    {
        try {       
          $user = $this->userService->updateProfile($request, $email);
        return response()->json(['status' => true, 'message' => 'Profile Updated Successfully', 'data' => new UserResource($user)], 201);
        } catch (Exception $e) {
            return response()->json(['status' => false, 'message' => 'Problem Updating Profile'], 400);
        }
    }

    public function changePassword(ChangePasswordRequest $request)
    {
        try {
            $user = auth()->user();
            $user->update(['password' => $request->password]);
            return response()->json(['status' => true, 'message' => 'Password changed successfully', 'data' => new UserResource($user)], 201);
        } catch (Exception $e) {
            report($e);
            return response()->json(['status' => false, 'message' => 'Password change failed! Please contact admin', 'data'=> null], 201);
        }
    }

     /**
     * @OA\Get(
     *     path="/users",
     *     summary="Get all users",
     *     tags={"Users"},
     *     @OA\Response(
     *         response=200,
     *         description="List of users retrieved successfully",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 @OA\Property(property="id", type="string", format="uuid"),
     *                 @OA\Property(property="name", type="string", example="John Doe"),
     *                 @OA\Property(property="email", type="string", format="email"),
     *                 @OA\Property(property="role", type="string"),
     *                 @OA\Property(property="email_verified_at", type="string", format="date-time", nullable=true),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time")
     *             )
     *         )
     *     )
     * )
     */
    public function index()
    {
        return User::all();
    }

    /**
     * @OA\Put(
     *     path="/users/{id}",
     *     summary="Update a user",
     *     tags={"Users"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="UUID of the user",
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string", example="John Doe"),
     *             @OA\Property(property="email", type="string", format="email"),
     *             @OA\Property(property="role", type="string")
     *         )
     *     ),
     *     @OA\Response(response=200, description="User updated successfully"),
     *     @OA\Response(response=404, description="User not found"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);
        $user->update([
            'firstname' => $request->firstname ?? $user->firstname,
            'lastname' => $request->lastname ?? $user->lastname,
            'email' => $request->email ?? $user->email,
            'role' => $request->role ?? $user->role
        ]);

        return response()->json(['status' => true,'message' => 'User updated successfully']);
    }

    /**
     * @OA\Patch(
     *     path="/users/{id}/toggle-status",
     *     summary="Toggle user active status",
     *     tags={"Users"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="UUID of the user",
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(response=200, description="User status updated successfully"),
     *     @OA\Response(response=404, description="User not found")
     * )
     */
    public function toggleStatus($id)
    {
        $user = User::findOrFail($id);
        $user->is_active = !$user->is_active;
        $user->save();

        return response()->json(['status' => true,'message' => 'User status updated successfully']);
    }

    /**
     * @OA\Delete(
     *     path="/users/{id}",
     *     summary="Delete a user",
     *     tags={"Users"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="UUID of the user",
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(response=200, description="User deleted successfully"),
     *     @OA\Response(response=404, description="User not found")
     * )
     */
    public function destroy($id)
    {
        $user = User::findOrFail($id);
        $user->delete();

        return response()->json(['status' => true,'message' => 'User deleted successfully']);
    }
}
