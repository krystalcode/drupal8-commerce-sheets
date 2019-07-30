<?php

namespace Drupal\commerce_sheets\FieldHandler;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * The plugin manager for field handler plugins.
 *
 * @see \Drupal\commerce_sheets\Annotation\CommerceSheetsFieldHandler
 * @see \Drupal\commerce_sheets\FieldHandler\FieldHandlerInterface
 * @see plugin_api
 */
class FieldHandlerManager extends DefaultPluginManager implements FieldHandlerManagerInterface {

  /**
   * Constructs a new FieldHandlerManager object.
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
      'Plugin/CommerceSheets/FieldHandler',
      $namespaces,
      $module_handler,
      'Drupal\commerce_sheets\FieldHandler\FieldHandlerInterface',
      'Drupal\commerce_sheets\Annotation\CommerceSheetsFieldHandler'
    );

    $this->alterInfo('commerce_sheets_field_handler_info');
    $this->setCacheBackend($cache_backend, 'commerce_sheets_field_handler');
  }

}
