<?php
/*
 * Copyright MADE/YOUR/DAY OG <mail@madeyourday.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace MadeYourDay\Contao;

/**
 * RockSolid Slider DCA
 *
 * Provide miscellaneous methods that are used by the data configuration arrays.
 *
 * @author Martin Auswöger <martin@madeyourday.net>
 */
class Slider extends \Backend
{
	/**
	 * Return the "toggle visibility" button
	 *
	 * @return string
	 */
	public function toggleSlideIcon($row, $href, $label, $title, $icon, $attributes)
	{
		if (strlen(\Input::get('tid'))) {
			$this->toggleVisibility(\Input::get('tid'), (\Input::get('state') == 1));
			if (\Environment::get('isAjaxRequest')) {
				exit;
			}
			$this->redirect($this->getReferer());
		}

		$href .= '&amp;id=' . \Input::get('id') . '&amp;tid=' . $row['id'] . '&amp;state=' . ($row['published'] ? '' : 1);

		if (! $row['published']) {
			$icon = 'invisible.gif';
		}

		return '<a href="' . $this->addToUrl($href) . '" title="' . specialchars($title) . '"' . $attributes . '>' . $this->generateImage($icon, $label) . '</a> ';
	}

	/**
	 * Disable/enable a slide
	 */
	public function toggleVisibility($intId, $blnVisible)
	{
		$this->createInitialVersion('tl_rocksolid_slide', $intId);

		// Trigger the save_callback
		if (is_array($GLOBALS['TL_DCA']['tl_rocksolid_slide']['fields']['published']['save_callback'])) {
			foreach ($GLOBALS['TL_DCA']['tl_rocksolid_slide']['fields']['published']['save_callback'] as $callback) {
				$this->import($callback[0]);
				$blnVisible = $this->$callback[0]->$callback[1]($blnVisible, $this);
			}
		}

		$this->Database
			->prepare("UPDATE tl_rocksolid_slide SET tstamp=". time() .", published='" . ($blnVisible ? 1 : '') . "' WHERE id=?")
			->execute($intId);

		$this->createNewVersion('tl_rocksolid_slide', $intId);
	}

	/**
	 * Return the "edit slide" button
	 */
	public function editSlideIcon($row, $href, $label, $title, $icon, $attributes)
	{
		if (trim($row['videoURL']) || trim($row['singleSRC'])) {
			return '';
		}
		$href .= '&amp;id=' . $row['id'];
		return '<a href="' . $this->addToUrl($href) . '" title="' . specialchars($title) . '"' . $attributes . '>' . $this->generateImage($icon, $label) . '</a> ';
	}

	/**
	 * DCA Header callback
	 *
	 * Adds selected images to the header or redirects to the parent slider if
	 * no slides are found
	 *
	 * @param  array          $headerFields label value pairs of header fields
	 * @param  \DataContainer $dc           data container
	 * @return array
	 */
	public function headerCallback($headerFields, $dc)
	{
		$sliderData = $this->Database
			->prepare('SELECT * FROM ' . $GLOBALS['TL_DCA'][$dc->table]['config']['ptable'] . ' WHERE id = ?')
			->limit(1)
			->execute(CURRENT_ID);

		if ($sliderData->numRows < 1) {
			return $headerFields;
		}

		$files = deserialize($sliderData->multiSRC);
		if (is_array($files) && count($files)) {

			$slidesCount = $this->Database
				->prepare('SELECT count(*) as count FROM ' . $dc->table . ' WHERE pid = ?')
				->execute(CURRENT_ID);

			if (!$slidesCount->count) {
				$this->redirect('contao/main.php?do=rocksolid_slider&act=edit&id=' . CURRENT_ID . '&ref=' . \Input::get('ref') . '&rt=' . REQUEST_TOKEN);
			}

			$headerFields[$GLOBALS['TL_LANG']['tl_rocksolid_slide']['headerImagesSelected'][0]] = $GLOBALS['TL_LANG']['tl_rocksolid_slide']['headerImagesSelected'][1];

			$images = array();
			if (version_compare(VERSION, '3.2', '<')) {
				$files = \FilesModel::findMultipleByIds($files);
			}
			else {
				$files = \FilesModel::findMultipleByUuids($files);
			}

			while ($files->next()) {

				// Continue if the files has been processed or does not exist
				if (isset($images[$files->path]) || ! file_exists(TL_ROOT . '/' . $files->path)) {
					continue;
				}

				$file = new \File($files->path, true);

				if (!$file->isGdImage) {
					continue;
				}

				// Add the image
				$images[$files->path] = array(
					'id'=> $files->id,
					'uuid' => isset($files->uuid) ? $files->uuid : null,
					'name' => $file->basename,
					'path' => $files->path,
				);

			}

			if ($sliderData->orderSRC) {

				// Turn the order string into an array and remove all values
				if (version_compare(VERSION, '3.2', '<')) {
					$order = explode(',', $sliderData->orderSRC);
					$order = array_map('intval', $order);
				}
				else {
					$order = deserialize($sliderData->orderSRC);
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
			$imagesHtml = '';

			foreach ($images as $image) {
				$imagesHtml .= ' ' . $this->generateImage(\Image::get($image['path'], 60, 45, 'center_center'), '', 'class="gimage"');
			}

			$headerFields[$GLOBALS['TL_LANG']['tl_rocksolid_slide']['headerImages']] = '<div style="margin-top: 12px; margin-right: -40px;">'
				. $imagesHtml
				. '</div>';

		}

		return $headerFields;
	}

	/**
	 * Add the type of input field
	 *
	 * @return string
	 */
	public function listSlides($arrRow)
	{
		return '<div class="tl_content_left">' . $arrRow['title'] . '</div>';
	}

	/**
	 * Get all sliders and return them as array
	 *
	 * @return array
	 */
	public function getSliderIds()
	{
		$arrSliders = array();
		$objSliders = $this->Database->execute("SELECT id, name FROM tl_rocksolid_slider ORDER BY name");

		while ($objSliders->next()) {
			$arrSliders[$objSliders->id] = $objSliders->name;
		}

		return $arrSliders;
	}

	/**
	 * Return all slider templates as array
	 *
	 * @return array
	 */
	public function getSliderTemplates()
	{
		return $this->getTemplateGroup('rsts_');
	}

	/**
	 * On load callback for tl_content
	 *
	 * @param \DataContainer $dc
	 * @return void
	 */
	public function contentOnloadCallback($dc)
	{
		if (!$dc->id) {
			return;
		}

		$contentElement = \ContentModel::findByPk($dc->id);

		if (!$contentElement || !isset($contentElement->type)) {
			return;
		}

		if ($contentElement->type === 'rocksolid_slider') {
			$GLOBALS['TL_DCA'][$dc->table]['fields']['multiSRC']['eval']['mandatory'] = false;
			$GLOBALS['TL_DCA'][$dc->table]['fields']['multiSRC']['eval']['isGallery'] = true;
			$GLOBALS['TL_DCA'][$dc->table]['fields']['multiSRC']['eval']['filesOnly'] = true;
		}
	}
}
