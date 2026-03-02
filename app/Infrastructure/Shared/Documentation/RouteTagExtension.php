<?php

declare(strict_types=1);

namespace App\Infrastructure\Shared\Documentation;

use Dedoc\Scramble\Extensions\OperationExtension;
use Dedoc\Scramble\Support\Generator\Operation;
use Dedoc\Scramble\Support\RouteInfo;

final class RouteTagExtension extends OperationExtension
{
    private const TAGS = [
        'auth' => 'Authentication',
        'profile' => 'Profile',
        'organizations' => 'Organizations',
        'social-accounts' => 'Social Accounts',
        'campaigns' => 'Campaigns',
        'contents' => 'Content',
        'ai/' => 'AI Generation',
        'publishing' => 'Publishing',
        'scheduled-posts' => 'Scheduled Posts',
        'analytics' => 'Analytics',
        'automation-rules' => 'Automation Rules',
        'comments' => 'Comments',
        'webhooks' => 'Webhooks',
        'billing' => 'Billing',
        'admin' => 'Platform Admin',
        'clients' => 'Client Finance',
        'listening' => 'Social Listening',
        'ai-intelligence' => 'AI Intelligence',
        'ads' => 'Paid Advertising',
        'crm' => 'CRM',
        'media' => 'Media',
    ];

    public function handle(Operation $operation, RouteInfo $routeInfo): void
    {
        $uri = $routeInfo->route->uri();

        foreach (self::TAGS as $pattern => $tag) {
            if (str_contains($uri, $pattern)) {
                $operation->addTag($tag);

                return;
            }
        }

        $operation->addTag('General');
    }
}
