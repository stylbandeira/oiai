<?php

namespace App\Actions\Event;

use App\Http\Requests\Event\EventUpdateRequest;
use App\Models\Event;

class UpdateEventAction
{
    public function execute(EventUpdateRequest $request, Event $event)
    {
        $event->update($request->validated());

        return response(['message' => 'Evento alterado com sucesso!']);
    }
}
