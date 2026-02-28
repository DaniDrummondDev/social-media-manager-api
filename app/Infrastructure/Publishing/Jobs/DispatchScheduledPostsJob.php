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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final class DispatchScheduledPostsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(ScheduledPostRepositoryInterface $repository): void
    {
        $now = new DateTimeImmutable;

        $dueCount = DB::transaction(function () use ($repository, $now): int {
            $duePosts = $repository->findDuePostsForUpdate($now);

            foreach ($duePosts as $post) {
                $dispatched = $post->markAsDispatched();
                $repository->update($dispatched);

                ProcessScheduledPostJob::dispatch((string) $post->id)->onQueue('publishing');
            }

            return count($duePosts);
        });

        $retryCount = DB::transaction(function () use ($repository, $now): int {
            $retryablePosts = $repository->findRetryableForUpdate($now);

            foreach ($retryablePosts as $post) {
                $dispatched = $post->markAsDispatched();
                $repository->update($dispatched);

                ProcessScheduledPostJob::dispatch((string) $post->id)->onQueue('publishing');
            }

            return count($retryablePosts);
        });

        if ($dueCount > 0 || $retryCount > 0) {
            Log::info('Dispatched scheduled posts', [
                'due' => $dueCount,
                'retryable' => $retryCount,
            ]);
        }
    }
}
