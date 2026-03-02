<?php

declare(strict_types=1);

namespace App\Infrastructure\Shared\Documentation;

use Dedoc\Scramble\Extensions\OperationExtension;
use Dedoc\Scramble\Support\Generator\Operation;
use Dedoc\Scramble\Support\Generator\SecurityRequirement;
use Dedoc\Scramble\Support\RouteInfo;

final class JwtSecurityExtension extends OperationExtension
{
    public function handle(Operation $operation, RouteInfo $routeInfo): void
    {
        $middlewares = $routeInfo->route->middleware();

        if (in_array('auth.jwt', $middlewares, true)) {
            $operation->addSecurity(new SecurityRequirement('bearer'));
        }
    }
}
