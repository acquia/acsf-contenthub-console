<?php

namespace Acquia\Console\Acsf\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\ConfirmationQuestion;

/**
 * Provides command for Acsf. Delete db backup.
 *
 * @package Acquia\Console\Acsf\Command
 */
class AcsfDatabaseBackupDelete extends AcsfCommandBase {

  /**
   * {@inheritdoc}
   */
  protected static $defaultName = 'acsf:database:backup:delete';

  /**
   * {@inheritdoc}
   */
  protected function configure(): void {
    $this->setDescription('Delete database backup of an ACSF site');
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output): int {
    if (!$sites = $this->getAcsfSites()) {
      $output->writeln('<error>No sites found.</error>');
      return 1;
    }

    do {
      $helper = $this->getHelper('question');
      $site_question = new ChoiceQuestion('Pick one of the following sites:', $sites);
      $site = $helper->ask($input, $output, $site_question);

      $site_id = array_search($site, $sites, TRUE);
      $backups = $this->acsfClient->getBackupsBySiteId($site_id);

      if (empty($backups)) {
        $output->writeln('<info>There are no available backups for the given site!<info>');
        return 2;
      }

      $list = [];
      foreach ($backups['backups'] as $backup) {
        $list[$backup['id']] = $backup['label'];
      }

      $backup_question = new ChoiceQuestion('Pick which db backup should be deleted:', $list);
      $db_to_delete = $helper->ask($input, $output, $backup_question);
      $confirm_question = new ConfirmationQuestion("Do you want to delete backup: $db_to_delete?");
      $answer2 = $helper->ask($input, $output, $confirm_question);
    } while ($answer2 !== TRUE);

    $db_backup_id = array_search($db_to_delete, $list, TRUE);
    if (!$db_backup_id) {
      return 3;
    }

    $response_body = $this->acsfClient->deleteAcsfSiteBackup($site_id, $db_backup_id);
    if (!isset($response_body['task_id'])) {
      return 4;
    }

    $output->writeln("Request sent to ACSF for deletion of backup: <comment>$db_to_delete</comment>. Backups deletion take several minutes to complete.");
    $output->writeln('You can check your backups on ACSF or using this CLI tool. (acsf:backup:list)');

    return 0;
  }

}
