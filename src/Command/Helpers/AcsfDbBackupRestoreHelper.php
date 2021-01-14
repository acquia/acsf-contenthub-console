<?php

namespace Acquia\Console\Acsf\Command\Helpers;

use Acquia\Console\Acsf\Command\AcsfCommandBase;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class AcsfDbBackupRestoreHelper.
 *
 * @package Acquia\Console\Acsf\Command\Helpers
 */
class AcsfDbBackupRestoreHelper extends AcsfCommandBase {

  /**
   * {@inheritdoc}
   */
  protected static $defaultName = 'acsf:database:backup:restore:helper';

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this->setDescription('Restore database backups.');
    $this->addOption('backups', 'bid', InputOption::VALUE_REQUIRED, 'Database backups array of backup_id\'s keyed by site_id\'s.');
    $this->setHidden(TRUE);
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $backups = $input->getOption('backups');

    $task_ids = $this->restoreSiteFromBackup($backups);
    return $task_ids ? 0 : 1;
  }

  /**
   * Post request to restore site from backup for a specific site.
   *
   * @param array $backups
   *   Acsf site id.
   *
   * @return array
   *   Task ids.
   */
  protected function restoreSiteFromBackup(array $backups): array {
    $task_ids = [];
    foreach ($backups as $site_id => $backup_id) {
      $options['body'] = json_encode([
        'backup_id' => $backup_id,
        'components' => [
          'database'
        ],
      ]);

      $body = $this->acsfClient->restoreAcsfSiteBackup($site_id, $options);
      $task_ids[] = $body['task_id'] ?? 0;
    }

    return $task_ids;
  }

}
