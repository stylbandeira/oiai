<?php

namespace App\Http\Controllers;

use App\Http\Resources\UnityResource;
use App\Models\Unity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class UnityController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'search' => 'sometimes|string',
            'per_page' => 'sometimes|integer|min:1|max:100',
        ]);

        if ($validator->fails()) {
            return response([
                'errors' => $validator->errors()
            ], 422);
        }

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

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
