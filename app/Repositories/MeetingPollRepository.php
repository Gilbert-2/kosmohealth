<?php

namespace App\Repositories;

use App\Helpers\IpHelper;
use App\Http\Resources\MeetingPollCollection;
use App\Models\Meeting;
use App\Models\Poll;
use App\Models\PollResult;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

class MeetingPollRepository
{
    protected $meeting;
    protected $poll;

    /**
     * Instantiate a new instance
     * @return void
     */
    public function __construct(
        Meeting $meeting,
        Poll $poll
    ) {
        $this->meeting = $meeting;
        $this->poll = $poll;
    }

    /**
     * Find meeting with given uuid or throw an error
     * @param uuid $uuid
     */
    public function findByUuidOrFail(Meeting $meeting, $uuid, $field = 'message') : Poll
    {
        $poll = $this->poll->whereMeetingId($meeting->id)->whereUuid($uuid)->first();

        if (! $poll) {
            throw ValidationException::withMessages([$field => __('global.could_not_find', ['attribute' => __('meeting.poll.poll')])]);
        }

        return $poll;
    }

    /**
     * Paginate all polls
     */
    public function paginate(Meeting $meeting) : MeetingPollCollection
    {
        $sort_by = $this->poll->getSortBy();
        $order   = $this->poll->getOrder();

        $sort_by = 'start_at';
        $order = 'desc';

        $query = $this->poll->with('results')->whereMeetingId($meeting->id);



        if (empty(auth()->user())) {

            $alreadyVotedPolls = PollResult::whereHas('poll', function($q) use ($meeting) {
                $q->whereMeetingId($meeting->id)->whereNull('user_id')->where('meta->user->ip_address', '!=', IpHelper::getClientIp());
            })->select('poll_id')->get()->pluck('poll_id')->all();

            $query->whereIsPublished(1)->whereNull('meta->result_published_at');
        } elseif (! $meeting->canModerate()) {
            $query->when($meeting->user_id != auth()->id(), function($q) use ($meeting) {

                $alreadyVotedPolls = PollResult::whereHas('poll', function($q) use ($meeting) {
                    $q->whereMeetingId($meeting->id)->where('user_id', '!=', auth()->id());
                })->select('poll_id')->get()->pluck('poll_id')->all();

                $q->where('is_published', 1)
                ->whereNull('meta->result_published_at')
                ->whereNotIn('id', $alreadyVotedPolls);
            });
        }

        $per_page     = $this->poll->getPerPage();
        $current_page = $this->poll->getCurrentPage();

        return new MeetingPollCollection($query->orderBy($sort_by, $order)->paginate((int) $per_page, ['*'], 'current_page'));
    }

    public function create(Request $request, Meeting $meeting) : Poll
    {
        if (Poll::whereMeetingId($meeting->id)->whereQuestion($request->question)->exists()) {
            throw ValidationException::withMessages(['message' => trans('validation.unique', ['attribute' => trans('meeting.poll.props.question')])]);
        }

        if (count($request->options) < 2) {
            throw ValidationException::withMessages(['message' => trans('meeting.poll.min_two_option_required')]);
        }

        $poll = $this->poll->create([
            'meeting_id' => $meeting->id,
            'question'   => $request->input('question'),
            'duration'   => $request->input('duration'),
            'options'    => $request->input('options'),
            'user_id'    => auth()->id(),
            'meta' => array('config' => array(
                'position' => $request->input('config.position'),
                'is_closable' => $request->boolean('config.is_closable'),
                'show_result_after' => $request->boolean('config.show_result_after'),
                )
            )
        ]);

        return $poll;
    }

    public function formatParams(Request $request) : array
    {
        return [];
    }

    public function publish(Request $request, Meeting $meeting, Poll $poll) : Poll
    {
        if ($poll->start_at) {
            throw ValidationException::withMessages(['message' => trans('meeting.poll.already_published')]);
        }

        $poll->start_at = now();
        $poll->is_published = 1;
        $poll->save();

        return $poll;
    }

    public function publishResult(Request $request, Meeting $meeting, Poll $poll) : Poll
    {
        if ($poll->getMeta('result_published_at')) {
            throw ValidationException::withMessages(['message' => trans('meeting.poll.result_already_published')]);
        }

        $meta = $poll->meta;
        $meta['result_published_at'] = now()->toDateTimeString();
        $poll->meta = $meta;
        $poll->save();

        return $poll;
    }

    public function vote(Request $request, Meeting $meeting, Poll $poll) : Poll
    {
        if (! $poll->start_at) {
            throw ValidationException::withMessages(['message' => trans('meeting.poll.invalid_poll')]);
        }

        // check if poll is live

        if (! empty(auth()->user())) {
            $alreadyVoted = PollResult::wherePollId($poll->id)->whereUserId(auth()->id())->exists();

            if ($alreadyVoted) {
                throw ValidationException::withMessages(['message' => trans('meeting.poll.already_voted')]);
            }
        }

        $options = $poll->options;

        if (! in_array($request->input('answer.uuid'), Arr::pluck($options, 'uuid'))) {
            throw ValidationException::withMessages(['message' => trans('meeting.poll.invalid_option')]);
        }

        $user = [];
        if (empty(auth()->user())) {
            $user = array(
                'name' => $request->input('user.name'),
                'ip' => IpHelper::getClientIp()
            );
        }

        PollResult::forceCreate([
            'poll_id' => $poll->id,
            'user_id' => ! empty(auth()->user()) ? auth()->id() : null,
            'answer' => $request->input('answer.uuid'),
            'meta' => array(
                'user' => $user
            )
        ]);

        return $poll;
    }

    public function update(Request $request, Meeting $meeting, Poll $poll) : void
    {
        if (Poll::where('id', '!=', $poll->id)->whereMeetingId($meeting->id)->whereQuestion($request->question)->exists()) {
            throw ValidationException::withMessages(['message' => trans('validation.unique', ['attribute' => trans('meeting.poll.props.question')])]);
        }

        if ($poll->start_at) {
            throw ValidationException::withMessages(['message' => trans('meeting.poll.could_not_update_after_publish')]);
        }

        if (count($request->options) < 2) {
            throw ValidationException::withMessages(['message' => trans('meeting.poll.min_two_option_required')]);
        }

        $poll->question = $request->input('question');
        $poll->duration = $request->input('duration');
        $poll->options = $request->input('options');
        $poll->meta = array('config' => array(
                'position' => $request->input('config.position'),
                'is_closable' => $request->boolean('config.is_closable'),
                'show_result_after' => $request->boolean('config.show_result_after'),
            )
        );
        $poll->save();
    }

    public function delete(Meeting $meeting, Poll $poll)
    {
        if ($poll->results()->count()) {
            throw ValidationException::withMessages(['message' => trans('global.associated_with_dependency', ['attribute' => trans('meeting.poll.poll'), 'dependency' => trans('meeting.poll.result')])]);
        }

        $this->poll->whereMeetingId($meeting->id)->whereId($poll->id)->delete();
    }
}
