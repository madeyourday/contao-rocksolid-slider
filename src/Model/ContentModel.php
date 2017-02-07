<?php
/*
 * Copyright MADE/YOUR/DAY OG <mail@madeyourday.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace MadeYourDay\Contao\Model;

/**
 * Content Model Extension
 *
 * @author Martin Auswöger <martin@madeyourday.net>
 */
class ContentModel extends \ContentModel
{
	/**
	 * Find all published content elements by their parent IDs and parent table
	 *
	 * @param array  $parentIds   The parent IDs
	 * @param string $parentTable The parent table name
	 * @param array  $options     An optional options array
	 *
	 * @return \Model\Collection|null A collection of models or null if there are no content elements
	 */
	public static function findPublishedByPidsAndTable(array $parentIds, $parentTable, array $options = array())
	{
		$table = static::$strTable;

		$columns = array("$table.pid IN(" . implode(',', array_map('intval', $parentIds)) . ") AND ptable=?");

		if (!BE_USER_LOGGED_IN) {
			$columns[] = "$table.invisible=''";
		}

		if (! isset($options['order'])) {
			$options['order'] = "$table.sorting";
		}

		return static::findBy($columns, $parentTable, $options);
	}
}
