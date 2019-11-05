<?php

namespace Drupal\commerce_sheets\Entity;

use Drupal\commerce\Entity\CommerceBundleEntityBase;

/**
 * Defines the default Import Type entity class.
 *
 * @ConfigEntityType(
 *   id = "commerce_sheets_import_type",
 *   label = @Translation("Import type"),
 *   label_collection = @Translation("Import types"),
 *   label_singular = @Translation("import type"),
 *   label_plural = @Translation("import types"),
 *   label_count = @PluralTranslation(
 *     singular = "@count import type",
 *     plural = "@count import types",
 *   ),
 *   handlers = {
 *     "access" = "Drupal\commerce_sheets\Entity\Access\ImportType",
 *     "list_builder" = "Drupal\commerce_sheets\Entity\ListBuilder\ImportType",
 *     "form" = {
 *       "add" = "Drupal\commerce\Form\CommerceBundleEntityFormBase",
 *       "edit" = "Drupal\commerce\Form\CommerceBundleEntityFormBase",
 *       "duplicate" = "Drupal\commerce\Form\CommerceBundleEntityFormBase",
 *       "delete" = "Drupal\commerce\Form\CommerceBundleEntityDeleteFormBase"
 *     },
 *     "route_provider" = {
 *       "default" = "Drupal\entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   admin_permission = "administer commerce_sheets_import_type",
 *   bundle_of = "commerce_sheets_import",
 *   config_prefix = "commerce_sheets_import_type",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid",
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "uuid",
 *     "description",
 *   },
 *   links = {
 *     "add-form" = "/admin/commerce/config/sheets/import-types/add",
 *     "edit-form" = "/admin/commerce/config/sheets/import-types/{commerce_sheets_import_type}/edit",
 *     "duplicate-form" = "/admin/commerce/config/sheets/import-types/{commerce_sheets_import_type}/duplicate",
 *     "delete-form" = "/admin/commerce/config/sheets/import-types/{commerce_sheets_import_type}/delete",
 *     "collection" = "/admin/commerce/config/sheets/import-types",
 *   }
 * )
 */
class ImportType extends CommerceBundleEntityBase implements
  ImportTypeInterface {

  /**
   * A brief description of this import type.
   *
   * @var string
   */
  protected $description;

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->description;
  }

  /**
   * {@inheritdoc}
   */
  public function setDescription($description) {
    $this->description = $description;
    return $this;
  }

}
