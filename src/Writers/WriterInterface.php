<?php

namespace MscConverter\Writers;

interface WriterInterface {
    
    public function prepare(int $remessa): void;
    
    public function storeRow(array $data): void;
    
    public function save(): void;
}
