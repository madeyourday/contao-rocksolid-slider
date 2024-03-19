<?php
/*
 * Copyright MADE/YOUR/DAY OG <mail@madeyourday.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace MadeYourDay\RockSolidSlider;

use Contao\Backend;
use Contao\BackendUser;
use Contao\Config;
use Contao\ContentModel;
use Contao\CoreBundle\Exception\AccessDeniedException;
use Contao\DataContainer;
use Contao\Environment;
use Contao\Image;
use Contao\Input;
use Contao\ModuleModel;
use Contao\StringUtil;
use Contao\System;
use Contao\ThemeModel;
use MadeYourDay\RockSolidSlider\Module\SliderEvents;

/**
 * RockSolid Slider DCA
 *
 * Provide miscellaneous methods that are used by the data configuration arrays.
 *
 * @author Martin Auswöger <martin@madeyourday.net>
 */
class Slider extends Backend
{
	/**
	 * Return the "toggle visibility" button
	 *
	 * @return string
	 */
	public function toggleSlideIcon($row, $href, $label, $title, $icon, $attributes)
	{
		$href .= '&amp;id=' . $row['id'];

		if (! $row['published']) {
			$icon = 'invisible.gif';
		}

		return '<a href="' . $this->addToUrl($href) . '" title="' . StringUtil::specialchars($title) . '" onclick="Backend.getScrollOffset();return AjaxRequest.toggleField(this,true)">' . Image::getHtml($icon, $label, 'data-icon="' . Image::getPath('visible.svg') . '" data-icon-disabled="' . Image::getPath('invisible.svg') . '" data-state="' . ($row['published'] ? 1 : 0) . '"') . '</a> ';
	}

	public function sliderLicenseButton($href, $label, $title, $class, $attributes)
	{
		if (!($user = BackendUser::getInstance()) || !$user->isAdmin) {
			return '';
		}

		return '<a href="' . $this->addToUrl($href) . '" class="' . $class . '" title="' . StringUtil::specialchars($title) . '"' . $attributes . '>' . $label . '</a> ';
	}

	/**
	 * Return the "edit slider" button
	 */
	public function editSliderIcon($row, $href, $label, $title, $icon, $attributes)
	{
		if ($row['type'] !== 'content') {
			return '';
		}
		$href .= '&amp;id=' . $row['id'];
		return '<a href="' . $this->addToUrl($href) . '" title="' . StringUtil::specialchars($title) . '"' . $attributes . '>' . Image::getHtml($icon, $label) . '</a> ';
	}

	/**
	 * Return the "copy slider" button
	 */
	public function copySliderIcon($row, $href, $label, $title, $icon, $attributes)
	{
		$href .= '&amp;id=' . $row['id'];
		if (!($user = BackendUser::getInstance()) || !(static::canSkipPermissionCheck($user) || $user->hasAccess('create', 'rsts_permissions'))) {
			return Image::getHtml(preg_replace('/\.svg$/i', '_.svg', $icon), $label) . ' ';
		}

		return '<a href="' . $this->addToUrl($href) . '" title="' . StringUtil::specialchars($title) . '"' . $attributes . '>' . Image::getHtml($icon, $label) . '</a> ';
	}

	/**
	 * Return the "delete slider" button
	 */
	public function deleteSliderIcon($row, $href, $label, $title, $icon, $attributes)
	{
		$href .= '&amp;id=' . $row['id'];
		if (!($user = BackendUser::getInstance()) || !(static::canSkipPermissionCheck($user) || $user->hasAccess('delete', 'rsts_permissions'))) {
			return Image::getHtml(preg_replace('/\.svg$/i', '_.svg', $icon), $label) . ' ';
		}

		return '<a href="' . $this->addToUrl($href) . '" title="' . StringUtil::specialchars($title) . '"' . $attributes . '>' . Image::getHtml($icon, $label) . '</a> ';
	}

	/**
	 * Return the "edit slide" button
	 */
	public function editSlideIcon($row, $href, $label, $title, $icon, $attributes)
	{
		if ($row['type'] !== 'content') {
			return '';
		}
		$href .= '&amp;id=' . $row['id'];
		return '<a href="' . $this->addToUrl($href) . '" title="' . StringUtil::specialchars($title) . '"' . $attributes . '>' . Image::getHtml($icon, $label) . '</a> ';
	}

