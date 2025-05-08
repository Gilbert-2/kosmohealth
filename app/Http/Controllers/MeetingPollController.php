<?php

namespace App\Http\Controllers;

use App\Http\Requests\MeetingPollRequest;
use App\Http\Resources\MeetingPoll as MeetingPollResource;
use App\Models\Meeting;
use App\Repositories\MeetingPollRepository;
use Illuminate\Http\Request;

class MeetingPollController extends Controller
{
    protected $repo;

    public function __construct(
        MeetingPollRepository $repo
    ) {
        $this->repo = $repo;
    }

    public function index(Meeting $meeting)
    {
        $meeting->isAccessible();

        $meeting->load('invitees');

        request()->merge([
            'is_moderator' => $meeting->canModerate()
        ]);

        return $this->repo->paginate($meeting);
    }

    public function store(MeetingPollRequest $request, Meeting $meeting)
    {
        $meeting->isAccessible(true);

        $meeting->ensureIsScheduledOrLive();

        $poll = $this->repo->create($request, $meeting);

        $poll = new MeetingPollResource($poll);

        return $this->success(['message' => __('global.added', ['attribute' => __('meeting.poll.poll')]), 'poll' => $poll]);
    }

    public function show(Meeting $meeting, $poll)
    {
        $meeting->isAccessible();

        $meeting->load('invitees');

        request()->merge([
            'is_moderator' => $meeting->canModerate()
        ]);

        $poll = $this->repo->findByUuidOrFail($meeting, $poll);

        $poll->load('results');

        return new MeetingPollResource($poll);
    }

    public function publish(Request $request, Meeting $meeting, $poll)
    {
        $this->authorize('list', $meeting);

        $meeting->isAccessible(true);

        $meeting->ensureIsLive();

        $poll = $this->repo->findByUuidOrFail($meeting, $poll);

        $poll = $this->repo->publish($request, $meeting, $poll);

        return new MeetingPollResource($poll);
    }

    public function publishResult(Request $request, Meeting $meeting, $poll)
    {
        $this->authorize('list', $meeting);

        $meeting->isAccessible(true);

        $poll = $this->repo->findByUuidOrFail($meeting, $poll);

        $poll = $this->repo->publishResult($request, $meeting, $poll);

        $poll->load('results');

        return new MeetingPollResource($poll);
    }

    public function vote(Request $request, Meeting $meeting, $poll)
    {
        $meeting->isAccessible();

        $meeting->ensureIsLive();

        $poll = $this->repo->findByUuidOrFail($meeting, $poll);

        $poll = $this->repo->vote($request, $meeting, $poll);

        $poll->load('results');

        $poll = new MeetingPollResource($poll);

        return $this->success(['message' => __('meeting.poll.vote_response'), 'poll' => $poll]);
    }

    public function update(MeetingPollRequest $request, Meeting $meeting, $poll)
    {
        $meeting->isAccessible(true);

        $meeting->ensureIsScheduledOrLive();

        $poll = $this->repo->findByUuidOrFail($meeting, $poll);

        $poll = $this->repo->update($request, $meeting, $poll);

        return $this->success(['message' => __('global.updated', ['attribute' => __('meeting.poll.poll')])]);
    }

    public function destroy(Meeting $meeting, $poll)
    {
        $meeting->isAccessible(true);

        $poll = $this->repo->findByUuidOrFail($meeting, $poll);

        $this->repo->delete($meeting, $poll);

        return $this->success(['message' => __('global.deleted', ['attribute' => __('meeting.poll.poll')])]);
    }
}
