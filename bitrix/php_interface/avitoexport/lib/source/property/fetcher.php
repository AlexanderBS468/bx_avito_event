<?php
namespace AvitoExport\PhpInterface\Source\Property;

use Avito\Export\Assert;
use Avito\Export\Feed;
use Avito\Export\Feed\Source\Registry;
use Bitrix\Main\Loader;
use AvitoExport\PhpInterface\Mapper;

if (!Loader::includeModule('avito.export')) { return; }

class Fetcher extends Feed\Source\FetcherSkeleton
{
	use Mapper\HasMapper;

	protected $mapper;
	protected $dictionaries;
	protected const PRODUCT_TYPE_TIRE = 'TIRE';
	protected const PRODUCT_TYPE_MOTO_TIRE = 'MOTO_TIRE';
	protected const PRODUCT_TYPE_TIRE_TRUCK = 'TRUCK_TIRE';
	private const PROPERTY_IDS = [
		'season' => 1000,
		'spiked' => 1001,
		'diameter' => 1002,
		'width' => 1003,
		'height' => 1004,
		'brand' => 1005,
		'model' => 1006,
	];

	public function __construct()
	{
		$this->mapper = $this->createMapper();
		$this->dictionaries = $this->loadDictionary();
	}

	/**
	 * @description Название группы для своих пользовательских свойств
	 * @return string
	 */
	public function title() : string
	{
		return 'ShopsEventProps';
	}

	/**
	 * @description загрузка словарей модуля
	 * @return array
	 */
	protected function loadDictionary() : array
	{
		$baseDirDictionary = 'transportation/partsandaccessories/tiresrimsandwheels/producttype/';

		$dictionaries = [
			self::PRODUCT_TYPE_TIRE => [
				'RimDiameter' => 'tires/rimdiameter.xml',
			],
			self::PRODUCT_TYPE_MOTO_TIRE => [
				'RimDiameter' => 'moto_tires/rimdiameter.xml',
			],
			self::PRODUCT_TYPE_TIRE_TRUCK => [
				'RimDiameter' => 'for_trucks_and_special_vehicles/rimdiameter.xml',
			]
		];

		$result = [];

		foreach ($dictionaries as $type => $dictionary)
		{
			foreach ($dictionary as $attribute => $dictionaryPath)
			{
				$result[$type][$attribute] = Mapper\Variants::fromXmlTree($baseDirDictionary . $dictionaryPath, $attribute);
			}
		}

		return $result;
	}

	/**
	 * @description создание карты свойств для сопоставления и сборки данных при формировании файла выгрузки
	 * @return \AvitoExport\PhpInterface\Mapper\Mapper
	 */
	protected function createMapper() : Mapper\Mapper
	{
		$mapper = new Mapper\Mapper();

		$mapper->add('Event_TireType', [ $this, 'buildTireType' ], [
			'season' => [ Registry::IBLOCK_PROPERTY, self::PROPERTY_IDS['season'] ],
			'spiked' => [ Registry::IBLOCK_PROPERTY, self::PROPERTY_IDS['spiked'] ],
		]);
		$mapper->add('Event_RimDiameter', [ $this, 'buildRimDiameter' ], [
			'RimDiameter' => [ Registry::IBLOCK_PROPERTY, self::PROPERTY_IDS['diameter'] ],
			'ProductType' => [ Registry::SECTION_PROPERTY, 'UF_AVITO_PRODUCTTYPE'],
		]);
		$mapper->add('Event_DescriptionParams', [ $this, 'buildDescriptionParams' ], [
			'Марка' => [ Registry::IBLOCK_PROPERTY, self::PROPERTY_IDS['brand'] ],
			'Модель' => [ Registry::IBLOCK_PROPERTY, self::PROPERTY_IDS['model'] ],
			'Диаметр' => [ Registry::IBLOCK_PROPERTY, self::PROPERTY_IDS['diameter']  ],
			'Ширина' => [ Registry::IBLOCK_PROPERTY, self::PROPERTY_IDS['width'] ],
			'Профиль' => [ Registry::IBLOCK_PROPERTY, self::PROPERTY_IDS['height']  ],
			'Сезонность' => [ Registry::IBLOCK_PROPERTY, self::PROPERTY_IDS['season']  ],
			'Наличие шипов' => [ Registry::IBLOCK_PROPERTY, self::PROPERTY_IDS['spiked'] ],
		]);

		return $mapper;
	}

