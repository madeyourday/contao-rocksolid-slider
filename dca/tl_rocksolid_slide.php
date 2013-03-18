<?php
/*
 * Copyright MADE/YOUR/DAY OG <mail@madeyourday.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * RockSolid Slide DCA
 *
 * @author Martin Auswöger <martin@madeyourday.net>
 */
$GLOBALS['TL_DCA']['tl_rocksolid_slide'] = array(

	'config' => array(
		'dataContainer' => 'Table',
		'ptable' => 'tl_rocksolid_slider',
		'ctable' => array('tl_content'),
		'switchToEdit' => true,
		'enableVersioning' => true,
		'sql' => array(
			'keys' => array(
				'id' => 'primary',
				'pid' => 'index',
			)
		),
	),

	'list' => array(
		'sorting' => array(
			'mode' => 4,
			'fields' => array('sorting'),
			'headerFields' => array('name'),
			'panelLayout' => 'limit',
			'child_record_callback' => array('MadeYourDay\\Contao\\Slider', 'listSlides'),
			'child_record_class' => 'no_padding',
		),
		'global_operations' => array(
			'all' => array(
				'label' => &$GLOBALS['TL_LANG']['MSC']['all'],
				'href' => 'act=select',
				'class' => 'header_edit_all',
				'attributes' => 'onclick="Backend.getScrollOffset()" accesskey="e"',
			)
		),
		'operations' => array(
			'edit' => array(
				'label' => &$GLOBALS['TL_LANG']['tl_rocksolid_slide']['edit'],
				'href' => 'table=tl_content',
				'icon' => 'edit.gif',
				'attributes' => 'class="contextmenu"',
				'button_callback' => array('MadeYourDay\\Contao\\Slider', 'editSlideIcon'),
			),
			'editheader' => array(
				'label' => &$GLOBALS['TL_LANG']['tl_rocksolid_slide']['editheader'],
				'href' => 'act=edit',
				'icon' => 'header.gif',
				'attributes' => 'class="edit-header"',
			),
			'copy' => array(
				'label' => &$GLOBALS['TL_LANG']['tl_rocksolid_slide']['copy'],
				'href' => 'act=paste&amp;mode=copy',
				'icon' => 'copy.gif',
			),
			'cut' => array(
				'label' => &$GLOBALS['TL_LANG']['tl_rocksolid_slide']['cut'],
				'href' => 'act=paste&amp;mode=cut',
				'icon' => 'cut.gif',
			),
			'delete' => array(
				'label' => &$GLOBALS['TL_LANG']['tl_rocksolid_slide']['delete'],
				'href' => 'act=delete',
				'icon' => 'delete.gif',
				'attributes' => 'onclick="if(!confirm(\'' . $GLOBALS['TL_LANG']['MSC']['deleteConfirm'] . '\'))return false;Backend.getScrollOffset()"',
			),
			'toggle' => array(
				'label' => &$GLOBALS['TL_LANG']['tl_rocksolid_slide']['toggle'],
				'icon' => 'visible.gif',
				'attributes' => 'onclick="Backend.getScrollOffset();return AjaxRequest.toggleVisibility(this,%s)"',
				'button_callback' => array('MadeYourDay\\Contao\\Slider', 'toggleSlideIcon'),
			),
			'show' => array(
				'label' => &$GLOBALS['TL_LANG']['tl_rocksolid_slide']['show'],
				'href' => 'act=show',
				'icon' => 'show.gif',
			)
		)
	),

	'palettes' => array(
		'default' => '{title_legend},title,videoURL,singleSRC;{publish_legend},published,start,stop',
	),

	'fields' => array(
		'id' => array(
			'sql' => "int(10) unsigned NOT NULL auto_increment",
		),
		'pid' => array(
			'foreignKey' => 'tl_rocksolid_slider.name',
			'sql' => "int(10) unsigned NOT NULL default '0'",
			'relation' => array('type' => 'belongsTo', 'load' => 'eager'),
		),
		'tstamp' => array(
			'sql' => "int(10) unsigned NOT NULL default '0'",
		),
		'sorting' => array(
			'sql' => "int(10) unsigned NOT NULL default '0'",
		),
		'title' => array(
			'label' => &$GLOBALS['TL_LANG']['tl_rocksolid_slide']['title'],
			'exclude' => true,
			'search' => true,
			'flag' => 1,
			'inputType' => 'text',
			'eval' => array('maxlength' => 255),
			'sql' => "varchar(255) NOT NULL default ''",
		),
		'videoURL' => array(
			'label' => &$GLOBALS['TL_LANG']['tl_rocksolid_slide']['videoURL'],
			'exclude' => true,
			'inputType' => 'text',
			'eval' => array('maxlength' => 255),
			'sql' => "varchar(255) NOT NULL default ''",
		),
		'singleSRC' => array(
			'label' => &$GLOBALS['TL_LANG']['tl_rocksolid_slide']['singleSRC'],
			'exclude' => true,
			'inputType' => 'fileTree',
			'eval' => array(
				'fieldType' => 'radio',
				'files' => true,
				'filesOnly' => true,
			),
			'sql' => "varchar(255) NOT NULL default ''",
		),
		'published' => array(
			'label' => &$GLOBALS['TL_LANG']['tl_rocksolid_slide']['published'],
			'exclude' => true,
			'filter' => true,
			'flag' => 1,
			'inputType' => 'checkbox',
			'eval' => array('doNotCopy'=>true),
			'sql' => "char(1) NOT NULL default ''",
		),
		'start' => array(
			'label' => &$GLOBALS['TL_LANG']['tl_rocksolid_slide']['start'],
			'exclude' => true,
			'inputType' => 'text',
			'eval' => array('rgxp'=>'datim', 'datepicker'=>true, 'tl_class'=>'w50 wizard'),
			'sql' => "varchar(10) NOT NULL default ''",
		),
		'stop' => array(
			'label' => &$GLOBALS['TL_LANG']['tl_rocksolid_slide']['stop'],
			'exclude' => true,
			'inputType' => 'text',
			'eval' => array('rgxp'=>'datim', 'datepicker'=>true, 'tl_class'=>'w50 wizard'),
			'sql' => "varchar(10) NOT NULL default ''",
		)
	),

);
