<?php

namespace App\Http\Controllers;

use App\Models\HostPatient;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class HostPatientController extends Controller
{
    /**
     * Get all patients assigned to the authenticated host
     * GET /api/host/patients
     */
    public function getMyPatients(Request $request): JsonResponse
    {
        try {
            $host = Auth::user();
            
            // Check if user is a host
            if (!$host->hasRole('host') && !$host->hasRole('admin')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied. Host role required.'
                ], 403);
            }

            $query = HostPatient::with(['patient:id,name,email,avatar,status'])
                ->where('host_id', $host->id)
                ->orderBy('created_at', 'desc');

            // Apply filters
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            if ($request->has('search')) {
                $search = $request->search;
                $query->whereHas('patient', function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%");
                });
            }

            $patients = $query->paginate($request->get('per_page', 15));

            return response()->json([
                'success' => true,
                'data' => $patients
            ]);

        } catch (\Exception $e) {
            \Log::error('Host get patients error', [
                'error' => $e->getMessage(),
                'host_id' => Auth::id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to load patients'
            ], 500);
        }
    }

    /**
     * Get patient details with health data
     * GET /api/host/patients/{patientId}
     */
    public function getPatientDetails($patientId): JsonResponse
    {
        try {
            $host = Auth::user();
            
            // Check if user is a host
            if (!$host->hasRole('host') && !$host->hasRole('admin')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied. Host role required.'
                ], 403);
            }

            // Find the host-patient relationship
            $relationship = HostPatient::where('host_id', $host->id)
                ->where('patient_id', $patientId)
                ->with(['patient.periodCycles', 'patient.pregnancyRecords'])
                ->first();

            if (!$relationship) {
                return response()->json([
                    'success' => false,
                    'message' => 'Patient not found or not assigned to you'
                ], 404);
            }

            $patient = $relationship->patient;
            
            // Add health data summary
            $healthSummary = [
                'period_tracking' => [
                    'total_cycles' => $patient->periodCycles()->count(),
                    'last_cycle' => $patient->periodCycles()->latest()->first(),
                    'avg_cycle_length' => $patient->periodCycles()->whereNotNull('cycle_length')->avg('cycle_length')
                ],
                'pregnancy_tracking' => [
                    'total_pregnancies' => $patient->pregnancyRecords()->count(),
                    'active_pregnancy' => $patient->pregnancyRecords()->where('status', 'active')->first(),
                    'completed_pregnancies' => $patient->pregnancyRecords()->where('status', 'completed')->count()
                ]
            ];

            return response()->json([
                'success' => true,
                'data' => [
                    'relationship' => $relationship,
                    'patient' => $patient,
                    'health_summary' => $healthSummary
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('Host get patient details error', [
                'error' => $e->getMessage(),
                'host_id' => Auth::id(),
                'patient_id' => $patientId
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to load patient details'
            ], 500);
        }
    }

    /**
     * Assign a patient to the authenticated host
     * POST /api/host/patients/assign
     */
    public function assignPatient(Request $request): JsonResponse
    {
        try {
            $host = Auth::user();
            
            // Check if user is a host
            if (!$host->hasRole('host') && !$host->hasRole('admin')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied. Host role required.'
                ], 403);
            }

            $request->validate([
                'patient_id' => 'required|exists:users,id',
                'consultation_reason' => 'required|string|max:500',
                'notes' => 'nullable|string|max:1000'
            ]);

            // Check if patient is already assigned
            $existingAssignment = HostPatient::where('host_id', $host->id)
                ->where('patient_id', $request->patient_id)
                ->first();

            if ($existingAssignment) {
                return response()->json([
                    'success' => false,
                    'message' => 'Patient is already assigned to you'
                ], 400);
            }

            // Create the assignment
            $assignment = HostPatient::create([
                'host_id' => $host->id,
                'patient_id' => $request->patient_id,
                'status' => 'active',
                'assigned_at' => now(),
                'consultation_reason' => $request->consultation_reason,
                'notes' => $request->notes
            ]);

            $assignment->load('patient:id,name,email');

            return response()->json([
                'success' => true,
                'message' => 'Patient assigned successfully',
                'data' => $assignment
            ], 201);

        } catch (\Exception $e) {
            \Log::error('Host assign patient error', [
                'error' => $e->getMessage(),
                'host_id' => Auth::id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to assign patient'
            ], 500);
        }
    }

    /**
     * Update patient assignment status
     * PUT /api/host/patients/{patientId}/status
     */
    public function updatePatientStatus(Request $request, $patientId): JsonResponse
    {
        try {
            $host = Auth::user();
            
            // Check if user is a host
            if (!$host->hasRole('host') && !$host->hasRole('admin')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied. Host role required.'
                ], 403);
            }

            $request->validate([
                'status' => ['required', Rule::in(['active', 'inactive', 'pending', 'completed'])],
                'notes' => 'nullable|string|max:1000'
            ]);

            // Find the relationship
            $relationship = HostPatient::where('host_id', $host->id)
                ->where('patient_id', $patientId)
                ->first();

            if (!$relationship) {
                return response()->json([
                    'success' => false,
                    'message' => 'Patient assignment not found'
                ], 404);
            }

            $relationship->update([
                'status' => $request->status,
                'notes' => $request->notes
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Patient status updated successfully',
                'data' => $relationship
            ]);

        } catch (\Exception $e) {
            \Log::error('Host update patient status error', [
                'error' => $e->getMessage(),
                'host_id' => Auth::id(),
                'patient_id' => $patientId
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update patient status'
            ], 500);
        }
    }

    /**
     * Get available hosts for patient assignment
     * GET /api/host/available-hosts
     */
    public function getAvailableHosts(Request $request): JsonResponse
    {
        try {
            $hosts = User::whereHas('roles', function($query) {
                $query->whereIn('name', ['host', 'admin']);
            })
            ->select('id', 'name', 'email', 'avatar')
            ->withCount(['hostPatients as active_patients' => function($query) {
                $query->where('status', 'active');
            }])
            ->orderBy('name')
            ->get();

            return response()->json([
                'success' => true,
                'data' => $hosts
            ]);

        } catch (\Exception $e) {
            \Log::error('Get available hosts error', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to load available hosts'
            ], 500);
        }
    }

    /**
     * Get consultation reasons for patient assignment
     * GET /api/host/consultation-reasons
     */
    public function getConsultationReasons(): JsonResponse
    {
        $reasons = [
            'pregnancy_care' => 'Pregnancy Care & Monitoring',
            'period_tracking' => 'Period Tracking & Irregularities',
            'fertility_consultation' => 'Fertility Consultation',
            'gynecological_issues' => 'Gynecological Issues',
            'family_planning' => 'Family Planning',
            'postpartum_care' => 'Postpartum Care',
            'menstrual_health' => 'Menstrual Health Issues',
            'prenatal_care' => 'Prenatal Care',
            'general_consultation' => 'General Women Health Consultation',
            'emergency_care' => 'Emergency Care',
            'follow_up' => 'Follow-up Consultation',
            'other' => 'Other'
        ];

        return response()->json([
            'success' => true,
            'data' => $reasons
        ]);
    }

    /**
     * Remove patient assignment
     * DELETE /api/host/patients/{patientId}
     */
    public function removePatient($patientId): JsonResponse
    {
        try {
            $host = Auth::user();
            
            // Check if user is a host
            if (!$host->hasRole('host') && !$host->hasRole('admin')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied. Host role required.'
                ], 403);
            }

            // Find the relationship
            $relationship = HostPatient::where('host_id', $host->id)
                ->where('patient_id', $patientId)
                ->first();

            if (!$relationship) {
                return response()->json([
                    'success' => false,
                    'message' => 'Patient assignment not found'
                ], 404);
            }

            $relationship->delete();

            return response()->json([
                'success' => true,
                'message' => 'Patient assignment removed successfully'
            ]);

        } catch (\Exception $e) {
            \Log::error('Host remove patient error', [
                'error' => $e->getMessage(),
                'host_id' => Auth::id(),
                'patient_id' => $patientId
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to remove patient assignment'
            ], 500);
        }
    }
}
