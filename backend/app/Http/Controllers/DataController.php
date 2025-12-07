<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class DataController extends Controller
{
    // Cache duration in minutes (15 minutes)
    private $cacheDuration = 15;

    /**
     * Fetch latest news from NewsAPI with caching
     * Get your free API key from: https://newsapi.org/register
     */
    public function getAllNews()
    {
        try {
            $cacheKey = 'news_top_headlines';

            // Try to get data from cache first
            $cachedData = Cache::get($cacheKey);

            if ($cachedData) {
                return response()->json([
                    'success' => true,
                    'cached' => true,
                    'totalResults' => $cachedData['totalResults'] ?? 0,
                    'articles' => $cachedData['articles'] ?? []
                ]);
            }

            // If not in cache, fetch from API
            $apiKey = env('NEWS_API_KEY', 'demo');

            $response = Http::get('https://newsapi.org/v2/top-headlines', [
                'apiKey' => $apiKey,
                'country' => 'us',
                'pageSize' => 20,
            ]);

            if ($response->successful()) {
                $data = $response->json();

                // Store in cache for 15 minutes
                Cache::put($cacheKey, [
                    'totalResults' => $data['totalResults'] ?? 0,
                    'articles' => $data['articles'] ?? []
                ], now()->addMinutes($this->cacheDuration));

                return response()->json([
                    'success' => true,
                    'cached' => false,
                    'totalResults' => $data['totalResults'] ?? 0,
                    'articles' => $data['articles'] ?? []
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch news from API'
            ], 500);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Search news by keyword/topic with caching
     */
    public function searchNews(Request $request)
    {
        try {
            $query = $request->input('q', 'technology');
            $cacheKey = 'news_search_' . md5($query);

            // Try to get data from cache first
            $cachedData = Cache::get($cacheKey);

            if ($cachedData) {
                return response()->json([
                    'success' => true,
                    'cached' => true,
                    'query' => $query,
                    'totalResults' => $cachedData['totalResults'] ?? 0,
                    'articles' => $cachedData['articles'] ?? []
                ]);
            }

            // If not in cache, fetch from API
            $apiKey = env('NEWS_API_KEY', 'demo');

            $response = Http::get('https://newsapi.org/v2/everything', [
                'apiKey' => $apiKey,
                'q' => $query,
                'pageSize' => 20,
                'sortBy' => 'publishedAt',
                'language' => 'en'
            ]);

            if ($response->successful()) {
                $data = $response->json();

                // Store in cache for 15 minutes
                Cache::put($cacheKey, [
                    'totalResults' => $data['totalResults'] ?? 0,
                    'articles' => $data['articles'] ?? []
                ], now()->addMinutes($this->cacheDuration));

                return response()->json([
                    'success' => true,
                    'cached' => false,
                    'query' => $query,
                    'totalResults' => $data['totalResults'] ?? 0,
                    'articles' => $data['articles'] ?? []
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Failed to search news'
            ], 500);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get news by category with caching
     */
    public function getNewsByCategory($category = 'general')
    {
        try {
            // Valid categories: business, entertainment, general, health, science, sports, technology
            $validCategories = ['business', 'entertainment', 'general', 'health', 'science', 'sports', 'technology'];

            if (!in_array($category, $validCategories)) {
                $category = 'general';
            }

            $cacheKey = 'news_category_' . $category;

            // Try to get data from cache first
            $cachedData = Cache::get($cacheKey);

            if ($cachedData) {
                return response()->json([
                    'success' => true,
                    'cached' => true,
                    'category' => $category,
                    'totalResults' => $cachedData['totalResults'] ?? 0,
                    'articles' => $cachedData['articles'] ?? []
                ]);
            }

            // If not in cache, fetch from API
            $apiKey = env('NEWS_API_KEY', 'demo');

            $response = Http::get('https://newsapi.org/v2/top-headlines', [
                'apiKey' => $apiKey,
                'category' => $category,
                'country' => 'us',
                'pageSize' => 20
            ]);

            if ($response->successful()) {
                $data = $response->json();

                // Store in cache for 15 minutes
                Cache::put($cacheKey, [
                    'totalResults' => $data['totalResults'] ?? 0,
                    'articles' => $data['articles'] ?? []
                ], now()->addMinutes($this->cacheDuration));

                return response()->json([
                    'success' => true,
                    'cached' => false,
                    'category' => $category,
                    'totalResults' => $data['totalResults'] ?? 0,
                    'articles' => $data['articles'] ?? []
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch news by category'
            ], 500);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Clear news cache (useful for testing or manual refresh)
     */
    public function clearCache()
    {
        Cache::flush();

        return response()->json([
            'success' => true,
            'message' => 'Cache cleared successfully'
        ]);
    }
}
