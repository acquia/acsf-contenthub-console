<?php

namespace Acquia\Console\Acsf\Client;

use Acquia\Console\Acsf\Libs\Task\TaskException;
use Acquia\Console\Acsf\Libs\Task\Tasks;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\RequestOptions;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;

/**
 * A simple client implementing acsf api.
 *
 * @package Acquia\Console\Acsf\Client
 */
class AcsfClient implements ClientInterface {

  use ResponseHandlerTrait;

  /**
   * GuzzleHttp client.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * AcsfClient constructor.
   *
   * @param \Psr\Log\LoggerInterface $logger
   *   The symfony console logger.
   * @param \GuzzleHttp\ClientInterface $client
   *   GuzzleClient instance.
   */
  public function __construct(LoggerInterface $logger, ClientInterface $client) {
    $this->logger = $logger;
    $this->httpClient = $client;
  }

  /**
   * Sends a get request to the /ping endpoint.
   *
   * @return bool
   *   TRUE if the server is reachable.
   */
  public function ping(): bool {
    $response = $this->get('ping');
    if ($response->getStatusCode() !== 200) {
      $this->logger->error('Server is unreachable.');
      return FALSE;
    }

    return TRUE;
  }

  /**
   * Returns all the sites from the acsf subscription.
   *
   * @param int $limit
   *   Query limit.
   *
   * @return array
   *   All the existing site the user has permission to view.
   */
  public function listSites(int $limit = 1000): array {
    $response = $this->getJsonResponseBody($this->get('sites', [
      RequestOptions::QUERY => ['limit' => $limit],
    ]), 'No sites found.');
    if (!isset($response['sites'])) {
      $this->logger->error('Unknown error occurred.');
      return [];
    }

    return $response['sites'];
  }

  /**
   * Returns detailed information about a site.
   *
   * @param int $site_id
   *   The site id.
   *
   * @return array
   *   Data derived from the response body.
   */
  public function getSiteInfo(int $site_id): array {
    return $this->getJsonResponseBody(
      $this->get("sites/$site_id"),
      'Could not get site info.'
    );
  }

  /**
   * Return information about site backups.
   *
   * @param int $site_id
   *   The site id.
   *
   * @return array
   *   Data derived from the response body.
   */
  public function getBackupsBySiteId(int $site_id): array {
    return $this->getJsonResponseBody(
      $this->get("sites/$site_id/backups"),
      'Could not get backup list.'
    );
  }

  /**
   * Post request to create site backup for a specific site.
   *
   * @param string $site_id
   *   The site id.
   * @param array $options
   *   Request parameters.
   *
   * @return array
   *   Data derived from the response body.
   */
  public function createDatabaseBackup(string $site_id, array $options): array {
    return $this->getJsonResponseBody(
      $this->post("sites/$site_id/backup", $options),
      'Could not create task for backup creation.'
    );
  }

  /**
   * Restore site from backup for a specific site.
   *
   * @param int $site_id
   *   The site id.
   * @param array $options
   *   Request parameters.
   *
   * @return array
   *   Data derived from the response body.
   */
  public function restoreAcsfSiteBackup(int $site_id, array $options): array {
    return $this->getJsonResponseBody(
      $this->post("sites/$site_id/restore", $options),
      'Could not restore site from given backup.'
    );
  }

  /**
   * Delete backup of a specific site.
   *
   * @param int $site_id
   *   The site id.
   * @param int $backup_id
   *   The backup id.
   *
   * @return array
   *   Data derived from the response body.
   */
  public function deleteAcsfSiteBackup(int $site_id, int $backup_id): array {
    return $this->getJsonResponseBody(
      $this->delete("sites/$site_id/backups/$backup_id"),
      'Cannot delete the given backup.'
    );
  }

  /**
   * {@inheritdoc}
   */
  public function __call($method, $args) {
    try {
      return $this->httpClient->__call($method, $args);
    }
    catch (\Exception $e) {
      $this->logger->error($e->getMessage());
    }

    return NULL;
  }

  /**
   * Get request against ACSF wip task status endpoint.
   *
   * @param string $task_id
   *   Task id.
   *
   * @return array
   *   Response.
   */
  public function pingStatusEndpoint(string $task_id): array {
    return $this->getJsonResponseBody($this->get("wip/task/{$task_id}/status"), 'Could not get task status.');
  }

  /**
   * Returns a list of tasks.
   *
   * @return \Acquia\Console\Acsf\Libs\Task\Tasks
   *   The currently running tasks.
   *
   * @throws \Acquia\Console\Acsf\Libs\Task\TaskException
   *   If the tasks cannot be retrieved that indicates a connection or server
   *   error.
   */
  public function getTasks(): Tasks {
    $tasks = $this->getJsonResponseBody($this->get('tasks'), 'Could not retrieve tasks');
    if ($tasks) {
      return new Tasks($tasks);
    }
    throw new TaskException('Tasks cannot be retrieved', TaskException::TASK_RETRIEVAL_ERROR);
  }

  /**
   * {@inheritdoc}
   */
  public function send(RequestInterface $request, array $options = []): ResponseInterface {
    return $this->httpClient->send($request, $options);
  }

  /**
   * {@inheritdoc}
   */
  public function sendAsync(RequestInterface $request, array $options = []): PromiseInterface {
    return $this->httpClient->sendAsync($request, $options);
  }

  /**
   * {@inheritdoc}
   */
  public function request($method, $uri, array $options = []): ResponseInterface {
    return $this->httpClient->request($method, $uri, $options);
  }

  /**
   * {@inheritdoc}
   */
  public function requestAsync($method, $uri, array $options = []): PromiseInterface {
    return $this->httpClient->requestAsync($method, $uri, $options);
  }

  /**
   * {@inheritdoc}
   */
  public function getConfig($option = NULL) {
    return $this->httpClient->getConfig($option);
  }

}
