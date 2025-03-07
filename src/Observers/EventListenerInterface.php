<?php

namespace MscConverter\Observers;

interface EventListenerInterface {
    public function update(EventInterface $event): void;
}
