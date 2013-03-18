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

$GLOBALS['BE_MOD']['content']['rocksolid_slider'] = array(
	'tables' => array('tl_rocksolid_slider', 'tl_rocksolid_slide', 'tl_content'),
	'icon' => 'system/modules/rocksolid-slider/assets/img/icon.png',
);

array_insert($GLOBALS['FE_MOD'], 2, array(
	'miscellaneous' => array(
		'rocksolid_slider' => 'MadeYourDay\\Contao\\Module\\Slider',
	)
));
