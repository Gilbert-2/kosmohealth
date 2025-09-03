<?php
namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Http\Requests\Site\PageRequest;
use App\Repositories\Site\PageRepository;
use App\Http\Resources\Site\Page as PageResource;

class PageController extends Controller
{
    protected $repo;

    /**
     * Instantiate a new instance
     * @return void
     */
    public function __construct(
        PageRepository $repo
    ) {
        $this->repo = $repo;

        $this->middleware(['permission:access-page'])->except(['fetch']);
    }

    /**
     * Get page pre requisites
     * @get ("/api/site/pages/pre-requisite")
     * @return array
     */
    public function preRequisite()
    {
        return $this->ok($this->repo->getPreRequisite());
    }

    /**
     * Get all pages
     * @get ("/api/site/pages")
     * @return array
     */
    public function index()
    {
        return $this->repo->paginate();
    }

    /**
     * Store page
     * @post ("/api/site/pages")
     * @param ({
     *      @Parameter("title", type="string", required="true", description="Page title"),
     *      @Parameter("description", type="text", required="optional", description="Page description"),
     *      @Parameter("code", type="string", required="true", description="Page code"),
     * })
     * @return array
     */
    public function store(PageRequest $request)
    {
        $page = $this->repo->create();

        $page = new PageResource($page);

        return $this->success(['message' => __('global.added', ['attribute' => __('site.page.page')]), 'page' => $page]);
    }

    /**
     * Get page detail
     * @get ("/api/site/pages/{uuid}")
     * @param ({
     *      @Parameter("uuid", type="uuid", required="true", description="Page unique id"),
     * })
     * @return PageResource
     */
    public function show($uuid)
    {
        $page = $this->repo->findByUuidOrFail($uuid);

        return new PageResource($page);
    }

