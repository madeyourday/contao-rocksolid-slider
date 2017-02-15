<?php

namespace MadeYourDay\RockSolidSlider;

use Contao\CoreBundle\ContaoCoreBundle;
use Contao\ManagerPlugin\Bundle\BundlePluginInterface;
use Contao\ManagerPlugin\Bundle\Parser\ParserInterface;
use Contao\ManagerPlugin\Bundle\Config\BundleConfig;

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
				->setReplace(['rocksolid-slider']),
		];
	}
}
