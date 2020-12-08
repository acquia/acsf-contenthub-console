<?php

namespace Acquia\Console\Acsf\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\ConfirmationQuestion;

/**
 * Provides command for Acsf. Restore db backup.
 *
 * @package Acquia\Console\Acsf\Command
 */
class AcsfDatabaseBackupRestore extends AcsfCommandBase {

  /**
   * {@inheritdoc}
   */
  protected static $defaultName = 'acsf:database:backup:restore';

  /**
   * {@inheritdoc}
   */
  protected function configure(): void {
    $this->setDescription('Restores database backups for ACSF sites.');
    $this->setAliases(['acsf-dbr']);
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output): int {
    if (!$sites = $this->getAcsfSites()) {
      $output->writeln('No sites found.');
      return 1;
    }

    do {
      $output->writeln('You are about to restore one of your ACSF site\'s database');
      $helper = $this->getHelper('question');

      $quest_site = new ChoiceQuestion('Pick on which site you want to perform this operation:', $sites);
      $site = $helper->ask($input, $output, $quest_site);

      $site_id = array_search($site, $sites, TRUE);
      $backups = $this->acsfClient->getBackupsBySiteId($site_id);
      if (empty($backups)) {
        $output->writeln('No database backups found.');
        return 2;
      }

      $backup_list = [];
      foreach ($backups['backups'] as $backup) {
        $backup_list[$backup['id']] = $backup['label'];
      }

      $quest_backup = new ChoiceQuestion('Pick which backup you want to use:', $backup_list);
      $backup = $helper->ask($input, $output, $quest_backup);
      $backup_id = array_search($backup, $backup_list);

      $confirm_quest = new ConfirmationQuestion("Do you want to restore site: <comment>$site</comment> from backup: <comment>$backup</comment>?");
      $answer = $helper->ask($input, $output, $confirm_quest);
    } while ($answer !== TRUE);

    $exit_code = $this->restoreSiteFromBackup($site_id, $backup_id);

    if (!$exit_code) {
      return 3;
    }

    $output->writeln('Restore backups can take several minutes to complete for small websites, but larger websites can take much longer to complete.');

    return 0;
  }

  /**
   * Post request to restore site from backup for a specific site.
   *
   * @param int $site_id
   *   Acsf site id.
   * @param int $backup_id
   *   Acsf backup id.
   *
   * @return bool
   *   TRUE if task has been created for database backup restoration.
   */
  protected function restoreSiteFromBackup(int $site_id, int $backup_id): bool {
    $options['body'] = json_encode([
      'backup_id' => $backup_id,
      'components' => [
        'database'
      ],
    ]);

    $body = $this->acsfClient->restoreAcsfSiteBackup($site_id, $options);

    return isset($body['task_id']) ?? FALSE;
  }

}
