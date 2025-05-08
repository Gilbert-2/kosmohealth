<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\KycVerification;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

class KycRequestController extends Controller
{
    /**
     * Get all KYC verification requests
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        // Check if user has admin role
        if (!auth()->user()->hasRole('admin')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $requests = KycVerification::with('user:id,name,email')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($request) {
                return [
                    'id' => $request->id,
                    'user' => [
                        'id' => $request->user->id,
                        'name' => $request->user->name,
                        'email' => $request->user->email,
                        'avatar' => $request->user->avatar ? url($request->user->avatar) : null
                    ],
                    'status' => $request->status,
                    'created_at' => $request->created_at,
                    'completed_at' => $request->completed_at,
                    'document_path' => $request->document_path ? true : false,
                    'face_match_score' => $request->face_match_score
                ];
            });

        return response()->json($requests);
    }

    /**
     * Get document for a KYC verification request
     *
     * @param int $id
     * @return JsonResponse|Response
     */
    public function getDocument($id)
    {
        // Check if user has admin role
        if (!auth()->user()->hasRole('admin')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $verification = KycVerification::findOrFail($id);

        if (!$verification->document_path) {
            return response()->json(['message' => 'No document found'], 404);
        }

        // Check if file exists
        if (!Storage::disk('private')->exists($verification->document_path)) {
            return response()->json(['message' => 'Document file not found'], 404);
        }

        // Return the file
        return response()->file(Storage::disk('private')->path($verification->document_path));
    }

    /**
     * Approve a KYC verification request
     *
     * @param int $id
     * @return JsonResponse
     */
    public function approve($id): JsonResponse
    {
        // Check if user has admin role
        if (!auth()->user()->hasRole('admin')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $verification = KycVerification::findOrFail($id);
        
        $verification->update([
            'status' => 'approved',
            'completed_at' => now()
        ]);

        // Update user's KYC status if needed
        $user = User::find($verification->user_id);
        if ($user) {
            $user->update([
                'kyc_verified' => true
            ]);
        }

        return response()->json([
            'message' => 'KYC verification request approved successfully',
            'verification' => $verification
        ]);
    }

    /**
     * Reject a KYC verification request
     *
     * @param int $id
     * @return JsonResponse
     */
    public function reject($id): JsonResponse
    {
        // Check if user has admin role
        if (!auth()->user()->hasRole('admin')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $verification = KycVerification::findOrFail($id);
        
        $verification->update([
            'status' => 'rejected',
            'completed_at' => now()
        ]);

        return response()->json([
            'message' => 'KYC verification request rejected successfully',
            'verification' => $verification
        ]);
    }
}
