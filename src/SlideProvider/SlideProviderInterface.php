<?php

/*
 * Copyright MADE/YOUR/DAY OG <mail@madeyourday.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace MadeYourDay\RockSolidSlider\SlideProvider;

use MadeYourDay\RockSolidSlider\SliderContent;

/**
 * Describes a generic slide provider.
 *
 * @author Christian Schiffler <c.schiffler@cyberspectrum.de>
 */
interface SlideProviderInterface
{
    /**
     * Retrieve the name of the provider.
     *
     * @return string
     */
    public function getName();

    /**
     * Process the passed configuration.
     *
     * @param array         $config  The configuration to process (refer to provider implementation for contents).
     * @param SliderContent $content The content to populate.
     *
     * @return void
     */
    public function process(array $config, SliderContent $content);
}
