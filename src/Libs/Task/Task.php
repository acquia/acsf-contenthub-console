<?php

namespace Acquia\Console\Acsf\Libs\Task;

/**
 * Represents a task in ACSF.
 */
class Task {

  public const TYPE_BACKUP = 'backup';
  public const TYPE_DRUSH_CMD = 'drush_cmd';
  public const TYPE_VERSIONS = 'versions';

  private string $taskId;
  private string $siteId;
  private string $type;
  private bool $isRunning;

  /**
   * Constructs a new Task object.
   *
   * @param string $taskId
   *   The task id.
   * @param string $siteId
   *   The site id.
   * @param string $type
   *   The task type.
   * @param bool $isRunning
   *   Indication whether the task is in progress.
   */
  public function __construct(string $taskId, string $siteId, string $type, bool $isRunning) {
    $this->taskId = $taskId;
    $this->siteId = $siteId;
    $this->type = $type;
    $this->isRunning = $isRunning;
  }

  /**
   * Returns the task id.
   *
   * @return string
   *   The task id.
   */
  public function getTaskId(): string {
    return $this->taskId;
  }

  /**
   * Returns the site id.
   *
   * @return string
   *   The site id.
   */
  public function getSiteId(): string {
    return $this->siteId;
  }

  /**
   * Returns the type of the task.
   *
   * @return string
   *   The type of the task.
   */
  public function getType(): string {
    return $this->type;
  }

  /**
   * Returns whether the task is running or not.
   *
   * @return bool
   *   True if it is running.
   */
  public function isRunning(): bool {
    return $this->isRunning;
  }

}
