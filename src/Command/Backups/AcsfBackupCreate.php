<?php

namespace Acquia\Console\Acsf\Command\Backups;

use Acquia\Console\Acsf\Client\ResponseHandlerTrait;
use Acquia\Console\Acsf\Command\AcsfDatabaseBackupCreate;
use Acquia\Console\Acsf\Command\AcsfDatabaseBackupList;
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
    $list_before = $this->runAcsfBackupListCommand($platform, $output);
    $raw = $this->runAcsfBackupCreateCommand($platform);

    if ($raw->getReturnCode() !== 0) {
      throw new \Exception('Database backup creation failed.');
    }

    $list_after = $this->runAcsfBackupListCommand($platform, $output);

    return $this->getDifference($list_before, $list_after);
  }

  /**
   * Helper function to get the difference of backups list before and after backup creation.
   *
   * @param object $before
   *   List of backups before backup creation.
   * @param object $after
   *   List of backups after backup creation.
   *
   * @return array
   *   Array of sites with latest backup id created.
   */
  protected function getDifference(object $before, object $after) {
    $diff = $diff_backup_ids = [];
    $before = json_decode(json_encode($before), true);
    $after = json_decode(json_encode($after), true);

    foreach ($before as $site_id => $backup_ids) {
      $diff[$site_id] = current(array_diff($after[$site_id], $backup_ids));
    }

    // This needs to be done because even if backup isn't created, $diff still has an associative array of site ids => false
    // which fails the condition in AcquiaCloudBackupCreate to check empty $backups for this command.
    foreach ($diff as $backup_id) {
      if (!is_bool($backup_id)) {
        $diff_backup_ids[] = $backup_id;
      }
    }

    return $diff_backup_ids;
  }

  /**
   * Helper function to get the list of Acsf sites backup list.
   *
   * @param PlatformInterface $platform
   *   Platform instance.
   * @param OutputInterface $output
   *   Output instance.
   *
   * @return object|null
   *   Object of list containing the sites and associated backup ids.
   *
   * @throws \Exception
   */
  protected function runAcsfBackupListCommand(PlatformInterface $platform, OutputInterface $output): ?object  {
    $raw = $this->platformCommandExecutioner->runLocallyWithMemoryOutput(AcsfDatabaseBackupList::getDefaultName(),
      $platform, ['--silent' => TRUE]);

    $lines = explode(PHP_EOL, trim($raw));
    foreach ($lines as $line) {
      $data = $this->fromJson($line, $output);
      if (!$data) {
        continue;
      }

      return $data;
    }

    return NULL;
  }

  /**
   * Runs backup creation or ACSF sites.
   *
   * @param \EclipseGc\CommonConsole\PlatformInterface $platform
   *   Platform instance.
   *
   * @return object
   *   Object containing command run info.
   *
   * @throws \Exception
   */
  protected function runAcsfBackupCreateCommand(PlatformInterface $platform): object {
    $cmd_input = [
      '--all' => TRUE,
      '--wait' => 300,
      '--silent' => TRUE,
    ];

    return $this->platformCommandExecutioner->runLocallyWithMemoryOutput(AcsfDatabaseBackupCreate::getDefaultName(),
      $platform, $cmd_input);
  }

}
