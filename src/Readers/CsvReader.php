<?php

namespace MscConverter\Readers;

use MscConverter\Observers\EventInterface;
use MscConverter\Observers\EventListenerInterface;
use MscConverter\Observers\Events\NoticeMessageEvent;
use Override;
use RuntimeException;
use ValueError;

class CsvReader extends ReaderBase{
    
    private $fhandler;
    private readonly string $filePath;
    private int $totalRows = 0;
    
    public function __construct(string $filePath) {
        $this->filePath = $filePath;
    }
    
    private function openCsv(string $filePath) {
        $this->events->notify(new NoticeMessageEvent("Abrindo MSC de $filePath"));
        $fhandler = @fopen($filePath, 'r');
        if($fhandler === false){
            throw new RuntimeException("Arquivo $filePath não pôde ser aberto para leitura.");
        }
        return $fhandler;
    }
    
    private function parseMetadata(): void {
        $this->events->notify(new NoticeMessageEvent("Lendo metadados da MSC..."));
        $firstLine = fgetcsv($this->fhandler, length: null, separator: ';', enclosure: '"', escape: '\\');
        if($firstLine === false){
            throw new ValueError('Não foi possível ler a linha de metadados.');
        }
        if(is_null($firstLine[array_key_first($firstLine)])){
            throw new ValueError('A linha de metadados parece estar em branco.');
        }
        $this->codInstituicaoSiconfi = $firstLine[0];
        $this->remessa = (int) str_replace('-', '', $firstLine[1]);
        $this->totalRows = $this->countTotalRows();
        
    }
    
    private function countTotalRows(): int {
        rewind($this->fhandler);// Coloca o ponteiro no início do arquivo
        fgets($this->fhandler);// Pula a linha de metadados
        fgets($this->fhandler);// Pula a linha de cabeçalho
        $totalRows = 0;
        while (fgets($this->fhandler)){
            $totalRows++;
        }
        return $totalRows;
    }
        
    #[Override]
    public function load(): void {
        $this->events->notify(new NoticeMessageEvent("Carregando dados da MSC."));
        $this->fhandler = $this->openCsv($this->filePath);
        $this->parseMetadata();
//        $this->readFieldNames();
        rewind($this->fhandler);// Coloca o ponteiro no início do arquivo
        fgets($this->fhandler);// Pula a linha de metadados
        fgets($this->fhandler);// Pula a linha de cabeçalho
    }

    #[Override]
    public function readRow(): bool|array {
        $row = fgetcsv($this->fhandler, length: null, separator: ';', enclosure: '"', escape: '\\');
        if($row === false){
            return false;
        }
        if(is_null($row[array_key_first($row)])){
            return false;
        }
        return $row;
    }
    
    public function getTotalRows(): int {
        return $this->totalRows;
    }
}
