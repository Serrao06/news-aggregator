<?php

namespace App\Console\Commands;

use App\Models\News;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class FetchNews extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:fetch-news';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $categories = ['technology', 'business', 'sports', 'health', 'entertainment'];
        foreach ($categories as $category) {
            $this->fetchFromNewsAPI($category);
            $this->fetchFromNYTimes($category);
            $this->fetchFromGuardian($category);
        }
        if ($this->output) {
            $this->info("News from all sources fetched and stored successfully.");
        }
    }

    protected function fetchFromNewsAPI($category) {
        $response = Http::get("https://newsapi.org/v2/top-headlines", [
            'country' => 'us',
            'category' => $category,
            'pageSize' => '10',
            'apikey' => config('services.newsapi.key'),
        ]);

        if ($response->successful()) {
            $articles = $response->json()['articles'];
            foreach ($articles as $article) {
                News::firstOrCreate(
                    [
                        'url' => $article['url'],
                    ],
                    [
                        'title' => $article['title'],
                        'provider' => 'NewsAPI',
                        'description' => $article['description'],
                        'author' => $article['author'] ?? null,
                        'source' => $article['source']['name'],
                        'published_at' => $article['publishedAt'],
                        'category' => $category,
                    ]
                );
            }
        } 
        else {
            \Log::error("Failed to fetch news from NewsAPI for category: $category.");
        }
    }

    protected function fetchFromNYTimes($category)
    {
        $nyTimesCategoryMap = [
            'technology' => 'technology',
            'business' => 'business',
            'sports' => 'sports',
            'health' => 'health',
            'entertainment' => 'arts'
        ];

        if (!isset($nyTimesCategoryMap[$category])) {
            return; 
        }

        $response = Http::get("https://api.nytimes.com/svc/topstories/v2/{$nyTimesCategoryMap[$category]}.json", 
        [
            "api-key" => config('services.nytimesapi.key')
        ]);

        if ($response->successful()) {
            $articles = $response->json()['results'];
            foreach ($articles as $article) {
                News::firstOrCreate(
                    [
                        'url' => $article['url'],
                    ],
                    [
                        'title' => $article['title'],
                        'provider' => 'NYTimes',
                        'description' => $article['abstract'],
                        'author' => $article['byline'] ?? null,
                        'source' => 'NYTimes',
                        'published_at' => $article['published_date'],
                        'category' => $category,
                    ]
                );
            }
        } 
        else {
            \Log::error("Failed to fetch news from NYTimes for category: $category.");
        }
    }

    protected function fetchFromGuardian($category)
    {
        $response = Http::get('https://content.guardianapis.com/search', [
            'api-key' => config('services.guardianapi.key'),
            'section' => $category,
            'show-fields' => 'headline,trailText,webUrl,publication',
            'page-size' => 10,
        ]);

        if ($response->successful()) {
            $articles = $response->json()['response']['results'];
            foreach ($articles as $article) {
                News::updateOrCreate(
                    [
                        'url' => $article['webUrl'],
                    ],
                    [
                        'title' => $article['webTitle'],
                        'provider' => 'The Guardian',
                        'description' => $article['fields']['trailText'] ?? null,
                        'author' => $article['byline'] ?? null,
                        'source' => 'The Guardian',
                        'published_at' => $article['webPublicationDate'],
                        'category' => $category,
                    ]
                );
            }
        } 
        else {
            \Log::error("Failed to fetch news from The Guardian for category: $category.");
        }
    }


}
