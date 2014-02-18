<?php
/*
 * Copyright MADE/YOUR/DAY OG <mail@madeyourday.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * RockSolid Slide Content DCA
 *
 * @author Martin Auswöger <martin@madeyourday.net>
 */

/**
 * Dynamically add the parent table
 */
if (Input::get('do') == 'rocksolid_slider') {
	$GLOBALS['TL_DCA']['tl_content']['config']['ptable'] = 'tl_rocksolid_slide';
}

$GLOBALS['TL_DCA']['tl_content']['palettes']['rocksolid_slider'] = '{type_legend},type,headline;{rocksolid_slider_legend},rsts_id,rsts_type,rsts_direction,rsts_random,rsts_skin,rsts_width,rsts_height,rsts_preloadSlides,rsts_gapSize,rsts_duration,rsts_autoplay,rsts_videoAutoplay,rsts_autoplayRestart,rsts_autoplayProgress,rsts_pauseAutoplayOnHover,rsts_navType,rsts_controls,rsts_scaleMode,rsts_deepLinkPrefix,rsts_keyboard,rsts_captions;{template_legend:hide},rsts_template,imgSize;{protected_legend:hide},protected;{expert_legend:hide},guests,rsts_customSkin,rsts_cssPrefix,cssID,space;{invisible_legend:hide},invisible,start,stop';

