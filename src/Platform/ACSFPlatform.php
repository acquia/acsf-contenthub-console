<?php

namespace Acquia\Console\Acsf\Platform;

use Acquia\Console\Acsf\Client\AcsfClient;
use Acquia\Console\Acsf\Client\AcsfClientFactory;
use Acquia\Console\Cloud\Client\AcquiaCloudClientFactory;
use Acquia\Console\Cloud\Platform\AcquiaCloudPlatform;
use AcquiaCloudApi\Connector\Client;
use AcquiaCloudApi\Endpoints\Applications;
use AcquiaCloudApi\Endpoints\Environments;
use Consolidation\Config\Config;
use Consolidation\Config\ConfigInterface;
use EclipseGc\CommonConsole\Event\Traits\PlatformArgumentInjectionTrait;
use EclipseGc\CommonConsole\Platform\PlatformBase;
use EclipseGc\CommonConsole\Platform\PlatformSitesInterface;
use EclipseGc\CommonConsole\Platform\PlatformStorage;
use EclipseGc\CommonConsole\PlatformDependencyInjectionInterface;
use EclipseGc\CommonConsole\ProcessRunner;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\LogicException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Process\Process;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Class AcquiaCloudPlatform.
 *
 * @package Acquia\Console\Acsf\Platform
 */
class ACSFPlatform extends PlatformBase implements PlatformSitesInterface, PlatformDependencyInjectionInterface {

  use PlatformArgumentInjectionTrait;

  const PLATFORM_NAME = "Acquia Cloud Site Factory";

  public const SITEFACTORY_URL = 'acquia.acsf.url';

  public const SITEFACTORY_USER = 'acquia.acsf.user';

