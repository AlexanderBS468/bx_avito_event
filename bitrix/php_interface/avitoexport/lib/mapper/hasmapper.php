<?php
namespace AvitoExport\PhpInterface\Mapper;

use Avito\Export\Feed;

/** @property Mapper $mapper */
trait HasMapper
{
	public function order() : int
	{
		return 600;
	}

	public function listener() : Feed\Source\Listener
	{
		return new Feed\Source\NoValue\Listener();
	}

	public function fields(Feed\Source\Context $context) : array
	{
		return $this->mapper->fields();
	}

	public function extend(array $fields, Feed\Setup\TagSources $sources, Feed\Source\Context $context) : void
	{
		$this->mapper->extend($fields, $sources);
	}

	public function values(array $elements, array $parents, array $siblings, array $select, Feed\Source\Context $context) : array
	{
		return $this->mapper->values($elements, $siblings, $select);
	}
}
