<?php

namespace Acquia\Console\Acsf\Libs\Task;

/**
 * Object representation of tasks returned from /api/v1/tasks.
 */
class Tasks {

  /**
   * The raw list of tasks.
   *
   * @var array
   *   The list of tasks, running and completed.
   */
  private array $tasks;

  /**
   * Constructs a Tasks object.
   *
   * Expected format matches the response from {acsf_url}/api/v1/tasks.
   * Example:
   * {
   *   "id": "1099756",
   *   "parent": "0",
   *   "name": "SiteArchive 356",
   *   "group_name": "SiteArchive liftcontent_01",
   *   "priority": "2",
   *   "object_id": "1099756",
   *   "status": "4",
   *   "wake": "1",
   *   "added": "1645812585",
   *   "started": "1645812586",
   *   "completed": "0",
   *   "taken": "1645812591",
   *   "lease": "301",
   *   "max_run_time": "300",
   *   "paused": "0",
   *   "concurrency_exceeded": "0",
   *   "error_message": "",
   *   "nid": "356",
   *   "uid": "91",
   *   "class": "Acquia\\SfBackup\\SiteArchiveV2D8"
   * }
   *
   * @param array $tasks
   *   Raw array of the response returned from /api/v1/tasks.
   */
  public function __construct(array $tasks) {
    $this->tasks = $tasks;
  }

  /**
   * Returns a list of Tasks.
   *
   * @param string $required_type
   *   (Optional) Only return Tasks from required types.
   *
   * @return Task[]
   *   The array of tasks.
   */
  public function getTasks(string $required_type = ''): array {
    $tasks = [];
    foreach ($this->tasks as $task) {
      $type = $this->getType($task['class']);
      if ($required_type && $type !== $required_type) {
        continue;
      }
      $tasks[] = new Task(
        $task['id'],
        $task['nid'],
        $this->getType($task['class']),
        $task['completed'] === "0",
      );
    }
    return $tasks;
  }

  /**
   * Returns the type based on the class marked in the response.
   *
   * @param string $class
   *   The class handling the give task in ACSF.
   *
   * @return string
   *   The type based on the class.
   */
  protected function getType(string $class): string {
    $typeMapping = [
      'Acquia\\SfBackup\\SiteArchiveV2D8' => Task::TYPE_BACKUP,
      'Acquia\\Sf\\SfDrushCommandWip' => Task::TYPE_DRUSH_CMD,
      'Acquia\\Sf\\SfVersions' => Task::TYPE_VERSIONS,
    ];
    return $typeMapping[$class] ?? 'undefined';
  }

}