  public const SITEFACTORY_TOKEN = 'acquia.acsf.token';

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
   * ACSFPlatform constructor.
   *
   * @param \Consolidation\Config\ConfigInterface $config
   *   The configuration object.
   * @param \EclipseGc\CommonConsole\ProcessRunner $runner
   *   The process runner service.
   * @param \EclipseGc\CommonConsole\Platform\PlatformStorage $storage
   *   The platform storage service.
   * @param \Acquia\Console\Cloud\Client\AcquiaCloudClientFactory $aceFactory
   *   The Acquia Cloud client factory service.
   * @param \Acquia\Console\Acsf\Client\AcsfClientFactory $acsfFactory
   *   The Acquia Cloud Site Factory client factory service.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $dispatcher
   *   The event dispatcher service.
   */
  public function __construct(
    ConfigInterface $config,
    ProcessRunner $runner,
    PlatformStorage $storage,
    AcquiaCloudClientFactory $aceFactory,
    AcsfClientFactory $acsfFactory,
    EventDispatcherInterface $dispatcher
  ) {
    parent::__construct($config, $runner, $storage);

    $this->aceFactory = $aceFactory;
    $this->acsfFactory = $acsfFactory;
    $this->dispatcher = $dispatcher;
  }

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container, ConfigInterface $config, ProcessRunner $runner, PlatformStorage $storage): PlatformDependencyInjectionInterface {
    return new static(
      $config,
      $runner,
      $storage,
      $container->get('http_client_factory.acquia_cloud'),
      $container->get('http_client_factory.acsf'),
      $container->get('event_dispatcher')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function getPlatformId(): string {
    return static::PLATFORM_NAME;
  }

  /**
   * {@inheritdoc}
   */
  public static function getQuestions() {
    $platform_questions = [
      AcquiaCloudPlatform::ACE_API_KEY => new Question("Acquia Cloud API Key? (Instructions: https://docs.acquia.com/acquia-cloud/develop/api/auth/) "),
      AcquiaCloudPlatform::ACE_API_SECRET => new Question("Acquia Cloud Secret? "),
      AcquiaCloudPlatform::ACE_APPLICATION_ID => [
        'question' => [ACSFPlatform::class, 'getApplicationQuestion'],
        'services' => ['http_client_factory.acquia_cloud'],
      ],
      AcquiaCloudPlatform::ACE_ENVIRONMENT_NAME => [
        'question' => [ACSFPlatform::class, 'getEnvironmentQuestion'],
        'services' => ['http_client_factory.acquia_cloud'],
      ],
      // @todo Add validation to this question that includes https://
      self::SITEFACTORY_URL => new Question("Acquia Cloud Site Factory Url: "),
      self::SITEFACTORY_USER => new Question("ACSF Username: "),
      self::SITEFACTORY_TOKEN => new Question("ACSF API Token: "),
    ];
    return $platform_questions;
  }

  /**
   * Creates a question about available ACSF applications for the key/secret.
   *
   * @param \Consolidation\Config\Config $config
   *   The values thus collected.
   * @param \Acquia\Console\Cloud\Client\AcquiaCloudClientFactory $factory
   *   The Acquia cloud client factory.
   *
   * @return \Symfony\Component\Console\Question\ChoiceQuestion
   *   ChoiceQuestion object.
   */
  public static function getApplicationQuestion(Config $config, AcquiaCloudClientFactory $factory) {
    $client = $factory->fromCredentials($config->get(AcquiaCloudPlatform::ACE_API_KEY), $config->get(AcquiaCloudPlatform::ACE_API_SECRET));
    $applications = new Applications($client);
    $options = [];
    /** @var \AcquiaCloudApi\Response\ApplicationResponse $item */
    foreach ($applications->getAll() as $item) {
      if ($item->hosting->type === 'acsf') {
        $options[$item->uuid] = $item->name;
      }
    }
    if (!$options) {
      throw new LogicException("No Acquia Cloud Site Factory applications found.");
    }
    return new ChoiceQuestion("Choose an Application: ", $options);
  }

  /**
   * Creates question for available environments for the selected application.
   *
   * @param \Consolidation\Config\Config $config
   *   Config object.
   * @param \Acquia\Console\Cloud\Client\AcquiaCloudClientFactory $factory
   *   Acquia Cloud Client Factory object.
   *
   * @return \Symfony\Component\Console\Question\ChoiceQuestion
   *   Choice question object.
   */
  public static function getEnvironmentQuestion(Config $config, AcquiaCloudClientFactory $factory) {
    $client = $factory->fromCredentials($config->get(AcquiaCloudPlatform::ACE_API_KEY), $config->get(AcquiaCloudPlatform::ACE_API_SECRET));
    $environment = new Environments($client);
    $options = [];
    /** @var \AcquiaCloudApi\Response\EnvironmentResponse $item */
    foreach ($environment->getAll($config->get(AcquiaCloudPlatform::ACE_APPLICATION_ID)) as $item) {
      $options[$item->uuid] = $item->name;
    }
    return new ChoiceQuestion("Choose an Environment: ", $options);
  }

  /**
   * {@inheritdoc}
   */
  public function execute(Command $command, InputInterface $input, OutputInterface $output) : int {
    $aceClient = $this->getAceClient();
    $environments = new Environments($aceClient);
    $env_id = $this->get(AcquiaCloudPlatform::ACE_ENVIRONMENT_NAME);
    $environment = $environments->get($env_id);
    $sshUrl = $environment->sshUrl;
    [, $url] = explode('@', $sshUrl);
    [$application] = explode('.', $url);
    $output->writeln(sprintf("Attempting to execute requested command in environment: %s", $environment->uuid));
    $commands = [];
    if ($input->hasOption('uri') && $uri = $input->getOption('uri')) {
      if (!$this->isValidUri($uri)) {
        $output->writeln("<error>The provided uri '$uri' was invalid. There's no such acsf site.</error>");
        return 1;
      }
      $sites = [$uri];
    }
    else {
      $sites = $this->getPlatformSites();
      if (!$sites) {
        $output->writeln('<warning>No sites available. Exiting...</warning>');
        return 2;
      }
      $sites = array_column($sites, 'uri');
    }

    $vendor_path = $this->get('acquia.cloud.environment.vendor_paths');
    $args = $this->dispatchPlatformArgumentInjectionEvent($input, $sites, $command);
    foreach ($sites as $site) {
      $commands[] = "echo " . sprintf("Attempting to execute requested command for site: %s", $site);
      $commands[] = "cd {$vendor_path[$env_id]}";
      $commands[] = "./vendor/bin/commoncli {$args[$site]->__toString()}";
    }

    if ($commands) {
      $commands = implode("; ", $commands);
      $process = new Process("ssh $sshUrl 'cd /var/www/html/$application; $commands'");
      return $this->runner->run($process, $this, $output);
    }
    // If no commands were passed, then exit without errors.
    return 0;
  }

  /**
   * {@inheritdoc}
   */
  public function out(Process $process, OutputInterface $output, string $type, string $buffer) : void {
    if (Process::ERR === $type) {
      if (substr(trim($buffer), -32) === ": Permission denied (publickey).") {
        $output->writeln("<warning>Your SSH key is likely missing from Acquia Cloud. Follow this document to troubleshoot it: </warning><url>https://docs.acquia.com/acquia-cloud/manage/ssh/enable/add-key/</url>");
      }
    }
  }

  /**
   * Gets an Ace Client for this platform.
   *
   * @return \AcquiaCloudApi\Connector\Client
   *   Ace Client object.
   */
  public function getAceClient() : Client {
    return $this->aceFactory->fromCredentials($this->get(AcquiaCloudPlatform::ACE_API_KEY), $this->get(AcquiaCloudPlatform::ACE_API_SECRET));
  }

  /**
   * Gets an AcsfClient for this platform.
   *
   * @return \Acquia\Console\Acsf\Client\AcsfClient
   *   Acsf Client object.
   */
  public function getAcsfClient() : AcsfClient {
    return $this->acsfFactory->fromCredentials($this->get(self::SITEFACTORY_USER), $this->get(self::SITEFACTORY_TOKEN), $this->get(self::SITEFACTORY_URL));
  }

  /**
   * {@inheritdoc}
   */
  public function getPlatformSites(): array {
    $sites = [];
    foreach ($this->getAcsfClient()->listSites() as $site) {
      $sites[$site['domain']] = [
        'uri' => $this->prefixDomain($site['domain'], $site['id']),
        'platform_id' => static::getPlatformId(),
      ];
    }
    return $sites;
  }

  /**
   * Prefix uris with http protocol.
   *
   * @param string $domain
   *   Plain domain.
   * @param string $site_id
   *   Environment id.
   *
   * @return string
   *   Uri with http:// or https:// prefix.
   */
  public function prefixDomain(string $domain, string $site_id): string {
    $http_conf = $this->get(AcquiaCloudPlatform::ACE_SITE_HTTP_PROTOCOL);
    $prefix = isset($http_conf[$site_id]) ? $http_conf[$site_id] : 'https://';
    return $prefix . $domain;
  }

  /**
   * Compares the provided uri with the acsf site list.
   *
   * @param string $uri
   *   The uri to check.
   *
   * @return bool
   *   TRUE if the uri is valid and exists within the current platform.
   */
  protected function isValidUri(string $uri): bool {
    $sites = $this->getAcsfClient()->listSites();
    // Fix for issue arisen from protocol attached to uri.
    $sites_uri = array_map(function ($site) {
      // Attach protocol to the domain for each site.
      return $this->prefixDomain($site['domain'], $site['id']);
    }, $sites);
    return in_array($uri, $sites_uri, TRUE);
  }

}
