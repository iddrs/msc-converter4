<?php

namespace MscConverter\Readers;

interface ReaderInterface {
       
    public function load(): void;
    
    public function readRow(): bool|array;
    
    public function setEventManager(\MscConverter\Observers\ObserverInterface $events): ReaderInterface;
    
}
