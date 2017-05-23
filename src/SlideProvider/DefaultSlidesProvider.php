<?php

/*
 * Copyright MADE/YOUR/DAY OG <mail@madeyourday.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace MadeYourDay\RockSolidSlider\SlideProvider;

use Contao\CoreBundle\Framework\Adapter;
use Contao\Frontend;
use Contao\StringUtil;
use MadeYourDay\RockSolidSlider\Model\ContentModel;
use MadeYourDay\RockSolidSlider\Model\SlideModel;
use MadeYourDay\RockSolidSlider\Model\SliderModel;
use MadeYourDay\RockSolidSlider\SliderContent;

/**
 * Provides slides from the embedded slide table.
 *
 * @author Christian Schiffler <c.schiffler@cyberspectrum.de>
 */
class DefaultSlidesProvider implements SlideProviderInterface
{
    /**
     * @var Adapter|SliderModel
     */
    private $sliderModelAdapter;

    /**
     * @var Adapter|SlideModel
     */
    private $slideModelAdapter;

    /**
     * @var Adapter|Frontend
     */
    private $frontendAdapter;

    /**
     * Create a new instance.
     *
     * @param Adapter $sliderModelAdapter
     * @param Adapter $slideModelAdapter
     * @param Adapter $frontendAdapter
     */
    public function __construct(Adapter $sliderModelAdapter, Adapter $slideModelAdapter, Adapter $frontendAdapter)
    {
        $this->sliderModelAdapter = $sliderModelAdapter;
        $this->slideModelAdapter  = $slideModelAdapter;
        $this->frontendAdapter    = $frontendAdapter;
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return 'rsts_default';
    }

    /**
     * {@inheritDoc}
     */
    public function process(array $config, SliderContent $content)
    {
        $slider = $this->sliderModelAdapter->findByPk($config['rsts_id']);

        // Return if there is no slider
        if (!$slider || $slider->id !== $config['rsts_id']) {
            return;
        }

        if ($slider->type === 'image') {
            $content->addFiles(
                deserialize($slider->multiSRC),
                $slider->orderSRC ? StringUtil::deserialize($slider->multiSRC) : []
            );

            return;
        }

        if ($slider->type === 'content') {
            $content->addSlides($this->parseSlides(SlideModel::findPublishedByPid($slider->id), $config));
        }
    }

    /**
     * Parse slides
     *
     * @param  \Model\Collection $objSlides slides retrieved from the database
     * @return array                        parsed slides
     */
    private function parseSlides($objSlides, $config)
    {
        $slides = array();
        $pids = array();
        $idIndexes = array();

        if (! $objSlides) {
            return $slides;
        }

        while ($objSlides->next()) {

            $slide = $objSlides->row();
            $slide['text'] = '';
            if ($slide['type'] === 'content') {
                $pids[] = $slide['id'];
                $idIndexes[(int)$slide['id']] = count($slides);
            }

            if (in_array($slide['type'], array('image', 'video'))) {
                $slide['image'] = $this->tryPrepareImage(
                    $slide['singleSRC'],
                    ['size' => isset($config['imgSize']) ? $config['imgSize'] : $config['size']],
                    true
                );
            }

            if ($slide['type'] === 'video' && $slide['videoURL'] && empty($slide['image'])) {
                $slide['image'] = new \stdClass;
                if (preg_match(
                    '(^
                        https?://  # http or https
                        (?:
                            www\\.youtube\\.com/(?:watch\\?v=|v/|embed/)  # Different URL formats
                            | youtu\\.be/  # Short YouTube domain
                        )
                        ([0-9a-z_\\-]{11})  # YouTube ID
                        (?:$|&|/)  # End or separator
                    )ix',
                    html_entity_decode($slide['videoURL']), $matches)
                ) {
                    $video = $matches[1];
                    $slide['image']->src = '//img.youtube.com/vi/' . $video . '/0.jpg';
                }
                else {
                    // Grey dummy image
                    $slide['image']->src = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAJCAMAAAAM9FwAAAAAA1BMVEXGxsbd/8BlAAAAFUlEQVR42s3BAQEAAACAkP6vdiO6AgCZAAG/wrlvAAAAAElFTkSuQmCC';
                }
                $slide['image']->imgSize = '';
                $slide['image']->alt = '';
                $slide['image']->picture = array(
                    'img' => array('src' => $slide['image']->src, 'srcset' => $slide['image']->src),
                    'sources' => array(),
                );
            }

            if ($slide['type'] !== 'video' && $slide['videoURL']) {
                $slide['videoURL'] = '';
            }

            if ($slide['type'] === 'video' && $slide['videos']) {
                $videoFiles = deserialize($slide['videos'], true);
                $videoFiles = \FilesModel::findMultipleByUuids($videoFiles);
                $videos = array();
                foreach ($videoFiles as $file) {
                    $videos[] = $file;
                }
                $slide['videos'] = $videos;
            }
            else {
                $slide['videos'] = null;
            }

            $slide['backgroundImage'] = $this->tryPrepareImage(
                $slide['backgroundImage'],
                ['size' => $slide['backgroundImageSize']],
                true
            );

            if ($slide['backgroundVideos']) {
                $videoFiles = deserialize($slide['backgroundVideos'], true);
                $videoFiles = \FilesModel::findMultipleByUuids($videoFiles);
                $videos = array();
                foreach ($videoFiles as $file) {
                    $videos[] = $file;
                }
                $slide['backgroundVideos'] = $videos;
            }

            if ($config['rsts_navType'] === 'thumbs') {
                $slide['thumb'] = $this->generateThumb($slide, $config);
            }

            $slides[] = $slide;

        }

