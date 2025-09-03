<?php

namespace App\Http\Controllers;

use App\Models\UserStory;
use App\Models\PublicStory;
use App\Models\StoryInteraction;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Rule;

class CommunityStoriesController extends Controller
{
    /**
     * Get public stories for community feed
     * GET /api/community/stories
     */
    public function getPublicStories(Request $request): JsonResponse
    {
        try {
            $query = PublicStory::query();

            // Apply filters
            if ($request->has('category')) {
                $query->byCategory($request->category);
            }

            if ($request->has('featured')) {
                $query->featured();
            }

            if ($request->has('tags')) {
                $tags = is_array($request->tags) ? $request->tags : explode(',', $request->tags);
                $query->whereJsonContains('tags', $tags);
            }

            // Apply sorting
            $sortBy = $request->get('sort_by', 'recent');
            switch ($sortBy) {
                case 'popular':
                    $query->popular();
                    break;
                case 'most_viewed':
                    $query->mostViewed();
                    break;
                case 'featured':
                    $query->orderBy('is_featured', 'desc')->orderBy('published_at', 'desc');
                    break;
                default:
                    $query->orderBy('published_at', 'desc');
            }

            $stories = $query->paginate($request->get('per_page', 15));

            // Add user interaction context if authenticated
            $userId = auth()->id();
            $storiesData = $stories->getCollection()->map(function ($story) use ($userId) {
                return $userId 
                    ? $story->toPublicArrayWithUserContext($userId)
                    : $story->toPublicArray();
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
            Log::error('Get public stories error', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to load community stories'
            ], 500);
        }
    }

    /**
     * Get single public story
     * GET /api/community/stories/{uuid}
     */
    public function getPublicStory(string $uuid): JsonResponse
    {
        try {
            $story = PublicStory::where('uuid', $uuid)->firstOrFail();
            $userId = auth()->id();

            // Record view interaction if user is authenticated
            if ($userId) {
                StoryInteraction::createOrUpdate(
                    $userId,
                    $story->id,
                    'view',
                    ['ip_address' => request()->ip()]
                );
                $story->incrementViews();
            }

            $storyData = $userId 
                ? $story->toPublicArrayWithUserContext($userId)
                : $story->toPublicArray();

            return response()->json([
                'success' => true,
                'data' => $storyData
            ]);

        } catch (\Exception $e) {
            Log::error('Get public story error', [
                'error' => $e->getMessage(),
                'story_uuid' => $uuid,
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Story not found'
            ], 404);
        }
    }

    /**
     * Like/unlike a story
     * POST /api/community/stories/{uuid}/like
     */
    public function toggleLike(string $uuid): JsonResponse
    {
        try {
            $story = PublicStory::where('uuid', $uuid)->firstOrFail();
            $userId = auth()->id();

            $existingLike = StoryInteraction::where('user_id', $userId)
                ->where('public_story_id', $story->id)
                ->where('interaction_type', 'like')
                ->first();

            if ($existingLike) {
                // Unlike
                $existingLike->delete();
                $story->decrementLikes();
                $isLiked = false;
            } else {
                // Like
                StoryInteraction::createOrUpdate(
                    $userId,
                    $story->id,
                    'like',
                    ['ip_address' => request()->ip()]
                );
                $story->incrementLikes();
                $isLiked = true;
            }

            Log::info('Story like toggled', [
                'story_uuid' => $uuid,
                'user_id' => $userId,
                'is_liked' => $isLiked
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'isLiked' => $isLiked,
                    'likesCount' => $story->fresh()->likes_count
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Toggle story like error', [
                'error' => $e->getMessage(),
                'story_uuid' => $uuid,
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update like status'
            ], 500);
        }
    }

    /**
     * Report a story
     * POST /api/community/stories/{uuid}/report
     */
    public function reportStory(Request $request, string $uuid): JsonResponse
    {
        $request->validate([
            'reason' => 'required|string|max:500',
            'details' => 'nullable|string|max:1000'
        ]);

        try {
            $story = PublicStory::where('uuid', $uuid)->firstOrFail();
            $userId = auth()->id();

            // Check if user already reported this story
            $existingReport = StoryInteraction::where('user_id', $userId)
                ->where('public_story_id', $story->id)
                ->where('interaction_type', 'report')
                ->first();

            if ($existingReport) {
                return response()->json([
                    'success' => false,
                    'message' => 'You have already reported this story'
                ], 400);
            }

            StoryInteraction::createOrUpdate(
                $userId,
                $story->id,
                'report',
                [
                    'ip_address' => request()->ip(),
                    'metadata' => [
                        'reason' => $request->reason,
                        'details' => $request->details
                    ]
                ]
            );

            Log::warning('Story reported', [
                'story_uuid' => $uuid,
                'user_id' => $userId,
                'reason' => $request->reason
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Story reported successfully. Thank you for helping keep our community safe.'
            ]);

        } catch (\Exception $e) {
            Log::error('Report story error', [
                'error' => $e->getMessage(),
                'story_uuid' => $uuid,
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to report story'
            ], 500);
        }
    }

    /**
     * Get story categories and tags
     * GET /api/community/stories/metadata
     */
    public function getStoriesMetadata(): JsonResponse
    {
        try {
            $categories = Cache::remember('story_categories', 3600, function () {
                return PublicStory::distinct()->pluck('category')->filter()->values();
            });

            $tags = Cache::remember('story_tags', 3600, function () {
                return PublicStory::whereNotNull('tags')
                    ->get()
                    ->pluck('tags')
                    ->flatten()
                    ->unique()
                    ->values();
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'categories' => $categories,
                    'tags' => $tags,
                    'sortOptions' => [
                        'recent' => 'Most Recent',
                        'popular' => 'Most Liked',
                        'most_viewed' => 'Most Viewed',
                        'featured' => 'Featured First'
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Get stories metadata error', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to load metadata'
            ], 500);
        }
    }
}
