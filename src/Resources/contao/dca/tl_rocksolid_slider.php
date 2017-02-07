<?php
/*
 * Copyright MADE/YOUR/DAY OG <mail@madeyourday.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * RockSolid Slider DCA
 *
 * @author Martin Auswöger <martin@madeyourday.net>
 */
$GLOBALS['TL_DCA']['tl_rocksolid_slider'] = array(

	'config' => array(
		'dataContainer' => 'Table',
		'ctable' => array('tl_rocksolid_slide'),
		'switchToEdit' => true,
		'enableVersioning' => true,
		'sql' => array(
			'keys' => array(
				'id' => 'primary',
			),
		),
	),

	'list' => array(
		'sorting' => array(
			'mode' => 1,
			'fields' => array('name'),
			'flag' => 1,
			'panelLayout' => 'filter;search,limit',
		),
		'label' => array(
			'fields' => array('name', 'type'),
			'format' => '%s <span style="color:#999;padding-left:3px">%s</span>',
		),
		'global_operations' => array(
			'license' => array(
				'label' => &$GLOBALS['TL_LANG']['tl_rocksolid_slider']['editLicense'],
				'href' => 'table=tl_rocksolid_slider_license',
				'class' => 'header_icon',
				'icon' => 'system/themes/' . \Backend::getTheme() . '/images/settings.gif',
			),
			'all' => array(
				'label' => &$GLOBALS['TL_LANG']['MSC']['all'],
				'href' => 'act=select',
				'class' => 'header_edit_all',
				'attributes' => 'onclick="Backend.getScrollOffset()" accesskey="e"',
			),
		),
		'operations' => array(
			'edit' => array(
				'label' => &$GLOBALS['TL_LANG']['tl_rocksolid_slider']['edit'],
				'href' => 'table=tl_rocksolid_slide',
				'icon' => 'edit.gif',
				'attributes' => 'class="contextmenu"',
				'button_callback' => array('MadeYourDay\\RockSolidSlider\\Slider', 'editSliderIcon'),
			),
			'editheader' => array(
				'label' => &$GLOBALS['TL_LANG']['tl_rocksolid_slider']['editheader'],
				'href' => 'act=edit',
				'icon' => 'header.gif',
				'attributes' => 'class="edit-header"',
			),
			'copy' => array(
				'label' => &$GLOBALS['TL_LANG']['tl_rocksolid_slider']['copy'],
				'href' => 'act=copy',
				'icon' => 'copy.gif',
			),
			'delete' => array(
				'label' => &$GLOBALS['TL_LANG']['tl_rocksolid_slider']['delete'],
				'href' => 'act=delete',
				'icon' => 'delete.gif',
				'attributes' => 'onclick="if(!confirm(\'' . $GLOBALS['TL_LANG']['MSC']['deleteConfirm'] . '\'))return false;Backend.getScrollOffset()"',
			),
			'show' => array(
				'label' => &$GLOBALS['TL_LANG']['tl_rocksolid_slider']['show'],
				'href' => 'act=show',
				'icon' => 'show.gif',
			),
		),
	),

	'palettes' => array(
		'__selector__' => array('type'),
		'default' => '{slider_legend},name,type',
		'image' => '{slider_legend},name,type,multiSRC',
	),

	'fields' => array(
		'id' => array(
			'sql' => "int(10) unsigned NOT NULL auto_increment",
		),
		'tstamp' => array(
			'sql' => "int(10) unsigned NOT NULL default '0'",
		),
		'name' => array(
			'label' => &$GLOBALS['TL_LANG']['tl_rocksolid_slider']['name'],
			'exclude' => true,
			'search' => true,
			'inputType' => 'text',
			'eval' => array(
				'mandatory' => true,
				'maxlength' => 255,
				'tl_class' => 'w50',
			),
			'sql' => "varchar(255) NOT NULL default ''",
		),
		'type' => array(
			'label' => &$GLOBALS['TL_LANG']['tl_rocksolid_slider']['type'],
			'exclude' => true,
			'inputType' => 'select',
			'options' => array(
				'content',
				'image',
			),
			'reference' => &$GLOBALS['TL_LANG']['tl_rocksolid_slider']['types'],
			'eval' => array(
				'mandatory' => true,
				'includeBlankOption' => true,
				'submitOnChange' => true,
				'tl_class' => 'w50',
			),
			'sql' => "varchar(255) NOT NULL default ''",
		),
		'multiSRC' => array(
			'label' => &$GLOBALS['TL_LANG']['tl_rocksolid_slider']['multiSRC'],
			'exclude' => true,
			'inputType' => 'fileTree',
			'eval' => array(
				'mandatory' => true,
				'multiple' => true,
				'fieldType' => 'checkbox',
				'orderField' => 'orderSRC',
				'files' => true,
				'isGallery' => true,
				'extensions' => \Config::get('validImageTypes'),
				'tl_class' => 'clr',
			),
			'sql' => "blob NULL",
		),
		'orderSRC' => array(
			'label' => &$GLOBALS['TL_LANG']['tl_rocksolid_slider']['orderSRC'],
			'sql' => "blob NULL",
		),
	),

);
