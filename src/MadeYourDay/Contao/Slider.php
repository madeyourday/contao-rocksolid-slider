<?php
/*
 * Copyright MADE/YOUR/DAY OG <mail@madeyourday.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace MadeYourDay\Contao;

use MadeYourDay\Contao\Module\SliderEvents;

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
		if (trim($row['videoURL']) || trim($row['singleSRC']) || $row['videos']) {
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
	 * Page picker wizard for url fields
	 *
	 * @param  \DataContainer $dc Data container
	 * @return string             Page picker button html code
	 */
	public function pagePickerWizard($dc) {
		return ' <a'
			. ' href="contao/page.php'
				. '?do=' . \Input::get('do')
				. '&amp;table=' . $dc->table
				. '&amp;field=' . $dc->field
				. '&amp;value=' . str_replace(array('{{link_url::', '}}'), '', $dc->value)
			. '"'
			. ' title="' . specialchars($GLOBALS['TL_LANG']['MSC']['pagepicker']) . '"'
			. ' onclick="'
				. 'Backend.getScrollOffset();'
				. 'Backend.openModalSelector({'
					. '\'width\':765,'
					. '\'title\':' . specialchars(json_encode($GLOBALS['TL_LANG']['MOD']['page'][0])) . ','
					. '\'url\':this.href,'
					. '\'id\':\'' . $dc->field . '\','
					. '\'tag\':\'ctrl_'. $dc->field . ((\Input::get('act') == 'editAll') ? '_' . $dc->id : '') . '\','
					. '\'self\':this'
				. '});'
				. 'return false;'
			. '">'
			. \Image::getHtml(
				'pickpage.gif',
				$GLOBALS['TL_LANG']['MSC']['pagepicker'],
				'style="vertical-align:top;cursor:pointer"'
			)
			. '</a>';
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
	 * Remove pro fields from DCA
	 *
	 * @param  string $table
	 * @param  array  $fields
	 * @param  array  $legends
	 * @return void
	 */
	protected function removeProFields($table, $fields = array(), $legends = array())
	{
		\System::loadLanguageFile('tl_rocksolid_slider');

		foreach ($fields as $field) {
			$GLOBALS['TL_DCA'][$table]['fields'][$field]['eval']['disabled'] = true;
			$GLOBALS['TL_DCA'][$table]['fields'][$field]['eval']['mandatory'] = false;
			if ($GLOBALS['TL_DCA'][$table]['fields'][$field]['inputType'] === 'fileTree') {
				// fileTree can’t be disabled
				$GLOBALS['TL_DCA'][$table]['fields'][$field]['eval']['extensions'] = 'none';
				$GLOBALS['TL_DCA'][$table]['fields'][$field]['eval']['path'] = 'none';
			}
			$GLOBALS['TL_DCA'][$table]['fields'][$field]['label'] = array(
				$GLOBALS['TL_DCA'][$table]['fields'][$field]['label'][0],
				sprintf($GLOBALS['TL_LANG']['tl_rocksolid_slider']['proFieldDescription'], 'contao/main.php?do=rocksolid_slider&amp;table=tl_rocksolid_slider_license&amp;ref=' . TL_REFERER_ID) . '<br>' . $GLOBALS['TL_DCA'][$table]['fields'][$field]['label'][1],
			);
		}

		foreach ($GLOBALS['TL_DCA'][$table]['palettes'] as $key => $palette) {
			foreach ($legends as $legend) {
				$GLOBALS['TL_DCA'][$table]['palettes'][$key] = preg_replace('(\\{' . $legend . '\\}[^;]*(;|$))', '{' . $legend . '},rsts_getPro$1', $palette);
			}
			$GLOBALS['TL_DCA'][$table]['fields']['rsts_getPro'] = array(
				'input_field_callback' => function() {
					return '<div class="tl_message">'
						. sprintf($GLOBALS['TL_LANG']['tl_rocksolid_slider']['proLegendDescription'], 'contao/main.php?do=rocksolid_slider&amp;table=tl_rocksolid_slider_license&amp;ref=' . TL_REFERER_ID)
						. '</div>';
				},
			);
		}
	}

	/**
	 * On load callback for tl_rocksolid_slide
	 *
	 * @param \DataContainer $dc
	 * @return void
	 */
	public function slideOnloadCallback($dc)
	{
		if (!static::checkLicense()) {
			$this->removeProFields($dc->table, array('videos', 'centerContent', 'autoplay'), array('background_legend'));
		}
	}

	/**
	 * On load callback for tl_content
	 *
	 * @param \DataContainer $dc
	 * @return void
	 */
	public function contentOnloadCallback($dc)
	{
		if (!static::checkLicense()) {
			$this->removeProFields($dc->table, array('rsts_content_type', 'rsts_direction', 'rsts_centerContent'), array('rsts_carousel_legend'));
		}

		if (!$dc->id) {
			return;
		}

		$contentElement = \ContentModel::findByPk($dc->id);

		if (!$contentElement || !isset($contentElement->type)) {
			return;
		}

		if ($contentElement->type === 'rocksolid_slider') {
			$GLOBALS['TL_DCA'][$dc->table]['fields']['multiSRC']['eval']['isGallery'] = true;
			$GLOBALS['TL_DCA'][$dc->table]['fields']['multiSRC']['eval']['filesOnly'] = true;
			$GLOBALS['TL_DCA'][$dc->table]['fields']['multiSRC']['eval']['extensions'] = $GLOBALS['TL_CONFIG']['validImageTypes'];
		}
	}

	/**
	 * On load callback for tl_module
	 *
	 * @param \DataContainer $dc
	 * @return void
	 */
	public function moduleOnloadCallback($dc)
	{
		if (!static::checkLicense()) {
			$this->removeProFields($dc->table, array('rsts_content_type', 'rsts_direction', 'rsts_centerContent'), array('rsts_carousel_legend'));
		}

		if (!$dc->id) {
			return;
		}

		$module = \ModuleModel::findByPk($dc->id);

		if (!$module || !isset($module->type)) {
			return;
		}

		if ($module->type === 'rocksolid_slider') {
			$GLOBALS['TL_DCA'][$dc->table]['fields']['multiSRC']['eval']['isGallery'] = true;
			$GLOBALS['TL_DCA'][$dc->table]['fields']['multiSRC']['eval']['filesOnly'] = true;
			$GLOBALS['TL_DCA'][$dc->table]['fields']['multiSRC']['eval']['extensions'] = $GLOBALS['TL_CONFIG']['validImageTypes'];
		}
	}

	/**
	 * parseFrontendTemplate hook for SliderEvents::getEventItems()
	 *
	 * @param  string $contents HTML output
	 * @param  string $template Temmplate name
	 * @return string           Modified HTML output
	 */
	public function parseEventsTemplateHook($contents, $template)
	{
		// Only modify output of event templates
		if (substr($template, 0, 6) === 'event_') {
			$contents = SliderEvents::TEMPLATE_SEPARATOR . $contents;
		}

		return $contents;
	}

	/**
	 * Check if the license key is valid
	 *
	 * @param  string         $value
	 * @param  \DataContainer $dc
	 * @return string         value
	 */
	public function licenseSaveCallback($value, $dc)
	{
		if ($value !== '' && !static::checkLicense($value)) {
			throw new \Exception($GLOBALS['TL_LANG']['tl_rocksolid_slider_license']['invalidLicense']);
		}

		return $value;
	}

	/**
	 * Checksums of valid license keys
	 */
	static private $validLicenseChecksums = array(
		'85380d820cc50b8542cec3e51d4eac9f', '4732b9a9dd3aae401c98f63294d170a1', '22d538b507a6a0bc095e1a811f4e6f7d', '60f659e658b30dd06902953309f0fb34', '0176edfaa880a282190fd3e165317d10', 'f3d8f0ebfab6534a5a27a62ef95a6529', 'ad82206facc36748c8bc8d91c0586ca3', '8384d476c3a9d6382543d304cfd6c21d', 'f8e8f2da06997ef4c9377563789cc35c', '8ef64e085fa33b4d854b2946def8c869', '5174f9cbb4bac399c12d40660a5e3113', '41b73c9596d5eac961f2d2e96645e004', '1e51b48b566c82563da966f2ee6a7dbc', '822d2eb467c9729ed9c0541fdeae590e', '454e2484c52f396499670928667f43b2', 'e71564d3ebff2ec8636085c379b7e29d', '25bbedcf242e3a0380f59880b6d46d81', '2c1eb7b8a750988d2ec66668ec26d253', '4f2b3524fc8ec25a883cc6329081dccf', 'b9e92376c5e0fae6d335bc2ac699c5e9', 'fe7f4692ee9a6a17b902d2afef1fc384', 'cc067cd4ccd6d4b5af5a665ab59bfd43', '4797c57225b8a98ef2b8c8c97ba6e76f', 'd5ed13cf6bcca1577a7f67207b02cd97', '0b045681ff3eca3a592a7d77961c2fe8', 'dfe6840b260c9b9316febfa7c406da65', '0bf69b1dc131a8bbba1bf5807fb9cfa2', '63d833a0af1ef63d8888125a7639433e', '466331268d08c68e14f3616f8566d099', '6775eb75f3d4b3e6b2450ed15f13aa32', '5657190a80d001785e89a0ca0a40a27c', 'e77567456d818ee70771a7df84612aee', 'ce935bcbf538c212179f53bac0ab507b', '0cd0860142ebc0053337baea065bfb22', '5276e0da003cd2dfed0466088c6538fc', 'c0d482aa84f849dd4d28e0520033319b', 'ba6da4b992141b83865c34fbf6214c68', '482f1d5372ee8567029fea14a09496f7', 'af56c615f0ebc15bd32053a34783f1c9', 'cc1c33a8d5bfbd86f889e0ca3c26de6b', 'e2d95da64963873b96465bcf89f620b2', 'd2319086afa76cc40e79dd4f78bf7178', 'ce5c1dbf30c9e04de201cd5ac8ffd5f3', '2d9d84c1fd29688b898569107bf96472', '2122d3eafd546a065208d9aaa22dc4e0', '239081bf138b3266f20adf9e7b290dec', 'c8e627a9d37345cf069b7a7932c5e806', '66602424a1de4f52949fab44a995d56a', '8c2efdf01db429ebaa9556151099ba33', '4c3e8df2beee8b9a141f582a3cea4dd1', '9fda8bb2036bfbac9b218dec79eeb541', '195cab27a2e0aaeade6fdb25213e06a8', 'c7a25f2a4db2090aba98820f959ab935', '5dbeead3204e47d03a7566ab778dfbf3', '84ec8527df930916bd33c8e11a277e5f', 'da4b029288c2ca045534be801201f69f', '2c77196a51f422fe5a08fbf7b8ec8785', '603a63463eb4b6ef49481dd649d68141', '02cf5192d6b1c89a7433fee4c3d7562d', '0fe2abdb479f42e79f66b3b2eb681cdf', 'a939ca53f988ed92aca3c9406b1e6bdf', '877ff77b1fbd3f88dd4c4544d28382cd', '60362edf52603ad5913c2f83d2dda53d', '846d3be076206ae4f7a9cb96c8473fbf', '95f63730fc688b71551cfe9971bdc60d',
	);

	/**
	 * Check if the license key is valid
	 *
	 * @param  string $license license key or null to get the it from the config
	 * @return bool            true if the license key is valid
	 */
	public static function checkLicense($license = null)
	{
		if ($license === null) {
			$license = \Config::get('rocksolid_slider_license');
		}

		if (!$license) {
			return false;
		}

		if (in_array(md5($license), static::$validLicenseChecksums, true)) {
			return true;
		}

		return false;
	}
}
