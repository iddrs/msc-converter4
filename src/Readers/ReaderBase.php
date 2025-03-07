<?php

namespace MscConverter\Readers;

use MscConverter\Observers\EventListenerInterface;
use MscConverter\Observers\ObserverInterface;

abstract class ReaderBase implements ReaderInterface {
    
    public readonly int $remessa;
    protected ObserverInterface $events;

    public readonly string $codInstituicaoSiconfi;

    abstract public function load(): void;
    
    abstract public function readRow(): bool|array;
    
    public function setEventManager(ObserverInterface $events): ReaderBase {
        $this->events = $events;
        return $this;
    }
    
}
