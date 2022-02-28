<?php

namespace Acquia\Console\Acsf\Libs\Task;

/**
 * Represents a task in ACSF.
 */
class Task {

  public const TYPE_BACKUP = 'backup';
  public const TYPE_DRUSH_CMD = 'drush_cmd';
  public const TYPE_VERSIONS = 'versions';

  /**
   * The task id.
   *
   * @var string
   */
  private string $taskId;

  /**
   * The site id.
   *
   * @var string
   */
  private string $siteId;

  /**
   * The task type.
   *
   * @var string
   */
  private string $type;

  /**
   * Indicator whether the task is running or not.
   *
   * Warning: this can be outdated, so it is advised to refresh the request at
   * certain intervals.
   *
   * @var bool
   */
  private bool $isRunning;

  /**
   * Constructs a new Task object.
   *
   * @param string $task_id
   *   The task id.
   * @param string $site_id
   *   The site id.
   * @param string $type
   *   The task type.
   * @param bool $is_running
   *   Indication whether the task is in progress.
   */
  public function __construct(string $task_id, string $site_id, string $type, bool $is_running) {
    $this->taskId = $task_id;
    $this->siteId = $site_id;
    $this->type = $type;
    $this->isRunning = $is_running;
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
