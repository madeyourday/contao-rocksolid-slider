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
	'MadeYourDay\\Contao\\Slider' => 'system/modules/rocksolid-slider/src/MadeYourDay/Contao/Slider.php',
	'MadeYourDay\\Contao\\Module\\Slider' => 'system/modules/rocksolid-slider/src/MadeYourDay/Contao/Module/Slider.php',
	'MadeYourDay\\Contao\\Model\\SlideModel' => 'system/modules/rocksolid-slider/src/MadeYourDay/Contao/Model/SlideModel.php',
	'MadeYourDay\\Contao\\Model\\SliderModel' => 'system/modules/rocksolid-slider/src/MadeYourDay/Contao/Model/SliderModel.php',
	'MadeYourDay\\Contao\\Model\\ContentModel' => 'system/modules/rocksolid-slider/src/MadeYourDay/Contao/Model/ContentModel.php',
));

TemplateLoader::addFiles(array(
	'rsts_default' => 'system/modules/rocksolid-slider/templates',
));
