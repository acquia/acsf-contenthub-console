<?php

namespace Acquia\Console\Acsf\Tests\Command;

use Acquia\Console\Acsf\Command\AcsfDatabaseBackupCreate;
use Acquia\Console\Acsf\Command\AcsfDatabaseBackupDelete;
use Acquia\Console\Acsf\Platform\ACSFPlatform;
use EclipseGc\CommonConsole\PlatformInterface;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;

/**
 * Class AcsfDatabaseBackupDeleteTest.
 *
 * @coversDefaultClass \Acquia\Console\Acsf\Command\AcsfDatabaseBackupDelete
 *
 * @group acquia-console-acsf
 *
 * @package Acquia\Console\Acsf\Tests\Command
 */
class AcsfDatabaseBackupDeleteTest extends AcsfDatabaseTestBase {

  /**
   * Test coverage for Acsf db backup operations.
   *
   * @param array $sites
   *   Contains site id and site name.
   * @param int $task_id
   *   Contains task id.
   * @param array $backup_list
   *   Contains backup information.
   *
   * @dataProvider databaseBackupProvider
   *
   * @throws \Exception
   */
  public function testAcsfDatabaseBackupDelete(array $sites, int $task_id, array $backup_list): void {
    $delete_backup_command = new AcsfDatabaseBackupDelete(
      $this->getDispatcher(),
      AcsfDatabaseBackupCreate::getDefaultName()
    );
    $args = [
      'sites' => $sites,
      'tasks' => ['task_id' => $task_id],
      'backups' => ['backups' => [$backup_list]],
    ];
    $delete_backup_command->addPlatform('test', $this->getPlatform($args));

    /** @var \Symfony\Component\Console\Tester\CommandTester $command_tester */
    $command_tester = $this->doRunCommand($delete_backup_command, [1, 1, 'yes'], ['alias' => 'test']);
    $this->assertStringContainsString('Do you want to delete backup: backup_test_label?', $command_tester->getDisplay());
    $this->assertEquals(0, $command_tester->getStatusCode());
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
    $client_modifier = function (ObjectProphecy $client) use ($args) {
      $client->listSites(Argument::any())
        ->willReturn($args['sites']);

      $client->getBackupsBySiteId(Argument::any())
        ->willReturn($args['backups']);

      $client->deleteAcsfSiteBackup(Argument::any(), Argument::any())
        ->willReturn($args['tasks']);
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
