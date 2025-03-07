<?php

namespace MscConverter\Observers\Events;

class NoticeMessageEvent implements \MscConverter\Observers\EventInterface {
    
    public readonly string $eventType;
    public readonly string $message;
    
    public function __construct(string $message) {
        $this->message = $message;
        $this->eventType = self::class;
    }
}
