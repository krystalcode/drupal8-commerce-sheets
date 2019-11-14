<?php

namespace Drupal\commerce_sheets\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * The annotation object for the entity format plugins.
 *
 * Plugin namespace: Plugin\CommerceSheets\EntityFormat.
 *
 * @Annotation
 */
class CommerceSheetsEntityFormat extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable name of the entity format type.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $label;

  /**
   * A short description of the entity format type.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $description;

  /**
   * An array of entity types for which the plugin can define a format for.
   *
   * All entity types will be supported if left empty.
   *
   * @var array
   */
  public $entity_types = [];

}
