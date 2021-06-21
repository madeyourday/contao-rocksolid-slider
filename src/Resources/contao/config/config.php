<?php
/*
 * Copyright MADE/YOUR/DAY OG <mail@madeyourday.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * RockSolid Slider back end modules configuration
 *
 * @author Martin Auswöger <martin@madeyourday.net>
 */

$GLOBALS['TL_MODELS']['tl_rocksolid_slider'] = 'MadeYourDay\\RockSolidSlider\\Model\\SliderModel';
$GLOBALS['TL_MODELS']['tl_rocksolid_slide'] = 'MadeYourDay\\RockSolidSlider\\Model\\SlideModel';

$GLOBALS['TL_CTE']['includes']['rocksolid_slider'] = 'MadeYourDay\\RockSolidSlider\\Module\\Slider';

$GLOBALS['BE_MOD']['content']['rocksolid_slider'] = array(
	'tables' => array(
		'tl_rocksolid_slider',
		'tl_rocksolid_slide',
		'tl_content',
		'tl_rocksolid_slider_license',
	),
	'icon' => 'bundles/rocksolidslider/img/icon.png',
);

array_insert($GLOBALS['FE_MOD'], 2, array(
	'miscellaneous' => array(
		'rocksolid_slider' => 'MadeYourDay\\RockSolidSlider\\Module\\Slider',
	)
));

// TODO: Replace with migration services
$GLOBALS['TL_HOOKS']['sqlCompileCommands'][] = array('MadeYourDay\\RockSolidSlider\\SliderRunonce', 'onSqlCompileCommands');

$GLOBALS['TL_PERMISSIONS'][] = 'rsts_sliders';
$GLOBALS['TL_PERMISSIONS'][] = 'rsts_permissions';
