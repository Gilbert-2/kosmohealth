<?php

namespace App\Http\Controllers;

use App\Models\UserStory;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class UserStoriesController extends Controller
{
    /**
     * Get user's own stories
     * GET /api/user/stories
     */
    public function getUserStories(Request $request): JsonResponse
    {
        try {
            $userId = auth()->id();
            $query = UserStory::byUser($userId)->with('approver:id,name');

            // Apply filters
            if ($request->has('status')) {
                $query->byStatus($request->status);
            }

            if ($request->has('category')) {
                $query->byCategory($request->category);
            }

            $stories = $query->orderBy('created_at', 'desc')
                ->paginate($request->get('per_page', 15));

            $storiesData = $stories->getCollection()->map(function ($story) {
                return $story->toUserArray();
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
            Log::error('Get user stories error', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to load your stories'
            ], 500);
        }
    }

    /**
     * Create new story
     * POST /api/user/stories
     */
    public function createStory(Request $request): JsonResponse
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string|max:10000',
            'category' => 'nullable|string|max:100',
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:50',
            'metadata' => 'nullable|array',
            'save_as_draft' => 'boolean'
        ]);

        try {
            $userId = auth()->id();
            $saveAsDraft = $request->get('save_as_draft', false);

            $story = UserStory::create([
                'user_id' => $userId,
                'title' => $request->title,
                'content' => $request->content,
                'category' => $request->category,
                'tags' => $request->tags,
                'status' => $saveAsDraft ? 'draft' : 'submitted',
                'submitted_at' => $saveAsDraft ? null : now(),
                'metadata' => $request->metadata
            ]);

            Log::info('User story created', [
                'story_uuid' => $story->uuid,
                'user_id' => $userId,
                'status' => $story->status
            ]);

            return response()->json([
                'success' => true,
                'message' => $saveAsDraft 
                    ? 'Story saved as draft successfully'
                    : 'Story submitted for review successfully',
                'data' => $story->toUserArray()
            ], 201);

        } catch (\Exception $e) {
            Log::error('Create user story error', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create story'
            ], 500);
        }
    }

    /**
     * Get single user story
     * GET /api/user/stories/{uuid}
     */
    public function getUserStory(string $uuid): JsonResponse
    {
        try {
            $userId = auth()->id();
            $story = UserStory::where('uuid', $uuid)
                ->byUser($userId)
                ->with('approver:id,name')
                ->firstOrFail();

            return response()->json([
                'success' => true,
                'data' => $story->toUserArray()
            ]);

        } catch (\Exception $e) {
            Log::error('Get user story error', [
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
     * Update user story
     * PUT /api/user/stories/{uuid}
     */
    public function updateStory(Request $request, string $uuid): JsonResponse
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string|max:10000',
            'category' => 'nullable|string|max:100',
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:50',
            'metadata' => 'nullable|array',
            'save_as_draft' => 'boolean'
        ]);

        try {
            $userId = auth()->id();
            $story = UserStory::where('uuid', $uuid)
                ->byUser($userId)
                ->firstOrFail();

            // Only allow editing if story is draft or rejected
            if (!in_array($story->status, ['draft', 'rejected'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot edit story in current status'
                ], 400);
            }

            $saveAsDraft = $request->get('save_as_draft', false);
            $newStatus = $saveAsDraft ? 'draft' : 'submitted';

            $story->update([
                'title' => $request->title,
                'content' => $request->content,
                'category' => $request->category,
                'tags' => $request->tags,
                'status' => $newStatus,
                'submitted_at' => $saveAsDraft ? null : now(),
                'metadata' => $request->metadata
            ]);

            Log::info('User story updated', [
                'story_uuid' => $story->uuid,
                'user_id' => $userId,
                'new_status' => $newStatus
            ]);

            return response()->json([
                'success' => true,
                'message' => $saveAsDraft 
                    ? 'Story saved as draft successfully'
                    : 'Story submitted for review successfully',
                'data' => $story->fresh()->toUserArray()
            ]);

        } catch (\Exception $e) {
            Log::error('Update user story error', [
                'error' => $e->getMessage(),
                'story_uuid' => $uuid,
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update story'
            ], 500);
        }
    }

    /**
     * Delete user story (Two-step deletion process)
     * DELETE /api/user/stories/{uuid}
     */
    public function deleteStory(string $uuid): JsonResponse
    {
        try {
            $userId = auth()->id();
            $story = UserStory::where('uuid', $uuid)
                ->byUser($userId)
                ->firstOrFail();

            // Store story info for logging
            $storyInfo = [
                'title' => $story->title,
                'status' => $story->status,
                'was_published' => $story->status === 'approved'
            ];

            // Two-step deletion process:
            // 1. Delete the private user story (removes from user's dashboard)
            // 2. Keep the public anonymous story intact (if it was approved/published)

            if ($story->status === 'approved') {
                // Story was published - check if public version exists
                $publicStory = \App\Models\PublicStory::where('original_story_uuid', $story->uuid)->first();

                if ($publicStory) {
                    Log::info('User deleted private story - public version remains', [
                        'user_story_uuid' => $uuid,
                        'public_story_uuid' => $publicStory->uuid,
                        'user_id' => $userId,
                        'story_title' => $story->title,
                        'public_story_continues' => true
                    ]);

                    // Delete only the private user story
                    $story->delete();

                    return response()->json([
                        'success' => true,
                        'message' => 'Your story has been removed from your dashboard. The anonymous version will continue to be available to the community.',
                        'public_version_remains' => true
                    ]);
                }
            }

            // For draft, submitted, or rejected stories - normal deletion
            // Also for approved stories without public versions
            $story->delete();

            Log::info('User story deleted completely', [
                'story_uuid' => $uuid,
                'user_id' => $userId,
                'story_info' => $storyInfo
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Story deleted successfully',
                'public_version_remains' => false
            ]);

        } catch (\Exception $e) {
            Log::error('Delete user story error', [
                'error' => $e->getMessage(),
                'story_uuid' => $uuid,
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete story'
            ], 500);
        }
    }

    /**
     * Submit draft story for review
     * POST /api/user/stories/{uuid}/submit
     */
    public function submitStory(string $uuid): JsonResponse
    {
        try {
            $userId = auth()->id();
            $story = UserStory::where('uuid', $uuid)
                ->byUser($userId)
                ->firstOrFail();

            if ($story->status !== 'draft') {
                return response()->json([
                    'success' => false,
                    'message' => 'Only draft stories can be submitted'
                ], 400);
            }

            $story->submit();

            Log::info('User story submitted', [
                'story_uuid' => $uuid,
                'user_id' => $userId
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Story submitted for review successfully',
                'data' => $story->fresh()->toUserArray()
            ]);

        } catch (\Exception $e) {
            Log::error('Submit user story error', [
                'error' => $e->getMessage(),
                'story_uuid' => $uuid,
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to submit story'
            ], 500);
        }
    }
}
