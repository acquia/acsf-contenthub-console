<?php

namespace Acquia\Console\Acsf\Command;

use Acquia\Console\Acsf\Platform\ACSFPlatform;
use EclipseGc\CommonConsole\Platform\PlatformCommandTrait;
use EclipseGc\CommonConsole\PlatformCommandInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Class AcsfCommandBase.
 *
 * @package Acquia\Console\Acsf\Command
 */
abstract class AcsfCommandBase extends Command implements PlatformCommandInterface {

  use PlatformCommandTrait;

  /**
   * Acsf client.
   *
   * @var \Acquia\Console\Acsf\Client\AcsfClient
   */
  protected $acsfClient;

  /**
   * The platform in hand of type ACSF.
   *
   * @var \Acquia\Console\Acsf\Platform\ACSFPlatform
   */
  protected $platform;

  /**
   * AcquiaCloudCommandBase constructor.
   *
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher.
   * @param string|null $name
   *   The command name.
   */
  public function __construct(EventDispatcherInterface $event_dispatcher, string $name = NULL) {
    parent::__construct($name);

    $this->dispatcher = $event_dispatcher;
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
  protected function initialize(InputInterface $input, OutputInterface $output): void {
    $this->platform = $this->getPlatform('source');
    if (!$this->platform) {
      throw new \Exception('Platform is not available.');
    }

    $this->acsfClient = $this->platform->getAcsfClient();
  }

  /**
   * Returns Acsf site ids and names.
   *
   * @return array
   *   Array where the key is the acsf site id and value is the site name.
   */
  protected function getAcsfSites(): array {
    $sites = [];

    foreach ($this->acsfClient->listSites() as $site) {
      $sites[$site['id']] = $site['site'];
    }

    return $sites;
  }

}
