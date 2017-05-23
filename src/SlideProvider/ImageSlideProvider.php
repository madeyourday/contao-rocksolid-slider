<?php

/*
 * Copyright MADE/YOUR/DAY OG <mail@madeyourday.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace MadeYourDay\RockSolidSlider\SlideProvider;

use Contao\StringUtil;
use MadeYourDay\RockSolidSlider\SliderContent;

/**
 * Provides files.
 *
 * @author Christian Schiffler <c.schiffler@cyberspectrum.de>
 */
class ImageSlideProvider implements SlideProviderInterface
{
    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return 'rsts_images';
    }

    /**
     * {@inheritDoc}
     */
    public function process(array $config, SliderContent $content)
    {
        $content->addFiles(StringUtil::deserialize($config['multiSRC']));
    }
}
