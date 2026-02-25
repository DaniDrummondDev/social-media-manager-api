<?php

declare(strict_types=1);

namespace App\Infrastructure\Publishing\Jobs;

use App\Domain\Publishing\Contracts\ScheduledPostRepositoryInterface;
use DateTimeImmutable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

final class DispatchScheduledPostsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(ScheduledPostRepositoryInterface $repository): void
    {
        $now = new DateTimeImmutable;

        $duePosts = $repository->findDuePosts($now);

        foreach ($duePosts as $post) {
            ProcessScheduledPostJob::dispatch((string) $post->id)->onQueue('publishing');
        }

        $retryablePosts = $repository->findRetryable($now);

        foreach ($retryablePosts as $post) {
            ProcessScheduledPostJob::dispatch((string) $post->id)->onQueue('publishing');
        }

        if (count($duePosts) > 0 || count($retryablePosts) > 0) {
            Log::info('Dispatched scheduled posts', [
                'due' => count($duePosts),
                'retryable' => count($retryablePosts),
            ]);
        }
    }
}
