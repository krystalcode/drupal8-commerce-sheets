<?php

namespace Drupal\commerce_sheets\EntityFormat;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * The plugin manager for entity format plugins.
 *
 * @see \Drupal\commerce_sheets\Annotation\CommerceSheetsEntityFormat
 * @see \Drupal\commerce_sheets\EntityFormat\EntityFormatInterface
 * @see plugin_api
 */
class EntityFormatManager extends DefaultPluginManager implements
  EntityFormatManagerInterface {

  /**
   * Constructs a new EntityFormatManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   */
  public function __construct(
    \Traversable $namespaces,
    CacheBackendInterface $cache_backend,
    ModuleHandlerInterface $module_handler
  ) {
    parent::__construct(
      'Plugin/CommerceSheets/EntityFormat',
      $namespaces,
      $module_handler,
      'Drupal\commerce_sheets\EntityFormat\EntityFormatInterface',
      'Drupal\commerce_sheets\Annotation\CommerceSheetsEntityFormat'
    );

    $this->alterInfo('commerce_sheets_entity_format_info');
    $this->setCacheBackend($cache_backend, 'commerce_sheets_entity_format');
  }

}
