<?php
/*
 * Copyright MADE/YOUR/DAY OG <mail@madeyourday.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace MadeYourDay\Contao\Module;

use MadeYourDay\Contao\Model\SlideModel;
use MadeYourDay\Contao\Model\SliderModel;
use MadeYourDay\Contao\Model\ContentModel;

/**
 * Slider Frontend Module
 *
 * @author Martin Auswöger <martin@madeyourday.net>
 */
class Slider extends \Module
{
	/**
	 * @var string Template
	 */
	protected $strTemplate = 'rsts_default';

	/**
	 * @return string
	 */
	public function generate()
	{
		// Display a wildcard in the back end
		if (TL_MODE === 'BE') {
			$template = new \BackendTemplate('be_wildcard');

			$template->wildcard = '### ROCKSOLID SLIDER ###';
			$template->title = $this->name;
			$template->id = $this->id;
			$template->link = $this->name;
			$template->href = 'contao/main.php?do=themes&amp;table=tl_module&amp;act=edit&amp;id=' . $this->id;

			return $template->parse();
		}

		// Return if there is no slider id
		if (! $this->rsts_id) {
			return '';
		}

		$this->slider = SliderModel::findByPk($this->rsts_id);

		// Return if there is no slider
		if (! $this->slider || $this->slider->id !== $this->rsts_id) {
			return '';
		}

		$this->multiSRC = deserialize($this->slider->multiSRC);
		if (version_compare(VERSION, '3.2', '<')) {
			$this->files = \FilesModel::findMultipleByIds($this->multiSRC);
		}
		else {
			$this->files = \FilesModel::findMultipleByUuids($this->multiSRC);
		}

		if ($this->rsts_template) {
			$this->strTemplate = $this->rsts_template;
		}

		return parent::generate();
	}

	/**
	 * Generate the module
	 */
	protected function compile()
	{
		global $objPage;

		$images = array();

		if ($this->files) {

			$files = $this->files;

			// Get all images
			while ($files->next()) {

				// Continue if the files has been processed or does not exist
				if (isset($images[$files->path]) || ! file_exists(TL_ROOT . '/' . $files->path)) {
					continue;
				}

				$file = new \File($files->path, true);

				if (!$file->isGdImage) {
					continue;
				}

				$arrMeta = $this->getMetaData($files->meta, $objPage->language);

				// Add the image
				$images[$files->path] = array
				(
					'id'        => $files->id,
					'uuid'      => isset($files->uuid) ? $files->uuid : null,
					'name'      => $file->basename,
					'singleSRC' => $files->path,
					'alt'       => $arrMeta['title'],
					'imageUrl'  => $arrMeta['link'],
					'caption'   => $arrMeta['caption'],
				);

			}

			if ($this->slider->orderSRC) {
				// Turn the order string into an array and remove all values
				if (version_compare(VERSION, '3.2', '<')) {
					$order = explode(',', $this->slider->orderSRC);
					$order = array_map('intval', $order);
				}
				else {
					$order = deserialize($this->slider->orderSRC);
				}
				if (!$order || !is_array($order)) {
					$order = array();
				}
				$order = array_flip($order);
				$order = array_map(function(){}, $order);

				// Move the matching elements to their position in $order
				$idKey = version_compare(VERSION, '3.2', '<') ? 'id' : 'uuid';
				foreach ($images as $k => $v) {
					if (array_key_exists($v[$idKey], $order)) {
						$order[$v[$idKey]] = $v;
						unset($images[$k]);
					}
				}

				$order = array_merge($order, array_values($images));

				// Remove empty (unreplaced) entries
				$images = array_filter($order);
				unset($order);
			}

			$images = array_values($images);

			foreach ($images as $key => $image) {
				$newImage = new \stdClass();
				$image['size'] = isset($this->imgSize) ? $this->imgSize : $this->size;
				$this->addImageToTemplate($newImage, $image);
				$images[$key] = $newImage;
			}

		}

		// use custom skin if specified
		if (trim($this->arrData['rsts_customSkin'])) {
			$this->arrData['rsts_skin'] = trim($this->arrData['rsts_customSkin']);
		}

		$this->Template->images = $images;
		$this->Template->slides = $this->parseSlides(SlideModel::findPublishedByPid($this->rsts_id));

		$options = array();

		// strings
		foreach (array('type', 'cssPrefix', 'skin', 'width', 'height', 'navType', 'scaleMode', 'imagePosition', 'deepLinkPrefix') as $key) {
			if (! empty($this->arrData['rsts_' . $key])) {
				$options[$key] = $this->arrData['rsts_' . $key];
			}
		}

		// strings / boolean
		foreach (array('centerContent') as $key) {
			if (! empty($this->arrData['rsts_' . $key])) {
				$options[$key] = $this->arrData['rsts_' . $key];
				if ($options[$key] === 'false') {
					$options[$key] = false;
				}
				if ($options[$key] === 'true') {
					$options[$key] = true;
				}
			}
		}

		// boolean
		foreach (array('random', 'loop', 'videoAutoplay', 'autoplayProgress', 'pauseAutoplayOnHover', 'keyboard', 'captions', 'controls') as $key) {
			$options[$key] = (bool) $this->arrData['rsts_' . $key];
		}

		// positive numbers
		foreach (array('preloadSlides', 'duration', 'autoplay', 'autoplayRestart') as $key) {
			if (! empty($this->arrData['rsts_' . $key]) && $this->arrData['rsts_' . $key] > 0) {
				$options[$key] = $this->arrData['rsts_' . $key] * 1;
			}
		}

		// gap size
		if (isset($this->arrData['rsts_gapSize']) && $this->arrData['rsts_gapSize'] !== '') {
			if (substr($this->arrData['rsts_gapSize'], -1) === '%') {
				$options['gapSize'] = $this->arrData['rsts_gapSize'];
			}
			else {
				$options['gapSize'] = $this->arrData['rsts_gapSize'] * 1;
			}
		}

		$this->Template->options = $options;

		$GLOBALS['TL_JAVASCRIPT'][] = 'system/modules/rocksolid-slider/assets/js/rocksolid-slider.min.js|static';
		$GLOBALS['TL_CSS'][] = 'system/modules/rocksolid-slider/assets/css/rocksolid-slider.min.css||static';
		$skinPath = 'system/modules/rocksolid-slider/assets/css/' . (empty($this->arrData['rsts_skin']) ? 'default' : $this->arrData['rsts_skin']) . '-skin.min.css';
		if (file_exists(TL_ROOT . '/' . $skinPath)) {
			$GLOBALS['TL_CSS'][] = $skinPath . '||static';
		}
	}

