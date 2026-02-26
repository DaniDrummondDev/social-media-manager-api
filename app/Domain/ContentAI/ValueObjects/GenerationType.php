<?php

declare(strict_types=1);

namespace App\Domain\ContentAI\ValueObjects;

enum GenerationType: string
{
    case Title = 'title';
    case Description = 'description';
    case Hashtags = 'hashtags';
    case FullContent = 'full_content';
    case CrossNetworkAdaptation = 'cross_network_adaptation';
    case CalendarPlanning = 'calendar_planning';
}
