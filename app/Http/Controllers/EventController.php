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
        $events = $request->user()->events;
        Log::alert($events);
    }
}
