<?php

use Bitrix\Main;
use AvitoExport\PhpInterface;

$basePath = __DIR__ . '/lib';

Main\Loader::registerAutoLoadClasses(null, [
	AvitoExport\PhpInterface\Mapper\HasMapper::class => $basePath . '/mapper/hasmapper.php',
	AvitoExport\PhpInterface\Mapper\Mapper::class => $basePath . '/mapper/mapper.php',
	AvitoExport\PhpInterface\Source\Property\Fetcher::class => $basePath . '/source/property/fetcher.php',
]);

require_once __DIR__ . '/events.php';