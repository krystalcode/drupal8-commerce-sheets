<?php

namespace Drupal\commerce_sheets\Entity;

use Drupal\commerce\Entity\CommerceBundleEntityInterface;
use Drupal\Core\Entity\EntityDescriptionInterface;

/**
 * Defines the interface for Import Types.
 */
interface ImportTypeInterface extends
  CommerceBundleEntityInterface,
  EntityDescriptionInterface {}
