<?php

namespace Acquia\Console\Acsf\Command\Backups;

use Acquia\Console\Acsf\Client\ResponseHandlerTrait;
use Acquia\Console\Acsf\Command\AcsfDatabaseBackupCreate;
use Acquia\Console\Acsf\Platform\ACSFPlatform;
use Acquia\Console\Cloud\Command\Backups\AcquiaCloudBackupCreate;
use EclipseGc\CommonConsole\PlatformInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class AcsfBackupCreate.
 *
 * @package Acquia\Console\Acsf\Command\Backups
 */
class AcsfBackupCreate extends AcquiaCloudBackupCreate {

  use ResponseHandlerTrait;

  /**
   * {@inheritdoc}
   */
  protected static $defaultName = 'acsf:backup:create';

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this->setDescription('Creates a snapshot of Acquia Content Hub Service and database backups for all sites within the ACSF platform..');
    $this->setAliases(['acsf-bc']);
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
  protected function getBackupId(PlatformInterface $platform, OutputInterface $output): array {
    $output->writeln('<info>Starts creating the database backups.</info>');
    $task_ids = $this->runAcsfBackupCreateCommand($platform, $output);

    return $task_ids;
  }

  /**
   * Runs backup creation or ACSF sites.
   *
   * @param \EclipseGc\CommonConsole\PlatformInterface $platform
   *   Platform instance.
   * @param \Symfony\Component\Console\Output\OutputInterface $output
   *   Output.
   *
   * @return array
   *   Array containing created backup ids.
   *
   * @throws \Exception
   */
  protected function runAcsfBackupCreateCommand(PlatformInterface $platform, OutputInterface $output): array {
    $cmd_input = [
      '--all' => TRUE,
      '--wait' => 300,
      '--silent' => TRUE,
    ];

    $raw = $this->platformCommandExecutioner->runLocallyWithMemoryOutput(AcsfDatabaseBackupCreate::getDefaultName(),
      $platform, $cmd_input);

    $db_backup_list = [];
    $lines = explode(PHP_EOL, trim($raw));
    foreach ($lines as $line) {
      $data = $this->fromJson($line, $output);
      if (!$data) {
        continue;
      }

      if ($data->backups) {
        foreach ($data->backups as $id => $backup) {
          $db_backup_list[$id] = (array) $backup;
        }
      }
    }

    return $db_backup_list;
  }

}
