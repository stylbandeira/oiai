<?php

namespace App\Http\Controllers;

use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class EventController extends Controller
{
    public function update(Request $request, Event $event)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'nullable|integer',
            'title' => 'string',
            'description' => 'string',
            'where' => 'nullable|string',
            'type' => 'string',
            'points' => 'integer',
            'link' => 'string',
            'checked' => 'boolean',
            'target_type' => 'string',
            'entity_type' => 'nullable|string',
            'entity_id' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return response([
                'errors' => $validator->errors()
            ], 422);
        }

        $event->update($validator->validated());

        return response(['message' => 'Evento alterado com sucesso!']);
    }

    public function checkAll(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'notifications' => 'required|array',
            'notifications.*.id' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response([
                'errors' => $validator->errors()
            ], 422);
        }

        $notifications_ids = collect($validator->validated()['notifications'])
            ->pluck('id');

        try {
            DB::transaction(function () use ($notifications_ids) {
                Event::whereIn('id', $notifications_ids)->update(['checked' => true]);
            });
        } catch (\Throwable $th) {
            Log::alert($th);
        }

        return response([
            'message' => 'Todas as notificações marcadas como lidas.'
        ]);
    }
}
