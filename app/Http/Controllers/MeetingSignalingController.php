<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class MeetingSignalingController extends Controller
{
    private function key(string $uuid, string $suffix): string
    {
        return "meeting:signal:{$uuid}:{$suffix}";
    }

    public function postOffer(Request $request, string $uuid)
    {
        $data = $request->validate(['sdp' => 'required|string']);
        Cache::put($this->key($uuid, 'offer'), $data['sdp'], 3600);
        Cache::forget($this->key($uuid, 'answer'));
        return $this->success(['message' => 'offer stored']);
    }

    public function getOffer(Request $request, string $uuid)
    {
        return $this->ok(['sdp' => Cache::get($this->key($uuid, 'offer'))]);
    }

    public function postAnswer(Request $request, string $uuid)
    {
        $data = $request->validate(['sdp' => 'required|string']);
        Cache::put($this->key($uuid, 'answer'), $data['sdp'], 3600);
        return $this->success(['message' => 'answer stored']);
    }

    public function getAnswer(Request $request, string $uuid)
    {
        return $this->ok(['sdp' => Cache::get($this->key($uuid, 'answer'))]);
    }

    public function postCandidate(Request $request, string $uuid)
    {
        $data = $request->validate(['candidate' => 'required|array']);
        $list = Cache::get($this->key($uuid, 'candidates'), []);
        $list[] = $data['candidate'];
        Cache::put($this->key($uuid, 'candidates'), $list, 3600);
        return $this->success(['message' => 'candidate stored']);
    }

    public function getCandidates(Request $request, string $uuid)
    {
        $list = Cache::pull($this->key($uuid, 'candidates'), []);
        return $this->ok(['candidates' => $list]);
    }
}
