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
		$this->files = \FilesModel::findMultipleByIds($this->multiSRC);

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
					'name'      => $file->basename,
					'singleSRC' => $files->path,
					'alt'       => $arrMeta['title'],
					'imageUrl'  => $arrMeta['link'],
					'caption'   => $arrMeta['caption'],
				);

			}

			if ($this->slider->orderSRC) {
				// Turn the order string into an array and remove all values
				$order = explode(',', $this->slider->orderSRC);
				$order = array_flip(array_map('intval', $order));
				$order = array_map(function(){}, $order);

				// Move the matching elements to their position in $order
				foreach ($images as $k => $v) {
					if (array_key_exists($v['id'], $order)) {
						$order[$v['id']] = $v;
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
				$image['size'] = $this->imgSize;
				$this->addImageToTemplate($newImage, $image);
				$images[$key] = $newImage;
			}

		}

		$this->Template->images = $images;
		$this->Template->slides = $this->parseSlides(SlideModel::findPublishedByPid($this->rsts_id));
		$this->Template->options = $this->arrData;
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
				$slide['singleSRC'] &&
				($file = \FilesModel::findByPk($slide['singleSRC'])) &&
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
					'size' => $this->imgSize,
				));
			}

			if ($slide['videoURL'] && empty($slide['image'])) {
				if (substr(html_entity_decode($slide['videoURL']), 0, 31) === 'http://www.youtube.com/watch?v=') {
					$video = substr(html_entity_decode($slide['videoURL']), 31, 11);
					$slide['image'] = new \stdClass;
					$slide['image']->src = 'http://img.youtube.com/vi/' . $video . '/0.jpg';
					$slide['image']->imgSize = '';
					$slide['image']->alt = '';
				}
			}

			$slides[] = $slide;

		}

		$slideContents = ContentModel::findPublishedByPidsAndTable($pids, SlideModel::getTable());

		if ($slideContents) {
			while ($slideContents->next()) {
				$slides[$idIndexes[(int)$slideContents->pid]]['text'] .= $this->getContentElement($slideContents);
			}
		}

		return $slides;
	}
}
