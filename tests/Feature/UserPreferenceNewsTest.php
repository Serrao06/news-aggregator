<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\News;
use App\Models\UserPreference;
use Illuminate\Support\Facades\Cache;

class UserPreferenceNewsTest extends TestCase
{
    use RefreshDatabase;
    /**
     * A basic feature test example.
     */
    public function test_user_can_set_preferences()
    {
        $user = User::factory()->create();
        News::factory()->create(['author' => 'IGN']);
        $preferences = [
            'preference_key' => 'author',  
            'preference_value' => ['IGN'],  
        ];

        $response = $this->actingAs($user)->postJson('/api/set-user-preference', $preferences);

        $response->assertStatus(200)
                 ->assertJson([
                     'message' => 'Preferences updated successfully'
                 ]);

        $this->assertDatabaseHas('user_preferences', [
            'user_id' => $user->id,
            'preference_key' => 'author',
            'preference_value' => "[\"IGN\"]"
        ]);
    }

    public function test_user_cannot_set_preferences_with_missing_value()
    {
        $user = User::factory()->create();

        $preferences = [
            'preference_key' => 'author', 
        ];

        $response = $this->actingAs($user)->postJson('/api/set-user-preference', $preferences);

        $response->assertStatus(422)
                 ->assertJsonStructure([
                     'message',
                     'errors' => [
                         'preference_value' => []
                     ]
                 ]);
    }

    public function test_user_can_fetch_news_based_on_preferences()
    {
        $user = User::factory()->create();
        News::factory()->create(['author' => 'Ign']);
        $preferences = [
            'preference_key' => 'author',
            'preference_value' => ['Ign']
        ];

        $this->actingAs($user)->postJson('/api/set-user-preference', $preferences)
             ->assertStatus(200)
             ->assertJson([
                 'message' => 'Preferences updated successfully'
             ]);
        
        News::factory()->create([
            'author' => 'Ign',
            'category' => 'Technology',
            'source' => 'Tech News',
        ]);
        
        News::factory()->create([
            'author' => 'John',
            'category' => 'Science',
            'source' => 'Tech News',
        ]);
        
        $response = $this->actingAs($user)->getJson('/api/user-preference');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'message',
                     'news' => []
                 ]);
        
        $news = $response->json('news.data');
        $this->assertNotEmpty($news, 'No news articles found for your preferences.');
        
        foreach ($news as $article) {
            $this->assertArrayHasKey('author', $article);
            $this->assertEquals('Ign', $article['author']);
        }
    }
}
