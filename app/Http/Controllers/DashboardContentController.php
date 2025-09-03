<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Product;
use App\Models\Testimonial;
use App\Models\Partner;
use App\Models\TeamMember;
use App\Models\AboutUs;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class DashboardContentController extends Controller
{
    /**
     * Get published articles for dashboard
     * GET /api/dashboard/articles
     */
    public function getArticles(Request $request): JsonResponse
    {
        try {
            $featured = $request->boolean('featured');
            $limit = (int) $request->get('limit', 5);
            $limit = $limit > 0 ? min($limit, 20) : 5;

            // Cache articles for 10 minutes; include filters in key
            $cacheKey = 'dashboard_articles_' . ($featured ? 'featured_' : '') . $limit;
            $articles = Cache::remember($cacheKey, 600, function () use ($featured, $limit) {
                $query = Article::published()->ordered();
                if ($featured) {
                    $query->featured();
                }
                $query->limit($limit);
                return $query->get()->map(function ($article) {
                    return $article->toFrontendArray();
                });
            });

            return response()->json([
                'success' => true,
                'data' => $articles
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load articles'
            ], 500);
        }
    }

    /**
     * Public content articles list
     * GET /api/content/articles?featured=1&limit=3
     */
    public function getPublicArticles(Request $request): JsonResponse
    {
        try {
            $featured = $request->boolean('featured');
            $limit = (int) $request->get('limit', 3);
            $limit = $limit > 0 ? min($limit, 20) : 3;

            $cacheKey = 'public_articles_' . ($featured ? 'featured_' : '') . $limit;
            $articles = Cache::remember($cacheKey, 600, function () use ($featured, $limit) {
                $query = Article::published()->ordered();
                if ($featured) {
                    $query->featured();
                }
                $query->limit($limit);
                return $query->get()->map(function ($article) {
                    return $article->toPublicArray();
                });
            });

            return response()->json([
                'success' => true,
                'data' => $articles
            ]);
        } catch (\Exception $e) {
            // Never return 500 for "no data" - return empty array instead
            return response()->json([
                'success' => true,
                'data' => []
            ]);
        }
    }

    /**
     * Get active products for dashboard
     * GET /api/dashboard/products
     */
    public function getProducts(Request $request): JsonResponse
    {
        try {
            $category = $request->get('category');

            // Cache key includes category for specific caching
            $cacheKey = 'dashboard_products' . ($category ? "_$category" : '');

            $products = Cache::remember($cacheKey, 600, function () use ($category) {
                $query = Product::active()->ordered();
                
                if ($category) {
                    $query->byCategory($category);
                }

                return $query->get()->map(function ($product) {
                    return $product->toFrontendArray();
                });
            });

            return response()->json([
                'success' => true,
                'data' => $products
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load products'
            ], 500);
        }
    }

    /**
     * Get product categories
     * GET /api/dashboard/product-categories
     */
    public function getProductCategories(): JsonResponse
    {
        try {
            $categories = Cache::remember('product_categories', 3600, function () {
                return Product::active()
                    ->select('category')
                    ->distinct()
                    ->orderBy('category')
                    ->pluck('category');
            });

            return response()->json([
                'success' => true,
                'data' => $categories
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load product categories'
            ], 500);
        }
    }

    /**
     * Get dashboard content overview
     * GET /api/dashboard/content
     */
    public function getDashboardContent(): JsonResponse
    {
        try {
            $content = Cache::remember('dashboard_content_overview', 600, function () {
                $articles = Article::published()
                    ->ordered()
                    ->limit(5)
                    ->get()
                    ->map(function ($article) {
                        return $article->toFrontendArray();
                    });

                $products = Product::active()
                    ->ordered()
                    ->limit(10)
                    ->get()
                    ->map(function ($product) {
                        return $product->toFrontendArray();
                    });

                return [
                    'articles' => $articles,
                    'products' => $products,
                    'stats' => [
                        'total_articles' => Article::published()->count(),
                        'total_products' => Product::active()->count(),
                        'product_categories' => Product::active()->distinct('category')->count('category')
                    ]
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $content
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load dashboard content'
            ], 500);
        }
    }

    /**
     * Clear content cache (for admin use)
     * POST /api/admin/content/clear-cache
     */
    public function clearCache(): JsonResponse
    {
        try {
            // Clear all content-related cache
            Cache::forget('dashboard_articles');
            Cache::forget('product_categories');
            Cache::forget('dashboard_content_overview');
            
            // Clear category-specific product caches
            $categories = Product::distinct('category')->pluck('category');
            foreach ($categories as $category) {
                Cache::forget("dashboard_products_$category");
            }
            Cache::forget('dashboard_products');

            return response()->json([
                'success' => true,
                'message' => 'Content cache cleared successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to clear cache'
            ], 500);
        }
    }

    /**
     * Get active testimonials for public display
     * GET /api/dashboard/testimonials
     */
    public function getTestimonials(Request $request): JsonResponse
    {
        try {
            $rating = $request->get('rating');

            $cacheKey = $rating ? "dashboard_testimonials_rating_{$rating}" : 'dashboard_testimonials';

            $testimonials = Cache::remember($cacheKey, 600, function () use ($rating) {
                $query = Testimonial::active()->ordered();

                if ($rating) {
                    $query->byRating($rating);
                }

                return $query->get()->map(function ($testimonial) {
                    return $testimonial->toFrontendArray();
                });
            });

            return response()->json([
                'success' => true,
                'data' => $testimonials
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load testimonials'
            ], 500);
        }
    }

    /**
     * Get active partners for public display
     * GET /api/dashboard/partners
     */
    public function getPartners(): JsonResponse
    {
        try {
            $partners = Cache::remember('dashboard_partners', 600, function () {
                return Partner::active()
                    ->ordered()
                    ->get()
                    ->map(function ($partner) {
                        return $partner->toFrontendArray();
                    });
            });

            return response()->json([
                'success' => true,
                'data' => $partners
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load partners'
            ], 500);
        }
    }

    /**
     * Get active team members for public display
     * GET /api/dashboard/team-members
     */
    public function getTeamMembers(): JsonResponse
    {
        try {
            $teamMembers = Cache::remember('dashboard_team_members', 600, function () {
                return TeamMember::active()
                    ->ordered()
                    ->get()
                    ->map(function ($teamMember) {
                        return $teamMember->toFrontendArray();
                    });
            });

            return response()->json([
                'success' => true,
                'data' => $teamMembers
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load team members'
            ], 500);
        }
    }

    /**
     * Get about us information for public display
     * GET /api/dashboard/about-us
     */
    public function getAboutUs(): JsonResponse
    {
        try {
            $aboutUs = Cache::remember('dashboard_about_us', 3600, function () {
                $about = AboutUs::active()->latest()->first();
                return $about ? $about->toFrontendArray() : null;
            });

            return response()->json([
                'success' => true,
                'data' => $aboutUs
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load about us information'
            ], 500);
        }
    }

    /**
     * Get complete about us page data including team members
     * GET /api/dashboard/about-us/complete
     */
    public function getCompleteAboutUs(): JsonResponse
    {
        try {
            $data = Cache::remember('dashboard_about_us_complete', 600, function () {
                $aboutUs = AboutUs::active()->latest()->first();
                $teamMembers = TeamMember::active()->ordered()->get();

                return [
                    'about_us' => $aboutUs ? $aboutUs->toFrontendArray() : null,
                    'team_members' => $teamMembers->map(function ($member) {
                        return $member->toFrontendArray();
                    })
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $data
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load complete about us information'
            ], 500);
        }
    }
}
