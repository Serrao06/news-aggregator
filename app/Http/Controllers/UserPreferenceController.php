<?php

namespace App\Http\Controllers;

use App\Models\News;
use App\Models\UserPreference;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Exception;

class UserPreferenceController extends Controller
{
    function store(Request $request): JsonResponse
    {

        try {
            $details = $request->validate([
                'preference_key' => 'string|in:author,source,category',
                'preference_value' => 'array|required',
                'preference_value.*' => [
                    'string',
                    function ($attribute, $value, $fail) use ($request) {
                        $preferenceKey = $request->input('preference_key');
                        if (!News::where($preferenceKey, $value)->exists()) {
                            $fail("The selected '{$value}' '$preferenceKey' does not exist");
                        }
                    }
                ],
            ]);

            $userId = $request->user()->id;
            $newPreference = $request->preference_value;
            $preferenceKey = $request->input('preference_key');

            $userPreference = UserPreference::firstOrCreate(
                ['user_id' => $userId, 'preference_key' => $preferenceKey],
                ['preference_value' => $newPreference]
            );

            $currentPreference = $userPreference->preference_value ?? [];

            $preferenceToAdd = array_diff($newPreference, $currentPreference);

            if (!empty($preferenceToAdd)) {
                $userPreference->preference_value = array_merge($currentPreference, $preferenceToAdd);
                $userPreference->save();
            }

            return response()->json([
                'message' => "Preferences updated successfully",
                'preferences' => $userPreference->preference_value
            ], 200);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (Exception $e) {
            Log::error('Failed to update user preferences', [
                'user_id' => $request->input('user_id'),
                'preference_key' => $request->input('preference_key'),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'An error occurred while updating preferences. Please try later.'
            ], 500);
        }
    }

    public function getUserNews(Request $request): JsonResponse
    {
        try {
            $userId = $request->user()->id;

            $userPreferences = UserPreference::select('preference_key', 'preference_value')
                ->where('user_id', $userId)
                ->whereIn('preference_key', ['author', 'source', 'category'])->get();

            if ($userPreferences->isEmpty()) {
                return response()->json([
                    'message' => 'No preferences found for this user.'
                ], 404);
            }

            $cacheKey = 'news_' . auth()->id() . '_preferences_' . md5(json_encode($userPreferences));

            $news = Cache::remember($cacheKey, 600, function () use ($userPreferences) {
                $query = News::query();

                foreach ($userPreferences as $preference) {
                    $preferenceKey = $preference->preference_key;

                    switch ($preferenceKey) {
                        case 'author':
                            $query->orWhereIn('author', $preference->preference_value);
                            break;
                        case 'source':
                            $query->orWhereIn('source', $preference->preference_value);
                            break;
                        case 'category':
                            $query->orWhereIn('category', $preference->preference_value);
                            break;
                    }
                }

                return $query->orderBy('created_at', 'desc')->paginate(20);
                // return $query->toSql();
            });

            if ($news->isEmpty()) {
                return response()->json([
                    'message' => 'No news articles found for your preferences.'
                ], 404);
            }

            return response()->json([
                'message' => 'News articles fetched successfully.',
                'news' => $news
            ], 200);

        } catch (\Exception $e) {
            \Log::error('Error fetching news articles: ' . $e->getMessage());
            return response()->json([
                'message' => "An error occurred while fetching news articles. Please try later."
            ], 500);
        }
    }
}
