<?php

namespace Acquia\Console\Acsf\Tests\Command;

use Acquia\Console\Acsf\Command\AcsfDatabaseBackupCreate;
use Acquia\Console\Acsf\Command\AcsfDatabaseBackupRestore;
use Acquia\Console\Acsf\Platform\ACSFPlatform;
use EclipseGc\CommonConsole\PlatformInterface;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Class AcsfDatabaseBackupRestoreTest.
 *
 * @coversDefaultClass \Acquia\Console\Acsf\Command\AcsfDatabaseBackupRestore
 *
 * @group acquia-console-acsf
 *
 * @package Acquia\Console\Acsf\Tests\Command
 */
class AcsfDatabaseBackupRestoreTest extends AcsfDatabaseTestBase {

  /**
   * Test coverage for Acsf db backup operations.
   *
   * @param array $sites
   *   Contains site id and site name.
   * @param $task_id int
   *   Contains task id.
   * @param $backup_list
   *   Contains backup information.
   *
   * @dataProvider databaseBackupProvider
   *
   * @throws \Exception
   */
  public function testAcsfDatabaseBackupRestore($sites, $task_id, $backup_list): void {
    $restore_backup_command = new AcsfDatabaseBackupRestore(
      $this->getDispatcher(),
      AcsfDatabaseBackupCreate::getDefaultName()
    );
    $args = [
      'sites' => $sites,
      'tasks' => ['task_id' => $task_id],
      'backups' => ['backups' => [$backup_list]],
    ];
    $restore_backup_command->addPlatform('test', $this->getPlatform($args));

    $output = $this->doRunCommand($restore_backup_command, [1, 1, 'yes'], ['alias' => '@test']);
    $this->assertStringContainsString('Do you want to restore site: test-1 from backup: backup_test_label?', $output);
  }

  /**
   * Provides data for testAcsfDatabaseCrud().
   */
  public function databaseBackupProvider(): array {
    return [
      [
        [
          [
            'id' => 1,
            'site' => 'test-1',
          ],
          [
            'id' => 2,
            'site' => 'test-2',
          ],
        ],
        123123,
        [
          'id' => 1,
          'nid' => 1,
          'label' => 'backup_test_label',
          'timestamp' => '123123123',
        ],
      ]
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getPlatform(array $args = []): PlatformInterface {
    $client_modifier = function (MockObject $client) use ($args) {
      $client->method('listSites')->willReturn($args['sites']);
      $client->method('getBackupsBySiteId')->willReturn($args['backups']);
      $client->method('restoreAcsfSiteBackup')->willReturn($args['tasks']);
    };

    return $this->getAcsfPlatform(
      [
        ACSFPlatform::SITEFACTORY_USER => 'user_name',
        ACSFPlatform::SITEFACTORY_TOKEN => 'secret_token',
        ACSFPlatform::SITEFACTORY_URL => 'https://example.com'
      ],
      $client_modifier
    );
  }

}
