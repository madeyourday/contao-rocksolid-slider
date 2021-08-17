<?php
/*
 * Copyright MADE/YOUR/DAY OG <mail@madeyourday.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace MadeYourDay\RockSolidSlider\Migration;

use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Migration\AbstractMigration;
use Contao\CoreBundle\Migration\MigrationResult;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\FetchMode;
use Doctrine\DBAL\Types\BlobType;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Types\Types;
use MadeYourDay\RockSolidSlider\Slider;

/**
 * @internal
 */
class SliderPermissionsMigration extends AbstractMigration
{
	/**
	 * @var Connection
	 */
	private $connection;

	/**
	 * @var ContaoFramework
	 */
	private $framework;

	public function __construct(Connection $connection, ContaoFramework $framework)
	{
		$this->connection = $connection;
		$this->framework = $framework;
	}

	public function shouldRun(): bool
	{
		$schemaManager = $this->connection->getSchemaManager();

		if (
			!$schemaManager->tablesExist('tl_rocksolid_slider')
			|| !$schemaManager->tablesExist('tl_user')
			|| !$schemaManager->tablesExist('tl_user_group')
		) {
			return false;
		}

		$columnsUser = $schemaManager->listTableColumns('tl_user');
		$columnsGroup = $schemaManager->listTableColumns('tl_user_group');

		if (
			isset($columnsUser['rsts_sliders'])
			|| isset($columnsUser['rsts_permissions'])
			|| isset($columnsGroup['rsts_sliders'])
			|| isset($columnsGroup['rsts_permissions'])
		) {
			return false;
		}

		$this->framework->initialize();

		return Slider::checkLicense();
	}

	public function run(): MigrationResult
	{
		$defaultPermissions = serialize(['create', 'delete']);

		if (method_exists($this->connection, 'fetchFirstColumn')) {
			$defaultSliders = serialize(array_values($this->connection->fetchFirstColumn("SELECT id FROM tl_rocksolid_slider")));
		}
		else {
			$defaultSliders = serialize(array_values($this->connection->executeQuery("SELECT id FROM tl_rocksolid_slider")->fetchAll(FetchMode::COLUMN)));
		}

		foreach (['tl_user', 'tl_user_group'] as $table) {
			foreach ([
				"ALTER TABLE $table ADD rsts_permissions BLOB DEFAULT NULL",
				"ALTER TABLE $table ADD rsts_sliders BLOB DEFAULT NULL",
			] as $query) {
				if (method_exists($this->connection, 'executeStatement')) {
					$this->connection->executeStatement($query);
				}
				else {
					$this->connection->query($query);
				}
			}
			if (method_exists($this->connection, 'executeStatement')) {
				$this->connection->executeStatement(
					"UPDATE $table SET rsts_permissions = ?, rsts_sliders = ?",
					[$defaultPermissions, $defaultSliders],
					[Types::BLOB, Types::BLOB]
				);
			}
			else {
				$this->connection
					->prepare("UPDATE $table SET rsts_permissions = ?, rsts_sliders = ?")
					->execute([$defaultPermissions, $defaultSliders])
				;
			}
		}

		return $this->createResult(true);
	}
}
