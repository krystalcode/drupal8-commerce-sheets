<?php

namespace Drupal\commerce_sheets\FieldHandler;

use Drupal\Component\Plugin\Discovery\CachedDiscoveryInterface;
use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Cache\CacheableDependencyInterface;

/**
 * Defines the interface for the Commerce Sheets field handler plugin manager.
 */
interface FieldHandlerManagerInterface extends
  PluginManagerInterface,
  CachedDiscoveryInterface,
  CacheableDependencyInterface {}
