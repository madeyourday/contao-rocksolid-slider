<?php
/*
 * Copyright MADE/YOUR/DAY OG <mail@madeyourday.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * RockSolid Slider license DCA
 *
 * @author Martin Auswöger <martin@madeyourday.net>
 */
$GLOBALS['TL_DCA']['tl_rocksolid_slider_license'] = array(

	'config' => array(
		'dataContainer' => 'File',
		'closed' => true,
	),

	'palettes' => array(
		'default' => '{license_legend},rocksolid_slider_license',
	),

	'fields' => array(
		'rocksolid_slider_license' => array(
			'label' => &$GLOBALS['TL_LANG']['tl_rocksolid_slider_license']['rocksolid_slider_license'],
			'inputType' => 'text',
			'eval' => array(
				'tl_class' => 'w50',
			),
			'save_callback' => array(
				array('MadeYourDay\\Contao\\Slider', 'licenseSaveCallback'),
			),
		),
	),

);
