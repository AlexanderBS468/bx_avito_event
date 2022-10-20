<?php
namespace AvitoExport\PhpInterface\Mapper;

use Avito\Export\Feed;
use Avito\Export\Feed\Source\Field;

class Mapper
{
	protected $map;

	public function add($field, callable $builder, array $dependencies) : void
	{
		if (!($field instanceof Field\Field))
		{
			$field = new Field\StringField([
				'ID' => $field,
				'NAME' => $field,
			]);
		}

		$this->map[$field->id()] = [
			'FIELD' => $field,
			'PARTIALS' => $dependencies,
			'BUILDER' => $builder,
		];
	}

	public function fields() : array
	{
		return array_column($this->map, 'FIELD');
	}

	public function extend(array $fields, Feed\Setup\TagSources $sources) : void
	{
		foreach ($fields as $field)
		{
			if (!isset($this->map[$field])) { continue; }

			$link = $this->map[$field];

			foreach ($link['PARTIALS'] as [$siblingSource, $siblingField])
			{
				$sources->add($siblingSource, $siblingField);
			}
		}
	}

	public function values(array $elements, array $siblings, array $select) : array
	{
		$result = [];

		foreach ($elements as $id => $element)
		{
			$export = [];

			foreach ($select as $field)
			{
				if (!isset($this->map[$field])) { continue; }

				$link = $this->map[$field];
				$mapped = $this->collectMapped($link['PARTIALS'], $siblings[$id]);
				$builder = $link['BUILDER'];

				$export[$field] = $builder($mapped);
			}

			$result[$id] = $export;
		}

		return $result;
	}

	protected function collectMapped(array $map, array $sibling) : array
	{
		$result = [];

		foreach ($map as $name => [$siblingSource, $siblingField])
		{
			$result[$name] = $sibling[$siblingSource][$siblingField];
		}

		return $result;
	}
}