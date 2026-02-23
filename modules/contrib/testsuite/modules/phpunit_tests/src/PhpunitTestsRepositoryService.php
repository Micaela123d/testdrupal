<?php

namespace Drupal\phpunit_tests;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Database\Connection;

/**
 * Phpunit Tests Services.
 */
class PhpunitTestsRepositoryService {

  /**
   * The database service.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * The time interface.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $timeInterface;

  /**
   * PhpunitTestsResourceService constructor.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   * @param \Drupal\Component\Datetime\TimeInterface $timeInterface
   *   The load resource service.
   */
  public function __construct(
    Connection $connection,
    TimeInterface $timeInterface,
  ) {
    $this->connection = $connection;
    $this->timeInterface = $timeInterface;
  }

  /**
   * Gathers a list of groups.
   *
   * @return array
   *   List of groups.
   */
  public function getGroups() {
    $db = $this->connection->query("SELECT id, name FROM {phpunit_test_group}")->fetchAllAssoc('id', \PDO::FETCH_ASSOC);
    $groups = [];
    foreach ($db as $item) {
      $groups[$item['id']] = $item['name'];
    }
    return $groups;
  }

  /**
   * Gathers a list of uniquely defined database library module names.
   *
   * @return array
   *   List of uniquely defined database library module names.
   */
  public function getModules() {
    return $this->connection->query("SELECT DISTINCT([module]) FROM {phpunit_test_item} ORDER BY [module]")->fetchAllKeyed(0, 0);
  }

  /**
   * Gathers a list of uniquely defined database library module names.
   *
   * @return array
   *   List of uniquely defined database library module names.
   */
  public function getItemsModules() {
    return $this->connection->query("SELECT DISTINCT([module]) FROM {phpunit_test_group_item} ORDER BY [module]")->fetchAllKeyed(0, 0);
  }

  /**
   * Creates a group entry.
   *
   * @param string $name
   *   The group name.
   *
   * @return bool
   *   True or false depending on if insert succeeds.
   */
  public function createGroup($name) {
    $db = $this->connection->insert('phpunit_test_group')
      ->fields(
        [
          'name' => $name,
          'created' => $this->timeInterface->getRequestTime(),
        ]
      )
      ->execute();
    if ($db) {
      return TRUE;
    }
    else {
      return FALSE;
    }
  }

  /**
   * Creates a group entry.
   *
   * @param array $group
   *   The group array.
   *
   * @return bool
   *   True or false depending on if insert succeeds.
   */
  public function createGroupItem($group) {
    $db = $this->connection->insert('phpunit_test_group_item')
      ->fields(
        [
          'phpunit_test_group_id' => $group['id'],
          'area' => $group['area'],
          'module' => $group['module'],
          'directory' => $group['directory'],
          'test' => $group['test'],
          'created' => $this->timeInterface->getRequestTime(),
        ]
      )
      ->execute();
    if ($db) {
      return TRUE;
    }
    else {
      return FALSE;
    }
  }

  /**
   * Creates a log entry.
   *
   * @param string $area
   *   The area the module is located. Core, contrib or custom.
   * @param string $module
   *   The module name.
   * @param string $directory
   *   The test directory. Unit, Functional etc.
   * @param string $test
   *   The absolute path the tests.
   * @param array $data
   *   The area where the module is. Core, contrib or custom.
   *
   * @return bool
   *   True or false depending on if insert succeeds.
   */
  public function createLog($area, $module, $directory, $test, $data) {
    $db1 = $this->connection->insert('phpunit_test')
      ->fields(
        [
          'created' => $this->timeInterface->getRequestTime(),
        ]
      )
      ->execute();

    $db2 = $this->connection->insert('phpunit_test_item')
      ->fields(
        [
          'phpunit_test_id' => $db1,
          'area' => $area,
          'module' => $module,
          'directory' => $directory,
          'test' => $test,
          'error_report' => $data,
        ]
      )
      ->execute();

    if ($db1 && $db2) {
      return TRUE;
    }
    else {
      return FALSE;
    }
  }

}
