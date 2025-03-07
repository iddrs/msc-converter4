<?php

use MscConverter\Factories\ReaderFactory;
use MscConverter\Factories\WriterFactory;
use MscConverter\Observers\EventManager;
use MscConverter\Observers\Events\InfoMessageEvent;
use MscConverter\Observers\Events\NoticeMessageEvent;
use MscConverter\Observers\Events\ProgressEvent;
use MscConverter\Processors\ConverterProcessor;
use MscConverter\UI\Console;

require __DIR__ . '/vendor/autoload.php';

$climate = new \League\CLImate\CLImate();
$climate->arguments->add([
    'from' => [
        'prefix' => 'f',
        'longPrefix' => 'from',
        'description' => 'Caminho para o arquivo de origem dos dados da MSC.',
    ],
    'to' => [
        'prefix' => 't',
        'longPrefix' => 'to',
        'description' => 'Caminho para o arquivo de destino dos dados.',
    ],
    'help' => [
        'prefix' => 'h',
        'longPrefix' => 'help',
        'description' => 'Mostra a ajuda.',
        'noValue' => true,
    ],
]);

$climate->arguments->parse();

if ($climate->arguments->defined('help')) {
    echo file_get_contents(__DIR__ . '/assets/help.txt');
    exit(0);
}

if (!$climate->arguments->defined('from') && !$climate->arguments->defined('to')) {
    echo 'Parâmetros de entrada requeridos não foram definidos. Consulte a ajuda:', PHP_EOL, PHP_EOL;
    echo file_get_contents('./assets/help.txt');
    exit(0);
}

$readerFilePath = $climate->arguments->get('from');
$writerFilePath = $climate->arguments->get('to');

//$readerFilePath = 'Z:\\MSC\\2025\\MSCAgregadaJaneiro2025.csv';
//$writerFilePath = './temp/msc-prod.db';

$reader = ReaderFactory::createReaderFromFile($readerFilePath);
$writer = WriterFactory::createWriterToFile($writerFilePath);

$events = new EventManager();
$events
    ->subscribe(InfoMessageEvent::class, new Console())
    ->subscribe(NoticeMessageEvent::class, new Console())
    ->subscribe(ProgressEvent::class, new Console())
;

$reader->setEventManager($events);
$writer->setEventManager($events);

$processor = new ConverterProcessor($reader, $writer)->setEventManager($events)->convert();