	/**
	 * DCA Header callback
	 *
	 * Redirects to the parent slider if type is not "content"
	 *
	 * @param  array          $headerFields label value pairs of header fields
	 * @param  DataContainer  $dc           data container
	 * @return array
	 */
	public function headerCallback($headerFields, $dc)
	{
		$sliderData = $this->Database
			->prepare('SELECT * FROM ' . $GLOBALS['TL_DCA'][$dc->table]['config']['ptable'] . ' WHERE id = ?')
			->limit(1)
			->execute($dc->currentPid);

		if ($sliderData->numRows && $sliderData->type !== 'content') {
			$this->redirect(System::getContainer()->get('router')->generate('contao_backend', ['do' => 'rocksolid_slider', 'act' => 'edit', 'id' => $dc->currentPid, 'ref' => Input::get('ref'), 'rt' => System::getContainer()->get('contao.csrf.token_manager')->getDefaultTokenValue()]));
		}

		return $headerFields;
	}

	/**
	 * DCA Header callback
	 *
	 * Redirects to the parent slide if type is not "content"
	 *
	 * @param  array          $headerFields label value pairs of header fields
	 * @param  DataContainer  $dc           data container
	 * @return array
	 */
	public function headerCallbackContent($headerFields, $dc)
	{
		$slideData = $this->Database
			->prepare('SELECT * FROM ' . $GLOBALS['TL_DCA'][$dc->table]['config']['ptable'] . ' WHERE id = ?')
			->limit(1)
			->execute($dc->currentPid);

		if ($slideData->numRows && $slideData->type !== 'content') {
			$this->redirect(System::getContainer()->get('router')->generate('contao_backend', ['do' => 'rocksolid_slider', 'table' => 'tl_rocksolid_slide', 'act' => 'edit', 'id' => $dc->currentPid, 'ref' => Input::get('ref'), 'rt' => System::getContainer()->get('contao.csrf.token_manager')->getDefaultTokenValue()]));
		}

		return $headerFields;
	}

	/**
	 * Check access to a particular content element
	 *
	 * @param integer $id
	 * @param array   $root
	 * @param boolean $blnIsPid
	 *
	 * @throws AccessDeniedException
	 */
	protected function checkAccessToContentElement($id, $root, $blnIsPid=false)
	{
		if ($blnIsPid) {
			$objArchive = $this->Database->prepare("SELECT a.id, n.id AS nid FROM tl_rocksolid_slide n, tl_rocksolid_slider a WHERE n.id=? AND n.pid=a.id")
				->limit(1)
				->execute($id);
		} else {
			$objArchive = $this->Database->prepare("SELECT a.id, n.id AS nid FROM tl_content c, tl_rocksolid_slide n, tl_rocksolid_slider a WHERE c.id=? AND c.pid=n.id AND n.pid=a.id")
				->limit(1)
				->execute($id);
		}

		// Invalid ID
		if ($objArchive->numRows < 1) {
			throw new AccessDeniedException('Invalid slider content element ID ' . $id . '.');
		}

		if (!in_array($objArchive->id, $root)) {
			throw new AccessDeniedException('Not enough permissions to modify slide ID ' . $objArchive->nid . ' in slider ID ' . $objArchive->id . '.');
		}
	}

	/**
	 * Add the type of input field
	 *
	 * @return string
	 */
	public function listSlides($arrRow)
	{
		return '<div class="tl_content_left">' . $arrRow['title'] . ' <span style="color:#999;padding-left:3px">' . $GLOBALS['TL_LANG']['tl_rocksolid_slide']['types'][$arrRow['type']] . '</span></div>';
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

		if (($user = BackendUser::getInstance()) && !static::canSkipPermissionCheck($user)) {
			$userSliders = StringUtil::deserialize($user->rsts_sliders, true);
			$arrSliders = array_intersect_key($arrSliders, array_combine($userSliders, $userSliders));
		}

		return $arrSliders;
	}