	/**
	 * @description метод фрмирования типа шин зависящие от двух свойств
	 * @param array $values
	 *
	 * @return string|null
	 */
	public function buildTireType(array $values) : ?string
	{
		$result = null;
		$season = $values['season'];
		$spiked = $values['spiked'];

		$valuesAvito = [
			'summer' => 'Летние',
			'winter_non_spiked' => 'Зимние нешипованные',
			'winter_spiked' => 'Зимние шипованные',
			'all_season' => 'Всесезонные',
		];

		if ($season === 'Зимние' || $season === 'Зимняя')
		{
			$result = (
				 $spiked === 'с шипами' ?
					 $valuesAvito['winter_spiked']
					 : $valuesAvito['winter_non_spiked']
			 );
		}
		else if ($season === 'Летние' || $season === 'Летняя')
		{
			$result = $valuesAvito['summer'];
		}
		else if ($season === 'Всесезонные' || $season === 'Всесезонная')
		{
			$result = $valuesAvito['all_season'];
		}

		return $result;
	}

	/**
	 * @description метод проверки атрибута диаметра по словарю значений Авито
	 * @param array $values
	 *
	 * @return string|null
	 */
	public function buildRimDiameter(array $values) : ?string
	{
		$productType = $values['ProductType'];
		$rimDiameter = trim($values['RimDiameter']);

		if (!$productType) { return null; }

		$fallback = 'Другое';

		if($rimDiameter === '0' || $rimDiameter === '')
		{
			return $fallback;
		}

		$matched = $this->checkDictionaryValue($productType, 'RimDiameter', $rimDiameter);

		if ($matched !== null) { return $matched; }

		if (preg_match('/^(\d+[.,]\d+)0$/', $rimDiameter, $matches))
		{
			$matched = $this->checkDictionaryValue($productType, 'RimDiameter', $matches[1]);

			if ($matched !== null) { return $matched; }
		}

		return $fallback;
	}

	/**
	 * @description метод сбора и формирования пользовательского описания
	 * @param array $values
	 *
	 * @return string|null
	 */
	public function buildDescriptionParams(array $values) : ?string
	{
		$partials = [];

		foreach ($values as $name => $value)
		{
			$value = trim($value);

			if ($value === '') { continue; }

			$partials[] = $name . ': ' . $value;
		}

		return implode('<br />', $partials);
	}

	/**
	 * @description       Метод проверки значений с библиотекой значений Авито
	 * @param string      $productType
	 * @param string      $attribute
	 * @param string      $value
	 * @param string|null $valueCallBack
	 *
	 *
	 * @return string|null
	 */
	private function checkDictionaryValue(string $productType, string $attribute, string $value, string $valueCallBack = null) : ?string
	{
		$dictionaryType = $this->getDictionaryProductType($productType);
		$dictionary = $this->dictionaries[$dictionaryType][$attribute] ?? null;

		Assert::notNull($dictionary, sprintf('$dictionary[%s][%s]', $dictionaryType, $attribute));

		return $this->sanitizeDictionary($dictionary, $value, $valueCallBack);
	}

	/**
	 * @description  Метод получения типа шин для списка словарей
	 * @param string $productType
	 *
	 * @return string
	 */
	private function getDictionaryProductType(string $productType) : string
	{
		$result = self::PRODUCT_TYPE_TIRE;

		if ($productType === 'Мотошины')
		{
			$result = self::PRODUCT_TYPE_MOTO_TIRE;
		}
		else if ($productType === 'Шины для грузовиков и спецтехники')
		{
			$result = self::PRODUCT_TYPE_TIRE_TRUCK;
		}

		return $result;
	}

	/**
	 * @param \AvitoExport\PhpInterface\Mapper\Variants $variants
	 * @param                                           $value
	 * @param                                           $fallback
	 *
	 * @return mixed|string|null
	 */
	private function sanitizeDictionary(Mapper\Variants $variants, $value, $fallback = null)
	{
		return $variants->search($value) ?? $fallback;
	}
}