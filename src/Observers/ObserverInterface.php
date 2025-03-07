<?php

namespace MscConverter\Observers;

interface ObserverInterface {
    public function subscribe(string $eventType, EventListenerInterface $listener): ObserverInterface;
    public function unsubscribe(string $eventType, EventListenerInterface $listener): ObserverInterface;
    public function notify(EventInterface $event): void;
}
