<?php

namespace App\Http\Controllers;

use App\Enums\MeetingStatus;
use App\Models\Meeting;
use App\Http\Requests\MeetingRequest;
use App\Http\Resources\Meeting as MeetingResource;
use App\Http\Resources\MeetingSummary;
use App\Repositories\MeetingRepository;
use App\Models\MeetingEmotion;
use App\Notifications\MeetingScheduled;

class MeetingController extends Controller
{
    protected $repo;

    /**
     * Instantiate a new controller instance
     * @return void
     */
    public function __construct(
        MeetingRepository $repo
    ) {
        $this->repo = $repo;
    }

    /**
     * Get meeting pre requisite
     * @get ("/api/meetings/pre-requisite")
     * @return array
     */
    public function preRequisite()
    {
        $this->authorize('preRequisite', Meeting::class);

        return $this->ok($this->repo->getPreRequisite());
    }

    /**
     * Get all meetings
     * @get ("/api/meetings")
     * @return array
     */
    public function index()
    {
        $this->authorize('list', Meeting::class);

        // Summary mode for dashboard widget
        if (request()->boolean('summary')) {
            $user = auth()->user();
            $upcomingCount = Meeting::where(function ($q) use ($user) {
                    $q->where('user_id', $user->id)
                      ->orWhereHas('invitees', function ($query) use ($user) {
                          $query->where('user_id', $user->id);
                      });
                })
                ->where('start_date_time', '>', now())
                ->isScheduled()
                ->isNotCancelled()
                ->count();

            return $this->ok(['data' => ['upcoming_count' => $upcomingCount]]);
        }

        return $this->repo->paginate();
    }

