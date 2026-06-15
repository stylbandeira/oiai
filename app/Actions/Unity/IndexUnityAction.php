<?php

namespace App\Actions\Unity;

use App\Http\Requests\Unity\UnityIndexRequest;
use App\Http\Resources\UnityResource;
use App\Models\Unity;

class IndexUnityAction
{
    public function execute(UnityIndexRequest $request)
    {
        $query = Unity::query();

        if ($request->filled('search')) {
            $searchTerm = '%' . $request->search . '%';

            $query->where(function ($query) use ($searchTerm) {
                $query->where('name', 'like', $searchTerm)
                    ->orWhere('abbreviation', 'like', $searchTerm);
            });
        }

        $unities = $query
            ->orderBy('name')
            ->paginate($request->per_page ?? 15);

        return UnityResource::collection($unities);
    }
}
