<?php

namespace Drupal\commerce_sheets\EntityFormat;

use Drupal\Component\Plugin\Discovery\CachedDiscoveryInterface;
use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Cache\CacheableDependencyInterface;

/**
 * Defines the interface for the Commerce Sheets entity format plugin manager.
 */
interface EntityFormatManagerInterface extends
  PluginManagerInterface,
  CachedDiscoveryInterface,
  CacheableDependencyInterface {

  /**
   * The name of the spreadsheet's custom property where the format is stored.
   *
   * @I Review whether this is the right place to store the custom property name
   */
  const SPREADSHEET_CUSTOM_PROPERTY_FORMAT = 'CommerceSheetsFormat';

  /**
   * Returns a serialized representation of the given plugin's definition.
   *
   * It is used for adding the plugin defininition as a custom property to the
   * spreadsheet when writing so that the format can be loaded when reading it.
   *
   * @param \Drupal\commerce_sheets\EntityFormat\EntityFormatInterface $format
   *   The format for which to generate the serialized representation.
   *
   * @return string
   *   The serialized representation of the plugin's definition.
   */
  public function serializePluginDefinition(EntityFormatInterface $format);

  /**
   * Returns the plugin definition for the given serialized representation.
   *
   * @param string $definition
   *   The serialized plugin definition.
   *
   * @return array
   *   The deserialized array representation of the plugin's definition.
   *
   * @I Document the structure of the deserialized plugin definition
   *
   * @see \Drupal\commerce_sheets\EntityFormat\EntityFormatManagerInterface::serializePluginDefinition()
   */
  public function deserializePluginDefinition($definition);

}
