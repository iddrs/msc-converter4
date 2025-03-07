<?php

namespace MscConverter\Factories;

class WriterFactory {
    
    public static function createWriterToFile(string $filePath): \MscConverter\Writers\WriterInterface {
        switch (self::detectFileType($filePath)) {
            case 'db':
                return self::createSqliteWriter($filePath);
            default :
                throw new \RuntimeException("Arquivo $filePath não suportado.");
        }
    }
    
    private static function detectFileType(string $filePath): string {
        $jack = explode('.', $filePath);
        return strtolower($jack[array_key_last($jack)]);
        
    }
    
    private static function createSqliteWriter(string $filePath): \MscConverter\Writers\SqliteWriter {
        return new \MscConverter\Writers\SqliteWriter($filePath);
    }
}
