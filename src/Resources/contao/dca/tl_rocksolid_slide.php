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
		'onload_callback' => array(
			array('MadeYourDay\\RockSolidSlider\\Slider', 'slideOnloadCallback'),
		),
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
			'header_callback' => array('MadeYourDay\\RockSolidSlider\\Slider', 'headerCallback'),
			'child_record_callback' => array('MadeYourDay\\RockSolidSlider\\Slider', 'listSlides'),
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
				'button_callback' => array('MadeYourDay\\RockSolidSlider\\Slider', 'editSlideIcon'),
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
				'button_callback' => array('MadeYourDay\\RockSolidSlider\\Slider', 'toggleSlideIcon'),
			),
			'show' => array(
				'label' => &$GLOBALS['TL_LANG']['tl_rocksolid_slide']['show'],
				'href' => 'act=show',
				'icon' => 'show.gif',
			)
		)
	),

	'palettes' => array(
		'__selector__' => array('type'),
		'default' => '{title_legend},title,type',
		'content' => '{title_legend},title,type,thumbImage,thumbTitle,thumbClass,thumbText,centerContent,invertControls,autoplay,linkUrl,linkNewWindow;{background_legend},backgroundImage,backgroundVideos,muteVideos,hideVideoControls,videosPlayInline,backgroundImageSize,backgroundScaleMode,backgroundPosition;{expert_legend},slideClass,sliderClass;{publish_legend},published,start,stop',
		'image' => '{title_legend},title,type,singleSRC,thumbImage,thumbTitle,thumbClass,thumbText,scaleMode,imagePosition,centerContent,invertControls,autoplay,linkUrl,linkNewWindow;{background_legend},backgroundImage,backgroundVideos,muteVideos,hideVideoControls,videosPlayInline,backgroundImageSize,backgroundScaleMode,backgroundPosition;{expert_legend},slideClass,sliderClass;{publish_legend},published,start,stop',
		'video' => '{title_legend},title,type,videoURL,videos,muteVideos,hideVideoControls,videosPlayInline,singleSRC,thumbImage,thumbTitle,thumbClass,thumbText,scaleMode,imagePosition,centerContent,invertControls,autoplay,linkUrl,linkNewWindow;{background_legend},backgroundImage,backgroundVideos,backgroundImageSize,backgroundScaleMode,backgroundPosition;{expert_legend},slideClass,sliderClass;{publish_legend},published,start,stop',
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
			'eval' => array(
				'maxlength' => 255,
				'tl_class' => 'w50',
			),
			'sql' => "varchar(255) NOT NULL default ''",
		),
		'type' => array(
			'label' => &$GLOBALS['TL_LANG']['tl_rocksolid_slide']['type'],
			'exclude' => true,
			'inputType' => 'select',
			'options' => array(
				'content',
				'image',
				'video',
			),
			'reference' => &$GLOBALS['TL_LANG']['tl_rocksolid_slide']['types'],
			'eval' => array(
				'mandatory' => true,
				'includeBlankOption' => true,
				'submitOnChange' => true,
				'tl_class' => 'w50',
			),
			'sql' => "varchar(255) NOT NULL default ''",
		),
		'videoURL' => array(
			'label' => &$GLOBALS['TL_LANG']['tl_rocksolid_slide']['videoURL'],
			'exclude' => true,
			'inputType' => 'text',
			'eval' => array(
				'maxlength' => 255,
				'tl_class' => 'clr',
			),
			'sql' => "varchar(255) NOT NULL default ''",
		),
		'videos' => array(
			'label' => &$GLOBALS['TL_LANG']['tl_rocksolid_slide']['videos'],
			'exclude' => true,
			'inputType' => 'fileTree',
			'eval' => array(
				'multiple' => true,
				'fieldType' => 'checkbox',
				'orderField' => 'videosOrder',
				'files' => true,
				'filesOnly' => true,
				'extensions' => 'mp4,m4v,mov,wmv,webm,ogv',
			),
			'sql' => "blob NULL",
		),
		'videosOrder' => array(
			'label' => &$GLOBALS['TL_LANG']['tl_rocksolid_slide']['videosOrder'],
			'sql' => "blob NULL",
		),
		'muteVideos' => array(
			'label' => &$GLOBALS['TL_LANG']['tl_rocksolid_slide']['muteVideos'],
			'exclude' => true,
			'inputType' => 'checkbox',
			'eval' => array('tl_class' => 'clr w50'),
			'sql' => "char(1) NOT NULL default ''",
		),
		'hideVideoControls' => array(
			'label' => &$GLOBALS['TL_LANG']['tl_rocksolid_slide']['hideVideoControls'],
			'exclude' => true,
			'inputType' => 'checkbox',
			'eval' => array('tl_class' => 'w50'),
			'sql' => "char(1) NOT NULL default ''",
		),
		'videosPlayInline' => array(
			'label' => &$GLOBALS['TL_LANG']['tl_rocksolid_slide']['videosPlayInline'],
			'exclude' => true,
			'inputType' => 'checkbox',
			'eval' => array('tl_class' => 'clr'),
			'sql' => "char(1) NOT NULL default ''",
		),
		'singleSRC' => array(
			'label' => &$GLOBALS['TL_LANG']['tl_rocksolid_slide']['singleSRC'],
			'exclude' => true,
			'inputType' => 'fileTree',
			'eval' => array(
				'fieldType' => 'radio',
				'files' => true,
				'filesOnly' => true,
				'extensions' => \Config::get('validImageTypes'),
				'tl_class' => 'clr',
			),
			'sql' => "binary(16) NULL",
		),
		'thumbImage' => array(
			'label' => &$GLOBALS['TL_LANG']['tl_rocksolid_slide']['thumbImage'],
			'exclude' => true,
			'inputType' => 'fileTree',
			'eval' => array(
				'fieldType' => 'radio',
				'files' => true,
				'filesOnly' => true,
				'extensions' => \Config::get('validImageTypes'),
				'tl_class' => 'clr',
			),
			'sql' => "binary(16) NULL",
		),
		'thumbTitle' => array(
			'label' => &$GLOBALS['TL_LANG']['tl_rocksolid_slide']['thumbTitle'],
			'exclude' => true,
			'inputType' => 'text',
			'eval' => array(
				'tl_class' => 'w50',
				'maxlength' => 255,
			),
			'sql' => "varchar(255) NOT NULL default ''",
		),
		'thumbClass' => array(
			'label' => &$GLOBALS['TL_LANG']['tl_rocksolid_slide']['thumbClass'],
			'exclude' => true,
			'inputType' => 'text',
			'eval' => array(
				'tl_class' => 'w50',
				'maxlength' => 255,
			),
			'sql' => "varchar(255) NOT NULL default ''",
		),
		'thumbText' => array(
			'label' => &$GLOBALS['TL_LANG']['tl_rocksolid_slide']['thumbText'],
			'exclude' => true,
			'inputType' => 'textarea',
			'eval' => array(
				'tl_class' => 'clr',
			),
			'sql' => "mediumtext NULL",
		),
		'scaleMode' => array(
			'label' => &$GLOBALS['TL_LANG']['tl_rocksolid_slide']['scaleMode'],
			'exclude' => true,
			'inputType' => 'select',
			'options' => array('fit', 'crop', 'scale', 'none'),
			'reference' => &$GLOBALS['TL_LANG']['tl_rocksolid_slide']['scaleModes'],
			'eval' => array(
				'includeBlankOption' => true,
				'tl_class' => 'w50',
			),
			'sql' => "varchar(64) NOT NULL default ''",
		),
		'imagePosition' => array(
			'label' => &$GLOBALS['TL_LANG']['tl_rocksolid_slide']['imagePosition'],
			'exclude' => true,
			'inputType' => 'select',
			'options' => array('center', 'top', 'right', 'bottom', 'left', 'top-left', 'top-right', 'bottom-left', 'bottom-right'),
			'reference' => &$GLOBALS['TL_LANG']['tl_rocksolid_slide']['imagePositions'],
			'eval' => array(
				'includeBlankOption' => true,
				'tl_class' => 'w50',
			),
			'sql' => "varchar(64) NOT NULL default ''",
		),
		'centerContent' => array(
			'label' => &$GLOBALS['TL_LANG']['tl_rocksolid_slide']['centerContent'],
			'exclude' => true,
			'inputType' => 'select',
			'options' => array(
				'false',
				'true',
				'x',
				'y',
			),
			'reference' => &$GLOBALS['TL_LANG']['tl_rocksolid_slide']['centerContents'],
			'eval' => array(
				'includeBlankOption' => true,
				'tl_class' => 'w50',
			),
			'sql' => "varchar(64) NOT NULL default ''",
		),
		'invertControls' => array(
			'label' => &$GLOBALS['TL_LANG']['tl_rocksolid_slide']['invertControls'],
			'exclude' => true,
			'inputType' => 'checkbox',
			'eval' => array('tl_class' => 'w50 m12'),
			'sql' => "char(1) NOT NULL default ''",
		),
		'autoplay' => array(
			'label' => &$GLOBALS['TL_LANG']['tl_rocksolid_slide']['autoplay'],
			'exclude' => true,
			'inputType' => 'text',
			'eval' => array(
				'tl_class' => 'clr w50',
			),
			'sql' => "int(10) unsigned NOT NULL default '0'",
		),
		'linkUrl' => array(
			'label' => &$GLOBALS['TL_LANG']['tl_rocksolid_slide']['linkUrl'],
			'exclude' => true,
			'inputType' => 'text',
			'eval' => array(
				'rgxp' => 'url',
				'decodeEntities' => true,
				'maxlength' => 255,
				'tl_class' => 'clr w50 wizard',
			),
			'wizard' => array(
				array('MadeYourDay\\RockSolidSlider\\Slider', 'pagePickerWizard'),
			),
			'sql' => "varchar(255) NOT NULL default ''",
		),
		'linkNewWindow' => array(
			'label' => &$GLOBALS['TL_LANG']['MSC']['target'],
			'exclude' => true,
			'inputType' => 'checkbox',
			'eval' => array(
				'tl_class' => 'w50 m12',
			),
			'sql' => "char(1) NOT NULL default ''",
		),
		'backgroundImage' => array(
			'label' => &$GLOBALS['TL_LANG']['tl_rocksolid_slide']['backgroundImage'],
			'exclude' => true,
			'inputType' => 'fileTree',
			'eval' => array(
				'fieldType' => 'radio',
				'files' => true,
				'filesOnly' => true,
				'extensions' => \Config::get('validImageTypes'),
			),
			'sql' => "binary(16) NULL",
		),
		'backgroundVideos' => array(
			'label' => &$GLOBALS['TL_LANG']['tl_rocksolid_slide']['backgroundVideos'],
			'exclude' => true,
			'inputType' => 'fileTree',
			'eval' => array(
				'multiple' => true,
				'fieldType' => 'checkbox',
				'orderField' => 'backgroundVideosOrder',
				'files' => true,
				'filesOnly' => true,
				'extensions' => 'mp4,m4v,mov,wmv,webm,ogv,ogg',
			),
			'sql' => "blob NULL",
		),
		'backgroundVideosOrder' => array(
			'label' => &$GLOBALS['TL_LANG']['tl_rocksolid_slide']['backgroundVideosOrder'],
			'sql' => "blob NULL",
		),
		'backgroundImageSize' => array(
			'label' => &$GLOBALS['TL_LANG']['tl_rocksolid_slide']['backgroundImageSize'],
			'exclude' => true,
			'inputType' => 'imageSize',
			'options_callback' => function() {
				return System::getContainer()->get('contao.image.image_sizes')->getOptionsForUser(BackendUser::getInstance());
			},
			'reference' => &$GLOBALS['TL_LANG']['MSC'],
			'eval' => array(
				'rgxp' => 'digit',
				'nospace' => true,
				'helpwizard' => true,
				'tl_class' => 'w50',
				'includeBlankOption' => true,
			),
			'sql' => "varchar(64) NOT NULL default ''",
		),
		'backgroundScaleMode' => array(
			'label' => &$GLOBALS['TL_LANG']['tl_rocksolid_slide']['backgroundScaleMode'],
			'exclude' => true,
			'inputType' => 'select',
			'options' => array('fit', 'crop', 'scale', 'none'),
			'reference' => &$GLOBALS['TL_LANG']['tl_rocksolid_slide']['scaleModes'],
			'eval' => array(
				'includeBlankOption' => true,
				'tl_class' => 'w50 clr',
			),
			'sql' => "varchar(64) NOT NULL default ''",
		),
		'backgroundPosition' => array(
			'label' => &$GLOBALS['TL_LANG']['tl_rocksolid_slide']['backgroundPosition'],
			'exclude' => true,
			'inputType' => 'select',
			'options' => array('center', 'top', 'right', 'bottom', 'left', 'top-left', 'top-right', 'bottom-left', 'bottom-right'),
			'reference' => &$GLOBALS['TL_LANG']['tl_rocksolid_slide']['imagePositions'],
			'eval' => array(
				'includeBlankOption' => true,
				'tl_class' => 'w50',
			),
			'sql' => "varchar(64) NOT NULL default ''",
		),
		'slideClass' => array(
			'label' => &$GLOBALS['TL_LANG']['tl_rocksolid_slide']['slideClass'],
			'exclude' => true,
			'inputType' => 'text',
			'eval' => array('maxlength' => 255, 'tl_class' => 'w50 clr'),
			'sql' => "varchar(255) NOT NULL default ''",
		),
		'sliderClass' => array(
			'label' => &$GLOBALS['TL_LANG']['tl_rocksolid_slide']['sliderClass'],
			'exclude' => true,
			'inputType' => 'text',
			'eval' => array('maxlength' => 255, 'tl_class' => 'w50'),
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
