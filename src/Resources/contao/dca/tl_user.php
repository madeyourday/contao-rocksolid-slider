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
	->applyToPalette('extend', 'tl_user')
	->applyToPalette('custom', 'tl_user')
;

$GLOBALS['TL_DCA']['tl_user']['config']['onload_callback'][] = array('MadeYourDay\\RockSolidSlider\\Slider', 'userOnloadCallback');

$GLOBALS['TL_DCA']['tl_user']['fields']['rsts_sliders'] = array(
	'exclude' => true,
	'inputType' => 'checkbox',
	'foreignKey' => 'tl_rocksolid_slider.name',
	'eval' => array('multiple' => true),
	'sql' => "blob NULL",
);

$GLOBALS['TL_DCA']['tl_user']['fields']['rsts_permissions'] = array(
	'exclude' => true,
	'inputType' => 'checkbox',
	'options' => array('create', 'delete'),
	'reference' => &$GLOBALS['TL_LANG']['MSC'],
	'eval' => array('multiple' => true),
	'sql' => "blob NULL",
);
