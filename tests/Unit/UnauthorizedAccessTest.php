<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;

class UnauthorizedAccessTest extends TestCase
{
    /**
     * A basic unit test example.
     */
    public function test_unauthorized_user_cannot_set_preferences()
    {
        $preferences = [
            'preference_key' => 'category',
            'preference_value' => ['Sports']
        ];

        $response = $this->postJson('/api/set-user-preference', $preferences);

        $response->assertStatus(401)
            ->assertJson([
                'message' => 'Unauthenticated.'
            ]);
    }

    public function test_unauthorized_user_cannot_fetch_preferences()
    {
        $response = $this->getJson('/api/user-preference');

        $response->assertStatus(401)
            ->assertJson([
                'message' => 'Unauthenticated.'
            ]);
    }
}