        if (count($pids)) {
            $slideContents = ContentModel::findPublishedByPidsAndTable($pids, SlideModel::getTable());
            if ($slideContents) {
                while ($slideContents->next()) {
                    $slides[$idIndexes[(int)$slideContents->pid]]['text'] .= $this->frontendAdapter->getContentElement($slideContents->current());
                }
            }
        }

        return $slides;
    }

    /**
     * Gateway to frontend adapter.
     *
     * @param array $data The image data as array.
     *
     * @return \stdClass
     */
    private function prepareImageForTemplate($data)
    {
        $image = new \stdClass;
        $this->frontendAdapter->addImageToTemplate($image, $data);

        return $image;
    }

    /**
     * Try to prepare the file with the passed uuid.
     *
     * @param string $uuid       The uuid of the file.
     * @param array  $attributes The attributes to pass to Frontend::addImageToTemplate().
     * @param bool $addMeta      If true, the Meta information attributes will also get added
     *                          'alt'      => meta['title']
     *                          'imageUrl' => meta['link'],
     *                          'caption'  => meta['caption']
     *
     * @return null|\stdClass
     */
    private function tryPrepareImage($uuid, $attributes, $addMeta = false)
    {
        if (!trim($uuid)) {
            return null;
        }
        if (null === ($file = \FilesModel::findByUuid($uuid))) {
            return null;
        }
        $fileObject = new \File($file->path, true);
        // FIXME: Why check for isGdImage here? if isImage == true it is always also isGdImage == true?
        if (!$fileObject->isGdImage && !$fileObject->isImage) {
            return null;
        }

        if ($addMeta) {
            global $objPage;
            $meta                   = $this->frontendAdapter->getMetaData($file->meta, $objPage->language);
            $attributes['alt']      = $meta['title'];
            $attributes['imageUrl'] = $meta['link'];
            $attributes['caption']  = $meta['caption'];
        }

        return $this->prepareImageForTemplate(array_merge([
            'id'        => $file->id,
            'uuid'      => isset($file->uuid) ? $file->uuid : null,
            'name'      => $fileObject->basename,
            'singleSRC' => $file->path,
        ], $attributes));
    }

    /**
     * Generate the thumbnail for a slide.
     *
     * @param array $slide  The slide.
     * @param array $config The configuration.
     *
     * @return \stdClass
     */
    private function generateThumb($slide, $config)
    {
        if ($thumb = $this->tryPrepareImage($slide['thumbImage'], ['size' => $config['rsts_thumbs_imgSize']])) {
            return $thumb;
        }
        if (in_array($slide['type'], ['image', 'video']) &&
            ($thumb = $this->tryPrepareImage($slide['singleSRC'], ['size' => $config['rsts_thumbs_imgSize']]))) {
            return $thumb;
        }
        if (!empty($slide['image']->src)) {
            return clone $slide['image'];
        }
        if (!empty($slide['backgroundImage']->src)) {
            return clone $slide['backgroundImage'];
        }
        return $thumb;
    }
}
