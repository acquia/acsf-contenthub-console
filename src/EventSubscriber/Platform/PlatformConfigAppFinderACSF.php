<?php

namespace Acquia\Console\Acsf\EventSubscriber\Platform;

use Acquia\Console\Cloud\EventSubscriber\Platform\PlatformConfigAppFinderCloud;
use Acquia\Console\Cloud\Platform\AcquiaCloudPlatform;
use Consolidation\Config\Config;
use EclipseGc\CommonConsole\CommonConsoleEvents;

/**
 * Class PlatformConfigAppFinderACSF.
 *
 * @package Acquia\Console\Acsf\EventSubscriber\Platform
 */
class PlatformConfigAppFinderACSF extends PlatformConfigAppFinderCloud {

  /**
   * Platform types to handle within this subscriber.
   *
   * @var array
   *   Platform types.
   */
  protected $platformTypes = [
    'Acquia Cloud Site Factory',
  ];

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    $events[CommonConsoleEvents::PLATFORM_CONFIG] = ['onPlatformConfig', 100];
    return $events;
  }

  /**
   * {@inheritdoc}
   */
  protected function getEnvironmentIds(Config $config): array {
    return [$config->get(AcquiaCloudPlatform::ACE_ENVIRONMENT_NAME)];
  }

}
