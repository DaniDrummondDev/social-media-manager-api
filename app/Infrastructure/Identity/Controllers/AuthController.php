<?php

declare(strict_types=1);

namespace App\Infrastructure\Identity\Controllers;

use App\Application\Identity\DTOs\Confirm2FAInput;
use App\Application\Identity\DTOs\Disable2FAInput;
use App\Application\Identity\DTOs\ForgotPasswordInput;
use App\Application\Identity\DTOs\LoginInput;
use App\Application\Identity\DTOs\RefreshTokenInput;
use App\Application\Identity\DTOs\RegisterUserInput;
use App\Application\Identity\DTOs\ResetPasswordInput;
use App\Application\Identity\DTOs\TwoFactorChallengeOutput;
use App\Application\Identity\DTOs\Verify2FAInput;
use App\Application\Identity\DTOs\VerifyEmailInput;
use App\Application\Identity\UseCases\Confirm2FAUseCase;
use App\Application\Identity\UseCases\Disable2FAUseCase;
use App\Application\Identity\UseCases\Enable2FAUseCase;
use App\Application\Identity\UseCases\ForgotPasswordUseCase;
use App\Application\Identity\UseCases\LoginUseCase;
use App\Application\Identity\UseCases\LogoutUseCase;
use App\Application\Identity\UseCases\RefreshTokenUseCase;
use App\Application\Identity\UseCases\RegisterUserUseCase;
use App\Application\Identity\UseCases\ResetPasswordUseCase;
use App\Application\Identity\UseCases\Verify2FALoginUseCase;
use App\Application\Identity\UseCases\VerifyEmailUseCase;
use App\Infrastructure\Identity\Requests\Confirm2FARequest;
use App\Infrastructure\Identity\Requests\Disable2FARequest;
use App\Infrastructure\Identity\Requests\ForgotPasswordRequest;
use App\Infrastructure\Identity\Requests\LoginRequest;
use App\Infrastructure\Identity\Requests\RefreshTokenRequest;
use App\Infrastructure\Identity\Requests\RegisterRequest;
use App\Infrastructure\Identity\Requests\ResetPasswordRequest;
use App\Infrastructure\Identity\Requests\Verify2FARequest;
use App\Infrastructure\Identity\Requests\VerifyEmailRequest;
use App\Infrastructure\Identity\Resources\AuthTokensResource;
use App\Infrastructure\Identity\Resources\MessageResource;
use App\Infrastructure\Identity\Resources\TwoFactorChallengeResource;
use App\Infrastructure\Identity\Resources\TwoFactorSetupResource;
use App\Infrastructure\Identity\Resources\UserResource;
use App\Infrastructure\Shared\Http\Resources\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class AuthController
{
    public function register(RegisterRequest $request, RegisterUserUseCase $useCase): JsonResponse
    {
        $output = $useCase->execute(new RegisterUserInput(
            name: $request->validated('name'),
            email: $request->validated('email'),
            password: $request->validated('password'),
            passwordConfirmation: $request->validated('password_confirmation'),
        ));

        return ApiResponse::success(UserResource::fromOutput($output)->toArray(), status: 201);
    }

    public function login(LoginRequest $request, LoginUseCase $useCase): JsonResponse
    {
        $output = $useCase->execute(new LoginInput(
            email: $request->validated('email'),
            password: $request->validated('password'),
            ipAddress: $request->ip(),
            userAgent: $request->userAgent(),
        ));

        if ($output instanceof TwoFactorChallengeOutput) {
            return ApiResponse::success(TwoFactorChallengeResource::fromOutput($output)->toArray());
        }

        return ApiResponse::success(AuthTokensResource::fromOutput($output)->toArray());
    }

    public function verify2fa(Verify2FARequest $request, Verify2FALoginUseCase $useCase): JsonResponse
    {
        $output = $useCase->execute(new Verify2FAInput(
            tempToken: $request->validated('temp_token'),
            otpCode: $request->validated('otp_code'),
            ipAddress: $request->ip(),
            userAgent: $request->userAgent(),
        ));

        return ApiResponse::success(AuthTokensResource::fromOutput($output)->toArray());
    }

    public function refresh(RefreshTokenRequest $request, RefreshTokenUseCase $useCase): JsonResponse
    {
        $output = $useCase->execute(new RefreshTokenInput(
            refreshToken: $request->validated('refresh_token'),
        ));

        return ApiResponse::success(AuthTokensResource::fromOutput($output)->toArray());
    }

    public function logout(Request $request, LogoutUseCase $useCase): JsonResponse
    {
        $jti = $request->attributes->get('auth_jti');
        $userId = $request->attributes->get('auth_user_id');
        $tokenTtlSeconds = (int) config('jwt.ttl') * 60;

        $useCase->execute(
            jti: $jti,
            tokenTtlSeconds: $tokenTtlSeconds,
            refreshToken: $request->input('refresh_token'),
            allSessions: (bool) $request->input('all_sessions', false),
            userId: $userId,
        );

        return ApiResponse::noContent();
    }

    public function verifyEmail(VerifyEmailRequest $request, VerifyEmailUseCase $useCase): JsonResponse
    {
        $output = $useCase->execute(new VerifyEmailInput(
            token: $request->validated('token'),
        ));

        return ApiResponse::success(MessageResource::fromOutput($output)->toArray());
    }

    public function forgotPassword(ForgotPasswordRequest $request, ForgotPasswordUseCase $useCase): JsonResponse
    {
        $output = $useCase->execute(new ForgotPasswordInput(
            email: $request->validated('email'),
        ));

        return ApiResponse::success(MessageResource::fromOutput($output)->toArray());
    }

    public function resetPassword(ResetPasswordRequest $request, ResetPasswordUseCase $useCase): JsonResponse
    {
        $output = $useCase->execute(new ResetPasswordInput(
            token: $request->validated('token'),
            password: $request->validated('password'),
            passwordConfirmation: $request->validated('password_confirmation'),
        ));

        return ApiResponse::success(MessageResource::fromOutput($output)->toArray());
    }

    public function enable2fa(Request $request, Enable2FAUseCase $useCase): JsonResponse
    {
        $userId = $request->attributes->get('auth_user_id');

        $output = $useCase->execute($userId);

        return ApiResponse::success(TwoFactorSetupResource::fromOutput($output)->toArray());
    }

    public function confirm2fa(Confirm2FARequest $request, Confirm2FAUseCase $useCase): JsonResponse
    {
        $output = $useCase->execute(new Confirm2FAInput(
            userId: $request->attributes->get('auth_user_id'),
            secret: $request->input('secret', ''),
            otpCode: $request->validated('otp_code'),
        ));

        return ApiResponse::success(MessageResource::fromOutput($output)->toArray());
    }

    public function disable2fa(Disable2FARequest $request, Disable2FAUseCase $useCase): JsonResponse
    {
        $output = $useCase->execute(new Disable2FAInput(
            userId: $request->attributes->get('auth_user_id'),
            password: $request->validated('password'),
        ));

        return ApiResponse::success(MessageResource::fromOutput($output)->toArray());
    }
}
