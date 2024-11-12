<?php

namespace App\Http\Controllers;

use App\Models\News;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Illuminate\Support\Facades\Validator;

class NewsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request) : JsonResponse
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
        }catch (\Exception $e) {
            return response()->json([
                'error' => 'An error occurred while fetching news articles',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
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

        } catch(\Exception $e) {
            return response()->json([
                'error' => 'An error occured wile fetching news',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

}
