<?php

namespace Acquia\Console\Acsf\EventSubscriber\Platform;

use Acquia\Console\Acsf\Client\AcsfClientFactory;
use Acquia\Console\Acsf\Platform\ACSFPlatform;
use Acquia\Console\Cloud\Platform\AcquiaCloudPlatform;
use Consolidation\Config\Config;
use EclipseGc\CommonConsole\CommonConsoleEvents;
use EclipseGc\CommonConsole\Event\PlatformConfigEvent;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class AcsfPlatformConfigHttpProtocol.
 *
 * @package Acquia\Console\Acsf\EventSubscriber\Platform
 */
class AcsfPlatformConfigHttpProtocol implements EventSubscriberInterface {

  /**
   * Platform types to handle within this subscriber.
   *
   * @var array
   *   Platform types.
   */
  protected $platformTypes = [
    'Acquia Cloud Site Factory'
  ];

  /**
   * The Acquia Cloud Site Factory Client Factory object.
   *
   * @var \Acquia\Console\Acsf\Client\AcsfClientFactory
   */
  protected $acsfFactory;

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    $events[CommonConsoleEvents::PLATFORM_CONFIG] = ['onPlatformConfig', 99];
    return $events;
  }

  /**
   * PlatformConfigAppFinderCloud constructor.
   *
   * @param \Acquia\Console\Acsf\Client\AcsfClientFactory $acsfFactory
   *   Acsf Factory.
   */
  public function __construct(AcsfClientFactory $acsfFactory) {
    $this->acsfFactory = $acsfFactory;
  }

  /**
   * Writes info about Http protocol into the platform config.
   *
   * @param \EclipseGc\CommonConsole\Event\PlatformConfigEvent $event
   *   PlatformConfigEvent instance.
   *
   * @throws \Exception
   */
  public function onPlatformConfig(PlatformConfigEvent $event) {
    $input = $event->getInput();
    $output = $event->getOutput();
    $config = $event->getConfig();
    $platform_type = $config->get('platform.type');

    if (!in_array($platform_type, $this->platformTypes, TRUE)) {
      return;
    }

    $uris = $this->getAcsfSites($config);

    if (!$uris) {
      throw new \Exception('Cannot find platform sites.');
    }
    $output->writeln('<info>"We assume that all your sites are using HTTPS."</info>');
    $helper = new QuestionHelper();
    $confirm = new ConfirmationQuestion('<warning>Is this assumption correct?</warning>');
    $answer = $helper->ask($input, $output, $confirm);

    if ($answer) {
      array_walk($uris, function (&$uri) {
        $uri = 'https://';
      });

      $config->set(AcquiaCloudPlatform::ACE_SITE_HTTP_PROTOCOL, $uris);
      return;
    }

    $choice = new ChoiceQuestion('Please pick which sites are running on HTTP:', $uris);
    $choice->setMultiselect(TRUE);
    $answer = $helper->ask($input, $output, $choice);

    foreach ($uris as $site_id => $uri) {
      if (in_array($uri, $answer, TRUE)) {
        $uris[$site_id] = 'http://';
        continue;
      }
      $uris[$site_id] = 'https://';
    }

    $config->set(AcquiaCloudPlatform::ACE_SITE_HTTP_PROTOCOL, $uris);
  }

  /**
   * Returns Acsf sites.
   *
   * @param \Consolidation\Config\Config $config
   *   Config object.
   *
   * @return array
   *   Array containing site URI's and keys are ACSF site ids.
   */
  protected function getAcsfSites(Config $config): array {
    $acsf_client = $this
      ->acsfFactory
      ->fromCredentials($config->get(ACSFPlatform::SITEFACTORY_USER), $config->get(ACSFPlatform::SITEFACTORY_TOKEN), $config->get(ACSFPlatform::SITEFACTORY_URL));
    $acsf_sites = $acsf_client->listSites();

    $sites = [];
    foreach ($acsf_sites as $site) {
      $sites["{$site['id']}"] = $site['domain'];
    }

    return $sites;
  }

}
