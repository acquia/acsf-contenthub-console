<?php

namespace Acquia\Console\Acsf\Client;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
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
    $config = [
      'headers' => [
        'Content-Type' => 'application/json',
      ],
      'auth' => [$username, $api_key],
      'base_uri' => $site_name . '/api/v1/',
    ];

    return new AcsfClient($this->logger, self::getGuzzleClient($config));
  }

  /**
   * Creates Guzzle client.
   *
   * @param array $config
   *   Initial data.
   *
   * @return \GuzzleHttp\ClientInterface
   *   GuzzleClient instance.
   */
  public static function getGuzzleClient(array $config): ClientInterface {
    return new Client($config);
  }

}