    /**
     * Get current user's meetings
     * @get ("/api/user/meetings/my-meetings")
     * @return array
     */
    public function myMeetings()
    {
        $user = auth()->user();
        
        // Get meetings where the user is the creator or an invitee
        $meetings = Meeting::where('user_id', $user->id)
            ->orWhereHas('invitees', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->with(['user', 'invitees.user'])
            ->orderBy('start_date_time', 'desc')
            ->paginate();

        return $this->ok($meetings);
    }

    /**
     * Store meeting
     * @post ("/api/meetings")
     * @param ({
     *      @Parameter("title", type="string", required="true", description="Meeting title"),
     *      @Parameter("agenda", type="text", required="true", description="Meeting agenda"),
     *      @Parameter("description", type="text", required="true", description="Meeting description"),
     *      @Parameter("start_date_time", type="datetime", required="true", description="Meeting start date time"),
     *      @Parameter("period", type="integer", required="true", description="Meeting estimated period (in minutes)"),
     * })
     * @return array
     */
    public function store(MeetingRequest $request)
    {
        $this->authorize('create', Meeting::class);

        $meeting = $this->repo->create();

        $meeting = new MeetingResource($meeting);

        return $this->success(['message' => __('global.added', ['attribute' => __('meeting.meeting')]), 'meeting' => $meeting]);
    }

    /**
     * Resend meeting notification to a specific patient
     * @post ("/api/meetings/{uuid}/notify")
     * @param ({
     *      @Parameter("uuid", type="uuid", required="true", description="Meeting unique id"),
     *      @Parameter("patient_id", type="integer", required="true", description="Patient user id to notify"),
     * })
     * @return array
     */
    public function notify(Meeting $meeting)
    {
        $this->authorize('update', Meeting::class);

        $meeting->isAccessible(true);

        request()->validate([
            'patient_id' => 'required|exists:users,id'
        ]);

        $patient = \App\Models\User::find(request('patient_id'));

        if ($patient) {
            $patient->notify(new MeetingScheduled($meeting));
        }

        return $this->success(['message' => __('global.sent', ['attribute' => __('meeting.invitation')])]);
    }

    /**
     * Get meeting detail
     * @get ("/api/meetings/{uuid}")
     * @param ({
     *      @Parameter("uuid", type="uuid", required="true", description="Meeting unique id"),
     * })
     * @return MeetingResource
     */
    public function show(Meeting $meeting)
    {
        $this->authorize('show', Meeting::class);

        $meeting->shouldEnd();

        $meeting->isAccessible();

        $meeting->isCancellable();

        $meeting->ensureMemberCanJoin();

        return new MeetingResource($meeting);
    }

    /**
     * Get meeting summary
     * @get ("/api/meetings/{uuid}/summary")
     * @param ({
     *      @Parameter("uuid", type="uuid", required="true", description="Meeting unique id"),
     * })
     * @return MeetingResource
     */
    public function summary(Meeting $meeting)
    {
        return new MeetingSummary($meeting);
    }

    /**
     * Get pam meeting detail
     * @get ("/api/meetings/{uuid}/pam")
     * @param ({
     *      @Parameter("uuid", type="uuid", required="true", description="Meeting unique id"),
     * })
     * @return MeetingResource
     */
    public function pam(Meeting $meeting)
    {
        if (! $meeting->getMeta('is_pam')) {
            return $this->error(['message' => __('meeting.not_is_pam')]);
        }

        if ($meeting->getMeta('status') === MeetingStatus::ENDED) {
            return $this->error(['message' => __('meeting.meeting_ended')]);
        }

        return new MeetingResource($meeting);
    }

    /**
     * Get meeting detail from identifier
     * @get ("/api/meetings/pam/{identifier}")
     * @param ({
     *      @Parameter("identifier", type="string", required="true", description="Meeting identifier"),
     * })
     * @return MeetingResource
     */
    public function showPam($identifier)
    {
        $meeting = $this->repo->findByIdentifierOrFail($identifier);

        if (! $meeting->getMeta('is_pam')) {
            return $this->error(['message' => __('meeting.not_is_pam')]);
        }

        if ($meeting->getMeta('status') === MeetingStatus::ENDED) {
            return $this->error(['message' => __('meeting.meeting_ended')]);
        }

        return new MeetingResource($meeting);
    }

    /**
     * Get meeting detail from identifier
     * @get ("/api/meetings/m/{identifier}")
     * @param ({
     *      @Parameter("identifier", type="string", required="true", description="Meeting identifier"),
     * })
     * @return MeetingResource
     */
    public function showMeeting($identifier)
    {
        $this->authorize('show', Meeting::class);

        $meeting = $this->repo->findByIdentifierOrFail($identifier);

        $meeting->isAccessible();

        $meeting->isCancellable();

        $meeting->ensureMemberCanJoin();

        return new MeetingResource($meeting);
    }

    /**
     * Update meeting
     * @patch ("/api/meetings/{uuid}")
     * @param ({
     *      @Parameter("uuid", type="uuid", required="true", description="Meeting unique id"),
     *      @Parameter("title", type="string", required="true", description="Meeting title"),
     *      @Parameter("agenda", type="text", required="true", description="Meeting agenda"),
     *      @Parameter("description", type="text", required="true", description="Meeting description"),
     *      @Parameter("start_date_time", type="datetime", required="true", description="Meeting start date time"),
     *      @Parameter("period", type="integer", required="true", description="Meeting estimated period (in minutes)"),
     * })
     * @return array
     */
    public function update(MeetingRequest $request, Meeting $meeting)
    {
        $this->authorize('update', Meeting::class);

        $meeting->isAccessible(true);

        $meeting = $this->repo->update($meeting);

        return $this->success(['message' => __('global.updated', ['attribute' => __('meeting.meeting')])]);
    }

    /**
     * Delete meeting
     * @delete ("/api/meetings/{uuid}")
     * @param ({
     *      @Parameter("uuid", type="uuid", required="true", description="Meeting unique id"),
     * })
     * @return array
     */
    public function destroy(Meeting $meeting)
    {
        $this->authorize('delete', Meeting::class);

        $meeting->shouldEnd();

        $meeting->isAccessible(true);

        $this->repo->delete($meeting);

        return $this->success(['message' => __('global.deleted', ['attribute' => __('meeting.meeting')])]);
    }

    /**
     * Store meeting configuration
     * @post ("/api/meetings/{uuid}/config")
     * @param ({
     *      @Parameter("uuid", type="uuid", required="true", description="Meeting unique id"),
     * })
     * @return array
     */
    public function config(Meeting $meeting)
    {
        $this->authorize('list', Meeting::class);

        $meeting->isAccessible(true);

        $meeting = $this->repo->config($meeting);

        return $this->success(['message' => __('global.updated', ['attribute' => __('meeting.meeting')]), 'meeting' => new MeetingResource($meeting)]);
    }

    /**
     * Snooze meeting
     * @post ("/api/meetings/{uuid}/snooze")
     * @param ({
     *      @Parameter("uuid", type="uuid", required="true", description="Meeting unique id"),
     * })
     * @return array
     */
    public function snooze(Meeting $meeting)
    {
        $this->authorize('list', Meeting::class);

        $meeting->isAccessible(true);

        $meeting = $this->repo->snooze($meeting);

        return $this->success(['message' => __('global.snoozed', ['attribute' => __('meeting.meeting')]), 'meeting' => new MeetingResource($meeting)]);
    }

    /**
     * Snooze estimated end time of meeting
     * @post ("/api/meetings/{uuid}/snooze-end-time")
     * @param ({
     *      @Parameter("uuid", type="uuid", required="true", description="Meeting unique id"),
     * })
     * @return array
     */
    public function snoozeEndTime(Meeting $meeting)
    {
        $this->authorize('list', Meeting::class);

        $meeting->isAccessible(true);

        $meeting = $this->repo->snoozeEndTime($meeting);

        return $this->success(['message' => __('global.snoozed', ['attribute' => __('meeting.props.estimated_end_time')]), 'meeting' => new MeetingResource($meeting)]);
    }

    /**
     * Cancel meeting
     * @post ("/api/meetings/{uuid}/cancel")
     * @param ({
     *      @Parameter("uuid", type="uuid", required="true", description="Meeting unique id"),
     * })
     * @return array
     */
    public function cancel(Meeting $meeting)
    {
        $this->authorize('list', Meeting::class);

        $meeting->isAccessible(true);

        $meeting = $this->repo->cancel($meeting);

        return $this->success(['message' => __('global.cancelled', ['attribute' => __('meeting.meeting')]), 'meeting' => new MeetingResource($meeting)]);
    }

    /**
     * Cancel auto end of meeting
     * @post ("/api/meetings/{uuid}/cancel-auto-end")
     * @param ({
     *      @Parameter("uuid", type="uuid", required="true", description="Meeting unique id"),
     * })
     * @return array
     */
    public function cancelAutoEnd(Meeting $meeting)
    {
        $this->authorize('list', Meeting::class);

        $meeting->isAccessible(true);

        $meeting = $this->repo->cancelAutoEnd($meeting);

        return $this->success(['message' => __('global.cancelled', ['attribute' => __('meeting.auto_end_of_meeting')]), 'meeting' => new MeetingResource($meeting)]);
    }
}
