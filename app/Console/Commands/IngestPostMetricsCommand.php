<?php

namespace App\Console\Commands;

use App\Models\Post;
use App\Services\PostMetricIngestService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('metrics:ingest {post} {--impressions=} {--likes=0} {--comments=0}')]
#[Description('Stub-ingest LinkedIn impressions onto post_metrics until network OAuth exists')]
class IngestPostMetricsCommand extends Command
{
    public function __construct(private PostMetricIngestService $metrics)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $post = Post::query()->find($this->argument('post'));

        if (! $post instanceof Post) {
            $this->error('Post not found.');

            return self::FAILURE;
        }

        $impressions = $this->option('impressions');

        if ($impressions === null || $impressions === '') {
            $this->error('Pass --impressions.');

            return self::FAILURE;
        }

        $this->metrics->ingest($post, [
            'impressions' => (int) $impressions,
            'likes' => (int) $this->option('likes'),
            'comments' => (int) $this->option('comments'),
        ]);

        $this->info('Metrics stored for post '.$post->id);

        return self::SUCCESS;
    }
}
