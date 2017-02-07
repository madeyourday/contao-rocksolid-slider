<?php
/*
 * Copyright MADE/YOUR/DAY OG <mail@madeyourday.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace MadeYourDay\RockSolidSlider;

/**
 * RockSolid Slider Runonce
 *
 * @author Martin Auswöger <martin@madeyourday.net>
 */
abstract class SliderRunonce
{
	/**
	 * Run database migrations
	 *
	 * @return void
	 */
	public static function run()
	{
		$database = \Database::getInstance();

		// Copy license key from extension repository
		if (
			!\Config::get('rocksolid_slider_license')
			&& $database->tableExists('tl_repository_installs')
			&& $database->fieldExists('lickey', 'tl_repository_installs')
			&& $database->fieldExists('extension', 'tl_repository_installs')
		) {
			$result = $database->prepare('SELECT lickey FROM tl_repository_installs WHERE extension = \'rocksolid-slider-pro\'')->execute();
			if (
				$result
				&& $result->lickey
				&& class_exists('MadeYourDay\\RockSolidSlider\\Slider')
				&& Slider::checkLicense((string)$result->lickey)
			) {
				\Config::getInstance()->add(
					'$GLOBALS[\'TL_CONFIG\'][\'rocksolid_slider_license\']',
					(string)$result->lickey
				);
			}
		}

		// Fix the singleSRC data type for Contao 3.1 and below
		if (version_compare(VERSION, '3.2', '<') && $database->tableExists('tl_rocksolid_slide')) {
			$fields = $database->listFields('tl_rocksolid_slide');
			foreach ($fields as $field) {
				if ($field['name'] === 'singleSRC' && $field['type'] !== 'varchar') {
					$database->query('ALTER TABLE tl_rocksolid_slide CHANGE singleSRC singleSRC varchar(255) NOT NULL default \'\'');
					$database->query('UPDATE tl_rocksolid_slide SET singleSRC = trim(trailing CHAR(0x00) from singleSRC)');
				}
			}
		}

		// Update the multiSRC and orderSRC field from IDs to UUIDs
		if (version_compare(VERSION, '3.2', '>=') && $database->tableExists('tl_rocksolid_slider')) {

			$needUpdate = true;
			$result = $database->prepare('SELECT multiSRC FROM tl_rocksolid_slider WHERE multiSRC != \'\'')->execute();

			if (!$result->count()) {
				$needUpdate = false;
			}

			while ($result->next()) {
				foreach (deserialize($result->multiSRC, true) as $value) {
					if (\Validator::isUuid($value)) {
						$needUpdate = false;
						break 2;
					}
				}
			}

			if ($needUpdate) {
				\Database\Updater::convertMultiField('tl_rocksolid_slider', 'multiSRC');
				\Database\Updater::convertOrderField('tl_rocksolid_slider', 'orderSRC');
			}

		}

		// Update the singleSRC field from IDs to UUIDs
		if (
			version_compare(VERSION, '3.2', '>=') &&
			$database->tableExists('tl_rocksolid_slide')
		) {
			$fields = $database->listFields('tl_rocksolid_slide');
			foreach ($fields as $field) {
				if ($field['name'] === 'singleSRC' && $field['type'] !== 'binary') {
					\Database\Updater::convertSingleField('tl_rocksolid_slide', 'singleSRC');
				}
			}
		}

		// Initialize the slider type field
		if (
			$database->tableExists('tl_rocksolid_slider')
			&& $database->tableExists('tl_rocksolid_slide')
			&& $database->fieldExists('pid', 'tl_rocksolid_slide')
		) {
			if (!$database->fieldExists('type', 'tl_rocksolid_slider')) {
				$database->query('ALTER TABLE tl_rocksolid_slider ADD type varchar(255) NOT NULL default \'\'');
			}
			if ($database->prepare('SELECT id FROM tl_rocksolid_slider WHERE type = \'\'')->execute()->count()) {
				$database->query("UPDATE tl_rocksolid_slider
					SET type = CASE
						WHEN EXISTS (SELECT id FROM tl_rocksolid_slide
							WHERE tl_rocksolid_slide.pid = tl_rocksolid_slider.id
						)
							THEN 'content'
						ELSE 'image'
					END
					WHERE type = ''
				");
			}
		}

		// Initialize the slide type field
		if (
			$database->tableExists('tl_rocksolid_slide')
			&& $database->tableExists('tl_content')
			&& $database->fieldExists('pid', 'tl_content')
			&& $database->fieldExists('ptable', 'tl_content')
		) {
			if (!$database->fieldExists('type', 'tl_rocksolid_slide')) {
				$database->query('ALTER TABLE tl_rocksolid_slide ADD type varchar(255) NOT NULL default \'\'');
			}
			if ($database->prepare('SELECT id FROM tl_rocksolid_slide WHERE type = \'\'')->execute()->count()) {
				$database->query("UPDATE tl_rocksolid_slide
					SET type = CASE
						WHEN EXISTS (SELECT id FROM tl_content
							WHERE tl_content.ptable = 'tl_rocksolid_slide'
							AND tl_content.pid = tl_rocksolid_slide.id
						)
							THEN 'content'
						WHEN videoURL != '' OR videos IS NOT NULL
							THEN 'video'
						ELSE 'image'
					END
					WHERE type = ''
				");
			}
		}
	}
}
