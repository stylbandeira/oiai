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

        return response(['message' => 'Oie']);
    }

    public function checkAll(Request $request)
    {
        Event::where('user_id', $request->user()->id)->update(['checked' => true]);

        return response([
            'message' => 'Todas as notificações marcadas como lidas.'
        ]);
    }
}
