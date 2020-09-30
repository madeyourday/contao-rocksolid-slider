<?php

namespace MadeYourDay\RockSolidSlider;

use Contao\CalendarBundle\ContaoCalendarBundle;
use Contao\CoreBundle\ContaoCoreBundle;
use Contao\ManagerPlugin\Bundle\BundlePluginInterface;
use Contao\ManagerPlugin\Bundle\Parser\ParserInterface;
use Contao\ManagerPlugin\Bundle\Config\BundleConfig;
use Contao\NewsBundle\ContaoNewsBundle;

class ContaoManagerPlugin implements BundlePluginInterface
{
	/**
	 * {@inheritdoc}
	 */
	public function getBundles(ParserInterface $parser)
	{
		$bundleConfig = BundleConfig::create(RockSolidSliderBundle::class)
			->setLoadAfter([ContaoCoreBundle::class])
			->setReplace(['rocksolid-slider']);

		if (class_exists(ContaoNewsBundle::class)) {
			$bundleConfig->setLoadAfter([ContaoNewsBundle::class]);
		}
		
		if (class_exists(ContaoCalendarBundle::class)) {
			$bundleConfig->setLoadAfter([ContaoCalendarBundle::class]);
		}
		
		return [
			$bundleConfig,
		];
	}
}
