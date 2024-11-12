<?php

use App\Console\Commands\FetchNews;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

Artisan::command('app:fetch-news', function() {
    app(FetchNews::class)->handle();
})->purpose('Fetch news from multiple sources')->everyThreeMinutes();
