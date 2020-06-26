<?php

namespace Acquia\Console\Acsf\Command;

use Acquia\Console\ContentHub\Command\Helpers\ContentHubModuleChecker;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\StreamOutput;

/**
 * Class AcsfInitialize.
 *
 * @package Acquia\Console\Acsf\Command
 */
class AcsfInitialize extends AcsfCommandBase {

  /**
   * Acsf sites config key.
   */
  public const CONFIG_ACSF_SITES = 'acquia.acsf.sites';

  /**
   * {@inheritdoc}
   */
  protected static $defaultName = 'acsf:init';

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this->setDescription('Discover sites in Acquia Cloud Site Factory account and write them to current platform.');
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    if (!class_exists('Acquia\Console\ContentHub\Command\Helpers\ContentHubModuleChecker')) {
      // @todo: This command should probably be moved into the contenthub component or removed if unnecessary.
      $output->writeln("<error>This command is intended for use with the acquia/contenthub-console component to initialize ACSF Platforms for ContentHub. Please install the acquia/contenthub-console component and try again.</error>");
      return 1;
    }
    $output->writeln(sprintf('Gathering Acquia Cloud Site Factory sites using current platform: <info>%s</info>', $this->platform->getAlias()));
    // Keep output separate for easier parsing.
    $remote_output = new StreamOutput(fopen('php://memory', 'r+', false));

    // Run the command.
    $cmd = $this->getApplication()->find(ContentHubModuleChecker::getDefaultName());
    /** @var \Acquia\Console\Acsf\Platform\ACSFPlatform $platform */
    $platform_input = new ArrayInput(['command' => $cmd->getName()]);
    $this->platform->execute($cmd, $platform_input, $remote_output);

    // Parse the output.
    rewind($remote_output->getStream());
    $output_content = stream_get_contents($remote_output->getStream());
    $parsed = $this->parseOutput($output_content);

    $sites = $this->getFilteredAcsfSites($this->acsfClient->listSites(), $parsed);
    $this->platform
      ->set(self::CONFIG_ACSF_SITES, $sites)
      ->save();

    $output->writeln('<info>Acquired sites were written to the current platform.</info>');
  }

  /**
   * Parse remote output string.
   *
   * Expectations:
   *   Output:
   *     Attempting to execute requested command for site: site.name
   *     enabled
   *
   * @param string $output
   *   The output to parse.
   *
   * @return array
   *   The parsed output.
   */
  public function parseOutput(string $output): array {
    $lines = explode(PHP_EOL, trim($output));
    $lines = array_values(array_filter($lines, function ($val) {
      return !empty($val);
    }));

    array_shift($lines);
    $parsed = [];
    for ($i = 0; $i < count($lines); $i++) {
      if (!($i & 1)) {
        [, $site_name] = explode(': ', $lines[$i]);
        $status = $lines[$i+1];
        $parsed[$site_name] = $status;
      }
    }

    return $parsed;
  }

  /**
   * Filter acsf sites based on the results of ach:module-exists.
   *
   * Expectations:
   *   Input:
   *     [
   *       site.name => enabled,
   *       site.name.2 => disabled,
   *     ]
   *
   * @param array $sites
   *   The list of sites to filter out.
   * @param array $filter
   *   The parsed output.
   *
   * @return array
   *   The array of filtered acsf sites.
   */
  public function getFilteredAcsfSites(array $sites, array $filter): array {
    $filtered = [];
    /** @var \Acquia\Console\Acsf\Platform\ACSFPlatform $platform */
    foreach ($sites as $site) {
      $domain = $site['domain'];
      if (!isset($filter[$domain]) || $filter[$domain] !== 'enabled') {
        continue;
      }

      $filtered[] = $site;
    }

    return $filtered;
  }

}
