<?php

namespace Acquia\Console\Acsf\Command\Backups;

use Acquia\Console\Acsf\Command\Helpers\AcsfDbBackupRestoreHelper;
use Acquia\Console\Acsf\Platform\ACSFPlatform;
use Acquia\Console\Cloud\Command\Backups\AcquiaCloudBackupRestore;
use EclipseGc\CommonConsole\PlatformInterface;

/**
 * Class AcsfBackupRestore.
 *
 * @package Acquia\Console\Acsf\Command\Backups
 */
class AcsfBackupRestore extends AcquiaCloudBackupRestore {

  /**
   * {@inheritdoc}
   */
  protected static $defaultName = 'acsf:backup:restore';

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this->setDescription('Restore snapshot of ACH service and database backups for all site within the platform.');
    $this->setAliases(['acsf-br']);
  }

  /**
   * {@inheritdoc}
   */
  public static function getExpectedPlatformOptions(): array {
    return ['source' => ACSFPlatform::getPlatformId()];
  }

  /**
   * {@inheritdoc}
   */
  protected function restoreDatabaseBackups(PlatformInterface $platform, array $backups): int {
    $raw = $this
      ->executioner
      ->runLocallyWithMemoryOutput(AcsfDbBackupRestoreHelper::getDefaultName(), $platform, ['--backups' => $backups]);
    return $raw->getReturnCode();
  }

}
