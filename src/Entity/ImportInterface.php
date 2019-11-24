<?php

namespace Drupal\commerce_sheets\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface defining an import entity type.
 */
interface ImportInterface extends
  ContentEntityInterface,
  EntityOwnerInterface {

  /**
   * Indicates that imports should be run immediately after created.
   */
  const IMPORT_MODE_ON_CREATION = 0;

  /**
   * Indicates that imports should be run in a queue.
   */
  const IMPORT_MODE_QUEUE = 1;

  /**
   * Gets the Import creation timestamp.
   *
   * @return int
   *   Creation timestamp of the import.
   */
  public function getCreatedTime();

  /**
   * Sets the Import creation timestamp.
   *
   * @param int $timestamp
   *   The import creation timestamp.
   *
   * @return \Drupal\commerce_sheets\ImportInterface
   *   The called import entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Gets the Import's completed timestamp.
   *
   * @return int
   *   The Import's completed timestamp.
   */
  public function getCompletedTime();

  /**
   * Sets the Import's completed timestamp.
   *
   * @param int $timestamp
   *   The Import's completed timestamp.
   *
   * @return $this
   */
  public function setCompletedTime($timestamp);

  /**
   * Gets the Import's state.
   *
   * @return \Drupal\state_machine\Plugin\Field\FieldType\StateItemInterface
   *   The Import's state.
   */
  public function getState();

}
