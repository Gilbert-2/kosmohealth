<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class SignedMediaController extends Controller
{
    /**
     * Serve protected media files with security checks
     *
     * @param Request $request
     * @param Media $media
     * @param string $conversion
     * @return Response
     */
    public function __invoke(Request $request, Media $media, string $conversion = '')
    {
        // Security: Check authentication and signature
        if (!auth()->check() || !$request->hasValidSignature()) {
            Log::warning('Unauthorized media access attempt', [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'media_id' => $media->id ?? 'unknown',
                'user_id' => auth()->id() ?? 'guest'
            ]);
            abort(404);
        }

        try {
            // Additional security: Verify file exists and is accessible
            $filePath = $media->getPath($conversion);
            
            if (!file_exists($filePath) || !is_readable($filePath)) {
                Log::error('Media file not found or not readable', [
                    'path' => $filePath,
                    'media_id' => $media->id,
                    'conversion' => $conversion
                ]);
                abort(404);
            }

            // Security: Validate MIME type
            $allowedMimeTypes = [
                'image/jpeg',
                'image/png',
                'image/gif',
                'image/webp',
                'image/svg+xml',
                'application/pdf',
                'text/plain'
            ];

            if (!in_array($media->mime_type, $allowedMimeTypes)) {
                Log::warning('Attempt to access disallowed file type', [
                    'mime_type' => $media->mime_type,
                    'media_id' => $media->id,
                    'user_id' => auth()->id()
                ]);
                abort(403);
            }

            // Clear any previous output
            if (ob_get_level()) {
                ob_end_clean();
            }

            // Log successful access for audit
            Log::info('Media file accessed', [
                'media_id' => $media->id,
                'user_id' => auth()->id(),
                'mime_type' => $media->mime_type,
                'conversion' => $conversion
            ]);

            return response()->file($filePath, [
                'Content-Type' => $media->mime_type,
                'Cache-Control' => 'private, max-age=3600',
                'X-Content-Type-Options' => 'nosniff',
                'X-Frame-Options' => 'SAMEORIGIN'
            ]);

        } catch (\Exception $e) {
            Log::error('Error serving media file', [
                'error' => $e->getMessage(),
                'media_id' => $media->id ?? 'unknown',
                'user_id' => auth()->id()
            ]);
            abort(500);
        }
    }

    /**
     * Serve public storage images directly
     *
     * @param Request $request
     * @param string $path
     * @return Response
     */
    public function serveStorageImage(Request $request, string $path)
    {
        try {
            // Security: Validate path to prevent directory traversal
            $path = ltrim($path, '/');
            
            if (strpos($path, '..') !== false || strpos($path, './') !== false) {
                Log::warning('Directory traversal attempt detected', [
                    'path' => $path,
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent()
                ]);
                abort(403);
            }

            // Only allow access to editor-images directory
            if (!preg_match('/^editor-images\//', $path)) {
                abort(403);
            }

            // Check if file exists
            if (!Storage::disk('public')->exists($path)) {
                abort(404);
            }

            $fullPath = Storage::disk('public')->path($path);
            $mimeType = Storage::disk('public')->mimeType($path);

            // Validate MIME type
            $allowedMimeTypes = [
                'image/jpeg',
                'image/png',
                'image/gif',
                'image/webp',
                'image/svg+xml'
            ];

            if (!in_array($mimeType, $allowedMimeTypes)) {
                abort(403);
            }

            return response()->file($fullPath, [
                'Content-Type' => $mimeType,
                'Cache-Control' => 'public, max-age=2592000', // 30 days for public images
                'X-Content-Type-Options' => 'nosniff'
            ]);

        } catch (\Exception $e) {
            Log::error('Error serving storage image', [
                'path' => $path,
                'error' => $e->getMessage(),
                'ip' => $request->ip()
            ]);
            abort(404);
        }
    }
}
