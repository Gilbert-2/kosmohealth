<?php

namespace App\Http\Controllers;

use App\Models\KycVerification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\Controller;

class KycController extends Controller
{
    /**
     * Start a new KYC verification session
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function startSession(Request $request)
    {
        $sessionId = uniqid('kyc_', true);
        
        $verification = KycVerification::create([
            'user_id' => auth()->id(),
            'session_id' => $sessionId,
            'status' => 'pending'
        ]);

        return response()->json([
            'id' => $sessionId,
            'status' => 'created',
            'created_at' => $verification->created_at
        ]);
    }

    /**
     * Verify uploaded document
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function verifyDocument(Request $request)
    {
        $request->validate([
            'document' => 'required|file|image|max:10240', // max 10MB
            'session_id' => 'required|string'
        ]);

        $verification = KycVerification::where('session_id', $request->session_id)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        // Store document in private storage
        $path = $request->file('document')->store('documents', 'private');
        
        $verification->update([
            'document_path' => $path,
            'status' => 'document_verified'
        ]);

        return response()->json([
            'status' => 'verified',
            'document_id' => basename($path),
            'message' => 'Document verified successfully'
        ]);
    }

    /**
     * Complete the KYC verification process
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function completeVerification(Request $request)
    {
        $request->validate([
            'session_id' => 'required|string',
            'user_data' => 'required|array',
            'face_match_score' => 'required|numeric|min:0|max:100',
            'liveness_check' => 'required|array'
        ]);

        $verification = KycVerification::where('session_id', $request->session_id)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        if ($verification->document_path === null) {
            return response()->json([
                'message' => 'Document verification incomplete'
            ], 422);
        }

        $verification->update([
            'verification_data' => $request->user_data,
            'face_match_score' => $request->face_match_score,
            'liveness_check' => $request->liveness_check
        ]);

        $verification->markAsComplete();

        return response()->json([
            'status' => 'verified',
            'verification_id' => $verification->verification_id,
            'message' => 'Verification completed successfully'
        ]);
    }

    /**
     * Get verification status
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getStatus(Request $request)
    {
        $verification = KycVerification::where('session_id', $request->session_id)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        return response()->json([
            'status' => $verification->status,
            'verification_id' => $verification->verification_id,
            'completed_at' => $verification->completed_at,
            'steps' => [
                'document_upload' => $verification->document_path !== null,
                'document_verification' => $verification->status === 'document_verified',
                'liveness_check' => $verification->liveness_check !== null,
                'face_match' => $verification->face_match_score !== null
            ]
        ]);
    }
}