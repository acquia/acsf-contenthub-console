<?php

namespace Acquia\Console\Acsf\Tests\Command;

use Acquia\Console\Acsf\Command\AcsfDatabaseBackupCreate;
use Acquia\Console\Acsf\Platform\ACSFPlatform;
use Acquia\Console\Cloud\Tests\Command\CommandTestHelperTrait;
use EclipseGc\CommonConsole\PlatformInterface;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;

/**
 * Class AcsfDatabaseBackupCreateTest.
 *
 * @coversDefaultClass \Acquia\Console\Acsf\Command\AcsfDatabaseBackupCreate
 *
 * @group acquia-console-acsf
 *
 * @package Acquia\Console\Acsf\Tests\Command
 */
class AcsfDatabaseBackupCreateTest extends AcsfDatabaseTestBase {

  use CommandTestHelperTrait;

  /**
   * Test coverage for Acsf db backup operations.
   *
   * @param array $sites
   *   Contains site id and site name.
   * @param int $task_id
   *   Contains task id.
   *
   * @dataProvider databaseBackupProvider
   *
   * @throws \Exception
   */
  public function testAcsfDatabaseBackupCreate(array $sites, int $task_id) {
    $backup_create_command = new AcsfDatabaseBackupCreate(
      $this->getDispatcher(),
      AcsfDatabaseBackupCreate::getDefaultName()
    );
    $args = [
      'sites' => $sites,
      'tasks' => [
        'task_id' => $task_id,
      ],
      'status' => [
        'wip_task' => [
          'nid' => 1,
          'status_string' => 'Completed',
        ],
        'time' => 'formatted time',
      ],
      'status2' => [
        'wip_task' => [
          'nid' => 2,
          'status_string' => 'Completed',
        ],
        'time' => 'formatted time',
      ],
    ];
    $backup_create_command->addPlatform('test', $this->getPlatform($args));

    /** @var \Symfony\Component\Console\Tester\CommandTester $command_tester */
    $command_tester = $this->doRunCommand($backup_create_command, [1, 'yes'], ['alias' => 'test']);
    $this->assertStringContainsString('Backups can take several minutes to complete for small websites', $command_tester->getDisplay());
    $this->assertEquals(0, $command_tester->getStatusCode());

    $command_tester = $this->doRunCommand($backup_create_command, [], ['--all' => TRUE]);
    $this->assertStringContainsString('Create database backup for site: test-1...', $command_tester->getDisplay());
    $this->assertStringContainsString('Create database backup for site: test-2...', $command_tester->getDisplay());
    $this->assertEquals(0, $command_tester->getStatusCode());

    $command_tester = $this->doRunCommand($backup_create_command, [], [
      '--all' => TRUE,
      '--wait' => 12
    ]);
    $this->assertStringContainsString("Create database backup for site: test-1...", $command_tester->getDisplay());
    $this->assertStringContainsString("Ping ACSF about pending tasks...", $command_tester->getDisplay());
    $this->assertStringContainsString("Task with id: 123123 completed successfully.", $command_tester->getDisplay());
    $this->assertEquals(0, $command_tester->getStatusCode());

    $backup_create_command->addPlatform('test', $this->getPlatform($args));
    $command_tester = $this->doRunCommand($backup_create_command, [], [
      '--all' => TRUE,
      '--wait' => 12,
      '--silent' => TRUE
    ]);
    $this->assertEquals(0, $command_tester->getStatusCode());
  }

  /**
   * Provides data for testAcsfDatabaseCrud().
   */
  public function databaseBackupProvider() {
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

      $client->createDatabaseBackup(Argument::any(), Argument::any())
        ->willReturn($args['tasks']);

      $client->pingStatusEndpoint(Argument::any())
        ->willReturn($args['status'], $args['status'], $args['status'], $args['status2']);
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
