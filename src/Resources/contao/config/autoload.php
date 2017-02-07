<?php
/*
 * Copyright MADE/YOUR/DAY OG <mail@madeyourday.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * RockSolid Slider autload configuration
 *
 * @author Martin Auswöger <martin@madeyourday.net>
 */

ClassLoader::addClasses(array(
	'MadeYourDay\\RockSolidSlider\\Slider' => 'system/modules/rocksolid-slider/src/MadeYourDay/Contao/Slider.php',
	'MadeYourDay\\RockSolidSlider\\SliderRunonce' => 'system/modules/rocksolid-slider/src/MadeYourDay/Contao/SliderRunonce.php',
	'MadeYourDay\\RockSolidSlider\\Module\\Slider' => 'system/modules/rocksolid-slider/src/MadeYourDay/Contao/Module/Slider.php',
	'MadeYourDay\\RockSolidSlider\\Module\\SliderNews' => 'system/modules/rocksolid-slider/src/MadeYourDay/Contao/Module/SliderNews.php',
	'MadeYourDay\\RockSolidSlider\\Module\\SliderEvents' => 'system/modules/rocksolid-slider/src/MadeYourDay/Contao/Module/SliderEvents.php',
	'MadeYourDay\\RockSolidSlider\\Model\\SlideModel' => 'system/modules/rocksolid-slider/src/MadeYourDay/Contao/Model/SlideModel.php',
	'MadeYourDay\\RockSolidSlider\\Model\\SliderModel' => 'system/modules/rocksolid-slider/src/MadeYourDay/Contao/Model/SliderModel.php',
	'MadeYourDay\\RockSolidSlider\\Model\\ContentModel' => 'system/modules/rocksolid-slider/src/MadeYourDay/Contao/Model/ContentModel.php',
));

$templatesFolder = 'vendor/madeyourday/contao-rocksolid-slider/src/Resources/contao/templates';

TemplateLoader::addFiles(array(
	'rsts_default' => $templatesFolder,
	'rststhumb_default' => $templatesFolder,
));
