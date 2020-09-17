<?php

namespace Acquia\Console\Acsf\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\ConfirmationQuestion;

/**
 * Provides command for Acsf. Create database backup.
 *
 * @package Acquia\Console\Acsf\Command
 */
class AcsfDatabaseBackupCreate extends AcsfCommandBase {

  /**
   * {@inheritdoc}
   */
  protected static $defaultName = 'acsf:database:backup:create';

  /**
   * {@inheritdoc}
   */
  protected function configure(): void {
    $this->setDescription('Create database backup for each one of your ACSF sites.');
    $this->addOption('all', 'a', InputOption::VALUE_NONE, 'Perform backups for all sites in the platform.');
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output): int {
    if (!$sites = $this->getAcsfSites()) {
      $output->writeln('No sites found.');
      return 1;
    }

    if ($input->hasOption('all') && $input->getOption('all')) {
      $output->writeln('You are about to create site backups for all your ACSF sites.');
      foreach ($sites as $site_id => $site) {
        $output->writeln("Create database backup for site: $site...");
        if (!$this->createAcsfSiteBackup($site_id, $site)) {
          $output->writeln('Failed to queue task for creating db backup.');
          return 2;
        }
      }
    }
    else {
      do {
        $output->writeln('You are about to create a site backup for one of your ACSF sties.');
        $helper = $this->getHelper('question');
        $question = new ChoiceQuestion('Pick one of the following sites:', $sites);
        $site = $helper->ask($input, $output, $question);

        $output->writeln("Create database backup for site: $site");
        $quest = new ConfirmationQuestion('Do you want to proceed?');
        $answer = $helper->ask($input, $output, $quest);
      } while ($answer !== TRUE);

      $site_id = array_search($site, $sites, TRUE);

      if (!$this->createAcsfSiteBackup($site_id, $site)) {
        $output->writeln('Failed to queue task for creating db backup.');
        return 2;
      }
    }

    $output->writeln('Backups can take several minutes to complete for small websites, but larger websites can take much longer to complete.');
    $output->writeln('You can check your backups on ACSF or using this CLI tool. (acsf:backup:list)');

    return 0;
  }

  /**
   * Post request to create site backup for a specific site.
   *
   * @param int $site_id
   *   Acsf site id.
   * @param string $name
   *   Acsf site name.
   *
   * @return bool
   *   TRUE if task has been created for database backup creation.
   */
  protected function createAcsfSiteBackup(int $site_id, string $name): bool {
    $label = $name . '_' . time() . '_cli_tool';

    $options['body'] = json_encode([
      'label' => $label,
      'components' => [
        'database'
      ],
    ]);

    $body = $this->acsfClient->createDatabaseBackup($site_id, $options);

    return isset($body['task_id']) ?? FALSE;
  }

}
