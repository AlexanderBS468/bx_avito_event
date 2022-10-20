<?php

use Bitrix\Main;
use AvitoExport\PhpInterface\Source;

$eventManager = Main\EventManager::getInstance();

$titleSource = 'SHOP_PROPERTY';

$eventManager->addEventHandler('avito.export', 'onFeedSourceBuild', function(Main\Event $event) use ($titleSource) {
	return new Main\EventResult(Main\EventResult::SUCCESS, [
		$titleSource => new Source\Property\Fetcher(),
	]);
});