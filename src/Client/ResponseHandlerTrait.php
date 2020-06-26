<?php

namespace Acquia\Console\Acsf\Client;

use Psr\Http\Message\ResponseInterface;

/**
 * Trait ResponseHandlerTrait.
 *
 * @package Acquia\Console\Acsf\Client
 */
trait ResponseHandlerTrait {

  /**
   * The console logger.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Parses json response body.
   *
   * @param \Psr\Http\Message\ResponseInterface|null $response
   *   The response to parse.
   * @param string $logging_message
   *   The message to log when there is no body available.
   *
   * @return array
   *   Decoded body.
   */
  protected function getJsonResponseBody(?ResponseInterface $response, string $logging_message): array {
    if (is_null($response)) {
      return [];
    }

    $body = json_decode($response->getBody()->getContents(), TRUE);
    if (!is_array($body) || empty($body)) {
      $this->logger->error($logging_message);
      return [];
    }

    return $body;
  }

}
