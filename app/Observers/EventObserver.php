<?php

namespace App\Observers;

use App\Models\Event;
use App\Services\NotificationService;

class EventObserver
{
    protected $notification;

    public function __construct(NotificationService $notification)
    {
        $this->notification = $notification;
    }

    public function created(Event $event)
    {
        switch ($event->type) {
            case 'product_insert':
                //TODO - SEND NOTIFICATION
                break;

            default:
                # code...
                break;
        }
    }
}
