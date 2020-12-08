<?php

namespace Acquia\Console\Acsf\Command;

use Acquia\Console\Acsf\Platform\ACSFPlatform;
use Acquia\Console\Cloud\Command\AcquiaCloudCronList;

/**
 * Class AcsfCronList.
 *
 * @package Acquia\Console\Acsf\Command
 */
class AcsfCronList extends AcquiaCloudCronList {

  /**
   * {@inheritdoc}
   */
  protected static $defaultName = 'acsf:cron:list';

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this->setDescription('List Scheduled Jobs');
    $this->setAliases(['acsf-cl']);
  }

  /**
   * {@inheritdoc}
   */
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
