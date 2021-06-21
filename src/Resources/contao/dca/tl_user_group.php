<?php
/*
 * Copyright MADE/YOUR/DAY OG <mail@madeyourday.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Contao\CoreBundle\DataContainer\PaletteManipulator;

PaletteManipulator::create()
	->addLegend('rsts_slider_legend', 'amg_legend', PaletteManipulator::POSITION_BEFORE)
	->addField(array('rsts_sliders', 'rsts_permissions'), 'rsts_slider_legend', PaletteManipulator::POSITION_APPEND)
	->applyToPalette('default', 'tl_user_group')
;

$GLOBALS['TL_DCA']['tl_user_group']['config']['onload_callback'][] = array('MadeYourDay\\RockSolidSlider\\Slider', 'userGroupOnloadCallback');

$GLOBALS['TL_DCA']['tl_user_group']['fields']['rsts_sliders'] = array(
	'exclude' => true,
	'inputType' => 'checkbox',
	'foreignKey' => 'tl_rocksolid_slider.name',
	'eval' => array('multiple' => true),
	'sql' => "blob NULL",
);

$GLOBALS['TL_DCA']['tl_user_group']['fields']['rsts_permissions'] = array(
	'exclude' => true,
	'inputType' => 'checkbox',
	'options' => array('create', 'delete'),
	'reference' => &$GLOBALS['TL_LANG']['MSC'],
	'eval' => array('multiple' => true),
	'sql' => "blob NULL",
);
