<?php

/*
 * Copyright MADE/YOUR/DAY OG <mail@madeyourday.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace MadeYourDay\RockSolidSlider\SlideProvider;

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
     * Retrieve the slides for the passed config.
     *
     * The result will be the array of slide information.
     * Each slide must at least contain the key 'text'.
     *
     * @param array $config The configuration to process (refer to provider implementation for contents).
     *
     * @return array
     */
    public function getSlides(array $config);
}
