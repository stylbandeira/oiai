<?php

namespace App\Http\Controllers;

use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class EventController extends Controller
{
    public function update(Request $request, Event $event)
    {
        $event->update($request->all());

        return response(['message' => 'Evento alterado com sucesso!']);
    }

    public function checkAll(Request $request)
    {
        $notifications_ids = collect($request->notifications)
            ->pluck('id');

        $events = Event::whereIn('id', $notifications_ids)->get();

        try {
            Event::whereIn('id', $notifications_ids)->update(['checked' => true]);
        } catch (\Throwable $th) {
            Log::alert($th);
        }

        return response([
            'message' => 'Todas as notificações marcadas como lidas.'
        ]);
    }
}
