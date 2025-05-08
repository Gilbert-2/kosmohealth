<?php

namespace App\Http\Resources;

use App\Helpers\CalHelper;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Arr;

class MeetingPoll extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $options = $this->options ?? [];

        $is_mcq = Arr::where($options, function ($value, $key) {
            return Arr::get($value, 'selected') ? true : false;
            }) ? true : false;

        $is_result = false;
        $submitted_answer = null;
        $correct_answer = null;
        if ($this->relationLoaded('results') && ($this->getMeta('result_published_at') || ($is_mcq && Arr::get($this->meta, 'config.show_result_after')))) {
            $is_result = true;
            if (! empty(auth()->user())) {
                $submitted_answer = Arr::first($options, function($value, $key) {
                    return Arr::get($value, 'uuid') ==  optional($this->results->where('user_id', auth()->id())->first())->answer;
                });
                $correct_answer = Arr::first($options, function($value, $key) {
                    return Arr::get($value, 'selected') ? true : false;
                });
            }
        }

        return [
           'uuid'     => $this->uuid,
           'question' => $this->question,
           'options'  => $this->getOptions($request),
           'is_mcq'   => $is_mcq,
           'start_at'            => $this->start_at ? CalHelper::toDateTime($this->start_at) : null,
           'duration'            => $this->duration,
           'end_at'              => $this->start_at ? CalHelper::toDateTime($this->end_at) : null,
           'is_published'        => (bool) $this->is_published,
           'is_result_published' => (bool) $this->getMeta('result_published_at'),
           'is_result'           => $is_result,
           $this->mergeWhen($is_result, array(
                'submitted_answer' => $submitted_answer,
                'correct_answer'   => $correct_answer,
           )),
           'result'              => $this->getResult($request),
           'config'              => $this->getMeta('config'),
           'created_at'          => CalHelper::toDateTime($this->created_at),
           'updated_at'          => CalHelper::toDateTime($this->updated_at)
        ];
    }

    private function getOptions($request)
    {
        $options = $this->options ?? [];

        if ($request->is_moderator) {
            return $options;
        }

        if (! $this->getMeta('result_published_at')) {
            $items = array();
            foreach ($options as $option) {
                $items[] = array('uuid' => Arr::get($option, 'uuid'), 'title' => Arr::get($option, 'title'));
            };
        } else {
            $items = array();
            foreach ($options as $option) {
                $items[] = array('uuid' => Arr::get($option, 'uuid'), 'title' => Arr::get($option, 'title'), 'selected' => Arr::get($option, 'selected') ? true : false);
            };
        }

        return $items;
    }

    private function getResult($request)
    {
        if (! $this->getMeta('result_published_at')) {
            return null;
        }

        if (! $this->relationLoaded('results')) {
            return null;
        }

        $total_vote = $this->results->count();

        if (! $total_vote) {
            return null;
        }

        $options = $this->options ?? [];

        $items = array();
        foreach ($options as $option) {
            $items[] = array(
                'uuid' => Arr::get($option, 'uuid'),
                'title' => Arr::get($option, 'title'),
                'total_vote' => $total_vote,
                'count' => $this->results->where('answer', Arr::get($option, 'uuid'))->count(),
                'percentage' => $total_vote ? round($this->results->where('answer', Arr::get($option, 'uuid'))->count() / $total_vote * 100) : 0
            );
        };

        return $items;
    }
}
