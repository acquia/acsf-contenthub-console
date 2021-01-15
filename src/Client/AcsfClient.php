<?php

namespace Acquia\Console\Acsf\Client;

use GuzzleHttp\Client;
use Psr\Log\LoggerInterface;

/**
 * A simple client implementing acsf api.
 *
 * @package Acquia\Console\Acsf\Client
 */
class AcsfClient extends Client {

  use ResponseHandlerTrait;

  /**
   * AcsfClient constructor.
   *
   * @param string $username
   *   Acsf username.
   * @param string $api_key
   *   Acsf api key.
   * @param \Psr\Log\LoggerInterface $logger
   *   The symfony console logger.
   * @param array $config
   *   Client configuration.
   */
  public function __construct(string $username, string $api_key, LoggerInterface $logger, array $config = []) {
    $this->logger = $logger;

    $default_conf = [
      'headers' => [
        'Content-Type' => 'application/json',
      ],
      'auth' => [$username, $api_key],
    ];
    $config = array_merge_recursive($default_conf, $config);

    parent::__construct($config);
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
   * @return array
   *   All the existing site the user has permission to view.
   */
  public function listSites(): array {
    $response = $this->getJsonResponseBody($this->get('sites'), 'No sites found.');
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
      return parent::__call($method, $args);
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

}