    /**
     * Fetch page content
     * @get ("/pages/{page?}")
     * @param ({
     *      @Parameter("page", type="string", required="true", description="Page slug"),
     * })
     * @return \Illuminate\View\View|\Illuminate\Http\Response
     */
    public function fetch($page)
    {
        try {
            // Security: Validate and sanitize the page slug
            $pageSlug = $this->sanitizeSlug($page);
            
            // Find the page with proper error handling
            $pageModel = $this->repo->findBySlugOrFail($pageSlug);

            // Security: Check if page is published and accessible
            if (!$pageModel->status) {
                return $this->handleInactivePage();
            }

            // Security: Sanitize content with proper HTML cleaning
            $body = $this->sanitizeContent($pageModel->body);
            $slug = htmlspecialchars($pageModel->slug, ENT_QUOTES, 'UTF-8');
            $title = htmlspecialchars($pageModel->title, ENT_QUOTES, 'UTF-8');
            $meta = $this->sanitizeMeta($pageModel->meta);
            $parent = $pageModel->parent;

            // Security: Validate template exists and is safe to use
            $templateName = $this->validateTemplate($pageModel->template);
            
            if ($templateName && view()->exists('templates.' . $templateName)) {
                return view('templates.' . $templateName, compact('body', 'slug', 'title', 'meta', 'parent'))
                    ->with('pageModel', $pageModel);
            }

            // Fallback to blank template with proper error handling
            return view('templates.blank', compact('body', 'slug', 'title', 'meta', 'parent'))
                ->with('pageModel', $pageModel);
                
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            // Log security event for potential scanning attempts
            \Log::warning('Page not found attempt', [
                'slug' => $page,
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent()
            ]);
            
            return $this->handlePageNotFound();
            
        } catch (\Exception $e) {
            // Log general errors for monitoring
            \Log::error('Page fetch error', [
                'slug' => $page,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return $this->handleServerError();
        }
    }

    /**
     * Sanitize page slug for security
     * 
     * @param string $slug
     * @return string
     */
    private function sanitizeSlug($slug)
    {
        // Remove any potential malicious characters
        $slug = preg_replace('/[^a-zA-Z0-9\-_\/]/', '', $slug);
        
        // Prevent directory traversal
        $slug = str_replace(['../', '../', '..\\', '..\\\\'], '', $slug);
        
        // Limit length to prevent buffer overflow attempts
        return substr($slug, 0, 255);
    }

    /**
     * Sanitize page content with proper HTML cleaning
     * 
     * @param string $content
     * @return string
     */
    private function sanitizeContent($content)
    {
        // Use the existing clean function with additional security
        $cleaned = clean($content);
        
        // Additional security: Remove potentially dangerous attributes
        $cleaned = preg_replace('/on\w+\s*=\s*["\'][^"\']*["\']/i', '', $cleaned);
        
        // Remove javascript: and data: URIs
        $cleaned = preg_replace('/(?:javascript|data):\s*[^"\'\s>]*/i', '', $cleaned);
        
        return $cleaned;
    }

    /**
     * Sanitize meta information
     * 
     * @param mixed $meta
     * @return mixed
     */
    private function sanitizeMeta($meta)
    {
        if (is_array($meta)) {
            return array_map(function($value) {
                return is_string($value) ? htmlspecialchars($value, ENT_QUOTES, 'UTF-8') : $value;
            }, $meta);
        }
        
        return is_string($meta) ? htmlspecialchars($meta, ENT_QUOTES, 'UTF-8') : $meta;
    }

    /**
     * Validate template name for security
     * 
     * @param object $template
     * @return string|null
     */
    private function validateTemplate($template)
    {
        if (!$template || !isset($template->slug)) {
            return null;
        }

        // Security: Only allow alphanumeric, dash, and underscore characters
        $templateSlug = preg_replace('/[^a-zA-Z0-9\-_]/', '', $template->slug);
        
        // Prevent directory traversal in template names
        $templateSlug = str_replace(['../', '../', '..\\'], '', $templateSlug);
        
        return $templateSlug;
    }

    /**
     * Handle inactive page requests
     * 
     * @return \Illuminate\Http\Response
     */
    private function handleInactivePage()
    {
        if (config('config.website.show_inactive_page_message', false)) {
            return response()->view('errors.page-inactive', [], 404);
        }
        
        return $this->handlePageNotFound();
    }

    /**
     * Handle page not found with proper error response
     * 
     * @return \Illuminate\Http\Response
     */
    private function handlePageNotFound()
    {
        return response()->view('errors.404', [
            'message' => 'The requested page could not be found.'
        ], 404);
    }

    /**
     * Handle server errors gracefully
     * 
     * @return \Illuminate\Http\Response
     */
    private function handleServerError()
    {
        return response()->view('errors.500', [
            'message' => 'An error occurred while loading the page.'
        ], 500);
    }

    /**
     * Update page
     * @patch ("/api/site/pages/{uuid}")
     * @param ({
     *      @Parameter("uuid", type="uuid", required="true", description="Page unique id"),
     *      @Parameter("title", type="string", required="true", description="Page title"),
     *      @Parameter("description", type="text", required="optional", description="Page description"),
     *      @Parameter("code", type="string", required="true", description="Page code"),
     * })
     * @return array
     */
    public function update($uuid, PageRequest $request)
    {
        $page = $this->repo->findByUuidOrFail($uuid);

        $page = $this->repo->update($page);

        return $this->success(['message' => __('global.updated', ['attribute' => __('site.page.page')])]);
    }

    /**
     * Delete page
     * @delete ("/api/site/pages/{uuid}")
     * @param ({
     *      @Parameter("uuid", type="uuid", required="true", description="Page unique id"),
     * })
     * @return array
     */
    public function destroy($uuid)
    {
        $this->repo->delete($this->repo->findByUuidOrFail($uuid));

        return $this->success(['message' => __('global.deleted', ['attribute' => __('site.page.page')])]);
    }

    /**
     * Used to add media for a page
     * @post ("/api/site/pages/{uuid}/media")
     * @param ({
     *      @Parameter("ids", type="array", required="true", description="Id of Students"),
     *      @Parameter("action", type="string", required="true", description="Action to Perform"),
     * })
     * @return Response
     */
    public function addMedia($uuid)
    {
        $media = $this->repo->addMedia($this->repo->findByUuidOrFail($uuid));

        return $this->success(['message' => __('global.added', ['attribute' => __('site.page.props.media')]), 'upload' => $media]);
    }

    /**
     * Used to remove media for a page
     * @delete ("/api/site/pages/{uuid}/media")
     * @param ({
     *      @Parameter("ids", type="array", required="true", description="Id of Students"),
     *      @Parameter("action", type="string", required="true", description="Action to Perform"),
     * })
     * @return Response
     */
    public function removeMedia($uuid)
    {
        $this->repo->removeMedia($this->repo->findByUuidOrFail($uuid));

        return $this->success(['message' => __('global.deleted', ['attribute' => __('site.page.props.media')])]);
    }
}
