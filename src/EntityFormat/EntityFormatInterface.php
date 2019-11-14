<?php

namespace Drupal\commerce_sheets\EntityFormat;

use Drupal\Component\Plugin\ConfigurableInterface;
use Drupal\Component\Plugin\DependentPluginInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginFormInterface;

/**
 * Defines the interface for entity format plugins.
 *
 * @I Document entity format plugins
 */
interface EntityFormatInterface extends
  ConfigurableInterface,
  ContainerFactoryPluginInterface,
  DependentPluginInterface,
  PluginFormInterface,
  PluginInspectionInterface {

  /**
   * The main background color used in header rows.
   */
  const HEADER_COLOR = 'CCCCCC';

  /**
   * The secondary, lighter, background color used in header rows.
   */
  const HEADER_SUB_COLOR = 'EEEEEE';

  /**
   * Returns the ID of the format's entity type.
   *
   * @return string
   *   The ID of the entity type.
   */
  public function getEntityTypeId();

  /**
   * Returns the format's entity type.
   *
   * @return \Drupal\Core\Entity\EntityTypeInterface
   *   The entity type object.
   */
  public function getEntityType();

  /**
   * Returns the format's property definitions array.
   *
   * @return array
   *   The property definitions.
   */
  public function getPropertyDefinitions();

  /**
   * Sets the format's property definitions.
   *
   * @param array $property_definitions
   *   An array containing the format's property definitions.
   */
  public function setPropertyDefinitions(array $property_definitions);

}
