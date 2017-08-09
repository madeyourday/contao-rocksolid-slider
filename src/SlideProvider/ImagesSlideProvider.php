<?php

/*
 * Copyright MADE/YOUR/DAY OG <mail@madeyourday.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace MadeYourDay\RockSolidSlider\SlideProvider;

use Contao\StringUtil;
use Contao\System;
use MadeYourDay\RockSolidSlider\SliderContent;
use MadeYourDay\RockSolidSlider\Helper\FileHelper;

/**
 * Provides files.
 *
 * @author Christian Schiffler <c.schiffler@cyberspectrum.de>
 */
class ImagesSlideProvider implements SlideProviderInterface
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
        $content->addSlides($this->prepareImages(
            StringUtil::deserialize($config['multiSRC']),
            StringUtil::deserialize($config['orderSRC']),
            $config
        ));
    }

    /**
     * Prepare the images.
     *
     * @param array $files
     * @param array $order
     * @param array $config
     *
     * @return array
     */
    private function prepareImages($files, $order, $config)
    {
        if (empty($files)) {
            return [];
        }

        /** @var FileHelper $helper */
        $helper = System::getContainer()->get('madeyourday.rocksolid_slider.file_helper');
        $images = [];

        foreach ($helper->findMultipleFilesByUuidRecursive($files) as $file) {

            // Continue if the files has been processed or does not exist
            if (isset($images[$file->path]) || !file_exists(dirname(System::getContainer()->getParameter('kernel.root_dir')) . '/' . $file->path)) {
                continue;
            }

            if (null !== ($imageData = $helper->tryPrepareImage($file, [], true))) {
                // Add the image
                $images[$file->path] = $imageData;
            }

        }

        if ($order) {
            // Turn the order string into an array and remove all values
            if (!$order || !is_array($order)) {
                $order = array();
            }
            $order = array_flip($order);
            $order = array_map(
                function () {
                },
                $order
            );

            // Move the matching elements to their position in $order
            foreach ($images as $k => $v) {
                if (array_key_exists($v['uuid'], $order)) {
                    $order[$v['uuid']] = $v;
                    unset($images[$k]);
                }
            }

            $order = array_merge($order, array_values($images));

            // Remove empty (unreplaced) entries
            $images = array_filter($order);
            unset($order);
        }

        $images = array_values($images);
        $slides = [];

        foreach ($images as $key => $image) {

            $slide = [];

            $image['size'] = isset($config['imgSize']) ? $config['imgSize'] : $config['size'];
            $slide['image'] = $helper->prepareImageForTemplate($image['uuid'], $image);
            $slide['title'] = $slide['image']->imageTitle;

            if ($config['rsts_navType'] === 'thumbs') {
                $image['size'] = $config['rsts_thumbs_imgSize'];
                $slide['thumb'] = $helper->prepareImageForTemplate($image['uuid'], $image);
            }

            $slides[] = $slide;

        }

        return $slides;
    }
}
