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
  CacheableDependencyInterface {}
