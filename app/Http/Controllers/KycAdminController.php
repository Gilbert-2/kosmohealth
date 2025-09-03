<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\KycVerification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class KycAdminController extends Controller
{
    /**
     * Show the KYC admin page
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        // Log the request for debugging
        Log::info('KYC Admin page accessed', [
            'path' => $request->path(),
            'url' => $request->url(),
            'user' => Auth::check() ? Auth::user()->id : 'guest'
        ]);

        // Check if user is admin
        if (!Auth::check() || !Auth::user()->hasRole('admin')) {
            return response()->json(['error' => 'Unauthorized - Admin access required'], 403);
        }

        return response()->json([
            'message' => 'KYC Admin API',
            'endpoints' => [
                'get_requests' => '/api/kyc/requests',
                'approve' => '/api/kyc/approve/{id}',
                'reject' => '/api/kyc/reject/{id}'
            ],
            'note' => 'This is an API-only endpoint. Use the API endpoints for KYC operations.'
        ]);
    }

    /**
     * Get all KYC verification requests
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getRequests()
    {
        // Check if user is admin
        if (!Auth::check() || !Auth::user()->hasRole('admin')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $requests = KycVerification::with('user:id,name,email')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($requests);
    }

    /**
     * Approve a KYC verification request
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function approve($id)
    {
        // Check if user is admin
        if (!Auth::check() || !Auth::user()->hasRole('admin')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $verification = KycVerification::findOrFail($id);

        $verification->update([
            'status' => 'approved',
            'completed_at' => now()
        ]);

        return response()->json([
            'message' => 'KYC verification request approved successfully',
            'verification' => $verification
        ]);
    }

    /**
     * Reject a KYC verification request
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function reject($id)
    {
        // Check if user is admin
        if (!Auth::check() || !Auth::user()->hasRole('admin')) {
            return response()->json(['error' => 'Unauthorized'], 403);
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
