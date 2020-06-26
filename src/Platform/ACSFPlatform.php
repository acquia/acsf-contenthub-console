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
use EclipseGc\CommonConsole\Platform\PlatformBase;
use EclipseGc\CommonConsole\Platform\PlatformSitesInterface;
use EclipseGc\CommonConsole\Platform\PlatformStorage;
use EclipseGc\CommonConsole\ProcessRunner;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\LogicException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Process\Process;

/**
 * Class AcquiaCloudPlatform
 *
 * @package Acquia\Console\Acsf\Platform
 */
class ACSFPlatform extends PlatformBase implements PlatformSitesInterface {

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

  public function __construct(ConfigInterface $config, ProcessRunner $runner, PlatformStorage $storage, AcquiaCloudClientFactory $aceFactory, AcsfClientFactory $acsfFactory) {
    parent::__construct($config, $runner, $storage);
    $this->aceFactory = $aceFactory;
    $this->acsfFactory = $acsfFactory;
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
        'services' => ['http_client_factory.acquia_cloud']
      ],
      AcquiaCloudPlatform::ACE_ENVIRONMENT_NAME => [
        'question' => [ACSFPlatform::class, 'getEnvironmentQuestion'],
        'services' => ['http_client_factory.acquia_cloud']
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
   * @param \Acquia\Console\Cloud\Client\AcquiaCloudClientFactory $factory
   *
   * @return \Symfony\Component\Console\Question\ChoiceQuestion
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
  public function execute(Command $command, InputInterface $input, OutputInterface $output) : void {
    $aceClient = $this->getAceClient();
    $acsfClient = $this->getAcsfClient();
    $environments = new Environments($aceClient);
    $environment = $environments->get($this->get(AcquiaCloudPlatform::ACE_ENVIRONMENT_NAME));
    $sshUrl = $environment->sshUrl;
    [, $url] = explode('@', $sshUrl);
    [$application] = explode('.', $url);
    $output->writeln(sprintf("Attempting to execute requested command in environment: %s", $environment->uuid));
    // If the local command specifies a uri to run against, just run that one site.
    // @todo check that the uri option is in the available sites.
    if ($input->hasOption('uri') && $uri = $input->getOption('uri')) {
      $commands[] = "echo " . sprintf("Attempting to execute requested command for site: %s", $uri);
      $commands[] = "./vendor/bin/commoncli {$input->__toString()}";
    }
    else {
      // @todo don't expect to get data.
      $sites = json_decode($acsfClient->get('sites')->getBody());
      foreach ($sites->sites as $site) {
        $commands[] = "echo " . sprintf("Attempting to execute requested command for site: %s", $site->domain);
        $commands[] = "./vendor/bin/commoncli {$input->__toString()} --uri={$site->domain}";
      }
    }
    if ($commands) {
      $commands = implode("; ", $commands);
      $process = Process::fromShellCommandline("ssh $sshUrl 'cd /var/www/html/$application; $commands'");
      $this->runner->run($process, $this, $output);
    }
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
   */
  public function getAceClient() : Client {
    return $this->aceFactory->fromCredentials($this->get(AcquiaCloudPlatform::ACE_API_KEY), $this->get(AcquiaCloudPlatform::ACE_API_SECRET));
  }

  /**
   * Gets an AcsfClient for this platform.
   *
   * @return \Acquia\Console\Acsf\Client\AcsfClient
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
      $sites[$site['domain']] = [$site['domain'], static::getPlatformId()];
    }
    return $sites;
  }

}
