<?php

declare(strict_types=1);

namespace App\Infrastructure\Identity\Controllers;

use App\Application\Identity\DTOs\ChangeEmailInput;
use App\Application\Identity\DTOs\ChangePasswordInput;
use App\Application\Identity\DTOs\UpdateProfileInput;
use App\Application\Identity\DTOs\UserOutput;
use App\Application\Identity\UseCases\ChangeEmailUseCase;
use App\Application\Identity\UseCases\ChangePasswordUseCase;
use App\Application\Identity\UseCases\UpdateProfileUseCase;
use App\Domain\Identity\Repositories\UserRepositoryInterface;
use App\Domain\Shared\ValueObjects\Uuid;
use App\Infrastructure\Identity\Requests\ChangeEmailRequest;
use App\Infrastructure\Identity\Requests\ChangePasswordRequest;
use App\Infrastructure\Identity\Requests\UpdateProfileRequest;
use App\Infrastructure\Identity\Resources\MessageResource;
use App\Infrastructure\Identity\Resources\UserResource;
use App\Infrastructure\Shared\Http\Resources\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ProfileController
{
    public function show(Request $request, UserRepositoryInterface $userRepository): JsonResponse
    {
        $userId = $request->attributes->get('auth_user_id');

        $user = $userRepository->findById(Uuid::fromString($userId));

        $output = UserOutput::fromEntity($user);

        return ApiResponse::success(UserResource::fromOutput($output)->toArray());
    }

    public function update(UpdateProfileRequest $request, UpdateProfileUseCase $useCase): JsonResponse
    {
        $output = $useCase->execute(new UpdateProfileInput(
            userId: $request->attributes->get('auth_user_id'),
            name: $request->validated('name'),
            phone: $request->validated('phone'),
            timezone: $request->validated('timezone'),
        ));

        return ApiResponse::success(UserResource::fromOutput($output)->toArray());
    }

    public function changeEmail(ChangeEmailRequest $request, ChangeEmailUseCase $useCase): JsonResponse
    {
        $output = $useCase->execute(new ChangeEmailInput(
            userId: $request->attributes->get('auth_user_id'),
            newEmail: $request->validated('email'),
            password: $request->validated('password'),
        ));

        return ApiResponse::success(MessageResource::fromOutput($output)->toArray());
    }

    public function changePassword(ChangePasswordRequest $request, ChangePasswordUseCase $useCase): JsonResponse
    {
        $output = $useCase->execute(new ChangePasswordInput(
            userId: $request->attributes->get('auth_user_id'),
            currentPassword: $request->validated('current_password'),
            newPassword: $request->validated('password'),
            newPasswordConfirmation: $request->validated('password_confirmation'),
        ));

        return ApiResponse::success(MessageResource::fromOutput($output)->toArray());
    }
}
