<?php

/*
 * Copyright MADE/YOUR/DAY OG <mail@madeyourday.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace MadeYourDay\RockSolidSlider;

/**
 * This class holds the slider contents.
 *
 * @author Christian Schiffler <c.schiffler@cyberspectrum.de>
 */
class SliderContent
{
    /**
     * The parsed slides.
     *
     * @var array
     */
    private $slides = [];

    /**
     * Add slides.
     *
     * Each slide must at least contain the key 'text'.
     *
     * @param array $slides The slides to add.
     *
     * @return void
     */
    public function addSlides($slides)
    {
        foreach ($slides as $slide) {
            if (!isset($slide['text'])) {
                throw new \InvalidArgumentException('Slide does not contain key "text".');
            }
            $this->slides[] = $slide;
        }
    }

    /**
     * Check if slides are contained.
     *
     * @return bool
     */
    public function hasSlides()
    {
        return (bool) $this->slides;
    }

    /**
     * Retrieve the slides.
     *
     * @return array
     */
    public function getSlides()
    {
        return $this->slides;
    }
}
