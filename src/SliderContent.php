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
     * The uuids of the files to add.
     *
     * @var string[]
     */
    private $files = [];

    /**
     * The file order.
     *
     * @var string[]
     */
    private $filesOrder = [];

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

    /**
     * Add files to the content.
     *
     * @param string[] $files      The uuids.
     * @param string[] $filesOrder The uuids for sort order.
     *
     * @return void
     */
    public function addFiles(array $files, array $filesOrder = [])
    {
        $this->files = array_merge($this->files, array_values($files));
        if (!empty($this->filesOrder) || empty($filesOrder)) {
            $this->filesOrder = array_merge($this->filesOrder, array_values($filesOrder ?: $files));
        }
    }

    /**
     * Check if files are contained.
     *
     * @return bool
     */
    public function hasFiles()
    {
        return (bool) $this->files;
    }

    /**
     * Retrieve the files.
     *
     * @return array
     */
    public function getFiles()
    {
        return $this->files;
    }

    /**
     * Retrieve the files.
     *
     * @return array
     */
    public function getFilesOrder()
    {
        return $this->filesOrder;
    }
}
