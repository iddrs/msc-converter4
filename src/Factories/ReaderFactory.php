<?php

namespace MscConverter\Factories;

class ReaderFactory {
    
    public static function createReaderFromFile(string $filePath): \MscConverter\Readers\ReaderInterface {
        switch (self::detectFileType($filePath)) {
            case 'csv':
                return self::createCsvReader($filePath);
            default :
                throw new \RuntimeException("Arquivo $filePath não suportado.");
        }
    }
    
    private static function detectFileType(string $filePath): string {
        $jack = explode('.', $filePath);
        return strtolower($jack[array_key_last($jack)]);
        
    }
    
    private static function createCsvReader(string $filePath): \MscConverter\Readers\CsvReader {
        return new \MscConverter\Readers\CsvReader($filePath);
    }
}
