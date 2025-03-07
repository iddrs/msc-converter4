<?php

namespace MscConverter\UI;

use League\CLImate\CLImate;
use League\CLImate\TerminalObject\Dynamic\Progress;
use MscConverter\Observers\EventInterface;
use MscConverter\Observers\EventListenerInterface;
use MscConverter\Observers\Events\InfoMessageEvent;
use MscConverter\Observers\Events\NoticeMessageEvent;
use MscConverter\Observers\Events\ProgressEvent;
use Override;

class Console implements EventListenerInterface {
    
    private readonly CLImate $climate;
    
    public function __construct() {
        $this->climate = new CLImate();
    }
    #[Override]
    public function update(EventInterface $event): void {
        $this->processEvent($event);
    }
    
    private function processEvent(EventInterface $event): void {
        switch ($event->eventType){
            case InfoMessageEvent::class:
                $this->climate->green($event->message);
                break;
            case NoticeMessageEvent::class:
                $this->climate->out($event->message);
                break;
            case ProgressEvent::class:
                $event->progress->current($event->current, "[{$event->current}/{$event->total}]");
                break;
            default:
                break;
        }
    }
}
