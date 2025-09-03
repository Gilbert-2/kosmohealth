<?php

namespace App\Http\Controllers;

use App\Models\Resource;
use Illuminate\Http\Request;

class ResourcesController extends Controller
{
    public function index(Request $request)
    {
        $query = Resource::query()->where('status', 'published');
        if ($request->filled('category')) {
            $query->where('category', $request->get('category'));
        }
        if ($request->filled('q')) {
            $q = '%' . $request->get('q') . '%';
            $query->where(function($w) use ($q) {
                $w->where('title', 'like', $q)->orWhere('excerpt', 'like', $q)->orWhere('body', 'like', $q);
            });
        }
        $resources = $query->orderByDesc('created_at')->paginate((int)$request->get('per_page', 15));
        return $this->ok($resources);
    }

    public function show(string $uuid)
    {
        $res = Resource::where('uuid', $uuid)->where('status', 'published')->firstOrFail();
        return $this->ok($res);
    }
}


