<?php

namespace App\Actions\Event;

use App\Http\Requests\Event\EventCheckAllRequest;
use App\Models\Event;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CheckAllEventsAction
{
    public function execute(EventCheckAllRequest $request)
    {
        $notificationsIds = collect($request->validated()['notifications'])
            ->pluck('id');

        try {
            DB::transaction(function () use ($notificationsIds) {
                Event::whereIn('id', $notificationsIds)->update(['checked' => true]);
            });
        } catch (\Throwable $th) {
            Log::alert($th);
        }

        return response([
            'message' => 'Todas as notificações marcadas como lidas.',
        ]);
    }
}
