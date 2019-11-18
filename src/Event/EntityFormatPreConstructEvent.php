<?php

namespace Drupal\commerce_sheets\Event;

use Symfony\Component\EventDispatcher\Event;

/**
 * The event object for events that allow altering format plugin definitions.
 */
class EntityFormatPreConstructEvent extends Event {

  /**
   * The format's plugin ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The format's plugin configuration.
   *
   * @var array
   */
  protected $configuration;

  /**
   * Constructs a new EntityFormatPreConstructEvent object.
   *
   * @param string $id
   *   The format's plugin ID.
   * @param array $configuration
   *   The format's plugin configuration.
   */
  public function __construct($id, array $configuration) {
    $this->id = $id;
    $this->configuration = $configuration;
  }

  /**
   * Returns the format's plugin ID.
   *
   * @return string
   *   The plugin ID.
   */
  public function getPluginId() {
    return $this->id;
  }

  /**
   * Sets the format's plugin ID.
   *
   * @param string $id
   *   The plugin ID.
   */
  public function setPluginId($id) {
    $this->id = $id;
  }

  /**
   * Returns the format's plugin configuration.
   *
   * @return array
   *   The plugin configuration.
   */
  public function getConfiguration() {
    return $this->configuration;
  }

  /**
   * Sets the format's plugin configuration.
   *
   * @param array $configuration
   *   The plugin configuration.
   */
  public function setConfiguration(array $configuration) {
    $this->configuration = $configuration;
  }

}
