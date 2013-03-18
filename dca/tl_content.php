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
