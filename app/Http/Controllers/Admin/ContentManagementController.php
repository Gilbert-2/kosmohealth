<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Product;
use App\Models\Testimonial;
use App\Models\Partner;
use App\Models\TeamMember;
use App\Models\AboutUs;
use App\Models\ContactMessage;
use App\Models\ContactInfo;
use App\Models\UserStory;
use App\Models\PublicStory;
use App\Models\StoryInteraction;
use App\Services\ContactEmailService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class ContentManagementController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth:sanctum', 'role:admin']);
    }

    // ========================================
    // ARTICLES MANAGEMENT
    // ========================================

    /**
     * Get all articles with pagination
     * GET /api/admin/content/articles
     */
    public function getArticles(Request $request): JsonResponse
    {
        try {
            $query = Article::with('creator:id,name,email')
                ->orderBy('sort_order', 'asc')
                ->orderBy('created_at', 'desc');

            // Apply filters
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%");
                });
            }

            $articles = $query->paginate($request->get('per_page', 15));

            return response()->json([
                'success' => true,
                'data' => $articles
            ]);

        } catch (\Exception $e) {
            Log::error('Admin get articles error', [
                'error' => $e->getMessage(),
                'admin_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to load articles'
            ], 500);
        }
    }

    /**
     * Create new article
     * POST /api/admin/content/articles
     */
    public function createArticle(Request $request): JsonResponse
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            // Image may be a file upload or a string path/url
            'image' => 'sometimes|nullable',
            'image_url' => 'sometimes|nullable|string',
            'status' => 'sometimes|in:draft,published,archived',
            'sort_order' => 'nullable|integer|min:0',
            'metadata' => 'nullable|array'
        ]);

        try {
            $normalizedImageUrl = null;
            // If an image file is provided, store it
            if ($request->hasFile('image') && $request->file('image')->isValid()) {
                $file = $request->file('image');
                $directory = 'editor-images/' . Carbon::now()->format('Y/m/d');
                if (!Storage::disk('public')->exists($directory)) {
                    Storage::disk('public')->makeDirectory($directory);
                }
                $filename = $file->hashName();
                $filePath = Storage::disk('public')->putFileAs($directory, $file, $filename);
                // Use signed storage route to ensure availability regardless of symlink
                $normalizedImageUrl = route('storage.image', ['path' => ltrim($filePath, '/')]);
            } else {
                $rawImage = $request->image_url ?? $request->image;
                $normalizedImageUrl = $this->normalizeImageUrl($rawImage);
            }
            $article = Article::create([
                'title' => $request->title,
                'description' => $request->description,
                'image_url' => $normalizedImageUrl,
                'status' => $request->get('status', 'draft'),
                'sort_order' => $request->sort_order ?? 0,
                'metadata' => $request->metadata,
                'created_by' => auth()->id()
            ]);

            Log::info('Admin created article', [
                'article_id' => $article->uuid,
                'admin_id' => auth()->id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Article created successfully',
                'data' => $article->load('creator:id,name,email')
            ], 201);

        } catch (\Exception $e) {
            Log::error('Admin create article error', [
                'error' => $e->getMessage(),
                'admin_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create article'
            ], 500);
        }
    }

    /**
     * Update article
     * PUT /api/admin/content/articles/{uuid}
     */
    public function updateArticle(Request $request, string $uuid): JsonResponse
    {
        $request->validate([
            // All fields optional for partial updates
            'title' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            // Accept either a full URL or a storage path produced by our uploader
            'image_url' => 'sometimes|nullable|string',
            'image' => 'sometimes|nullable',
            'status' => 'sometimes|in:draft,published,archived',
            'sort_order' => 'sometimes|nullable|integer|min:0',
            'metadata' => 'sometimes|nullable|array'
        ]);

        try {
            $article = Article::where('uuid', $uuid)->firstOrFail();

            $updateData = [];
            if ($request->has('title')) {
                $updateData['title'] = $request->title;
            }
            if ($request->has('description')) {
                $updateData['description'] = $request->description;
            }
            if ($request->hasFile('image') && $request->file('image')->isValid()) {
                $file = $request->file('image');
                $directory = 'editor-images/' . Carbon::now()->format('Y/m/d');
                if (!Storage::disk('public')->exists($directory)) {
                    Storage::disk('public')->makeDirectory($directory);
                }
                $filename = $file->hashName();
                $filePath = Storage::disk('public')->putFileAs($directory, $file, $filename);
                $updateData['image_url'] = route('storage.image', ['path' => ltrim($filePath, '/')]);
            } elseif ($request->hasAny(['image_url', 'image'])) {
                $rawImage = $request->image_url ?? $request->image;
                $updateData['image_url'] = $this->normalizeImageUrl($rawImage);
            }
            if ($request->has('status')) {
                $updateData['status'] = $request->status;
            }
            if ($request->has('sort_order')) {
                $updateData['sort_order'] = $request->sort_order;
            }
            if ($request->has('metadata')) {
                $updateData['metadata'] = $request->metadata;
            }

            // If nothing to update, keep as is
            if (!empty($updateData)) {
                $article->update($updateData);
            }

            Log::info('Admin updated article', [
                'article_id' => $article->uuid,
                'admin_id' => auth()->id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Article updated successfully',
                'data' => $article->load('creator:id,name,email')
            ]);

        } catch (\Exception $e) {
            Log::error('Admin update article error', [
                'error' => $e->getMessage(),
                'admin_id' => auth()->id(),
                'article_uuid' => $uuid
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update article'
            ], 500);
        }
    }

    /**
     * Delete article
     * DELETE /api/admin/content/articles/{uuid}
     */
    public function deleteArticle(string $uuid): JsonResponse
    {
        try {
            $article = Article::where('uuid', $uuid)->firstOrFail();
            $article->delete();

            Log::info('Admin deleted article', [
                'article_id' => $uuid,
                'admin_id' => auth()->id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Article deleted successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Admin delete article error', [
                'error' => $e->getMessage(),
                'admin_id' => auth()->id(),
                'article_uuid' => $uuid
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete article'
            ], 500);
        }
    }

    /**
     * Normalize incoming image reference to a full URL
     */
    private function normalizeImageUrl(?string $raw): ?string
    {
        if (empty($raw)) {
            return null;
        }
        // Already an absolute URL
        if (preg_match('/^https?:\/\//i', $raw)) {
            return $raw;
        }
        $path = ltrim($raw, '/');
        if (str_starts_with($path, 'storage/')) {
            return asset($path);
        }
        if (str_starts_with($path, 'editor-images/') || str_starts_with($path, 'public/')) {
            return asset('storage/' . $path);
        }
        return asset('storage/' . $path);
    }

    // ========================================
    // PRODUCTS MANAGEMENT
    // ========================================

    /**
     * Get all products with pagination
     * GET /api/admin/content/products
     */
    public function getProducts(Request $request): JsonResponse
    {
        try {
            $query = Product::with('creator:id,name,email')
                ->orderBy('sort_order', 'asc')
                ->orderBy('created_at', 'desc');

            // Apply filters
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            if ($request->has('category')) {
                $query->where('category', $request->category);
            }

            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%")
                      ->orWhere('category', 'like', "%{$search}%");
                });
            }

            $products = $query->paginate($request->get('per_page', 15));

            return response()->json([
                'success' => true,
                'data' => $products
            ]);

        } catch (\Exception $e) {
            Log::error('Admin get products error', [
                'error' => $e->getMessage(),
                'admin_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to load products'
            ], 500);
        }
    }

    /**
     * Create new product
     * POST /api/admin/content/products
     */
    public function createProduct(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            // Icon may be a file upload or a string path/url
            'icon' => 'sometimes|nullable',
            'icon_url' => 'sometimes|nullable|string',
            'category' => 'required|string|max:100',
            'features' => 'sometimes|array',
            'features.*' => 'string',
            'price' => 'sometimes|nullable|string|max:50',
            'status' => 'sometimes|in:active,inactive,archived',
            'sort_order' => 'sometimes|nullable|integer|min:0',
            'metadata' => 'sometimes|nullable|array'
        ]);

        try {
            // Handle icon: file upload or URL/path
            $iconUrl = null;
            if ($request->hasFile('icon') && $request->file('icon')->isValid()) {
                $file = $request->file('icon');
                $directory = 'editor-images/' . Carbon::now()->format('Y/m/d');
                if (!Storage::disk('public')->exists($directory)) {
                    Storage::disk('public')->makeDirectory($directory);
                }
                $filename = $file->hashName();
                $filePath = Storage::disk('public')->putFileAs($directory, $file, $filename);
                $iconUrl = route('storage.image', ['path' => ltrim($filePath, '/')]);
            } else {
                $raw = $request->icon_url ?? $request->icon;
                $iconUrl = $this->normalizeImageUrl($raw);
            }

            $product = Product::create([
                'name' => $request->name,
                'description' => $request->description,
                'icon' => $iconUrl,
                'category' => $request->category,
                'features' => $request->get('features', []),
                'price' => $request->get('price'),
                'status' => $request->get('status', 'active'),
                'sort_order' => $request->get('sort_order', 0),
                'metadata' => $request->get('metadata'),
                'created_by' => auth()->id()
            ]);

            Log::info('Admin created product', [
                'product_id' => $product->uuid,
                'admin_id' => auth()->id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Product created successfully',
                'data' => $product->load('creator:id,name,email')
            ], 201);

        } catch (\Exception $e) {
            Log::error('Admin create product error', [
                'error' => $e->getMessage(),
                'admin_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create product'
            ], 500);
        }
    }

    /**
     * Update product
     * PUT /api/admin/content/products/{uuid}
     */
    public function updateProduct(Request $request, string $uuid): JsonResponse
    {
        $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            // Icon may be a file upload or a string path/url
            'icon' => 'sometimes|nullable',
            'icon_url' => 'sometimes|nullable|string',
            'category' => 'sometimes|string|max:100',
            'features' => 'sometimes|array',
            'features.*' => 'string',
            'price' => 'sometimes|nullable|string|max:50',
            'status' => 'sometimes|in:active,inactive,archived',
            'sort_order' => 'sometimes|nullable|integer|min:0',
            'metadata' => 'sometimes|nullable|array'
        ]);

        try {
            $product = Product::where('uuid', $uuid)->firstOrFail();

            $updateData = [];
            if ($request->has('name')) { $updateData['name'] = $request->name; }
            if ($request->has('description')) { $updateData['description'] = $request->description; }
            if ($request->hasFile('icon') && $request->file('icon')->isValid()) {
                $file = $request->file('icon');
                $directory = 'editor-images/' . Carbon::now()->format('Y/m/d');
                if (!Storage::disk('public')->exists($directory)) {
                    Storage::disk('public')->makeDirectory($directory);
                }
                $filename = $file->hashName();
                $filePath = Storage::disk('public')->putFileAs($directory, $file, $filename);
                $updateData['icon'] = route('storage.image', ['path' => ltrim($filePath, '/')]);
            } elseif ($request->hasAny(['icon_url', 'icon'])) {
                $raw = $request->icon_url ?? $request->icon;
                $updateData['icon'] = $this->normalizeImageUrl($raw);
            }
            if ($request->has('category')) { $updateData['category'] = $request->category; }
            if ($request->has('features')) { $updateData['features'] = $request->features; }
            if ($request->has('price')) { $updateData['price'] = $request->price; }
            if ($request->has('status')) { $updateData['status'] = $request->status; }
            if ($request->has('sort_order')) { $updateData['sort_order'] = $request->sort_order; }
            if ($request->has('metadata')) { $updateData['metadata'] = $request->metadata; }

            if (!empty($updateData)) {
                $product->update($updateData);
            }

            Log::info('Admin updated product', [
                'product_id' => $product->uuid,
                'admin_id' => auth()->id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Product updated successfully',
                'data' => $product->load('creator:id,name,email')
            ]);

        } catch (\Exception $e) {
            Log::error('Admin update product error', [
                'error' => $e->getMessage(),
                'admin_id' => auth()->id(),
                'product_uuid' => $uuid
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update product'
            ], 500);
        }
    }

    /**
     * Delete product
     * DELETE /api/admin/content/products/{uuid}
     */
    public function deleteProduct(string $uuid): JsonResponse
    {
        try {
            $product = Product::where('uuid', $uuid)->firstOrFail();
            $product->delete();

            Log::info('Admin deleted product', [
                'product_id' => $uuid,
                'admin_id' => auth()->id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Product deleted successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Admin delete product error', [
                'error' => $e->getMessage(),
                'admin_id' => auth()->id(),
                'product_uuid' => $uuid
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete product'
            ], 500);
        }
    }

    // ========================================
    // BULK OPERATIONS
    // ========================================

    /**
     * Update sort order for articles
     * POST /api/admin/content/articles/reorder
     */
    public function reorderArticles(Request $request): JsonResponse
    {
        $request->validate([
            'articles' => 'required|array',
            'articles.*.uuid' => 'required|string',
            'articles.*.sort_order' => 'required|integer|min:0'
        ]);

        try {
            foreach ($request->articles as $articleData) {
                Article::where('uuid', $articleData['uuid'])
                    ->update(['sort_order' => $articleData['sort_order']]);
            }

            Log::info('Admin reordered articles', [
                'admin_id' => auth()->id(),
                'count' => count($request->articles)
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Articles reordered successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Admin reorder articles error', [
                'error' => $e->getMessage(),
                'admin_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to reorder articles'
            ], 500);
        }
    }

    /**
     * Update sort order for products
     * POST /api/admin/content/products/reorder
     */
    public function reorderProducts(Request $request): JsonResponse
    {
        $request->validate([
            'products' => 'required|array',
            'products.*.uuid' => 'required|string',
            'products.*.sort_order' => 'required|integer|min:0'
        ]);

        try {
            foreach ($request->products as $productData) {
                Product::where('uuid', $productData['uuid'])
                    ->update(['sort_order' => $productData['sort_order']]);
            }

            Log::info('Admin reordered products', [
                'admin_id' => auth()->id(),
                'count' => count($request->products)
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Products reordered successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Admin reorder products error', [
                'error' => $e->getMessage(),
                'admin_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to reorder products'
            ], 500);
        }
    }

    /**
     * Clear content cache
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

            Log::info('Admin cleared content cache', [
                'admin_id' => auth()->id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Content cache cleared successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Admin clear cache error', [
                'error' => $e->getMessage(),
                'admin_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to clear cache'
            ], 500);
        }
    }

    // ========================================
    // TESTIMONIALS MANAGEMENT
    // ========================================

    /**
     * Get testimonials with pagination and filters
     * GET /api/admin/content/testimonials
     */
    public function getTestimonials(Request $request): JsonResponse
    {
        try {
            $query = Testimonial::with('creator:id,name,email');

            // Apply filters
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            if ($request->has('rating')) {
                $query->where('rating', $request->rating);
            }

            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('text', 'like', "%{$search}%");
                });
            }

            $testimonials = $query->ordered()
                ->paginate($request->get('per_page', 15));

            return response()->json([
                'success' => true,
                'data' => $testimonials
            ]);

        } catch (\Exception $e) {
            Log::error('Admin get testimonials error', [
                'error' => $e->getMessage(),
                'admin_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to load testimonials'
            ], 500);
        }
    }

    /**
     * Create new testimonial
     * POST /api/admin/content/testimonials
     */
    public function createTestimonial(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'text' => 'required|string',
            'rating' => 'sometimes|integer|min:1|max:5',
            'status' => 'sometimes|in:active,inactive,archived',
            'sort_order' => 'sometimes|nullable|integer|min:0',
            'metadata' => 'sometimes|nullable|array'
        ]);

        try {
            $testimonial = Testimonial::create([
                'name' => $request->name,
                'text' => $request->text,
                'rating' => $request->get('rating', 5),
                'status' => $request->get('status', 'active'),
                'sort_order' => $request->get('sort_order', 0),
                'metadata' => $request->get('metadata'),
                'created_by' => auth()->id()
            ]);

            Log::info('Admin created testimonial', [
                'testimonial_id' => $testimonial->uuid,
                'admin_id' => auth()->id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Testimonial created successfully',
                'data' => $testimonial->load('creator:id,name,email')
            ], 201);

        } catch (\Exception $e) {
            Log::error('Admin create testimonial error', [
                'error' => $e->getMessage(),
                'admin_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create testimonial'
            ], 500);
        }
    }

    /**
     * Update testimonial
     * PUT /api/admin/content/testimonials/{uuid}
     */
    public function updateTestimonial(Request $request, string $uuid): JsonResponse
    {
        $request->validate([
            'name' => 'sometimes|string|max:255',
            'text' => 'sometimes|string',
            'rating' => 'sometimes|integer|min:1|max:5',
            'status' => 'sometimes|in:active,inactive,archived',
            'sort_order' => 'sometimes|nullable|integer|min:0',
            'metadata' => 'sometimes|nullable|array'
        ]);

        try {
            $testimonial = Testimonial::where('uuid', $uuid)->firstOrFail();

            $updateData = [];
            if ($request->has('name')) { $updateData['name'] = $request->name; }
            if ($request->has('text')) { $updateData['text'] = $request->text; }
            if ($request->has('rating')) { $updateData['rating'] = $request->rating; }
            if ($request->has('status')) { $updateData['status'] = $request->status; }
            if ($request->has('sort_order')) { $updateData['sort_order'] = $request->sort_order; }
            if ($request->has('metadata')) { $updateData['metadata'] = $request->metadata; }
            if (!empty($updateData)) {
                $testimonial->update($updateData);
            }

            Log::info('Admin updated testimonial', [
                'testimonial_id' => $testimonial->uuid,
                'admin_id' => auth()->id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Testimonial updated successfully',
                'data' => $testimonial->load('creator:id,name,email')
            ]);

        } catch (\Exception $e) {
            Log::error('Admin update testimonial error', [
                'error' => $e->getMessage(),
                'admin_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update testimonial'
            ], 500);
        }
    }

    /**
     * Delete testimonial
     * DELETE /api/admin/content/testimonials/{uuid}
     */
    public function deleteTestimonial(string $uuid): JsonResponse
    {
        try {
            $testimonial = Testimonial::where('uuid', $uuid)->firstOrFail();
            $testimonial->delete();

            Log::info('Admin deleted testimonial', [
                'testimonial_id' => $uuid,
                'admin_id' => auth()->id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Testimonial deleted successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Admin delete testimonial error', [
                'error' => $e->getMessage(),
                'admin_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete testimonial'
            ], 500);
        }
    }

    // ========================================
    // PARTNERS MANAGEMENT
    // ========================================

    /**
     * Get partners with pagination and filters
     * GET /api/admin/content/partners
     */
    public function getPartners(Request $request): JsonResponse
    {
        try {
            $query = Partner::with('creator:id,name,email');

            // Apply filters
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('url', 'like', "%{$search}%");
                });
            }

            $partners = $query->ordered()
                ->paginate($request->get('per_page', 15));

            return response()->json([
                'success' => true,
                'data' => $partners
            ]);

        } catch (\Exception $e) {
            Log::error('Admin get partners error', [
                'error' => $e->getMessage(),
                'admin_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to load partners'
            ], 500);
        }
    }

    /**
     * Create new partner
     * POST /api/admin/content/partners
     */
    public function createPartner(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'logo' => 'required|string|max:255',
            'url' => 'nullable|url',
            'status' => 'required|in:active,inactive,archived',
            'sort_order' => 'nullable|integer|min:0',
            'metadata' => 'nullable|array'
        ]);

        try {
            $partner = Partner::create([
                'name' => $request->name,
                'logo' => $request->logo,
                'url' => $request->url,
                'status' => $request->status,
                'sort_order' => $request->sort_order ?? 0,
                'metadata' => $request->metadata,
                'created_by' => auth()->id()
            ]);

            Log::info('Admin created partner', [
                'partner_id' => $partner->uuid,
                'admin_id' => auth()->id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Partner created successfully',
                'data' => $partner->load('creator:id,name,email')
            ], 201);

        } catch (\Exception $e) {
            Log::error('Admin create partner error', [
                'error' => $e->getMessage(),
                'admin_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create partner'
            ], 500);
        }
    }

    /**
     * Update partner
     * PUT /api/admin/content/partners/{uuid}
     */
    public function updatePartner(Request $request, string $uuid): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'logo' => 'required|string|max:255',
            'url' => 'nullable|url',
            'status' => 'required|in:active,inactive,archived',
            'sort_order' => 'nullable|integer|min:0',
            'metadata' => 'nullable|array'
        ]);

        try {
            $partner = Partner::where('uuid', $uuid)->firstOrFail();

            $partner->update([
                'name' => $request->name,
                'logo' => $request->logo,
                'url' => $request->url,
                'status' => $request->status,
                'sort_order' => $request->sort_order ?? $partner->sort_order,
                'metadata' => $request->metadata
            ]);

            Log::info('Admin updated partner', [
                'partner_id' => $partner->uuid,
                'admin_id' => auth()->id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Partner updated successfully',
                'data' => $partner->load('creator:id,name,email')
            ]);

        } catch (\Exception $e) {
            Log::error('Admin update partner error', [
                'error' => $e->getMessage(),
                'admin_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update partner'
            ], 500);
        }
    }

    /**
     * Delete partner
     * DELETE /api/admin/content/partners/{uuid}
     */
    public function deletePartner(string $uuid): JsonResponse
    {
        try {
            $partner = Partner::where('uuid', $uuid)->firstOrFail();
            $partner->delete();

            Log::info('Admin deleted partner', [
                'partner_id' => $uuid,
                'admin_id' => auth()->id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Partner deleted successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Admin delete partner error', [
                'error' => $e->getMessage(),
                'admin_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete partner'
            ], 500);
        }
    }

    // ========================================
    // TEAM MEMBERS MANAGEMENT
    // ========================================

    /**
     * Get team members with pagination and filters
     * GET /api/admin/content/team-members
     */
    public function getTeamMembers(Request $request): JsonResponse
    {
        try {
            $query = TeamMember::with('creator:id,name,email');

            // Apply filters
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('role', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%");
                });
            }

            $teamMembers = $query->ordered()
                ->paginate($request->get('per_page', 15));

            return response()->json([
                'success' => true,
                'data' => $teamMembers
            ]);

        } catch (\Exception $e) {
            Log::error('Admin get team members error', [
                'error' => $e->getMessage(),
                'admin_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to load team members'
            ], 500);
        }
    }

    /**
     * Create new team member
     * POST /api/admin/content/team-members
     */
    public function createTeamMember(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'role' => 'required|string|max:255',
            'description' => 'required|string',
            'image_url' => 'nullable|url',
            'status' => 'required|in:active,inactive,archived',
            'sort_order' => 'nullable|integer|min:0',
            'metadata' => 'nullable|array'
        ]);

        try {
            $teamMember = TeamMember::create([
                'name' => $request->name,
                'role' => $request->role,
                'description' => $request->description,
                'image_url' => $request->image_url,
                'status' => $request->status,
                'sort_order' => $request->sort_order ?? 0,
                'metadata' => $request->metadata,
                'created_by' => auth()->id()
            ]);

            Log::info('Admin created team member', [
                'team_member_id' => $teamMember->uuid,
                'admin_id' => auth()->id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Team member created successfully',
                'data' => $teamMember->load('creator:id,name,email')
            ], 201);

        } catch (\Exception $e) {
            Log::error('Admin create team member error', [
                'error' => $e->getMessage(),
                'admin_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create team member'
            ], 500);
        }
    }

    /**
     * Update team member
     * PUT /api/admin/content/team-members/{uuid}
     */
    public function updateTeamMember(Request $request, string $uuid): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'role' => 'required|string|max:255',
            'description' => 'required|string',
            'image_url' => 'nullable|url',
            'status' => 'required|in:active,inactive,archived',
            'sort_order' => 'nullable|integer|min:0',
            'metadata' => 'nullable|array'
        ]);

        try {
            $teamMember = TeamMember::where('uuid', $uuid)->firstOrFail();

            $teamMember->update([
                'name' => $request->name,
                'role' => $request->role,
                'description' => $request->description,
                'image_url' => $request->image_url,
                'status' => $request->status,
                'sort_order' => $request->sort_order ?? $teamMember->sort_order,
                'metadata' => $request->metadata
            ]);

            Log::info('Admin updated team member', [
                'team_member_id' => $teamMember->uuid,
                'admin_id' => auth()->id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Team member updated successfully',
                'data' => $teamMember->load('creator:id,name,email')
            ]);

        } catch (\Exception $e) {
            Log::error('Admin update team member error', [
                'error' => $e->getMessage(),
                'admin_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update team member'
            ], 500);
        }
    }

    /**
     * Delete team member
     * DELETE /api/admin/content/team-members/{uuid}
     */
    public function deleteTeamMember(string $uuid): JsonResponse
    {
        try {
            $teamMember = TeamMember::where('uuid', $uuid)->firstOrFail();
            $teamMember->delete();

            Log::info('Admin deleted team member', [
                'team_member_id' => $uuid,
                'admin_id' => auth()->id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Team member deleted successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Admin delete team member error', [
                'error' => $e->getMessage(),
                'admin_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete team member'
            ], 500);
        }
    }

    // ========================================
    // ABOUT US MANAGEMENT
    // ========================================

    /**
     * Get about us information
     * GET /api/admin/content/about-us
     */
    public function getAboutUs(): JsonResponse
    {
        try {
            $aboutUs = AboutUs::with('creator:id,name,email')
                ->active()
                ->latest()
                ->first();

            return response()->json([
                'success' => true,
                'data' => $aboutUs
            ]);

        } catch (\Exception $e) {
            Log::error('Admin get about us error', [
                'error' => $e->getMessage(),
                'admin_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to load about us information'
            ], 500);
        }
    }

    /**
     * Create or update about us information
     * POST /api/admin/content/about-us
     */
    public function createOrUpdateAboutUs(Request $request): JsonResponse
    {
        $request->validate([
            'mission' => 'required|string',
            'vision' => 'required|string',
            'values' => 'nullable|string',
            'story' => 'nullable|string',
            'status' => 'required|in:active,inactive',
            'metadata' => 'nullable|array'
        ]);

        try {
            // Deactivate existing about us entries
            AboutUs::where('status', 'active')->update(['status' => 'inactive']);

            $aboutUs = AboutUs::create([
                'mission' => $request->mission,
                'vision' => $request->vision,
                'values' => $request->values,
                'story' => $request->story,
                'status' => $request->status,
                'metadata' => $request->metadata,
                'created_by' => auth()->id()
            ]);

            Log::info('Admin created/updated about us', [
                'about_us_id' => $aboutUs->uuid,
                'admin_id' => auth()->id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'About us information saved successfully',
                'data' => $aboutUs->load('creator:id,name,email')
            ], 201);

        } catch (\Exception $e) {
            Log::error('Admin create/update about us error', [
                'error' => $e->getMessage(),
                'admin_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to save about us information'
            ], 500);
        }
    }

    /**
     * Update existing about us information
     * PUT /api/admin/content/about-us/{uuid}
     */
    public function updateAboutUs(Request $request, string $uuid): JsonResponse
    {
        $request->validate([
            'mission' => 'required|string',
            'vision' => 'required|string',
            'values' => 'nullable|string',
            'story' => 'nullable|string',
            'status' => 'required|in:active,inactive',
            'metadata' => 'nullable|array'
        ]);

        try {
            $aboutUs = AboutUs::where('uuid', $uuid)->firstOrFail();

            $aboutUs->update([
                'mission' => $request->mission,
                'vision' => $request->vision,
                'values' => $request->values,
                'story' => $request->story,
                'status' => $request->status,
                'metadata' => $request->metadata
            ]);

            Log::info('Admin updated about us', [
                'about_us_id' => $aboutUs->uuid,
                'admin_id' => auth()->id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'About us information updated successfully',
                'data' => $aboutUs->load('creator:id,name,email')
            ]);

        } catch (\Exception $e) {
            Log::error('Admin update about us error', [
                'error' => $e->getMessage(),
                'admin_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update about us information'
            ], 500);
        }
    }

    // ========================================
    // CONTACT MESSAGES MANAGEMENT
    // ========================================

    /**
     * Get contact messages with pagination and filters
     * GET /api/admin/content/contact-messages
     */
    public function getContactMessages(Request $request): JsonResponse
    {
        try {
            $query = ContactMessage::with('assignedAdmin:id,name,email');

            // Apply filters
            if ($request->has('status')) {
                $query->byStatus($request->status);
            }

            if ($request->has('priority')) {
                $query->byPriority($request->priority);
            }

            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%")
                      ->orWhere('subject', 'like', "%{$search}%")
                      ->orWhere('message', 'like', "%{$search}%");
                });
            }

            if ($request->has('date_from')) {
                $query->where('created_at', '>=', $request->date_from);
            }

            if ($request->has('date_to')) {
                $query->where('created_at', '<=', $request->date_to);
            }

            $messages = $query->orderBy('created_at', 'desc')
                ->paginate($request->get('per_page', 15));

            return response()->json([
                'success' => true,
                'data' => $messages
            ]);

        } catch (\Exception $e) {
            Log::error('Admin get contact messages error', [
                'error' => $e->getMessage(),
                'admin_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to load contact messages'
            ], 500);
        }
    }

    /**
     * Get contact message details
     * GET /api/admin/content/contact-messages/{uuid}
     */
    public function getContactMessage(string $uuid): JsonResponse
    {
        try {
            $message = ContactMessage::with('assignedAdmin:id,name,email')
                ->where('uuid', $uuid)
                ->firstOrFail();

            // Mark as read if it's new
            $message->markAsRead();

            return response()->json([
                'success' => true,
                'data' => $message->toAdminArray()
            ]);

        } catch (\Exception $e) {
            Log::error('Admin get contact message error', [
                'error' => $e->getMessage(),
                'admin_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to load contact message'
            ], 500);
        }
    }

    /**
     * Update contact message status/assignment
     * PUT /api/admin/content/contact-messages/{uuid}
     */
    public function updateContactMessage(Request $request, string $uuid): JsonResponse
    {
        $request->validate([
            'status' => 'nullable|in:new,read,replied,archived',
            'priority' => 'nullable|in:low,medium,high,urgent',
            'assigned_to' => 'nullable|exists:users,id',
            'admin_notes' => 'nullable|string|max:1000'
        ]);

        try {
            $message = ContactMessage::where('uuid', $uuid)->firstOrFail();

            $updateData = array_filter([
                'status' => $request->status,
                'priority' => $request->priority,
                'assigned_to' => $request->assigned_to,
                'admin_notes' => $request->admin_notes
            ]);

            $message->update($updateData);

            Log::info('Admin updated contact message', [
                'message_id' => $message->uuid,
                'admin_id' => auth()->id(),
                'updates' => $updateData
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Contact message updated successfully',
                'data' => $message->load('assignedAdmin:id,name,email')->toAdminArray()
            ]);

        } catch (\Exception $e) {
            Log::error('Admin update contact message error', [
                'error' => $e->getMessage(),
                'admin_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update contact message'
            ], 500);
        }
    }

    /**
     * Delete contact message
     * DELETE /api/admin/content/contact-messages/{uuid}
     */
    public function deleteContactMessage(string $uuid): JsonResponse
    {
        try {
            $message = ContactMessage::where('uuid', $uuid)->firstOrFail();
            $message->delete();

            Log::info('Admin deleted contact message', [
                'message_id' => $uuid,
                'admin_id' => auth()->id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Contact message deleted successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Admin delete contact message error', [
                'error' => $e->getMessage(),
                'admin_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete contact message'
            ], 500);
        }
    }

    // ========================================
    // CONTACT INFO MANAGEMENT
    // ========================================

    /**
     * Get contact information
     * GET /api/admin/content/contact-info
     */
    public function getContactInfo(): JsonResponse
    {
        try {
            $contactInfo = ContactInfo::with('creator:id,name,email')
                ->active()
                ->latest()
                ->first();

            return response()->json([
                'success' => true,
                'data' => $contactInfo?->toAdminArray()
            ]);

        } catch (\Exception $e) {
            Log::error('Admin get contact info error', [
                'error' => $e->getMessage(),
                'admin_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to load contact information'
            ], 500);
        }
    }

    /**
     * Get a specific contact info by UUID
     * GET /api/admin/content/contact-info/{uuid}
     */
    public function showContactInfo(string $uuid): JsonResponse
    {
        try {
            $contactInfo = ContactInfo::with('creator:id,name,email')
                ->where('uuid', $uuid)
                ->firstOrFail();

            return response()->json([
                'success' => true,
                'data' => $contactInfo->toAdminArray()
            ]);

        } catch (\Exception $e) {
            Log::error('Admin show contact info error', [
                'error' => $e->getMessage(),
                'admin_id' => auth()->id(),
                'contact_info_uuid' => $uuid
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Contact information not found'
            ], 404);
        }
    }

    /**
     * Create or update contact information
     * POST /api/admin/content/contact-info
     */
    public function createOrUpdateContactInfo(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
            'business_hours' => 'nullable|string|max:255',
            'support_email' => 'required|email|max:255',
            'social_links' => 'nullable|array',
            'social_links.facebook' => 'nullable|url',
            'social_links.twitter' => 'nullable|url',
            'social_links.instagram' => 'nullable|url',
            'social_links.linkedin' => 'nullable|url',
            'status' => 'required|in:active,inactive',
            'metadata' => 'nullable|array'
        ]);

        try {
            // Deactivate existing contact info entries
            ContactInfo::where('status', 'active')->update(['status' => 'inactive']);

            $contactInfo = ContactInfo::create([
                'email' => $request->email,
                'phone' => $request->phone,
                'address' => $request->address,
                'business_hours' => $request->business_hours,
                'support_email' => $request->support_email,
                'social_links' => $request->social_links,
                'status' => $request->status,
                'metadata' => $request->metadata,
                'created_by' => auth()->id()
            ]);

            Log::info('Admin created/updated contact info', [
                'contact_info_id' => $contactInfo->uuid,
                'admin_id' => auth()->id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Contact information saved successfully',
                'data' => $contactInfo->load('creator:id,name,email')->toAdminArray()
            ], 201);

        } catch (\Exception $e) {
            Log::error('Admin create/update contact info error', [
                'error' => $e->getMessage(),
                'admin_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to save contact information'
            ], 500);
        }
    }

    /**
     * Delete contact info
     * DELETE /api/admin/content/contact-info/{uuid}
     */
    public function deleteContactInfo(string $uuid): JsonResponse
    {
        try {
            $contactInfo = ContactInfo::where('uuid', $uuid)->firstOrFail();
            $contactInfo->delete();

            Log::info('Admin deleted contact info', [
                'contact_info_uuid' => $uuid,
                'admin_id' => auth()->id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Contact information deleted successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Admin delete contact info error', [
                'error' => $e->getMessage(),
                'admin_id' => auth()->id(),
                'contact_info_uuid' => $uuid
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete contact information'
            ], 500);
        }
    }

    /**
     * Retry sending failed contact email
     * POST /api/admin/content/contact-messages/{uuid}/retry-email
     */
    public function retryContactEmail(string $uuid): JsonResponse
    {
        try {
            $message = ContactMessage::where('uuid', $uuid)->firstOrFail();

            $emailService = app(ContactEmailService::class);
            $result = $emailService->retryFailedEmail($message);

            Log::info('Admin retried contact email', [
                'message_id' => $message->uuid,
                'admin_id' => auth()->id(),
                'result' => $result
            ]);

            return response()->json([
                'success' => $result['success'],
                'message' => $result['message']
            ], $result['success'] ? 200 : 500);

        } catch (\Exception $e) {
            Log::error('Admin retry contact email error', [
                'error' => $e->getMessage(),
                'admin_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retry email'
            ], 500);
        }
    }

    // ========================================
    // COMMUNITY STORIES MANAGEMENT
    // ========================================

    /**
     * Get submitted stories for admin review
     * GET /api/admin/content/stories/pending
     */
    public function getPendingStories(Request $request): JsonResponse
    {
        try {
            $query = UserStory::with(['author:id,name,email', 'approver:id,name'])
                ->submitted();

            // Apply filters
            if ($request->has('category')) {
                $query->byCategory($request->category);
            }

            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                      ->orWhere('content', 'like', "%{$search}%")
                      ->orWhereHas('author', function($authorQuery) use ($search) {
                          $authorQuery->where('name', 'like', "%{$search}%")
                                     ->orWhere('email', 'like', "%{$search}%");
                      });
                });
            }

            if ($request->has('date_from')) {
                $query->where('submitted_at', '>=', $request->date_from);
            }

            if ($request->has('date_to')) {
                $query->where('submitted_at', '<=', $request->date_to);
            }

            $stories = $query->orderBy('submitted_at', 'desc')
                ->paginate($request->get('per_page', 15));

            $storiesData = $stories->getCollection()->map(function ($story) {
                return $story->toAdminArray();
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'data' => $storiesData,
                    'current_page' => $stories->currentPage(),
                    'total' => $stories->total(),
                    'per_page' => $stories->perPage(),
                    'last_page' => $stories->lastPage()
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Admin get pending stories error', [
                'error' => $e->getMessage(),
                'admin_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to load pending stories'
            ], 500);
        }
    }

    /**
     * Get all stories for admin review (includes orphaned public stories)
     * GET /api/admin/content/stories
     */
    public function getAllStories(Request $request): JsonResponse
    {
        try {
            // Get user stories with relationships
            $userStoriesQuery = UserStory::with(['author:id,name,email', 'approver:id,name']);

            // Apply filters to user stories
            if ($request->has('status') && $request->status !== 'all' && $request->status !== 'published_orphaned') {
                $userStoriesQuery->byStatus($request->status);
            }

            if ($request->has('category') && $request->category !== 'all') {
                $userStoriesQuery->byCategory($request->category);
            }

            if ($request->has('search') && !empty($request->search)) {
                $search = $request->search;
                $userStoriesQuery->where(function($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                      ->orWhere('content', 'like', "%{$search}%")
                      ->orWhereHas('author', function($authorQuery) use ($search) {
                          $authorQuery->where('name', 'like', "%{$search}%")
                                     ->orWhere('email', 'like', "%{$search}%");
                      });
                });
            }

            $userStories = $userStoriesQuery->orderBy('created_at', 'desc')->get();

            // Get orphaned public stories (where user deleted their private version)
            $existingUserStoryUuids = $userStories->pluck('uuid')->toArray();

            $orphanedPublicStoriesQuery = PublicStory::with(['publishedByAdmin:id,name,email'])
                ->whereNotIn('original_story_uuid', $existingUserStoryUuids);

            // Apply filters to orphaned public stories
            if ($request->has('status') && $request->status === 'published_orphaned') {
                // Only show orphaned stories
            } elseif ($request->has('status') && $request->status !== 'all') {
                // If filtering by specific status (not orphaned), exclude orphaned stories
                $orphanedPublicStoriesQuery->whereRaw('1 = 0'); // No results
            }

            if ($request->has('category') && $request->category !== 'all') {
                $orphanedPublicStoriesQuery->where('category', $request->category);
            }

            if ($request->has('search') && !empty($request->search)) {
                $search = $request->search;
                $orphanedPublicStoriesQuery->where(function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                      ->orWhere('content', 'like', "%{$search}%")
                      ->orWhere('anonymous_author', 'like', "%{$search}%");
                });
            }

            $orphanedPublicStories = $orphanedPublicStoriesQuery->orderBy('published_at', 'desc')->get();

            // Combine and format the results
            $allStories = collect();

            // Add user stories
            foreach ($userStories as $story) {
                $storyData = $story->toAdminArray();
                $storyData['source'] = 'user_story';
                $storyData['has_public_version'] = PublicStory::where('original_story_uuid', $story->uuid)->exists();
                $allStories->push($storyData);
            }

            // Add orphaned public stories (where user deleted their private version)
            foreach ($orphanedPublicStories as $publicStory) {
                $storyData = [
                    'id' => $publicStory->uuid,
                    'title' => $publicStory->title,
                    'content' => $publicStory->content,
                    'category' => $publicStory->category,
                    'tags' => $publicStory->tags,
                    'status' => 'published_orphaned',
                    'submittedAt' => null,
                    'approvedAt' => $publicStory->published_at->toISOString(),
                    'isFeatured' => $publicStory->is_featured,
                    'metadata' => $publicStory->metadata,
                    'createdAt' => $publicStory->created_at->toISOString(),
                    'updatedAt' => $publicStory->updated_at->toISOString(),
                    'author' => [
                        'id' => null,
                        'name' => 'User Deleted Private Version',
                        'email' => 'N/A'
                    ],
                    'approver' => $publicStory->publishedByAdmin ? [
                        'id' => $publicStory->publishedByAdmin->id,
                        'name' => $publicStory->publishedByAdmin->name
                    ] : null,
                    'source' => 'orphaned_public_story',
                    'has_public_version' => true,
                    'public_story_id' => $publicStory->uuid,
                    'anonymous_author' => $publicStory->anonymous_author,
                    'author_metadata' => $publicStory->author_metadata,
                    'views_count' => $publicStory->views_count,
                    'likes_count' => $publicStory->likes_count,
                    'admin_note' => 'This story is live on the public website. The user has deleted their private version but the anonymous public story remains active.'
                ];
                $allStories->push($storyData);
            }

            // Sort combined results by creation/publication date (most recent first)
            $allStories = $allStories->sortByDesc(function ($story) {
                return $story['createdAt'] ?? $story['approvedAt'];
            })->values();

            // Manual pagination
            $page = $request->get('page', 1);
            $perPage = $request->get('per_page', 15);
            $total = $allStories->count();
            $items = $allStories->forPage($page, $perPage)->values();

            return response()->json([
                'success' => true,
                'data' => [
                    'data' => $items,
                    'current_page' => (int) $page,
                    'total' => $total,
                    'per_page' => $perPage,
                    'last_page' => (int) ceil($total / $perPage)
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Admin get all stories error', [
                'error' => $e->getMessage(),
                'admin_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to load stories'
            ], 500);
        }
    }

    /**
     * Get orphaned public stories (where users deleted their private versions)
     * GET /api/admin/content/stories/orphaned
     */
    public function getOrphanedPublicStories(Request $request): JsonResponse
    {
        try {
            // Get all user story UUIDs
            $existingUserStoryUuids = UserStory::pluck('uuid')->toArray();

            // Get public stories that no longer have corresponding user stories
            $query = PublicStory::with(['publishedByAdmin:id,name,email'])
                ->whereNotIn('original_story_uuid', $existingUserStoryUuids);

            // Apply filters
            if ($request->has('category') && $request->category !== 'all') {
                $query->where('category', $request->category);
            }

            if ($request->has('search') && !empty($request->search)) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                      ->orWhere('content', 'like', "%{$search}%")
                      ->orWhere('anonymous_author', 'like', "%{$search}%");
                });
            }

            $orphanedStories = $query->orderBy('published_at', 'desc')
                ->paginate($request->get('per_page', 15));

            $storiesData = $orphanedStories->getCollection()->map(function ($publicStory) {
                return [
                    'id' => $publicStory->uuid,
                    'title' => $publicStory->title,
                    'content' => $publicStory->content,
                    'category' => $publicStory->category,
                    'tags' => $publicStory->tags,
                    'status' => 'published_orphaned',
                    'submittedAt' => null,
                    'approvedAt' => $publicStory->published_at->toISOString(),
                    'isFeatured' => $publicStory->is_featured,
                    'metadata' => $publicStory->metadata,
                    'createdAt' => $publicStory->created_at->toISOString(),
                    'updatedAt' => $publicStory->updated_at->toISOString(),
                    'author' => [
                        'id' => null,
                        'name' => 'User Deleted Private Version',
                        'email' => 'N/A'
                    ],
                    'approver' => $publicStory->publishedByAdmin ? [
                        'id' => $publicStory->publishedByAdmin->id,
                        'name' => $publicStory->publishedByAdmin->name
                    ] : null,
                    'source' => 'orphaned_public_story',
                    'has_public_version' => true,
                    'public_story_id' => $publicStory->uuid,
                    'anonymous_author' => $publicStory->anonymous_author,
                    'author_metadata' => $publicStory->author_metadata,
                    'views_count' => $publicStory->views_count,
                    'likes_count' => $publicStory->likes_count,
                    'published_at' => $publicStory->published_at->toISOString(),
                    'published_human' => $publicStory->published_at->diffForHumans(),
                    'admin_note' => 'This story is live on the public website. The user deleted their private version but the anonymous public story remains active.',
                    'can_delete' => true,
                    'can_edit' => true,
                    'can_feature' => true
                ];
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'data' => $storiesData,
                    'current_page' => $orphanedStories->currentPage(),
                    'total' => $orphanedStories->total(),
                    'per_page' => $orphanedStories->perPage(),
                    'last_page' => $orphanedStories->lastPage()
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Admin get orphaned public stories error', [
                'error' => $e->getMessage(),
                'admin_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to load orphaned public stories'
            ], 500);
        }
    }

    /**
     * Get single story for admin review
     * GET /api/admin/content/stories/{uuid}
     */
    public function getStoryForReview(string $uuid): JsonResponse
    {
        try {
            $story = UserStory::with(['author:id,name,email', 'approver:id,name'])
                ->where('uuid', $uuid)
                ->firstOrFail();

            return response()->json([
                'success' => true,
                'data' => $story->toAdminArray()
            ]);

        } catch (\Exception $e) {
            Log::error('Admin get story for review error', [
                'error' => $e->getMessage(),
                'story_uuid' => $uuid,
                'admin_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Story not found'
            ], 404);
        }
    }

    /**
     * Approve story for public display
     * POST /api/admin/content/stories/{uuid}/approve
     */
    public function approveStory(Request $request, string $uuid): JsonResponse
    {
        $request->validate([
            'anonymous_author' => 'nullable|string|max:100',
            'author_metadata' => 'nullable|array',
            'is_featured' => 'boolean',
            'sort_order' => 'integer|min:0',
            'metadata' => 'nullable|array'
        ]);

        try {
            $story = UserStory::where('uuid', $uuid)->firstOrFail();

            if ($story->status !== 'submitted') {
                return response()->json([
                    'success' => false,
                    'message' => 'Only submitted stories can be approved'
                ], 400);
            }

            $publicData = [
                'anonymous_author' => $request->get('anonymous_author', 'Anonymous'),
                'author_metadata' => $request->author_metadata,
                'metadata' => $request->metadata
            ];

            // Update featured and sort order if provided
            if ($request->has('is_featured')) {
                $story->is_featured = $request->is_featured;
            }
            if ($request->has('sort_order')) {
                $story->sort_order = $request->sort_order;
            }
            $story->save();

            $story->approve(auth()->id(), $publicData);

            Log::info('Admin approved story', [
                'story_uuid' => $uuid,
                'admin_id' => auth()->id(),
                'author_id' => $story->user_id
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Story approved and published successfully',
                'data' => $story->fresh()->toAdminArray()
            ]);

        } catch (\Exception $e) {
            Log::error('Admin approve story error', [
                'error' => $e->getMessage(),
                'story_uuid' => $uuid,
                'admin_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to approve story'
            ], 500);
        }
    }

    /**
     * Reject story
     * POST /api/admin/content/stories/{uuid}/reject
     */
    public function rejectStory(Request $request, string $uuid): JsonResponse
    {
        $request->validate([
            'reason' => 'required|string|max:1000'
        ]);

        try {
            $story = UserStory::where('uuid', $uuid)->firstOrFail();

            if ($story->status !== 'submitted') {
                return response()->json([
                    'success' => false,
                    'message' => 'Only submitted stories can be rejected'
                ], 400);
            }

            $story->reject($request->reason);

            Log::info('Admin rejected story', [
                'story_uuid' => $uuid,
                'admin_id' => auth()->id(),
                'author_id' => $story->user_id,
                'reason' => $request->reason
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Story rejected successfully',
                'data' => $story->fresh()->toAdminArray()
            ]);

        } catch (\Exception $e) {
            Log::error('Admin reject story error', [
                'error' => $e->getMessage(),
                'story_uuid' => $uuid,
                'admin_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to reject story'
            ], 500);
        }
    }

    /**
     * Get public stories management
     * GET /api/admin/content/public-stories
     */
    public function getPublicStories(Request $request): JsonResponse
    {
        try {
            $query = PublicStory::with('publisher:id,name');

            // Apply filters
            if ($request->has('category')) {
                $query->byCategory($request->category);
            }

            if ($request->has('featured')) {
                $query->featured();
            }

            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                      ->orWhere('content', 'like', "%{$search}%");
                });
            }

            $stories = $query->orderBy('published_at', 'desc')
                ->paginate($request->get('per_page', 15));

            $storiesData = $stories->getCollection()->map(function ($story) {
                return $story->toAdminArray();
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'data' => $storiesData,
                    'current_page' => $stories->currentPage(),
                    'total' => $stories->total(),
                    'per_page' => $stories->perPage(),
                    'last_page' => $stories->lastPage()
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Admin get public stories error', [
                'error' => $e->getMessage(),
                'admin_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to load public stories'
            ], 500);
        }
    }

    /**
     * Update public story
     * PUT /api/admin/content/public-stories/{uuid}
     */
    public function updatePublicStory(Request $request, string $uuid): JsonResponse
    {
        $request->validate([
            'is_featured' => 'boolean',
            'sort_order' => 'integer|min:0'
        ]);

        try {
            $story = PublicStory::where('uuid', $uuid)->firstOrFail();

            $updateData = array_filter([
                'is_featured' => $request->is_featured,
                'sort_order' => $request->sort_order
            ], function($value) { return $value !== null; });

            $story->update($updateData);

            Log::info('Admin updated public story', [
                'story_uuid' => $uuid,
                'admin_id' => auth()->id(),
                'updates' => $updateData
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Public story updated successfully',
                'data' => $story->fresh()->toAdminArray()
            ]);

        } catch (\Exception $e) {
            Log::error('Admin update public story error', [
                'error' => $e->getMessage(),
                'story_uuid' => $uuid,
                'admin_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update public story'
            ], 500);
        }
    }

    /**
     * Delete public story
     * DELETE /api/admin/content/public-stories/{uuid}
     */
    public function deletePublicStory(string $uuid): JsonResponse
    {
        try {
            $story = PublicStory::where('uuid', $uuid)->firstOrFail();

            // Also update the original user story status
            $originalStory = UserStory::where('uuid', $story->original_story_uuid)->first();
            if ($originalStory) {
                $originalStory->update(['status' => 'archived']);
            }

            $story->delete();

            Log::info('Admin deleted public story', [
                'story_uuid' => $uuid,
                'admin_id' => auth()->id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Public story deleted successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Admin delete public story error', [
                'error' => $e->getMessage(),
                'story_uuid' => $uuid,
                'admin_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete public story'
            ], 500);
        }
    }

    /**
     * Get story reports
     * GET /api/admin/content/story-reports
     */
    public function getStoryReports(Request $request): JsonResponse
    {
        try {
            $query = StoryInteraction::with(['user:id,name,email', 'story'])
                ->reports();

            $reports = $query->orderBy('created_at', 'desc')
                ->paginate($request->get('per_page', 15));

            $reportsData = $reports->getCollection()->map(function ($report) {
                return [
                    'id' => $report->uuid,
                    'storyId' => $report->story->uuid,
                    'storyTitle' => $report->story->title,
                    'reporterName' => $report->user->name,
                    'reporterEmail' => $report->user->email,
                    'reason' => $report->metadata['reason'] ?? null,
                    'details' => $report->metadata['details'] ?? null,
                    'reportedAt' => $report->created_at->toISOString()
                ];
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'data' => $reportsData,
                    'current_page' => $reports->currentPage(),
                    'total' => $reports->total(),
                    'per_page' => $reports->perPage(),
                    'last_page' => $reports->lastPage()
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Admin get story reports error', [
                'error' => $e->getMessage(),
                'admin_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to load story reports'
            ], 500);
        }
    }

    /**
     * Delete story (Admin only) - Handles both user stories and orphaned public stories
     * DELETE /api/admin/content/stories/{uuid}
     */
    public function deleteUserStory(string $uuid): JsonResponse
    {
        try {
            // First, try to find a user story with this UUID
            $userStory = UserStory::with(['author:id,name,email'])->where('uuid', $uuid)->first();

            if ($userStory) {
                // This is a user story - delete both user and public versions
                return $this->deleteUserStoryWithPublic($userStory, $uuid);
            }

            // If no user story found, check if this is an orphaned public story
            $publicStory = PublicStory::with(['publishedByAdmin:id,name,email'])
                ->where('uuid', $uuid)
                ->first();

            if ($publicStory) {
                // This is an orphaned public story - delete only the public version
                return $this->deleteOrphanedPublicStory($publicStory, $uuid);
            }

            // Story not found in either table
            return response()->json([
                'success' => false,
                'message' => 'Story not found'
            ], 404);

        } catch (\Exception $e) {
            Log::error('Admin delete story error', [
                'error' => $e->getMessage(),
                'story_uuid' => $uuid,
                'admin_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete story'
            ], 500);
        }
    }

    /**
     * Delete user story and its public version
     */
    private function deleteUserStoryWithPublic(UserStory $story, string $uuid): JsonResponse
    {
        // Store story info for logging before deletion
        $storyInfo = [
            'title' => $story->title,
            'author_name' => $story->author?->name,
            'author_email' => $story->author?->email,
            'status' => $story->status,
            'created_at' => $story->created_at
        ];

        // If story was approved and published, also delete the public version
        if ($story->status === 'approved') {
            $publicStory = PublicStory::where('original_story_uuid', $story->uuid)->first();
            if ($publicStory) {
                $publicStory->delete();
                Log::info('Admin deleted public story along with user story', [
                    'user_story_uuid' => $uuid,
                    'public_story_uuid' => $publicStory->uuid,
                    'admin_id' => auth()->id()
                ]);
            }
        }

        // Delete the user story
        $story->delete();

        Log::warning('Admin deleted user story', [
            'story_uuid' => $uuid,
            'story_info' => $storyInfo,
            'admin_id' => auth()->id(),
            'admin_name' => auth()->user()->name
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Story deleted successfully'
        ]);
    }

    /**
     * Delete orphaned public story
     */
    private function deleteOrphanedPublicStory(PublicStory $publicStory, string $uuid): JsonResponse
    {
        // Store story info for logging before deletion
        $storyInfo = [
            'title' => $publicStory->title,
            'anonymous_author' => $publicStory->anonymous_author,
            'published_at' => $publicStory->published_at,
            'views_count' => $publicStory->views_count,
            'likes_count' => $publicStory->likes_count,
            'published_by' => $publicStory->publishedByAdmin?->name
        ];

        // Delete the orphaned public story
        $publicStory->delete();

        Log::warning('Admin deleted orphaned public story', [
            'public_story_uuid' => $uuid,
            'story_info' => $storyInfo,
            'admin_id' => auth()->id(),
            'admin_name' => auth()->user()->name,
            'note' => 'This was an orphaned public story (user had already deleted their private version)'
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Orphaned public story deleted successfully'
        ]);
    }

    /**
     * Bulk delete stories (Admin only) - Handles both user stories and orphaned public stories
     * DELETE /api/admin/content/stories/bulk
     */
    public function bulkDeleteUserStories(Request $request): JsonResponse
    {
        $request->validate([
            'story_ids' => 'required|array|min:1',
            'story_ids.*' => 'required|string|uuid'
        ]);

        try {
            $storyIds = $request->story_ids;
            $deletedCount = 0;
            $errors = [];
            $userStoriesDeleted = 0;
            $orphanedStoriesDeleted = 0;

            foreach ($storyIds as $storyId) {
                try {
                    // First, try to find a user story with this UUID
                    $userStory = UserStory::with(['author:id,name,email'])
                        ->where('uuid', $storyId)
                        ->first();

                    if ($userStory) {
                        // This is a user story - delete both user and public versions
                        $storyInfo = [
                            'title' => $userStory->title,
                            'author_name' => $userStory->author?->name,
                            'status' => $userStory->status
                        ];

                        // Delete public version if exists
                        if ($userStory->status === 'approved') {
                            $publicStory = PublicStory::where('original_story_uuid', $userStory->uuid)->first();
                            if ($publicStory) {
                                $publicStory->delete();
                            }
                        }

                        // Delete user story
                        $userStory->delete();
                        $deletedCount++;
                        $userStoriesDeleted++;

                        Log::warning('Admin bulk deleted user story', [
                            'story_uuid' => $storyId,
                            'story_info' => $storyInfo,
                            'admin_id' => auth()->id()
                        ]);

                        continue;
                    }

                    // If no user story found, check if this is an orphaned public story
                    $publicStory = PublicStory::with(['publishedByAdmin:id,name,email'])
                        ->where('uuid', $storyId)
                        ->first();

                    if ($publicStory) {
                        // This is an orphaned public story - delete only the public version
                        $storyInfo = [
                            'title' => $publicStory->title,
                            'anonymous_author' => $publicStory->anonymous_author,
                            'published_at' => $publicStory->published_at,
                            'views_count' => $publicStory->views_count,
                            'likes_count' => $publicStory->likes_count
                        ];

                        // Delete the orphaned public story
                        $publicStory->delete();
                        $deletedCount++;
                        $orphanedStoriesDeleted++;

                        Log::warning('Admin bulk deleted orphaned public story', [
                            'public_story_uuid' => $storyId,
                            'story_info' => $storyInfo,
                            'admin_id' => auth()->id(),
                            'note' => 'This was an orphaned public story (user had already deleted their private version)'
                        ]);

                        continue;
                    }

                    // Story not found in either table
                    $errors[] = "Story $storyId not found";

                } catch (\Exception $e) {
                    $errors[] = "Failed to delete story $storyId: " . $e->getMessage();
                }
            }

            Log::warning('Admin bulk delete operation completed', [
                'total_requested' => count($storyIds),
                'successfully_deleted' => $deletedCount,
                'user_stories_deleted' => $userStoriesDeleted,
                'orphaned_stories_deleted' => $orphanedStoriesDeleted,
                'errors_count' => count($errors),
                'admin_id' => auth()->id(),
                'admin_name' => auth()->user()->name
            ]);

            $message = "Successfully deleted $deletedCount stories";
            if ($userStoriesDeleted > 0 && $orphanedStoriesDeleted > 0) {
                $message .= " ($userStoriesDeleted user stories, $orphanedStoriesDeleted orphaned public stories)";
            } elseif ($orphanedStoriesDeleted > 0) {
                $message .= " ($orphanedStoriesDeleted orphaned public stories)";
            } elseif ($userStoriesDeleted > 0) {
                $message .= " ($userStoriesDeleted user stories)";
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'deleted_count' => $deletedCount,
                'user_stories_deleted' => $userStoriesDeleted,
                'orphaned_stories_deleted' => $orphanedStoriesDeleted,
                'errors' => $errors
            ]);

        } catch (\Exception $e) {
            Log::error('Admin bulk delete stories error', [
                'error' => $e->getMessage(),
                'admin_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete stories'
            ], 500);
        }
    }

    /**
     * Get story deletion history (Admin only)
     * GET /api/admin/content/stories/deletion-history
     */
    public function getStoryDeletionHistory(Request $request): JsonResponse
    {
        try {
            // Check if user is admin
            $user = auth()->user();
            if (!$user || !$this->isAdmin($user)) {
                Log::warning('Non-admin attempted to access deletion history', [
                    'user_id' => $user?->id,
                    'user_email' => $user?->email,
                    'ip' => $request->ip()
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Admin access required'
                ], 403);
            }

            // This would typically come from an audit log table
            // For now, we'll return recent log entries from Laravel logs
            // In production, you'd want a dedicated audit_logs table

            return response()->json([
                'success' => true,
                'message' => 'Story deletion history retrieved',
                'data' => [
                    'note' => 'Deletion history is tracked in application logs. In production, implement a dedicated audit_logs table for better tracking.',
                    'log_location' => 'storage/logs/laravel.log',
                    'search_pattern' => 'Admin deleted user story'
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Get story deletion history error', [
                'error' => $e->getMessage(),
                'admin_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve deletion history'
            ], 500);
        }
    }

    /**
     * Check if user is admin
     */
    private function isAdmin($user): bool
    {
        // Check role field
        if (isset($user->role) && $user->role === 'admin') {
            return true;
        }

        // Check if user has admin permission (if using Spatie permissions)
        if (method_exists($user, 'can') && $user->can('admin-access')) {
            return true;
        }

        // Check if user has admin role (if using Spatie roles)
        if (method_exists($user, 'hasRole') && $user->hasRole('admin')) {
            return true;
        }

        // Check if user is in admin table or has admin flag
        if (isset($user->is_admin) && $user->is_admin) {
            return true;
        }

        return false;
    }
}
