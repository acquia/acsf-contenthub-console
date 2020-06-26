<?php

namespace Acquia\Console\Acsf\Client;

use Psr\Log\LoggerInterface;

/**
 * Responsible for constructing acsf client.
 *
 * @package Acquia\Console\Acsf\Client
 */
class AcsfClientFactory {

  /**
   * The console logger.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * AcsfClientFactory constructor.
   *
   * @param \Psr\Log\LoggerInterface $logger
   *   The symfony console logger.
   */
  public function __construct(LoggerInterface $logger) {
    $this->logger = $logger;
  }

  /**
   * Constructs an AcsfClient.
   *
   * @param string $username
   *   Acsf username.
   * @param string $api_key
   *   Acsf api key.
   * @param string $site_name
   *   Acsf site name.
   *
   * @return \Acquia\Console\Acsf\Client\AcsfClient
   *   The instantiated client.
   */
  public function fromCredentials(string $username, string $api_key, string $site_name): AcsfClient {
    return new AcsfClient($username, $api_key, $this->logger, ['base_uri' => $site_name . '/api/v1/']);
  }

}
