<?php
/*
 * Copyright MADE/YOUR/DAY OG <mail@madeyourday.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace MadeYourDay\RockSolidSlider\Module;

use MadeYourDay\RockSolidSlider\Model\SlideModel;
use MadeYourDay\RockSolidSlider\Model\SliderModel;
use MadeYourDay\RockSolidSlider\Model\ContentModel;

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

			if ($this->objModel->rsts_id && ($slider = SliderModel::findByPk($this->objModel->rsts_id)) !== null) {
				$template->id = $slider->id;
				$template->link = $slider->name;
				$template->href = 'contao/main.php?do=rocksolid_slider&amp;table=tl_rocksolid_slide&amp;id=' . $slider->id;
			}

			return $template->parse();
		}

		if (
			$this->rsts_import_settings
			&& $this->rsts_import_settings_from
			&& ($settingsModule = \ModuleModel::findByPk($this->rsts_import_settings_from))
		) {
			$this->objModel->imgSize = $settingsModule->imgSize;
			$this->objModel->fullsize = $settingsModule->fullsize;
		}

		if ($this->rsts_content_type === 'rsts_news') {
			$newsModule = new SliderNews($this->objModel, $this->strColumn);
			$this->newsArticles = $newsModule->getNewsArticles();
			if (!count($this->newsArticles)) {
				// Return if there are no news articles
				return '';
			}
		}
		else if ($this->rsts_content_type === 'rsts_events') {
			$eventsModule = new SliderEvents($this->objModel, $this->strColumn);
			$this->eventItems = $eventsModule->getEventItems();
			if (!count($this->eventItems)) {
				// Return if there are no events
				return '';
			}
		}
		else if ($this->rsts_content_type === 'rsts_settings') {
			return '';
		}
		else if ($this->rsts_content_type === 'rsts_images' || !$this->rsts_id) {

			$this->multiSRC = deserialize($this->multiSRC);
			if (!is_array($this->multiSRC) || !count($this->multiSRC)) {
				// Return if there are no images
				return '';
			}

		}
		else {

			$this->slider = SliderModel::findByPk($this->rsts_id);

			// Return if there is no slider
			if (! $this->slider || $this->slider->id !== $this->rsts_id) {
				return '';
			}

			if ($this->slider->type === 'image') {
				$this->multiSRC = deserialize($this->slider->multiSRC);
				$this->orderSRC = $this->slider->orderSRC;
			}

		}

		$this->files = \FilesModel::findMultipleByUuids($this->multiSRC);

		if (
			$this->rsts_import_settings
			&& $this->rsts_import_settings_from
			&& ($settingsModule = \ModuleModel::findByPk($this->rsts_import_settings_from))
		) {
			$exclude = array('rsts_import_settings', 'rsts_import_settings_from', 'rsts_content_type', 'rsts_id');
			$include = array('imgSize', 'fullsize');
			foreach ($settingsModule->row() as $key => $value) {
				if (
					(substr($key, 0, 5) === 'rsts_' || in_array($key, $include))
					&& !in_array($key, $exclude)
				) {
					$this->arrData[$key] = $value;
				}
			}
			$settingsCssId = \StringUtil::deserialize($settingsModule->cssID, true);
			if (!empty($settingsCssId[1])) {
				$this->arrData['cssID'][1] = (
					empty($this->arrData['cssID'][1]) ? '' : $this->arrData['cssID'][1] . ' '
				) . $settingsCssId[1];
			}
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
			$filesExpaned = array();

			// Get all images
			while ($files->next()) {
				if ($files->type === 'file') {
					$filesExpaned[] = $files->current();
				}
				else {
					$subFiles = \FilesModel::findByPid($files->uuid);
					while ($subFiles && $subFiles->next()) {
						if ($subFiles->type === 'file'){
							$filesExpaned[] = $subFiles->current();
						}
					}
				}
			}

			foreach ($filesExpaned as $files) {

				// Continue if the files has been processed or does not exist
				if (isset($images[$files->path]) || ! file_exists(TL_ROOT . '/' . $files->path)) {
					continue;
				}

				$file = new \File($files->path, true);

				if (!$file->isGdImage && !$file->isImage) {
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
					'alt'       => $arrMeta['alt'],
					'title'     => $arrMeta['title'],
					'imageUrl'  => $arrMeta['link'],
					'caption'   => $arrMeta['caption'],
				);

			}

			if ($this->orderSRC) {
				// Turn the order string into an array and remove all values
				$order = deserialize($this->orderSRC);
				if (!$order || !is_array($order)) {
					$order = array();
				}
				$order = array_flip($order);
				$order = array_map(function(){}, $order);

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

			foreach ($images as $key => $image) {
				$newImage = new \stdClass();
				$image['size'] = isset($this->imgSize) ? $this->imgSize : $this->size;
				$this->addImageToTemplate($newImage, $image, null, null, \FilesModel::findByPk($image['id']));
				if ($this->rsts_navType === 'thumbs') {
					$newImage->thumb = new \stdClass;
					$image['size'] = $this->rsts_thumbs_imgSize;
					$this->addImageToTemplate($newImage->thumb, $image);
				}
				$images[$key] = $newImage;
			}

		}

		// use custom skin if specified
		if (trim($this->arrData['rsts_customSkin'])) {
			$this->arrData['rsts_skin'] = trim($this->arrData['rsts_customSkin']);
		}

		$this->Template->images = $images;
		$slides = array();
		if (isset($this->newsArticles)) {
			foreach ($this->newsArticles as $newsArticle) {
				$slides[] = array(
					'text' => $newsArticle,
				);
			}
		}
		else if (isset($this->eventItems)) {
			foreach ($this->eventItems as $eventItem) {
				$slides[] = array(
					'text' => $eventItem,
				);
			}
		}
		else if (isset($this->slider->id) && $this->slider->type === 'content') {
			$slides = $this->parseSlides(SlideModel::findPublishedByPid($this->slider->id));
		}

		$this->Template->slides = $slides;

		$options = array();

		// strings
		foreach (array(
			'type',
			'direction',
			'cssPrefix',
			'skin',
			'width',
			'height',
			'navType',
			'scaleMode',
			'imagePosition',
			'deepLinkPrefix',
			'thumbs_width',
			'thumbs_height',
			'thumbs_scaleMode',
			'thumbs_imagePosition',
		) as $key) {
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
		foreach (array(
			'random',
			'loop',
			'videoAutoplay',
			'autoplayProgress',
			'pauseAutoplayOnHover',
			'keyboard',
			'captions',
			'controls',
			'thumbControls',
			'combineNavItems',
			'thumbs_controls',
		) as $key) {
			$options[$key] = (bool) $this->arrData['rsts_' . $key];
		}

		// positive numbers
		foreach (array(
			'preloadSlides',
			'duration',
			'autoplay',
			'autoplayRestart',
			'slideMaxCount',
			'slideMinSize',
			'slideMaxSize',
			'rowMaxCount',
			'rowMinSize',
			'prevNextSteps',
			'visibleAreaMax',
			'thumbs_duration',
			'thumbs_slideMaxCount',
			'thumbs_slideMinSize',
			'thumbs_slideMaxSize',
			'thumbs_rowMaxCount',
			'thumbs_rowMinSize',
			'thumbs_prevNextSteps',
			'thumbs_visibleAreaMax',
		) as $key) {
			if (! empty($this->arrData['rsts_' . $key]) && $this->arrData['rsts_' . $key] > 0) {
				$options[$key] = $this->arrData['rsts_' . $key] * 1;
			}
		}

		// percentages
		foreach (array('visibleArea', 'thumbs_visibleArea') as $key) {
			if (!empty($this->arrData['rsts_' . $key])) {
				$options[$key] = $this->arrData['rsts_' . $key] / 100;
			}
		}

		// percentages including zero
		foreach (array('visibleAreaAlign') as $key) {
			if (!empty($this->arrData['rsts_' . $key])) {
				$options[$key] = $this->arrData['rsts_' . $key] / 100;
			}
			else {
				$options[$key] = 0;
			}
		}

		// ratios
		foreach (array('rowSlideRatio', 'thumbs_rowSlideRatio') as $key) {
			if (!empty($this->arrData['rsts_' . $key])) {
				$ratio = explode('x', $this->arrData['rsts_' . $key], 2);
				if (empty($ratio[1])) {
					$ratio = floatval($ratio[0]);
				}
				else {
					$ratio = floatval($ratio[1]) / floatval($ratio[0]);
				}
				$options[$key] = $ratio;
			}
		}

		// gap size
		foreach (array('gapSize', 'thumbs_gapSize') as $key) {
			if (isset($this->arrData['rsts_' . $key]) && $this->arrData['rsts_' . $key] !== '') {
				if (substr($this->arrData['rsts_' . $key], -1) === '%') {
					$options[$key] = $this->arrData['rsts_' . $key];
				}
				else {
					$options[$key] = $this->arrData['rsts_' . $key] * 1;
				}
			}
		}

		foreach ($options as $key => $value) {
			if (substr($key, 0, 7) === 'thumbs_') {
				$options['thumbs'][substr($key, 7)] = $value;
				unset($options[$key]);
			}
		}

		if (empty($this->arrData['rsts_thumbs']) && isset($options['thumbs'])) {
			unset($options['thumbs']);
		}

		$this->Template->options = $options;

		$assetsDir = 'bundles/rocksolidslider';

		$GLOBALS['TL_JAVASCRIPT'][] = $assetsDir . '/js/rocksolid-slider.min.js|static';
		$GLOBALS['TL_CSS'][] = $assetsDir . '/css/rocksolid-slider.min.css||static';
		$skinPath = $assetsDir . '/css/' . (empty($this->arrData['rsts_skin']) ? 'default' : $this->arrData['rsts_skin']) . '-skin.min.css';
		if (file_exists(\System::getContainer()->getParameter('contao.web_dir') . '/' . $skinPath)) {
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
				$meta = $this->getMetaData($file->meta, $objPage->language);
				$slide['image'] = new \stdClass;
				$this->addImageToTemplate($slide['image'], array(
					'id' => $file->id,
					'name' => $fileObject->basename,
					'singleSRC' => $file->path,
					'alt' => $meta['alt'],
					'title' => $meta['title'],
					'imageUrl' => $meta['link'],
					'caption' => $meta['caption'],
					'size' => isset($this->imgSize) ? $this->imgSize : $this->size,
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
				$slide['image']->title = '';
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
				$meta = $this->getMetaData($file->meta, $objPage->language);
				$slide['backgroundImage'] = new \stdClass;
				$this->addImageToTemplate($slide['backgroundImage'], array(
					'id' => $file->id,
					'name' => $fileObject->basename,
					'singleSRC' => $file->path,
					'alt' => $meta['alt'],
					'title' => $meta['title'],
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

			if ($this->rsts_navType === 'thumbs') {
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
						'size' => $this->rsts_thumbs_imgSize,
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
						'size' => $this->rsts_thumbs_imgSize,
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
					$slides[$idIndexes[(int)$slideContents->pid]]['text'] .= $this->getContentElement($slideContents->current());
				}
			}
		}

		return $slides;
	}
}
