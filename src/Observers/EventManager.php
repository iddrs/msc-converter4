<?php

namespace MscConverter\Observers;


class EventManager implements ObserverInterface {
    
    private array $listeners = [];
    
    #[\Override]
    public function notify(EventInterface $event): void {
        $eventType = $event->eventType;
        if(key_exists($eventType, $this->listeners)){
            foreach ($this->listeners[$eventType] as $listener) {
                $listener->update($event);
            }
        }
    }

    #[\Override]
    public function subscribe(string $eventType, EventListenerInterface $listener): EventManager {
        $this->listeners[$eventType][] = $listener;
        return $this;
    }

    #[\Override]
    public function unsubscribe(string $eventType, EventListenerInterface $listener): EventManager {
        $listenerKey = array_search($listener, $this->listeners[$eventType], true);
        if($listenerKey !== false){
            unset($this->listeners[$eventType][$listenerKey]);
        }
        return $this;
    }
}