$GLOBALS['TL_DCA']['tl_content']['fields']['rsts_id'] = array(
	'label' => &$GLOBALS['TL_LANG']['tl_content']['rsts_id'],
	'exclude' => true,
	'inputType' => 'select',
	'options_callback' => array('MadeYourDay\\Contao\\Slider', 'getSliderIds'),
	'eval' => array('mandatory' => true),
	'sql' => "int(10) unsigned NOT NULL default '0'",
);
$GLOBALS['TL_DCA']['tl_content']['fields']['rsts_template'] = array(
	'label' => &$GLOBALS['TL_LANG']['tl_content']['rsts_template'],
	'default' => 'rsts_default',
	'exclude' => true,
	'inputType' => 'select',
	'options_callback' => array('MadeYourDay\\Contao\\Slider', 'getSliderTemplates'),
	'eval' => array('tl_class' => 'w50'),
	'sql' => "varchar(32) NOT NULL default ''",
);
// slider type (slide or fade)
$GLOBALS['TL_DCA']['tl_content']['fields']['rsts_type'] = array(
	'label' => &$GLOBALS['TL_LANG']['tl_content']['rsts_type'],
	'exclude' => true,
	'inputType' => 'select',
	'options' => array('slide' => 'slide', 'fade' => 'fade'),
	'eval' => array('tl_class' => 'w50'),
	'sql' => "varchar(64) NOT NULL default ''",
);
// "x" for horizontal or "y" for vertical
$GLOBALS['TL_DCA']['tl_content']['fields']['rsts_direction'] = array(
	'label' => &$GLOBALS['TL_LANG']['tl_content']['rsts_direction'],
	'exclude' => true,
	'inputType' => 'select',
	'options' => array(
		'x' => $GLOBALS['TL_LANG']['tl_content']['rsts_direction_x'],
		'y' => $GLOBALS['TL_LANG']['tl_content']['rsts_direction_y'],
	),
	'eval' => array('tl_class' => 'w50'),
	'sql' => "varchar(64) NOT NULL default ''",
);
// if true the slides get shuffled once on initialization
$GLOBALS['TL_DCA']['tl_content']['fields']['rsts_random'] = array(
	'label' => &$GLOBALS['TL_LANG']['tl_content']['rsts_random'],
	'exclude' => true,
	'inputType' => 'checkbox',
	'eval' => array('tl_class' => 'w50 m12'),
	'sql' => "char(1) NOT NULL default ''",
);
// slider skin (set this to "none" to disable the default skin)
$GLOBALS['TL_DCA']['tl_content']['fields']['rsts_skin'] = array(
	'label' => &$GLOBALS['TL_LANG']['tl_content']['rsts_skin'],
	'exclude' => true,
	'inputType' => 'select',
	'options' => array(
		'',
		'default-content',
		'liquid',
		'dark',
		'light',
		'custom',
	),
	'reference' => &$GLOBALS['TL_LANG']['tl_content']['rsts_skin_options'],
	'eval' => array('tl_class' => 'w50'),
	'sql' => "varchar(64) NOT NULL default ''",
);
// set width and height to one of the following values
// - "css": get the size from the applied css (default)
// - a css lenght value: e.g. "100%", "500px", "50em"
// - "auto": get the size from the active slide dimensions at runtime
//   height can be set to auto only if the direction is "x"
// - a proportion: keep a fixed proportion for the slides, e.g. "480x270"
//   this must not set to both dimensions
$GLOBALS['TL_DCA']['tl_content']['fields']['rsts_width'] = array(
	'label' => &$GLOBALS['TL_LANG']['tl_content']['rsts_width'],
	'exclude' => true,
	'inputType' => 'text',
	'eval' => array('tl_class' => 'w50'),
	'sql' => "varchar(64) NOT NULL default ''",
);
$GLOBALS['TL_DCA']['tl_content']['fields']['rsts_height'] = array(
	'label' => &$GLOBALS['TL_LANG']['tl_content']['rsts_height'],
	'exclude' => true,
	'inputType' => 'text',
	'eval' => array('tl_class' => 'w50'),
	'sql' => "varchar(64) NOT NULL default ''",
);
// number of slides to preload (to the left/right or top/bottom)
$GLOBALS['TL_DCA']['tl_content']['fields']['rsts_preloadSlides'] = array(
	'label' => &$GLOBALS['TL_LANG']['tl_content']['rsts_preloadSlides'],
	'exclude' => true,
	'inputType' => 'select',
	'options' => array(1, 2, 3, 4, 5, 6, 7, 8, 9),
	'eval' => array('tl_class' => 'w50', 'includeBlankOption' => true),
	'sql' => "int(10) unsigned NOT NULL default '0'",
);
// gap between the slides
$GLOBALS['TL_DCA']['tl_content']['fields']['rsts_gapSize'] = array(
	'label' => &$GLOBALS['TL_LANG']['tl_content']['rsts_gapSize'],
	'exclude' => true,
	'inputType' => 'text',
	'eval' => array('tl_class' => 'w50'),
	'sql' => "int(10) unsigned NOT NULL default '0'",
);
// duration of the slide animation in milliseconds
$GLOBALS['TL_DCA']['tl_content']['fields']['rsts_duration'] = array(
	'label' => &$GLOBALS['TL_LANG']['tl_content']['rsts_duration'],
	'exclude' => true,
	'inputType' => 'text',
	'eval' => array('tl_class' => 'w50'),
	'sql' => "int(10) unsigned NOT NULL default '0'",
);
// false or the duration between slides in milliseconds
$GLOBALS['TL_DCA']['tl_content']['fields']['rsts_autoplay'] = array(
	'label' => &$GLOBALS['TL_LANG']['tl_content']['rsts_autoplay'],
	'exclude' => true,
	'inputType' => 'text',
	'eval' => array('tl_class' => 'w50'),
	'sql' => "int(10) unsigned NOT NULL default '0'",
);
// true to autoplay videos
$GLOBALS['TL_DCA']['tl_content']['fields']['rsts_videoAutoplay'] = array(
	'label' => &$GLOBALS['TL_LANG']['tl_content']['rsts_videoAutoplay'],
	'exclude' => true,
	'inputType' => 'checkbox',
	'eval' => array('tl_class' => 'w50 m12'),
	'sql' => "char(1) NOT NULL default ''",
);
// false or the duration between user interaction and autoplay
// (must be bigger than autoplay)
$GLOBALS['TL_DCA']['tl_content']['fields']['rsts_autoplayRestart'] = array(
	'label' => &$GLOBALS['TL_LANG']['tl_content']['rsts_autoplayRestart'],
	'exclude' => true,
	'inputType' => 'text',
	'eval' => array('tl_class' => 'w50'),
	'sql' => "int(10) unsigned NOT NULL default '0'",
);
// displays a progress bar
$GLOBALS['TL_DCA']['tl_content']['fields']['rsts_autoplayProgress'] = array(
	'label' => &$GLOBALS['TL_LANG']['tl_content']['rsts_autoplayProgress'],
	'exclude' => true,
	'inputType' => 'checkbox',
	'eval' => array('tl_class' => 'w50'),
	'sql' => "char(1) NOT NULL default ''",
);
// true to pause the autoplay on hover
$GLOBALS['TL_DCA']['tl_content']['fields']['rsts_pauseAutoplayOnHover'] = array(
	'label' => &$GLOBALS['TL_LANG']['tl_content']['rsts_pauseAutoplayOnHover'],
	'exclude' => true,
	'inputType' => 'checkbox',
	'eval' => array('tl_class' => 'w50'),
	'sql' => "char(1) NOT NULL default ''",
);
// navigation type (bullets, numbers, tabs)
$GLOBALS['TL_DCA']['tl_content']['fields']['rsts_navType'] = array(
	'label' => &$GLOBALS['TL_LANG']['tl_content']['rsts_navType'],
	'exclude' => true,
	'inputType' => 'select',
	'options' => array(
		'bullets' => $GLOBALS['TL_LANG']['tl_content']['rsts_navType_bullets'],
		'numbers' => $GLOBALS['TL_LANG']['tl_content']['rsts_navType_numbers'],
		'tabs' => $GLOBALS['TL_LANG']['tl_content']['rsts_navType_tabs'],
		'none' => $GLOBALS['TL_LANG']['tl_content']['rsts_navType_none'],
	),
	'eval' => array('tl_class' => 'w50'),
	'sql' => "varchar(64) NOT NULL default ''",
);
// false to hide the prev and next controls
$GLOBALS['TL_DCA']['tl_content']['fields']['rsts_controls'] = array(
	'label' => &$GLOBALS['TL_LANG']['tl_content']['rsts_controls'],
	'exclude' => true,
	'inputType' => 'checkbox',
	'eval' => array('tl_class' => 'w50 m12'),
	'sql' => "char(1) NOT NULL default '1'",
);
// image scale mode (fit, crop, scale)
// only works if width and height are not set to "auto"
$GLOBALS['TL_DCA']['tl_content']['fields']['rsts_scaleMode'] = array(
	'label' => &$GLOBALS['TL_LANG']['tl_content']['rsts_scaleMode'],
	'exclude' => true,
	'inputType' => 'select',
	'options' => array(
		'fit' => $GLOBALS['TL_LANG']['tl_content']['rsts_scaleMode_fit'],
		'crop' => $GLOBALS['TL_LANG']['tl_content']['rsts_scaleMode_crop'],
		'scale' => $GLOBALS['TL_LANG']['tl_content']['rsts_scaleMode_scale'],
	),
	'eval' => array('tl_class' => 'w50'),
	'sql' => "varchar(64) NOT NULL default ''",
);
// URL hash prefix or false to disable deep linking, e.g. "slider-"
$GLOBALS['TL_DCA']['tl_content']['fields']['rsts_deepLinkPrefix'] = array(
	'label' => &$GLOBALS['TL_LANG']['tl_content']['rsts_deepLinkPrefix'],
	'exclude' => true,
	'inputType' => 'text',
	'eval' => array('tl_class' => 'w50'),
	'sql' => "varchar(255) NOT NULL default ''",
);
// true to enable keyboard arrow navigation
$GLOBALS['TL_DCA']['tl_content']['fields']['rsts_keyboard'] = array(
	'label' => &$GLOBALS['TL_LANG']['tl_content']['rsts_keyboard'],
	'exclude' => true,
	'inputType' => 'checkbox',
	'eval' => array('tl_class' => 'w50'),
	'sql' => "char(1) NOT NULL default '1'",
);
// true to enable keyboard arrow navigation
$GLOBALS['TL_DCA']['tl_content']['fields']['rsts_captions'] = array(
	'label' => &$GLOBALS['TL_LANG']['tl_content']['rsts_captions'],
	'exclude' => true,
	'inputType' => 'checkbox',
	'eval' => array('tl_class' => 'w50'),
	'sql' => "char(1) NOT NULL default '1'",
);
// custom slider skin (rsts_skin gets ignored if this is set)
$GLOBALS['TL_DCA']['tl_content']['fields']['rsts_customSkin'] = array(
	'label' => &$GLOBALS['TL_LANG']['tl_content']['rsts_customSkin'],
	'exclude' => true,
	'inputType' => 'text',
	'eval' => array('tl_class' => 'w50 clr'),
	'sql' => "varchar(64) NOT NULL default ''",
);
// prefix for all RockSolid Slider specific css class names
$GLOBALS['TL_DCA']['tl_content']['fields']['rsts_cssPrefix'] = array(
	'label' => &$GLOBALS['TL_LANG']['tl_content']['rsts_cssPrefix'],
	'exclude' => true,
	'inputType' => 'text',
	'eval' => array('tl_class' => 'w50'),
	'sql' => "varchar(64) NOT NULL default ''",
);
