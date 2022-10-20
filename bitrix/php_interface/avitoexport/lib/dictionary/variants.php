<?php
namespace AvitoExport\PhpInterface\Mapper;

use Avito\Export;
use Avito\Export\Dictionary\Dictionary;

class Variants
{
	private $dictionary;
	private $attribute;
	/** @var array */
	private $values;

	public static function fromXmlTree(string $path, string $attribute) : self
	{
		return new static(
			new Export\Dictionary\XmlTree($path),
			$attribute
		);
	}

	public function __construct(Dictionary $dictionary, string $attribute)
	{
		$this->dictionary = $dictionary;
		$this->attribute = $attribute;
	}

	public function search($value) : ?string
	{
		$sanitized = $this->sanitize($value);
		$map = $this->values();

		return $map[$sanitized] ?? null;
	}

	public function values() : ?array
	{
		if ($this->values === null)
		{
			$this->values = $this->load();
		}

		return $this->values;
	}

	private function load() : array
	{
		$variants = $this->dictionary->variants($this->attribute);
		$map = [];

		foreach ($variants as $variant)
		{
			$map[$this->sanitize($variant)] = $variant;
		}

		return $map;
	}

	private function sanitize($value) : string
	{
		$value = mb_strtolower($value);
		$value = \CUtil::translit($value, 'ru');
		$value = str_replace(['*', '\'', ' '], '', $value);

		return $value;
	}
}