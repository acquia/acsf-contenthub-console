<?php

namespace Acquia\Console\Acsf\Command\Helpers;

use Acquia\Console\Acsf\Command\AcsfCommandBase;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class AcsfDbBackupDeleteHelper.
 *
 * @package Acquia\Console\Acsf\Command\Helpers
 */
class AcsfDbBackupDeleteHelper extends AcsfCommandBase {

  /**
   * {@inheritdoc}
   */
  protected static $defaultName = 'acsf:database:backup:delete:helper';

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this->setDescription('Delete database backups.');
    $this->addOption('backups', 'bid', InputOption::VALUE_REQUIRED, 'Database backups array of backup_id\'s keyed by site_id\'s.');
    $this->setHidden(TRUE);
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $backups = $input->getOption('backups');
    $task_ids = $this->deleteSiteBackups($backups);
    return $task_ids ? 0 : 1;
  }

  /**
   * Helper function to delete database backup for given set of sites in Acsf Platform.
   *
   * @param array $backups
   *   Array of backups.
   *
   * @return array
   *   Task ids.
   */
  protected function deleteSiteBackups(array $backups): array {
    $task_ids = [];
    foreach ($backups as $site_id => $backup_id) {
      $body = $this->acsfClient->deleteAcsfSiteBackup($site_id, $backup_id);
      $task_ids[] = $body['task_id'] ?? 0;
    }

    return $task_ids;
  }

}
