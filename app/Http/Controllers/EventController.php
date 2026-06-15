<?php

namespace App\Http\Controllers;

use App\Http\Requests\Event\EventCheckAllRequest;
use App\Http\Requests\Event\EventUpdateRequest;
use App\Models\Event;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EventController extends Controller
{
    public function update(EventUpdateRequest $request, Event $event)
    {
        $event->update($request->validated());

        return response(['message' => 'Evento alterado com sucesso!']);
    }

    public function checkAll(EventCheckAllRequest $request)
    {
        $notifications_ids = collect($request->validated()['notifications'])
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
