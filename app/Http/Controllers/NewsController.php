<?php

namespace App\Http\Controllers;

use App\Models\News;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Illuminate\Support\Facades\Validator;
use OpenApi\Annotations as OA;


class NewsController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/news",
     *     summary="Get filtered news articles",
     *     description="Fetch news articles based on optional filters like keyword, date, category, source, and pagination or everything.",
     *     tags={"News"},
     *     @OA\Parameter(
     *         name="keyword",
     *         in="query",
     *         required=false,
     *         description="Search keyword for news title or description",
     *         @OA\Schema(type="string", example="Election results")
     *     ),
     *     @OA\Parameter(
     *         name="date",
     *         in="query",
     *         required=false,
     *         description="Publication date of the news article",
     *         @OA\Schema(type="string", format="date", example="2024-11-13")
     *     ),
     *     @OA\Parameter(
     *         name="category",
     *         in="query",
     *         required=false,
     *         description="Category of the news article",
     *         @OA\Schema(type="string", enum={"technology", "business", "sports", "health", "entertainment"}, example="technology")
     *     ),
     *     @OA\Parameter(
     *         name="source",
     *         in="query",
     *         required=false,
     *         description="Source of the news article",
     *         @OA\Schema(type="string", example="BBC News")
     *     ),
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         required=false,
     *         description="Page number for pagination",
     *         @OA\Schema(type="integer", minimum=1, example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="List of filtered news articles",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array",
     *                 @OA\Items(type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="title", type="string", example="Latest Technology Trends"),
     *                     @OA\Property(property="source", type="string", example="BBC News"),
     *                     @OA\Property(property="category", type="string", example="technology"),
     *                     @OA\Property(property="author", type="string", example="John Doe"),
     *                     @OA\Property(property="published_at", type="string", format="date-time", example="2024-11-13T10:00:00Z")
     *                 )
     *             ),
     *             @OA\Property(property="current_page", type="integer", example=1),
     *             @OA\Property(property="last_page", type="integer", example=5),
     *             @OA\Property(property="per_page", type="integer", example=20),
     *             @OA\Property(property="total", type="integer", example=100)
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Invalid filter provided"),
     *             @OA\Property(property="message", type="object",
     *                 @OA\Property(property="keyword", type="array", @OA\Items(type="string", example="The keyword must be a string.")),
     *                 @OA\Property(property="date", type="array", @OA\Items(type="string", example="The date is not a valid date.")),
     *                 @OA\Property(property="category", type="array", @OA\Items(type="string", example="The selected category is invalid."))
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="An error occurred while fetching news articles"),
     *             @OA\Property(property="message", type="string", example="Error details")
     *         )
     *     )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'keyword' => 'string|max:255|nullable',
            'date' => 'date|nullable',
            'category' => 'string|in:technology,business,sports,health,entertainment|nullable', // adjust categories as needed
            'source' => 'string|max:255|nullable',
            'page' => 'integer|min:1|nullable'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => "Inavlid filter provied",
                'message' => $validator->errors()
            ], 422);
        }

        try {
            $query = News::select('id', 'title', 'source', 'category', 'author', 'published_at');

            if ($request->has('keyword')) {
                $query->where(function ($q) use ($request) {
                    $q->where('title', 'like', '%' . $request->keyword . '%')
                        ->orWhere('description', 'like', '%' . $request->keyword . '%');
                });
            }

            if ($request->has('date')) {
                $query->whereDate('published_at', $request->date);
            }

            if ($request->has('category')) {
                $query->where('category', $request->category);
            }

            if ($request->has('source')) {
                $query->where('source', $request->source);
            }

            $news = $query->orderBy('created_at', 'desc')->paginate(20);

            return response()->json($news);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'An error occurred while fetching news articles',
                'message' => $e->getMessage()
            ], 500);
        }
    }


    /**
     * @OA\Get(
     *     path="/api/news/{news}",
     *     summary="Get a specific news article",
     *     description="Fetch a single news article by its news Id.",
     *     tags={"News"},
     *     @OA\Parameter(
     *         name="news",
     *         in="path",
     *         required=true,
     *         description="ID of the news article to fetch",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="News article found",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="id", type="integer", example=1),
     *             @OA\Property(property="title", type="string", example="Latest Technology Trends"),
     *             @OA\Property(property="source", type="string", example="BBC News"),
     *             @OA\Property(property="category", type="string", example="technology"),
     *             @OA\Property(property="author", type="string", example="John Doe"),
     *             @OA\Property(property="published_at", type="string", format="date-time", example="2024-11-13T10:00:00Z")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="News article not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="News article not found")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="An error occurred while fetching news"),
     *             @OA\Property(property="message", type="string", example="Error details")
     *         )
     *     )
     * )
     */
    public function show(News $news): JsonResponse
    {
        try {
            if (!$news) {
                return response()->json([
                    'message' => 'News article not found'
                ], 404);
            }

            return response()->json($news);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'An error occured wile fetching news',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

}
