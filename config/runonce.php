<?php
/*
 * Copyright MADE/YOUR/DAY OG <mail@madeyourday.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

if (!class_exists('MadeYourDay\\Contao\\SliderRunonce')) {
	include __DIR__ . '/../src/MadeYourDay/Contao/SliderRunonce.php';
}

MadeYourDay\Contao\SliderRunonce::run();