	/**
	 * Parse slides
	 *
	 * @param  \Model\Collection $objSlides slides retrieved from the database
	 * @return array                        parsed slides
	 */
	protected function parseSlides($objSlides)
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
			$pids[] = $slide['id'];
			$idIndexes[(int)$slide['id']] = count($slides);

			if (
				trim($slide['singleSRC']) &&
				($file = version_compare(VERSION, '3.2', '<')
					? \FilesModel::findByPk($slide['singleSRC'])
					: \FilesModel::findByUuid($slide['singleSRC'])
				) &&
				($fileObject = new \File($file->path, true)) &&
				$fileObject->isGdImage
			) {
				$meta = $this->getMetaData($file->meta, $objPage->language);
				$slide['image'] = new \stdClass;
				$this->addImageToTemplate($slide['image'], array(
					'id' => $file->id,
					'name' => $fileObject->basename,
					'singleSRC' => $file->path,
					'alt' => $meta['title'],
					'imageUrl' => $meta['link'],
					'caption' => $meta['caption'],
					'size' => isset($this->imgSize) ? $this->imgSize : $this->size,
				));
			}

			if ($slide['videoURL'] && empty($slide['image'])) {
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
					$slide['image'] = new \stdClass;
					$slide['image']->src = '//img.youtube.com/vi/' . $video . '/0.jpg';
					$slide['image']->imgSize = '';
					$slide['image']->alt = '';
				}
				else {
					$slide['image'] = new \stdClass;
					// Grey dummy image
					$slide['image']->src = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAJCAMAAAAM9FwAAAAAA1BMVEXGxsbd/8BlAAAAFUlEQVR42s3BAQEAAACAkP6vdiO6AgCZAAG/wrlvAAAAAElFTkSuQmCC';
					$slide['image']->imgSize = '';
					$slide['image']->alt = '';
				}
			}

			if (
				trim($slide['backgroundImage']) &&
				($file = version_compare(VERSION, '3.2', '<')
					? \FilesModel::findByPk($slide['backgroundImage'])
					: \FilesModel::findByUuid($slide['backgroundImage'])
				) &&
				($fileObject = new \File($file->path, true)) &&
				$fileObject->isGdImage
			) {
				$meta = $this->getMetaData($file->meta, $objPage->language);
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
				if (version_compare(VERSION, '3.2', '<')) {
					$videoFiles = \FilesModel::findMultipleByIds($videoFiles);
				}
				else {
					$videoFiles = \FilesModel::findMultipleByUuids($videoFiles);
				}
				$videos = array();
				foreach ($videoFiles as $file) {
					$videos[] = $file;
				}
				$slide['backgroundVideos'] = $videos;
			}

			$slides[] = $slide;

		}

		$slideContents = ContentModel::findPublishedByPidsAndTable($pids, SlideModel::getTable());

		if ($slideContents) {
			while ($slideContents->next()) {
				$slides[$idIndexes[(int)$slideContents->pid]]['text'] .= $this->getContentElement($slideContents->current());
			}
		}

		return $slides;
	}
}
