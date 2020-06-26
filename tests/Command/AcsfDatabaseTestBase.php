<?php

namespace Acquia\Console\Acsf\Tests\Command;

use Acquia\Console\Cloud\Tests\Command\CommandTestHelperTrait;
use Acquia\Console\Cloud\Tests\Command\PlatformCommandTestHelperTrait;
use PHPUnit\Framework\TestCase;

/**
 * Class AcsfDatabaseTestBase.
 *
 * @package Acquia\Console\Acsf\Tests\Command
 */
abstract class AcsfDatabaseTestBase extends TestCase {

  use CommandTestHelperTrait;
  use PlatformCommandTestHelperTrait;

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
        [
          'id' => 1,
          'nid' => 1,
          'label' => 'backup_test_label',
          'timestamp' => '123123123',
        ],
      ]
    ];
  }

}
