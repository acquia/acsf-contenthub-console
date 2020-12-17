<?php

namespace Acquia\Console\Acsf\Command;

use Acquia\Console\Acsf\Platform\ACSFPlatform;
use Acquia\Console\Cloud\Command\AcquiaCloudCronCreate;
use Acquia\Console\ContentHub\Command\Helpers\PlatformCmdOutputFormatterTrait;

/**
 * Class AcsfCronCreate.
 *
 * @package Acquia\Console\Acsf\Command
 */
class AcsfCronCreate extends AcquiaCloudCronCreate {

  use PlatformCmdOutputFormatterTrait;

  /**
   * @var \AcquiaCloudApi\Connector\ClientInterface
   */
  protected $acquiaCloudClient;

  /**
   * {@inheritdoc}
   */
  public static $defaultName = 'acsf:cron:create';

  /**
   * {@inheritdoc}
   */
  public function configure() {
    $this->setDescription('Creates Scheduled Jobs for Acquia Content Hub Export/Import queues.');
    $this->setAliases(['acsf-cc']);
  }

  /**
   * {@inheritdoc}
   */
  public static function getExpectedPlatformOptions(): array {
    return ['source' => ACSFPlatform::getPlatformId()];
  }

  /**
   * {@inheritdoc}
   */
  protected function getSiteInfo(): array {
    $sites = $this->platform->getPlatformSites();
    $env_uuid = $this->platform->get('acquia.cloud.environment.name');

    $site_info = [];
    foreach ($sites as $domain => $site) {
      $site_info[] = [
        'active_domain' => $domain,
        'env_uuid' => $env_uuid,
      ];
    }

    return $site_info;
  }

}
