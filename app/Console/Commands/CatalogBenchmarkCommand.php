<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Category;
use App\Models\Event;
use App\Models\Organizer;
use App\Models\Venue;
use App\Services\Discovery\EventSearchService;
use Illuminate\Console\Command;

class CatalogBenchmarkCommand extends Command
{
    protected $signature = 'catalog:benchmark {count=100}';

    protected $description = 'Benchmark catalog search performance';

    public function handle(EventSearchService $searchService): int
    {
        $count = (int) $this->argument('count');

        if ($count <= 0) {
            $this->error('Count must be greater than zero.');

            return self::FAILURE;
        }

        $this->info("Seeding {$count} events for benchmark...");

        $organizer = Organizer::factory()->create();
        $venue = Venue::factory()->create(['organizer_id' => $organizer->id]);
        $category = Category::factory()->create();

        Event::factory()->count($count)->create([
            'organizer_id' => $organizer->id,
            'venue_id' => $venue->venue_id,
            'category_id' => $category->category_id,
            'status' => 'published',
            'visibility' => 'public',
        ]);

        $this->info('Starting benchmark...');

        $start = microtime(true);
        $searchService->search();
        $firstCall = microtime(true) - $start;

        $start = microtime(true);
        $searchService->search();
        $secondCall = microtime(true) - $start;

        $this->line('First call (uncached or warm): '.round($firstCall * 1000, 2).'ms');
        $this->line('Second call (cached): '.round($secondCall * 1000, 2).'ms');

        return self::SUCCESS;
    }
}
