<?php

namespace App\Http\Controllers\Host;

use App\Http\Controllers\Controller;
use App\Models\Resource;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class HostResourcesController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth:sanctum']);
    }

    public function index(Request $request)
    {
        $hostId = $request->user()->id;
        $q = Resource::where('user_id', $hostId)
            ->when($request->filled('status'), fn($x)=>$x->where('status', $request->get('status')))
            ->orderByDesc('created_at')
            ->paginate((int)$request->get('per_page', 15));
        return $this->ok($q);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'excerpt' => 'nullable|string|max:500',
            'body' => 'nullable|string',
            'category' => 'nullable|string|max:100',
            'tags' => 'array',
            'read_time' => 'nullable|string|max:50',
            'status' => 'in:draft,published',
        ]);
        $res = Resource::create(array_merge($data, [
            'uuid' => (string) Str::uuid(),
            'user_id' => $request->user()->id,
        ]));
        return $this->success(['message' => 'Resource created', 'data' => $res]);
    }

    public function show(Request $request, int $id)
    {
        $res = Resource::where('user_id', $request->user()->id)->findOrFail($id);
        return $this->ok($res);
    }

    public function update(Request $request, int $id)
    {
        $res = Resource::where('user_id', $request->user()->id)->findOrFail($id);
        $data = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'excerpt' => 'nullable|string|max:500',
            'body' => 'nullable|string',
            'category' => 'nullable|string|max:100',
            'tags' => 'array',
            'read_time' => 'nullable|string|max:50',
            'status' => 'in:draft,published',
        ]);
        $res->fill($data)->save();
        return $this->success(['message' => 'Resource updated', 'data' => $res]);
    }

    public function destroy(Request $request, int $id)
    {
        $res = Resource::where('user_id', $request->user()->id)->findOrFail($id);
        $res->delete();
        return $this->success(['message' => 'Resource deleted']);
    }

    public function publish(Request $request, int $id)
    {
        $res = Resource::where('user_id', $request->user()->id)->findOrFail($id);
        $data = $request->validate([
            'status' => 'required|in:draft,published',
        ]);
        $res->status = $data['status'];
        $res->save();
        return $this->success(['message' => 'Resource status updated', 'data' => $res]);
    }
}


