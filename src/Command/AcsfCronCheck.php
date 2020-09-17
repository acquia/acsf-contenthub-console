<?php

namespace Acquia\Console\Acsf\Command;

use Acquia\Console\Acsf\Platform\ACSFPlatform;
use Acquia\Console\Cloud\Command\AcquiaCloudCronCheck;
use Symfony\Component\Console\Input\InputOption;

/**
 * Class AcsfCronCheck.
 *
 * @package Acquia\Console\Acsf\Command
 */
class AcsfCronCheck extends AcquiaCloudCronCheck {

  /**
   * {@inheritdoc}
   */
  protected static $defaultName = 'acsf:cron:check';

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this->setDescription('Checks for scheduled jobs which are running ACH queues.');
    $this->setAliases(['acsf-cch']);
    $this->addOption('fix', 'f', InputOption::VALUE_NONE, 'Disable schedule jobs which are running ACH queues.');
  }

  public static function getExpectedPlatformOptions(): array {
    return ['source' => ACSFPlatform::getPlatformId()];
  }

  /**
   * Get environment info from platform config.
   *
   * @return array
   *   Environment config.
   */
  protected function getEnvironmentInfo(): array {
    return [$this->platform->get('acquia.cloud.environment.name')];
  }

}
