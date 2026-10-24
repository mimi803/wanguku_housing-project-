<?php

use Illuminate\Foundation\Inspiring;
use App\Models\User;
use App\Services\ResourceMapClient;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('resource-map:sync-farms', function (ResourceMapClient $resourceMap) {
    $synced = 0;
    $failed = 0;

    User::query()->where('role', 'farmer')->where('show_on_map', true)
        ->whereNotNull('latitude')->whereNotNull('longitude')
        ->chunkById(100, function ($farmers) use ($resourceMap, &$synced, &$failed) {
            foreach ($farmers as $farmer) {
                $resourceMap->sync($farmer) ? $synced++ : $failed++;
            }
        });

    $this->info("Synced {$synced} farms to the Resource Center.");
    if ($failed > 0) {
        $this->warn("{$failed} farms could not be synced. Check the Resource Center URL, token and logs.");
    }
})->purpose('Publish opted-in Wanguku farms and in-stock produce to the Resource Center map');
