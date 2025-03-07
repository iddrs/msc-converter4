<?php

namespace MscConverter\Observers\Events;

use League\CLImate\CLImate;
use League\CLImate\TerminalObject\Dynamic\Progress;
use MscConverter\Observers\EventInterface;

class ProgressEvent implements EventInterface {
    public readonly string $eventType;
    public readonly int $total;
    public int $current;
    public readonly Progress $progress;


    public function __construct(int $current, int $total) {
        $this->current = $current;
        $this->total = $total;
        $this->eventType = self::class;
        $climate = new CLImate();
        $this->progress = $climate->progress($total);
    }
}
