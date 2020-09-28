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
		return [
			BundleConfig::create(RockSolidSliderBundle::class)
				->setLoadAfter([ContaoCoreBundle::class])
				->setLoadAfter([ContaoNewsBundle::class])
				->setLoadAfter([ContaoCalendarBundle::class])
				->setReplace(['rocksolid-slider']),
		];
	}
}