	/**
	 * Get all slider modules and return them as array
	 *
	 * @return array
	 */
	public function getSliderModuleIds()
	{
		$arrModules = array();
		$objModules = $this->Database->execute("SELECT id, pid, name FROM tl_module WHERE type = 'rocksolid_slider' ORDER BY name");

		while ($objModules->next()) {
			$objTheme = ThemeModel::findById($objModules->pid);
			$arrModules[$objTheme->name][$objModules->id] = $objModules->name;
		}

		if (count($arrModules) === 1) {
			$arrModules = array_values($arrModules)[0];
		}

		return $arrModules;
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
		System::loadLanguageFile('tl_rocksolid_slider');

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
				sprintf(
					$GLOBALS['TL_LANG']['tl_rocksolid_slider']['proFieldDescription'],
					StringUtil::specialcharsUrl(System::getContainer()->get('router')->generate('contao_backend', ['do' => 'rocksolid_slider', 'table' => 'tl_rocksolid_slider_license', 'ref' => System::getContainer()->get('request_stack')->getCurrentRequest()->get('_contao_referer_id')]))
				) . '<br>' . $GLOBALS['TL_DCA'][$table]['fields'][$field]['label'][1],
			);
		}

		foreach ($GLOBALS['TL_DCA'][$table]['palettes'] as $key => $palette) {
			foreach ($legends as $legend) {
				$GLOBALS['TL_DCA'][$table]['palettes'][$key] = preg_replace('(\\{' . $legend . '\\}[^;]*(;|$))', '{' . $legend . '},rsts_getPro$1', $palette);
			}
			$GLOBALS['TL_DCA'][$table]['fields']['rsts_getPro'] = array(
				'input_field_callback' => function() {
					return '<div class="tl_message" style="padding: 15px">'
						. sprintf(
							$GLOBALS['TL_LANG']['tl_rocksolid_slider']['proLegendDescription'],
							StringUtil::specialcharsUrl(System::getContainer()->get('router')->generate('contao_backend', ['do' => 'rocksolid_slider', 'table' => 'tl_rocksolid_slider_license', 'ref' => System::getContainer()->get('request_stack')->getCurrentRequest()->get('_contao_referer_id')]))
						)
						. '</div>';
				},
			);
		}
	}

	/**
	 * On load callback for tl_rocksolid_slider
	 *
	 * @param DataContainer $dc
	 * @return void
	 */
	public function onloadCallback($dc)
	{
		$user = BackendUser::getInstance();

		if (static::canSkipPermissionCheck($user)) {
			return;
		}

		// Set root IDs
		if (empty($user->rsts_sliders) || !is_array($user->rsts_sliders)) {
			$root = array(0);
		} else {
			$root = $user->rsts_sliders;
		}

		$GLOBALS['TL_DCA']['tl_rocksolid_slider']['list']['sorting']['root'] = $root;

		// Check permissions to add archives
		if (!$user->hasAccess('create', 'rsts_permissions')) {
			$GLOBALS['TL_DCA']['tl_rocksolid_slider']['config']['closed'] = true;
			$GLOBALS['TL_DCA']['tl_rocksolid_slider']['config']['notCreatable'] = true;
			$GLOBALS['TL_DCA']['tl_rocksolid_slider']['config']['notCopyable'] = true;
		}

		// Check permissions to delete calendars
		if (!$user->hasAccess('delete', 'rsts_permissions')) {
			$GLOBALS['TL_DCA']['tl_rocksolid_slider']['config']['notDeletable'] = true;
		}

		/** @var SessionInterface $objSession */
		$objSession = System::getContainer()->get('request_stack')->getSession();

		// Check current action
		switch (Input::get('act')) {
			case 'select':
				// Allow
				break;

			case 'create':
				if (!$user->hasAccess('create', 'rsts_permissions')) {
					throw new AccessDeniedException('Not enough permissions to create sliders.');
				}
				break;

			case 'edit':
			case 'copy':
			case 'delete':
			case 'show':
				if (!in_array(Input::get('id'), $root) || (Input::get('act') == 'delete' && !$user->hasAccess('delete', 'rsts_permissions'))) {
					throw new AccessDeniedException('Not enough permissions to ' . Input::get('act') . ' slider ID ' . Input::get('id') . '.');
				}
				break;

			case 'editAll':
			case 'deleteAll':
			case 'overrideAll':
			case 'copyAll':
				$session = $objSession->all();

				if (Input::get('act') == 'deleteAll' && !$user->hasAccess('delete', 'rsts_permissions')) {
					$session['CURRENT']['IDS'] = array();
				} else {
					$session['CURRENT']['IDS'] = array_intersect((array) $session['CURRENT']['IDS'], $root);
				}
				$objSession->replace($session);
				break;

			default:
				if (Input::get('act')) {
					throw new AccessDeniedException('Not enough permissions to ' . Input::get('act') . ' sliders.');
				}
				break;
		}
	}

	/**
	 * On create callback for tl_rocksolid_slider
	 *
	 * @param DataContainer $dc
	 * @return void
	 */
	public function oncreateCallback($table, $insertId, $row, $dc)
	{
		$user = BackendUser::getInstance();

		if (static::canSkipPermissionCheck($user)) {
			return;
		}

		// Set root IDs
		if (empty($user->rsts_sliders) || !is_array($user->rsts_sliders)) {
			$root = array(0);
		} else {
			$root = $user->rsts_sliders;
		}

		// The archive is enabled already
		if (in_array($insertId, $root)) {
			return;
		}

		/** @var AttributeBagInterface $objSessionBag */
		$objSessionBag = System::getContainer()->get('request_stack')->getSession()->getBag('contao_backend');

		$arrNew = $objSessionBag->get('new_records');

		if (is_array($arrNew['tl_rocksolid_slider']) && in_array($insertId, $arrNew['tl_rocksolid_slider'])) {
			// Add the permissions on group level
			if ($user->inherit != 'custom') {
				$objGroup = $this->Database->execute("SELECT id, rsts_sliders, rsts_permissions FROM tl_user_group WHERE id IN(" . implode(',', array_map('\intval', $user->groups)) . ")");

				while ($objGroup->next()) {
					$arrPermissions = StringUtil::deserialize($objGroup->rsts_permissions);

					if (is_array($arrPermissions) && in_array('create', $arrPermissions)) {
						$arrSliders = StringUtil::deserialize($objGroup->rsts_sliders, true);
						$arrSliders[] = $insertId;

						$this->Database->prepare("UPDATE tl_user_group SET rsts_sliders=? WHERE id=?")
							->execute(serialize($arrSliders), $objGroup->id);
					}
				}
			}

			// Add the permissions on user level
			if ($user->inherit != 'group') {
				$objUser = $this->Database->prepare("SELECT rsts_sliders, rsts_permissions FROM tl_user WHERE id=?")
					->limit(1)
					->execute($user->id);

				$arrPermissions = StringUtil::deserialize($objUser->rsts_permissions);

				if (is_array($arrPermissions) && in_array('create', $arrPermissions)) {
					$arrSliders = StringUtil::deserialize($objUser->rsts_sliders, true);
					$arrSliders[] = $insertId;

					$this->Database->prepare("UPDATE tl_user SET rsts_sliders=? WHERE id=?")
						->execute(serialize($arrSliders), $user->id);
				}
			}

			// Add the new element to the user object
			$root[] = $insertId;
			$user->rsts_sliders = $root;
		}
	}

	/**
	 * On copy callback for tl_rocksolid_slider
	 *
	 * @param DataContainer $dc
	 * @return void
	 */
	public function oncopyCallback($insertId, $dc)
	{
		return $this->oncreateCallback($dc->table, $insertId, [], $dc);
	}

	/**
	 * On load callback for tl_rocksolid_slider_license
	 *
	 * @param DataContainer $dc
	 * @return void
	 */
	public function licenseOnloadCallback($dc)
	{
		$user = BackendUser::getInstance();

		if ($user->isAdmin) {
			return;
		}

		throw new AccessDeniedException('Not enough permissions to access slider license key settings.');
	}

	/**
	 * On load callback for tl_rocksolid_slide
	 *
	 * @param DataContainer $dc
	 * @return void
	 */
	public function slideOnloadCallback($dc)
	{
		if (!static::checkLicense()) {
			$this->removeProFields($dc->table, array('videos', 'centerContent', 'autoplay'), array('background_legend'));
		}

		$user = BackendUser::getInstance();

		if (static::canSkipPermissionCheck($user)) {
			return;
		}

		// Set the root IDs
		if (empty($user->rsts_sliders) || !is_array($user->rsts_sliders)) {
			$root = array(0);
		} else {
			$root = $user->rsts_sliders;
		}

		$id = strlen(Input::get('id')) ? Input::get('id') : $dc->currentPid;

		// Check current action
		switch (Input::get('act')) {
			case 'paste':
			case 'select':
			case 'create':
				if (!in_array($dc->currentPid, $root)) {
					throw new AccessDeniedException('Not enough permissions to access slider ID ' . $id . '.');
				}
				break;

			case 'cut':
			case 'copy':
				if (Input::get('mode') == 1) {
					$objArchive = $this->Database->prepare("SELECT pid FROM tl_rocksolid_slide WHERE id=?")
						->limit(1)
						->execute(Input::get('pid'));

					if ($objArchive->numRows < 1)
					{
						throw new AccessDeniedException('Invalid slide ID ' . Input::get('pid') . '.');
					}

					$pid = $objArchive->pid;
				} else {
					$pid = Input::get('pid');
				}

				if (!in_array($pid, $root)) {
					throw new AccessDeniedException('Not enough permissions to ' . Input::get('act') . ' slide ID ' . $id . ' to slider ID ' . $pid . '.');
				}
			// no break

			case 'edit':
			case 'show':
			case 'delete':
			case 'toggle':
			case 'feature':
				$objArchive = $this->Database->prepare("SELECT pid FROM tl_rocksolid_slide WHERE id=?")
					->limit(1)
					->execute($id);

				if ($objArchive->numRows < 1) {
					throw new AccessDeniedException('Invalid slide ID ' . $id . '.');
				}

				if (!in_array($objArchive->pid, $root)) {
					throw new AccessDeniedException('Not enough permissions to ' . Input::get('act') . ' slide ID ' . $id . ' of slider ID ' . $objArchive->pid . '.');
				}
				break;

			case 'editAll':
			case 'deleteAll':
			case 'overrideAll':
			case 'cutAll':
			case 'copyAll':
				if (!in_array($id, $root)) {
					throw new AccessDeniedException('Not enough permissions to access slider ID ' . $id . '.');
				}

				$objArchive = $this->Database->prepare("SELECT id FROM tl_rocksolid_slide WHERE pid=?")
					->execute($id);

				/** @var SessionInterface $objSession */
				$objSession = System::getContainer()->get('request_stack')->getSession();

				$session = $objSession->all();
				$session['CURRENT']['IDS'] = array_intersect((array) $session['CURRENT']['IDS'], $objArchive->fetchEach('id'));
				$objSession->replace($session);
				break;

			default:
				if (Input::get('act')) {
					throw new AccessDeniedException('Invalid command "' . Input::get('act') . '".');
				}

				if (!in_array($id, $root)) {
					throw new AccessDeniedException('Not enough permissions to access slider ID ' . $id . '.');
				}
				break;
		}
	}

	/**
	 * On load callback for tl_content
	 *
	 * @param DataContainer $dc
	 * @return void
	 */
	public function contentOnloadCallback($dc)
	{
		if (!static::checkLicense()) {
			$this->removeProFields($dc->table, array('rsts_content_type', 'rsts_direction', 'rsts_centerContent'), array('rsts_carousel_legend'));
		}

		$this->contentCheckPermission($dc);

		if (!$dc->id) {
			return;
		}

		$contentElement = ContentModel::findByPk($dc->id);

		if (!$contentElement || !isset($contentElement->type)) {
			return;
		}

		if ($contentElement->type === 'rocksolid_slider') {
			$GLOBALS['TL_DCA'][$dc->table]['fields']['multiSRC']['eval']['isGallery'] = true;
			$GLOBALS['TL_DCA'][$dc->table]['fields']['multiSRC']['eval']['extensions'] = implode(',', System::getContainer()->getParameter('contao.image.valid_extensions'));
			$GLOBALS['TL_DCA'][$dc->table]['fields']['multiSRC']['eval']['isSortable'] = true;
			unset($GLOBALS['TL_DCA'][$dc->table]['fields']['multiSRC']['eval']['orderField']);
		}
	}

	/**
	 * @param DataContainer $dc
	 */
	private function contentCheckPermission($dc)
	{
		if (Input::get('do') !== 'rocksolid_slider') {
			return;
		}

		$user = BackendUser::getInstance();

		if (static::canSkipPermissionCheck($user)) {
			return;
		}

		// Set the root IDs
		if (empty($user->rsts_sliders) || !is_array($user->rsts_sliders)) {
			$root = array(0);
		} else {
			$root = $user->rsts_sliders;
		}

		// Check the current action
		switch (Input::get('act')) {
			case '': // empty
			case 'paste':
			case 'create':
			case 'select':
				$this->checkAccessToContentElement($dc->currentPid, $root, true);
				break;

			case 'editAll':
			case 'deleteAll':
			case 'overrideAll':
			case 'cutAll':
			case 'copyAll':
				// Check access to the parent element if a content element is moved
				if (in_array(Input::get('act'), array('cutAll', 'copyAll'))) {
					$this->checkAccessToContentElement(Input::get('pid'), $root, (Input::get('mode') == 2));
				}

				$objCes = $this->Database->prepare("SELECT id FROM tl_content WHERE ptable='tl_rocksolid_slide' AND pid=?")
					->execute($dc->currentPid);

				/** @var SessionInterface $objSession */
				$objSession = System::getContainer()->get('request_stack')->getSession();

				$session = $objSession->all();
				$session['CURRENT']['IDS'] = array_intersect((array) $session['CURRENT']['IDS'], $objCes->fetchEach('id'));
				$objSession->replace($session);
				break;

			case 'cut':
			case 'copy':
				// Check access to the parent element if a content element is moved
				$this->checkAccessToContentElement(Input::get('pid'), $root, (Input::get('mode') == 2));
			// no break

			default:
				// Check access to the content element
				$this->checkAccessToContentElement(Input::get('id'), $root);
				break;
		}
	}

	/**
	 * On load callback for tl_module
	 *
	 * @param DataContainer $dc
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

		$module = ModuleModel::findByPk($dc->id);

		if (!$module || !isset($module->type)) {
			return;
		}

		if ($module->type === 'rocksolid_slider') {
			$GLOBALS['TL_DCA'][$dc->table]['fields']['multiSRC']['eval']['isGallery'] = true;
			$GLOBALS['TL_DCA'][$dc->table]['fields']['multiSRC']['eval']['extensions'] = implode(',', System::getContainer()->getParameter('contao.image.valid_extensions'));
		}
	}

	/**
	 * On load callback for tl_user
	 *
	 * @param DataContainer $dc
	 * @return void
	 */
	public function userOnloadCallback($dc)
	{
		if (!static::checkLicense()) {
			$this->removeProFields($dc->table, array(), array('rsts_slider_legend'));
		}
	}

	/**
	 * On load callback for tl_user_group
	 *
	 * @param DataContainer $dc
	 * @return void
	 */
	public function userGroupOnloadCallback($dc)
	{
		if (!static::checkLicense()) {
			$this->removeProFields($dc->table, array(), array('rsts_slider_legend'));
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
	 * @param  DataContainer  $dc
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
			$license = Config::get('rocksolid_slider_license');
		}

		if (!$license) {
			return false;
		}

		if (in_array(md5($license), static::$validLicenseChecksums, true)) {
			return true;
		}

		return false;
	}

	public static function canSkipPermissionCheck(BackendUser $user): bool
	{
		if ($user->isAdmin) {
			return true;
		}

		// Active license means permissions feature is enabled
		if (static::checkLicense()) {
			return false;
		}

		// Still check permissions if set previously with an active license
		if (!empty($user->rsts_permissions) || !empty($user->rsts_sliders)) {
			return false;
		}

		return true;
	}
}
