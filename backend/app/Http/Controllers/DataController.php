<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use App\Models\NewsArticle;

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

                // Save articles to database
                $this->saveArticlesToDatabase($data['articles'] ?? [], 'general');

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

                // Save articles to database
                $this->saveArticlesToDatabase($data['articles'] ?? [], 'search');

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

                // Save articles to database with category
                $this->saveArticlesToDatabase($data['articles'] ?? [], $category);

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

    /**
     * Save articles to database
     */
    private function saveArticlesToDatabase($articles, $category = null)
    {
        foreach ($articles as $article) {
            try {
                // Download and save image if URL exists
                $localImagePath = null;
                if (!empty($article['urlToImage'])) {
                    $localImagePath = $this->downloadImage($article['urlToImage'], $article['title'] ?? 'untitled');
                }

                NewsArticle::updateOrCreate(
                    ['url' => $article['url']], // Find by URL
                    [
                        'source_id' => $article['source']['id'] ?? null,
                        'source_name' => $article['source']['name'] ?? 'Unknown',
                        'author' => $article['author'] ?? null,
                        'title' => $article['title'] ?? 'No title',
                        'description' => $article['description'] ?? null,
                        'url_to_image' => $localImagePath ?? $article['urlToImage'] ?? null,
                        'published_at' => $article['publishedAt'] ?? now(),
                        'content' => $article['content'] ?? null,
                        'category' => $category,
                    ]
                );
            } catch (\Exception $e) {
                // Continue saving other articles if one fails
                continue;
            }
        }
    }

    /**
     * Download image from URL and save locally
     */
    private function downloadImage($imageUrl, $title)
    {
        try {
            // Generate a safe filename
            $filename = preg_replace('/[^a-z0-9]+/', '-', strtolower($title));
            $filename = substr($filename, 0, 50); // Limit length
            $extension = pathinfo(parse_url($imageUrl, PHP_URL_PATH), PATHINFO_EXTENSION);

            // If no extension found, default to jpg
            if (empty($extension) || !in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                $extension = 'jpg';
            }

            // Create unique filename with timestamp
            $uniqueFilename = $filename . '-' . time() . '.' . $extension;
            $path = 'news-images/' . $uniqueFilename;

            // Download image
            $imageContents = Http::timeout(10)->get($imageUrl)->body();

            // Save to storage/app/public/news-images/
            Storage::disk('public')->put($path, $imageContents);

            // Return the public URL path
            return '/storage/' . $path;

        } catch (\Exception $e) {
            // If download fails, return null (will use original URL as fallback)
            return null;
        }
    }

    /**
     * Get saved news articles from database
     */
    public function getSavedNews(Request $request)
    {
        try {
            $category = $request->input('category');
            $perPage = $request->input('per_page', 1000); // Increased default to show all

            $query = NewsArticle::orderBy('published_at', 'desc');

            if ($category && $category !== 'all') {
                $query->where('category', $category);
            }

            // Get all articles (or use pagination if per_page is specified)
            if ($perPage === 'all') {
                $articles = $query->get();
                return response()->json([
                    'success' => true,
                    'source' => 'database',
                    'total' => $articles->count(),
                    'articles' => $articles
                ]);
            }

            $articles = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'source' => 'database',
                'total' => $articles->total(),
                'current_page' => $articles->currentPage(),
                'last_page' => $articles->lastPage(),
                'articles' => $articles->items()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Import new news from all categories
     */
    public function importNews()
    {
        try {
            $categories = ['business', 'entertainment', 'health', 'science', 'sports', 'technology'];
            $totalNewArticles = 0;
            $beforeCount = NewsArticle::count();

            // Fetch from multiple categories
            foreach ($categories as $category) {
                $this->getNewsByCategory($category);
            }

            // Also fetch general headlines
            $this->getAllNews();

            $afterCount = NewsArticle::count();
            $totalNewArticles = $afterCount - $beforeCount;

            return response()->json([
                'success' => true,
                'message' => 'News imported successfully',
                'new_articles' => $totalNewArticles,
                'total_articles' => $afterCount
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get database statistics
     */
    public function getDatabaseStats()
    {
        try {
            $totalArticles = NewsArticle::count();
            $categoryCounts = NewsArticle::selectRaw('category, COUNT(*) as count')
                ->groupBy('category')
                ->get();

            $latestArticle = NewsArticle::orderBy('published_at', 'desc')->first();
            $oldestArticle = NewsArticle::orderBy('published_at', 'asc')->first();

            return response()->json([
                'success' => true,
                'total_articles' => $totalArticles,
                'categories' => $categoryCounts,
                'latest_article' => $latestArticle ? $latestArticle->published_at : null,
                'oldest_article' => $oldestArticle ? $oldestArticle->published_at : null,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }
}
