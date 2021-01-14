<?php

namespace Acquia\Console\Acsf\Command;

use Acquia\Console\Acsf\Client\ResponseHandlerTrait;
use Acquia\Console\Helpers\Command\PlatformCmdOutputFormatterTrait;
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

  use PlatformCmdOutputFormatterTrait;
  use ResponseHandlerTrait;

  /**
   * {@inheritdoc}
   */
  protected static $defaultName = 'acsf:database:backup:create';

  /**
   * {@inheritdoc}
   */
  protected function configure(): void {
    $this->setDescription('Creates database backups for each site on the ACSF platform.');
    $this->setAliases(['acsf-dbc']);
    $this->addOption('all', 'a', InputOption::VALUE_NONE, 'Perform backups for all sites in the platform.');
    $this->addOption('wait', 'w', InputOption::VALUE_REQUIRED, 'Provide time (seconds) how long should we monitor task if completed or not.');
    $this->addOption('silent', 's', InputOption::VALUE_NONE, 'Returns list, but does not send it to the output.');
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output): int {
    if (!$sites = $this->getAcsfSites()) {
      $output->writeln('<error>No sites found.</error>');
      return 1;
    }

    if (!$input->getOption('all') && !$input->getOption('silent')) {
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
      $sites = [$site_id => $site];
    }

    $task_ids = [];
    foreach ($sites as $site_id => $site) {
      $output->writeln("Create database backup for site: $site...");
      $task_id = $this->createAcsfSiteBackup($site_id, $site);
      if (!$task_id) {
        $output->writeln('<error>Failed to queue task for creating db backup.</error>');
        return 2;
      }

      $task_ids[] = $task_id;
    }

    if (!$task_ids) {
      $output->writeln('<error>Cannot get task ids for database backup creation.</error>');
      return 3;
    }

    $wait = $input->hasOption('wait') ? $input->getOption('wait') : NULL;
    if ($wait && $wait < 10) {
      $output->writeln('<error>Input of wait option must be higher than 10 seconds.</error>');
      return 4;
    }

    if ($input->hasOption('silent') && $input->getOption('silent') && $wait) {
      $success = $this->wait($task_ids, $wait);
      return $success ? 0 : 5;
    }

    if ($wait) {
      $success = $this->waitInteractive($task_ids, $output, $wait);
      if (!$success) {
        $output->writeln('<warning>Some of the backups not created yet. Terminating...</warning>');
        return 6;
      }
    }

    if (!$wait) {
      $output->writeln('Backups can take several minutes to complete for small websites, but larger websites can take much longer to complete.');
      $output->writeln('You can check your backups on ACSF or using this CLI tool. (acsf:backup:list)');
    }

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
   * @return string
   *   Task id.
   */
  protected function createAcsfSiteBackup(int $site_id, string $name): string {
    $label = $name . '_' . time() . '_cli_tool';

    $options['body'] = json_encode([
      'label' => $label,
      'components' => [
        'database'
      ],
    ]);

    $body = $this->acsfClient->createDatabaseBackup($site_id, $options);

    return $body['task_id'] ?? '';
  }

  /**
   * Pings ACSF about task status and prints info to the output.
   *
   * @param array $task_ids
   *   Task ids.
   * @param \Symfony\Component\Console\Output\OutputInterface $output
   *   Output.
   * @param int $wait_time
   *   This long will try to ping status endpoint for info. (seconds)
   *
   * @return bool
   *   True if every task was finished successfully.
   */
  protected function waitInteractive(array $task_ids, OutputInterface $output, int $wait_time): bool {
    $tasks = implode(', ', $task_ids);
    $output->writeln("<info>Waiting for the following task(s) to complete: {$tasks}</info>");
    /** @var \Acquia\Console\Acsf\Client\AcsfClient $acsf_client */
    $successful_task = 0;
    $task_count = count($task_ids);

    while ($successful_task !== $task_count && $wait_time >= 0) {
      $output->writeln('Ping ACSF about pending tasks...');
      foreach ($task_ids as $id => $task_id) {
        $resp = $this->acsfClient->pingStatusEndpoint($task_id);
        if ($resp['wip_task']['status_string'] === 'Completed') {
          $successful_task++;
          $output->writeln("<info>Task with id: $task_id completed successfully.</info>");
          unset($task_ids[$id]);
        }
      }

      sleep(10);
      $wait_time -= 10;
    }

    if ($wait_time < 0) {
      $output->writeln("<warning>In the given time task of db backup creation cannot finish. Please check your task on ACSF!</warning>");
    }

    return $successful_task === $task_count;
  }

  /**
   * Pings ACSF about task status without output.
   *
   * @param array $task_ids
   *   Task ids.
   * @param int $wait_time
   *   This long will try to ping status endpoint for info. (seconds)
   *
   * @return bool
   *   TRUE if tasks are finished successfully.
   */
  protected function wait(array $task_ids, int $wait_time): bool {
    $successful_task = 0;
    $task_count = count($task_ids);

    while ($successful_task !== $task_count && $wait_time >= 0) {
      foreach ($task_ids as $id => $task_id) {
        $resp = $this->acsfClient->pingStatusEndpoint($task_id);
        if ($resp['wip_task']['status_string'] === 'Completed') {
          $successful_task++;
          unset($task_ids[$id]);
        }
      }

      sleep(10);
      $wait_time -= 10;
    }

    return $wait_time < 0 ? FALSE : TRUE;
  }

}
