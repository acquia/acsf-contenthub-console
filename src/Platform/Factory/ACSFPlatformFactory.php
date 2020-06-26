<?php

namespace Acquia\Console\Acsf\Platform\Factory;

use Acquia\Console\Acsf\Client\AcsfClientFactory;
use Acquia\Console\Cloud\Client\AcquiaCloudClientFactory;
use Consolidation\Config\ConfigInterface;
use EclipseGc\CommonConsole\Event\GetPlatformTypeEvent;
use EclipseGc\CommonConsole\Platform\PlatformStorage;
use EclipseGc\CommonConsole\PlatformFactoryInterface;
use EclipseGc\CommonConsole\PlatformInterface;
use EclipseGc\CommonConsole\ProcessRunner;

class ACSFPlatformFactory implements PlatformFactoryInterface {

  /**
   * The Acquia Cloud Client Factory object.
   *
   * @var \Acquia\Console\Cloud\Client\AcquiaCloudClientFactory
   */
  protected $aceFactory;

  /**
   * The Acquia Cloud Site Factory Client Factory object.
   *
   * @var \Acquia\Console\Acsf\Client\AcsfClientFactory
   */
  protected $acsfFactory;

  /**
   * The platform storage object.
   *
   * @var \EclipseGc\CommonConsole\Platform\PlatformStorage
   */
  protected $storage;

  /**
   * AcquiaCloudPlatformFactory constructor.
   *
   * @param \Acquia\Console\Cloud\Client\AcquiaCloudClientFactory $aceFactory
   *   The Acquia Cloud Client Factory object.
   * @param \Acquia\Console\Acsf\Client\AcsfClientFactory $acsfFactory
   *   The Acquia Cloud Site Factory Client Factory object.
   * @param \EclipseGc\CommonConsole\Platform\PlatformStorage $storage
   *   The platform storage service.
   */
  public function __construct(AcquiaCloudClientFactory $aceFactory, AcsfClientFactory $acsfFactory, PlatformStorage $storage) {
    $this->aceFactory = $aceFactory;
    $this->acsfFactory = $acsfFactory;
    $this->storage = $storage;
  }

  /**
   * Create a new AcquiaCloudPlatform.
   *
   * @param \EclipseGc\CommonConsole\Event\GetPlatformTypeEvent $event
   *   The get platform type event.
   * @param \Consolidation\Config\ConfigInterface $config
   *   The platform's configuration.
   * @param \EclipseGc\CommonConsole\ProcessRunner $runner
   *   The process runner.
   *
   * @return \EclipseGc\CommonConsole\PlatformInterface
   *   The AcquiaCloudPlatform object.
   */
  public function create(GetPlatformTypeEvent $event, ConfigInterface $config, ProcessRunner $runner) : PlatformInterface {
    $class = $event->getClass();
    return new $class($config, $runner, $this->storage, $this->aceFactory, $this->acsfFactory);
  }

}
