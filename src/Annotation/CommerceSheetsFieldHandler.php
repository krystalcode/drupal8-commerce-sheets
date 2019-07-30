<?php

namespace Drupal\commerce_sheets\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * The annotation object for the field handler plugins.
 *
 * Plugin namespace: Plugin\CommerceSheets\FieldHandler.
 *
 * @Annotation
 */
class CommerceSheetsFieldHandler extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable name of the field handler type.
   *
   * @ingroup plugin_translatable
   *
   * @var \Drupal\Core\Annotation\Translation
   */
  public $label;

  /**
   * A short description of the field handler type.
   *
   * @ingroup plugin_translatable
   *
   * @var \Drupal\Core\Annotation\Translation
   */
  public $description;

  /**
   * An array of field types that the handler supports.
   *
   * @var array
   */
  public $field_types = [];

}
