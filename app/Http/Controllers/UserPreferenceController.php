<?php

namespace App\Http\Controllers;

use App\Models\News;
use App\Models\UserPreference;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Illuminate\Support\Facades\Cache;
use OpenApi\Annotations as OA;
use Exception;

class UserPreferenceController extends Controller
{

    /**
     * @OA\Post(
     *     path="/api/set-user-preference",
     *     summary="Store user preferences",
     *     description="This endpoint allows the user to store or update their preferences (e.g., author, source, category) in the system.",
     *     tags={"Preferences"},
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"preference_key", "preference_value"},
     *             @OA\Property(
     *                 property="preference_key",
     *                 type="string",
     *                 enum={"author", "source", "category"},
     *                 example="author",
     *                 description="Key of the preference to store."
     *             ),
     *             @OA\Property(
     *                 property="preference_value",
     *                 type="array",
     *                 @OA\Items(
     *                     type="string",
     *                     example="John Doe",
     *                     description="Value for the preference (e.g., list of authors, sources, or categories)"
     *                 ),
     *                 description="List of preference values"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Preferences successfully updated",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Preferences updated successfully"),
     *             @OA\Property(
     *                 property="preferences",
     *                 type="array",
     *                 @OA\Items(type="string"),
     *                 example={"John Doe", "Jane Smith"},
     *                 description="Updated preference values"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *       response=422,
     *       description="Validation failed",
     *       @OA\JsonContent(
     *           @OA\Property(property="message", type="string", example="Validation failed"),
     *           @OA\Property(
     *               property="errors", 
     *               type="object", 
     *               additionalProperties={
     *                   @OA\Property(type="array", @OA\Items(type="string"))
     *               }
     *           )
     *       )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="An error occurred while updating preferences. Please try later.")
     *         )
     *     )
     * )
     */
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

    /**
     * @OA\Get(
     *     path="/api/user-preference",
     *     summary="Fetch news based on user preferences",
     *     description="Returns a list of news articles filtered by the user's preferences (author, source, category). Cached for 10 minutes.",
     *     operationId="getUserNews",
     *     tags={"Preferences"},
     *     security={{"apiAuth": {}}},
     *     @OA\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="Bearer token for authentication",
     *         required=true,
     *         @OA\Schema(
     *             type="string"
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="News articles fetched successfully.",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="News articles fetched successfully."),
     *             @OA\Property(
     *                 property="news",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer"),
     *                     @OA\Property(property="title", type="string"),
     *                     @OA\Property(property="author", type="string"),
     *                     @OA\Property(property="source", type="string"),
     *                     @OA\Property(property="category", type="string"),
     *                     @OA\Property(property="created_at", type="string", format="date-time"),
     *                     @OA\Property(property="updated_at", type="string", format="date-time")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="No preferences or news found for the user.",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="No preferences found for this user.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="An error occurred while fetching news articles.",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="An error occurred while fetching news articles. Please try later.")
     *         )
     *     )
     * )
     */
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
