<?php

namespace Acquia\Console\Acsf\Command;

use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;

/**
 * Provides command for Acsf. List database backups.
 *
 * @package Acquia\Console\Acsf\Command
 */
class AcsfDatabaseBackupList extends AcsfCommandBase {

  /**
   * {@inheritdoc}
   */
  protected static $defaultName = 'acsf:database:backup:list';

  /**
   * {@inheritdoc}
   */
  protected function configure(): void {
    $this->setDescription('Lists database backups for ACSF sites.');
    $this->setAliases(['acsf-dbl']);
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output): int {
    if (!$sites = $this->getAcsfSites()) {
      $output->writeln('No sites found.');
      return 1;
    }

    $helper = $this->getHelper('question');
    $question = new ChoiceQuestion('List backups for which site?', $sites);
    $site = $helper->ask($input, $output, $question);

    $site_id = array_search($site, $sites, TRUE);
    $backups = $this->acsfClient->getBackupsBySiteId($site_id);
    if (empty($backups)) {
      $output->writeln('No database backups found.');
      return 1;
    }

    $table = new Table($output);
    $table->setHeaders(['Backup ID', 'Site ID', 'Label', 'Timestamp']);
    foreach ($backups['backups'] as $backup) {
      $table->addRow([
        $backup['id'],
        $backup['nid'],
        $backup['label'],
        $backup['timestamp'],
      ]);
    }
    $table->render();

    return 0;
  }

}
