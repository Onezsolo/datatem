<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\AffiliateService;
use App\Services\SettingsService;
use App\Services\SmsOnlineService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    /**
     * Send OTP to phone for registration verification (when OTP verification is enabled).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendOtp(Request $request)
    {
        $request->validate(['phone' => 'required|string|max:20']);

        if (! SettingsService::getBool('otp_verification_enabled')) {
            return response()->json(['message' => 'OTP verification is not required.'], 200);
        }

        $sms = SmsOnlineService::fromConfig();
        $result = $sms->sendPhoneVerificationOtp($request->phone);

        if (! ($result['success'] ?? false)) {
            return response()->json(['error' => $result['message'] ?? 'Failed to send verification code.'], 422);
        }

        return response()->json(['message' => 'Verification code sent to your phone.']);
    }

    /**
     * Register a new user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function register(Request $request)
    {
        $rules = [
            'full_name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'phone' => 'required|string|max:20|unique:users',
            'password' => 'required|string|min:6|confirmed',
            'ref' => 'nullable|string|max:32',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $referredByAffiliateId = null;
        if ($request->filled('ref') && \Schema::hasTable('affiliates')) {
            $affiliate = \App\Models\Affiliate::where('referral_code', $request->ref)->where('status', \App\Models\Affiliate::STATUS_APPROVED)->first();
            if ($affiliate) {
                $referredByAffiliateId = $affiliate->id;
            }
        }

        try {
            $user = User::create([
                'full_name' => $request->full_name,
                'email' => $request->email,
                'phone' => $request->phone,
                'balance' => 0.00,
                'password' => Hash::make($request->password),
                'referred_by_affiliate_id' => $referredByAffiliateId,
            ]);

            AffiliateService::ensureAffiliateForUser($user);

            if ($referredByAffiliateId !== null) {
                AffiliateService::creditSignupBonus($referredByAffiliateId);
                $user->update(['referrer_signup_bonus_paid' => true]);
            }

            \App\Services\NotifyService::notifyAdminsNewRegistration($user);
            \App\Services\NotifyService::notifyUserWelcome($user);

            $token = JWTAuth::fromUser($user);

            return response()->json([
                'message' => 'User successfully registered',
                'user' => $user,
                'authorization' => [
                    'token' => $token,
                    'type' => 'bearer',
                ]
            ], 201);
        } catch (\Exception $e) {
            \Log::error('Registration Error: ' . $e->getMessage());
            return response()->json(['error' => 'Registration failed: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Get a JWT via given credentials.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'login' => 'required|string',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $loginType = filter_var($request->login, FILTER_VALIDATE_EMAIL) ? 'email' : 'phone';
        
        $credentials = [
            $loginType => $request->login,
            'password' => $request->password,
        ];

        if (!$token = auth('api')->attempt($credentials)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        return $this->createNewToken($token);
    }

    /**
     * Log the user out (Invalidate the token).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout()
    {
        auth('api')->logout();

        return response()->json(['message' => 'User successfully signed out']);
    }

    /**
     * Refresh a token.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function refresh()
    {
        return $this->createNewToken(auth('api')->refresh());
    }

    /**
     * Get the authenticated User.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function userProfile()
    {
        $user = auth('api')->user();
        if (SettingsService::getBool('affiliate_system_enabled')) {
            AffiliateService::ensureAffiliateForUser($user);
        }
        $user->load('affiliate');

        $payload = array_merge($user->toArray(), [
            'affiliate_system_enabled' => SettingsService::getBool('affiliate_system_enabled'),
        ]);
        if (SettingsService::getBool('affiliate_system_enabled') && $user->affiliate) {
            $payload['affiliate'] = [
                'id' => $user->affiliate->id,
                'referral_code' => $user->affiliate->referral_code,
                'balance' => $user->affiliate->balance,
                'total_earned' => $user->affiliate->total_earned,
                'total_withdrawn' => $user->affiliate->total_withdrawn,
                'status' => $user->affiliate->status,
                'referrals_count' => \App\Models\User::where('referred_by_affiliate_id', $user->affiliate->id)->count(),
            ];
        } else {
            $payload['affiliate'] = null;
        }

        return response()->json($payload);
    }

    /**
     * Get the token array structure.
     *
     * @param  string $token
     *
     * @return \Illuminate\Http\JsonResponse
     */
    protected function createNewToken($token)
    {
        $user = auth('api')->user();
        if (SettingsService::getBool('affiliate_system_enabled')) {
            AffiliateService::ensureAffiliateForUser($user);
        }
        $user->load('affiliate');

        $userPayload = $user->toArray();
        $userPayload['affiliate_system_enabled'] = SettingsService::getBool('affiliate_system_enabled');
        if (SettingsService::getBool('affiliate_system_enabled') && $user->affiliate) {
            $userPayload['affiliate'] = [
                'id' => $user->affiliate->id,
                'referral_code' => $user->affiliate->referral_code,
                'balance' => $user->affiliate->balance,
                'total_earned' => $user->affiliate->total_earned,
                'total_withdrawn' => $user->affiliate->total_withdrawn,
                'status' => $user->affiliate->status,
                'referrals_count' => \App\Models\User::where('referred_by_affiliate_id', $user->affiliate->id)->count(),
            ];
        } else {
            $userPayload['affiliate'] = null;
        }

        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth('api')->factory()->getTTL() * 60,
            'user' => $userPayload,
        ]);
    }
}
