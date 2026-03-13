<?php

declare(strict_types=1);

namespace App\Infrastructure\Organization\Controllers;

use App\Application\Organization\DTOs\AcceptInviteInput;
use App\Application\Organization\DTOs\ChangeMemberRoleInput;
use App\Application\Organization\DTOs\InviteMemberInput;
use App\Application\Organization\DTOs\OrganizationMemberOutput;
use App\Application\Organization\DTOs\RemoveMemberInput;
use App\Application\Organization\UseCases\AcceptInviteUseCase;
use App\Application\Organization\UseCases\ChangeMemberRoleUseCase;
use App\Application\Organization\UseCases\InviteMemberUseCase;
use App\Application\Organization\UseCases\RemoveMemberUseCase;
use App\Domain\Organization\Repositories\OrganizationMemberRepositoryInterface;
use App\Domain\Shared\ValueObjects\Uuid;
use App\Infrastructure\Identity\Resources\MessageResource;
use App\Infrastructure\Organization\Requests\AcceptInviteRequest;
use App\Infrastructure\Organization\Requests\ChangeMemberRoleRequest;
use App\Infrastructure\Organization\Requests\InviteMemberRequest;
use App\Infrastructure\Organization\Resources\OrganizationMemberResource;
use App\Infrastructure\Shared\Http\Resources\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class MemberController
{
    public function list(
        Request $request,
        OrganizationMemberRepositoryInterface $memberRepository,
        string $organizationId,
    ): JsonResponse {
        // SECURITY FIX (IDOR-002): Validate that user has access to this organization
        $authOrgId = $request->attributes->get('auth_organization_id');
        
        if ($authOrgId === null || $authOrgId !== $organizationId) {
            return ApiResponse::fail(
                code: 'AUTHORIZATION_ERROR',
                message: 'You do not have access to this organization.',
                status: 403
            );
        }

        $members = $memberRepository->listByOrganization(Uuid::fromString($organizationId));

        $data = array_map(
            fn ($member) => OrganizationMemberResource::fromOutput(
                OrganizationMemberOutput::fromEntity($member),
            )->toArray(),
            $members,
        );

        return ApiResponse::success($data);
    }

    public function invite(
        InviteMemberRequest $request,
        InviteMemberUseCase $useCase,
        string $organizationId,
    ): JsonResponse {
        $output = $useCase->execute(new InviteMemberInput(
            organizationId: $organizationId,
            userId: $request->attributes->get('auth_user_id'),
            email: $request->validated('email'),
            role: $request->validated('role'),
        ));

        return ApiResponse::success(MessageResource::fromOutput($output)->toArray());
    }

    public function acceptInvite(AcceptInviteRequest $request, AcceptInviteUseCase $useCase): JsonResponse
    {
        $output = $useCase->execute(new AcceptInviteInput(
            token: $request->validated('token'),
            userId: $request->attributes->get('auth_user_id'),
        ));

        return ApiResponse::success(OrganizationMemberResource::fromOutput($output)->toArray());
    }

    public function remove(
        Request $request,
        RemoveMemberUseCase $useCase,
        string $organizationId,
        string $userId,
    ): JsonResponse {
        $useCase->execute(new RemoveMemberInput(
            organizationId: $organizationId,
            userId: $request->attributes->get('auth_user_id'),
            targetUserId: $userId,
        ));

        return ApiResponse::noContent();
    }

    public function changeRole(
        ChangeMemberRoleRequest $request,
        ChangeMemberRoleUseCase $useCase,
        string $organizationId,
        string $userId,
    ): JsonResponse {
        $output = $useCase->execute(new ChangeMemberRoleInput(
            organizationId: $organizationId,
            userId: $request->attributes->get('auth_user_id'),
            targetUserId: $userId,
            newRole: $request->validated('role'),
        ));

        return ApiResponse::success(OrganizationMemberResource::fromOutput($output)->toArray());
    }
}
