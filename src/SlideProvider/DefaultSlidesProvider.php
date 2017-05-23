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

        // FIXME: image handling needs to be passed to image provider.
        if ($slider->type === 'image') {
            $this->multiSRC = deserialize($slider->multiSRC);
            $this->orderSRC = $slider->orderSRC;
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
        global $objPage;

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

            if (
                in_array($slide['type'], array('image', 'video')) &&
                trim($slide['singleSRC']) &&
                ($file = \FilesModel::findByUuid($slide['singleSRC'])) &&
                ($fileObject = new \File($file->path, true)) &&
                ($fileObject->isGdImage || $fileObject->isImage)
            ) {
                $meta = $this->frontendAdapter->getMetaData($file->meta, $objPage->language);
                $slide['image'] = new \stdClass;
                $this->addImageToTemplate($slide['image'], array(
                    'id' => $file->id,
                    'name' => $fileObject->basename,
                    'singleSRC' => $file->path,
                    'alt' => $meta['title'],
                    'imageUrl' => $meta['link'],
                    'caption' => $meta['caption'],
                    'size' => isset($config['imgSize']) ? $config['imgSize'] : $config['size'],
                ));
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

            if (
                trim($slide['backgroundImage']) &&
                ($file = \FilesModel::findByUuid($slide['backgroundImage'])) &&
                ($fileObject = new \File($file->path, true)) &&
                ($fileObject->isGdImage || $fileObject->isImage)
            ) {
                $meta = $this->frontendAdapter->getMetaData($file->meta, $objPage->language);
                $slide['backgroundImage'] = new \stdClass;
                $this->addImageToTemplate($slide['backgroundImage'], array(
                    'id' => $file->id,
                    'name' => $fileObject->basename,
                    'singleSRC' => $file->path,
                    'alt' => $meta['title'],
                    'imageUrl' => $meta['link'],
                    'caption' => $meta['caption'],
                    'size' => $slide['backgroundImageSize'],
                ));
            }
            else {
                $slide['backgroundImage'] = null;
            }

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
                $slide['thumb'] = new \stdClass;
                if (
                    trim($slide['thumbImage']) &&
                    ($file = \FilesModel::findByUuid($slide['thumbImage'])) &&
                    ($fileObject = new \File($file->path, true)) &&
                    ($fileObject->isGdImage || $fileObject->isImage)
                ) {
                    $this->addImageToTemplate($slide['thumb'], array(
                        'id' => $file->id,
                        'name' => $fileObject->basename,
                        'singleSRC' => $file->path,
                        'size' => $config['rsts_thumbs_imgSize'],
                    ));
                }
                elseif (
                    in_array($slide['type'], array('image', 'video')) &&
                    trim($slide['singleSRC']) &&
                    ($file = \FilesModel::findByUuid($slide['singleSRC'])) &&
                    ($fileObject = new \File($file->path, true)) &&
                    ($fileObject->isGdImage || $fileObject->isImage)
                ) {
                    $this->addImageToTemplate($slide['thumb'], array(
                        'id' => $file->id,
                        'name' => $fileObject->basename,
                        'singleSRC' => $file->path,
                        'size' => $config['rsts_thumbs_imgSize'],
                    ));
                }
                elseif (!empty($slide['image']->src)) {
                    $slide['thumb'] = clone $slide['image'];
                }
                elseif (!empty($slide['backgroundImage']->src)) {
                    $slide['thumb'] = clone $slide['backgroundImage'];
                }
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
     * @param object $template The template object to add the image to.
     * @param array  $data     The element or module as array.
     *
     * @return void
     */
    private function addImageToTemplate($template, $data)
    {
        $this->frontendAdapter->addImageToTemplate($template, $data);
    }
}
