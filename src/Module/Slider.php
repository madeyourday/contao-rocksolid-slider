<?php
/*
 * Copyright MADE/YOUR/DAY OG <mail@madeyourday.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace MadeYourDay\RockSolidSlider\Module;

use MadeYourDay\RockSolidSlider\Model\SliderModel;

/**
 * Slider Frontend Module
 *
 * @author Martin Auswöger <martin@madeyourday.net>
 *
 * @property string rsts_content_type The slider content type.
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

		$registry = \System::getContainer()->get('madeyourday.rocksolid_slider.slideproviders');
		/** @var \MadeYourDay\RockSolidSlider\SlideProvider\SlideProviderRegistry $registry */
		if ($registry->hasProvider($this->rsts_content_type)) {
			$this->slides = $registry
				->getProvider($this->rsts_content_type)
				->getSlides(array_merge(['slider-column' => $this->strColumn], $this->objModel->row()));
			if (empty($this->slides)) {
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
					'alt'       => $arrMeta['title'],
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
				$this->addImageToTemplate($newImage, $image);
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
		$this->Template->slides = $this->slides;

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

		$assetsDir = 'web/bundles/rocksolidslider';

		$GLOBALS['TL_JAVASCRIPT'][] = $assetsDir . '/js/rocksolid-slider.min.js|static';
		$GLOBALS['TL_CSS'][] = $assetsDir . '/css/rocksolid-slider.min.css||static';
		$skinPath = $assetsDir . '/css/' . (empty($this->arrData['rsts_skin']) ? 'default' : $this->arrData['rsts_skin']) . '-skin.min.css';
		if (file_exists(TL_ROOT . '/' . $skinPath)) {
			$GLOBALS['TL_CSS'][] = $skinPath . '||static';
		}
	}
}
