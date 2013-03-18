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
			$this->redirect($this->getReferer());
		}

		$href .= '&amp;tid=' . $row['id'] . '&amp;state=' . ($row['published'] ? '' : 1);

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
		if (! empty($row['videoURL']) || ! empty($row['singleSRC'])) {
			return '';
		}
		$href .= '&amp;id=' . $row['id'];
		return '<a href="' . $this->addToUrl($href) . '" title="' . specialchars($title) . '"' . $attributes . '>' . $this->generateImage($icon, $label) . '</a> ';
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
}
