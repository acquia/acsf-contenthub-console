<?php

namespace Acquia\Console\Acsf\Tests\Command;

use Acquia\Console\Acsf\Command\AcsfDatabaseBackupCreate;
use Acquia\Console\Acsf\Platform\ACSFPlatform;
use Acquia\Console\Cloud\Tests\Command\CommandTestHelperTrait;
use EclipseGc\CommonConsole\PlatformInterface;
use PHPUnit\Framework\MockObject\MockObject;

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
   * @param $task_id int
   *   Contains task id.
   *
   * @dataProvider databaseBackupProvider
   *
   * @throws \Exception
   */
  public function testAcsfDatabaseBackupCreate($sites, $task_id) {
    $backup_create_command = new AcsfDatabaseBackupCreate(
      $this->getDispatcher(),
      AcsfDatabaseBackupCreate::getDefaultName()
    );
    $args = [
      'sites' => $sites,
      'tasks' => [
        'task_id' => $task_id,
      ],
    ];
    $backup_create_command->addPlatform('test', $this->getPlatform($args));

    /** @var \Symfony\Component\Console\Tester\CommandTester $command_tester */
    $command_tester = $this->doRunCommand($backup_create_command, [1, 'yes'], ['alias' => 'test']);
    $this->assertStringContainsString('Backups can take several minutes to complete for small websites', $command_tester->getDisplay());
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
    $client_modifier = function (MockObject $client) use ($args) {
      $client->method('listSites')->willReturn($args['sites']);
      $client->method('createDatabaseBackup')->willReturn($args['tasks']);
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
