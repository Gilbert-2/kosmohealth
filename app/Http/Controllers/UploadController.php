<?php

namespace App\Http\Controllers;

use App\Http\Requests\UploadImageRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Intervention\Image\Facades\Image;
use Carbon\Carbon;

class UploadController extends Controller
{
    private const MAX_FILE_SIZE = 5 * 1024 * 1024; // 5MB
    private const ALLOWED_MIME_TYPES = [
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/webp',
        'image/svg+xml'
    ];
    private const ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];

    /**
     * Upload image with comprehensive security measures
     *
     * @param UploadImageRequest $request
     * @return JsonResponse
     */
    public function image(UploadImageRequest $request): JsonResponse
    {
        try {
            $file = $request->file('image');
            
            if (!$file || !$file->isValid()) {
                return $this->error(['image' => 'Invalid file upload'], 400);
            }

            // Enhanced security validation
            $validationResult = $this->validateImageSecurity($file);
            if ($validationResult !== true) {
                return $this->error(['image' => $validationResult], 422);
            }

            // Generate secure filename
            $filename = $this->generateSecureFilename($file);
            
            // Create directory structure by date
            $directory = 'editor-images/' . Carbon::now()->format('Y/m/d');
            
            // Ensure directory exists
            if (!Storage::disk('public')->exists($directory)) {
                Storage::disk('public')->makeDirectory($directory);
            }

            // Store the file
            $filePath = Storage::disk('public')->putFileAs($directory, $file, $filename);
            
            if (!$filePath) {
                Log::error('Failed to store uploaded image', [
                    'filename' => $filename,
                    'directory' => $directory,
                    'user_id' => auth()->id() ?? 'guest'
                ]);
                return $this->error(['image' => 'Failed to store image'], 500);
            }

            // Generate the full URL
            $url = $this->generateImageUrl($filePath);
            
            // Verify the file was actually stored
            if (!Storage::disk('public')->exists($filePath)) {
                Log::error('Image upload verification failed', [
                    'path' => $filePath,
                    'url' => $url
                ]);
                return $this->error(['image' => 'Upload verification failed'], 500);
            }

            // Log successful upload for audit
            Log::info('Image uploaded successfully', [
                'path' => $filePath,
                'url' => $url,
                'original_name' => $file->getClientOriginalName(),
                'size' => $file->getSize(),
                'user_id' => auth()->id() ?? 'guest',
                'ip' => $request->ip()
            ]);

            return $this->success([
                'success' => true,
                'url' => $url,
                'path' => $filePath,
                'filename' => $filename,
                'size' => $file->getSize(),
                'original_name' => $file->getClientOriginalName()
            ]);

        } catch (\Exception $e) {
            Log::error('Image upload failed with exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => auth()->id() ?? 'guest'
            ]);

            return $this->error(['image' => 'Upload failed due to server error'], 500);
        }
    }

    /**
     * Comprehensive security validation for uploaded images
     *
     * @param \Illuminate\Http\UploadedFile $file
     * @return bool|string
     */
    private function validateImageSecurity($file)
    {
        // Check file size
        if ($file->getSize() > self::MAX_FILE_SIZE) {
            return 'File size exceeds maximum allowed size of ' . (self::MAX_FILE_SIZE / 1024 / 1024) . 'MB';
        }

        // Check MIME type
        if (!in_array($file->getMimeType(), self::ALLOWED_MIME_TYPES)) {
            return 'Invalid file type. Only images are allowed.';
        }

        // Check file extension
        $extension = strtolower($file->getClientOriginalExtension());
        if (!in_array($extension, self::ALLOWED_EXTENSIONS)) {
            return 'Invalid file extension. Allowed: ' . implode(', ', self::ALLOWED_EXTENSIONS);
        }

        // Check if it's actually an image by trying to read it
        try {
            $imageInfo = getimagesize($file->getPathname());
            if ($imageInfo === false) {
                return 'File is not a valid image';
            }
        } catch (\Exception $e) {
            return 'Unable to verify image integrity';
        }

        // Additional security: Check for embedded PHP code in image
        $contents = file_get_contents($file->getPathname());
        if (preg_match('/<\?php|<\?=|<script/i', $contents)) {
            return 'Security violation: Malicious content detected';
        }

        return true;
    }

    /**
     * Generate a secure filename to prevent directory traversal and other attacks
     *
     * @param \Illuminate\Http\UploadedFile $file
     * @return string
     */
    private function generateSecureFilename($file): string
    {
        $extension = strtolower($file->getClientOriginalExtension());
        $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        
        // Sanitize original filename
        $sanitizedName = preg_replace('/[^a-zA-Z0-9\-_]/', '', $originalName);
        $sanitizedName = substr($sanitizedName, 0, 50); // Limit length
        
        // Generate unique identifier
        $uniqueId = Str::random(8);
        $timestamp = Carbon::now()->format('YmdHis');
        
        return $sanitizedName . '_' . $timestamp . '_' . $uniqueId . '.' . $extension;
    }

    /**
     * Generate the correct URL for the uploaded image
     *
     * @param string $filePath
     * @return string
     */
    private function generateImageUrl(string $filePath): string
    {
        // Prefer API-served storage route to avoid web server symlink issues
        $path = ltrim($filePath, '/');
        if (str_starts_with($path, 'editor-images/')) {
            return route('storage.image', ['path' => $path]);
        }
        return route('storage.image', ['path' => $path]);
    }

    /**
     * Get upload configuration and limits
     *
     * @return JsonResponse
     */
    public function getUploadConfig(): JsonResponse
    {
        return $this->success([
            'max_file_size' => self::MAX_FILE_SIZE,
            'max_file_size_mb' => self::MAX_FILE_SIZE / 1024 / 1024,
            'allowed_mime_types' => self::ALLOWED_MIME_TYPES,
            'allowed_extensions' => self::ALLOWED_EXTENSIONS,
            'upload_path' => 'editor-images'
        ]);
    }

    /**
     * Delete uploaded image (for cleanup)
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function deleteImage(Request $request): JsonResponse
    {
        $request->validate([
            'path' => 'required|string'
        ]);

        try {
            $path = $request->input('path');
            
            // Security: Ensure path is within allowed directory
            if (!Str::startsWith($path, 'editor-images/')) {
                return $this->error(['path' => 'Invalid file path'], 403);
            }

            if (Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
                
                Log::info('Image deleted successfully', [
                    'path' => $path,
                    'user_id' => auth()->id() ?? 'guest'
                ]);

                return $this->success(['message' => 'Image deleted successfully']);
            }

            return $this->error(['path' => 'File not found'], 404);

        } catch (\Exception $e) {
            Log::error('Image deletion failed', [
                'error' => $e->getMessage(),
                'path' => $request->input('path'),
                'user_id' => auth()->id() ?? 'guest'
            ]);

            return $this->error(['message' => 'Failed to delete image'], 500);
        }
    }
}
