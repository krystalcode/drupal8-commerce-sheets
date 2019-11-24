<?php

namespace Drupal\commerce_sheets\EntityFormat;

/**
 * Base class for all content entity format plugins.
 *
 * Content entity format plugins define formats for content entities.
 *
 * @I Add a check in the constructor that `entity_bundle` is defined
 * @I Review/rearchitecture using base/bundle field definitions
 */
abstract class ContentEntityFormatBase extends EntityFormatBase {

  /**
   * Array containing the base field definitions for the format's entity type.
   *
   * It contains the filtered and sorted definitions, that is, only the field
   * that are contained in the format - not the definitions for all base field
   * defined by the entity type.
   *
   * @var \Drupal\Core\Field\FieldDefinitionInterface[]
   */
  protected $baseFieldDefinitions;

  /**
   * Array containing the bundle definitions for the format's entity type.
   *
   * It contains the filtered and sorted definitions, that is, only the field
   * that are contained in the format - not the definitions for all bundle field
   * defined by the entity type.
   *
   * @var \Drupal\Core\Field\FieldDefinitionInterface[]
   */
  protected $bundleFieldDefinitions;

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      // Exclude properties that we most commonly do not want them to be edited
      // via the spreadsheet.
      'excluded_properties' => [
        'names' => [
          'changed',
          'content_translation_outdated',
          'content_translation_source',
          'content_translation_status',
          'content_translation_uid',
          'created',
          'default_langcode',
          'langcode',
          'metatag',
          'uid',
          'uuid',
        ],
      ],
      // The bundle property should also be protected, on top of the ID. Moving
      // bundled entities from one type to another is complicatd and not
      // supported.
      'protected_properties' => [
        'entity_keys' => [
          'id',
          'bundle',
        ],
      ],
      // Whether the format should include a section with base fields. Currently
      // they are included anyway; this setting has no effect.
      // @I Define behavior when the base fields section is set to FALSE
      'base_fields' => TRUE,
      // Whether the format should include a section with bundle fields.
      'bundle_fields' => TRUE,
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  protected function initPropertyDefinitions() {
    $this->propertyDefinitions = $this->entityFieldManager
      ->getFieldMap()[$this->getEntityType()->id()];

    $this->initBaseFieldDefinitions();
    $this->initBundleFieldDefinitions();

    // Add the section for the associated entities, if required.
    if (!$this->configuration['associated_entities']) {
      return;
    }

    $associated_start_column = count($this->baseFieldDefinitions) + 1;
    if (!empty($this->bundleFieldDefinitions)) {
      $associated_start_column += count($this->bundleFieldDefinitions);
    }

    $this->sections[] = $this->createAssociatedEntitiesSection(
      $this->configuration['associated_entities'],
      $associated_start_column
    );
  }

  /**
   * Initializes the base field definitions for the plugin's entity type.
   *
   * @see \Drupal\commerce_sheets\EntityFormat\EntityFormatBase::initPropertyDefinitions()
   */
  protected function initBaseFieldDefinitions() {
    // Create the section for the base fields.
    $all_base_field_definitions = $this->entityFieldManager
      ->getBaseFieldDefinitions($this->getEntityType()->id());
    $all_base_fields = array_keys($all_base_field_definitions);

    $base_fields = array_values(
      $this->sortProperties(
        $this->filterProperties($all_base_fields)
      )
    );
    $this->sections = [
      [
        'type' => 'properties',
        'properties' => $base_fields,
        'start' => 1,
        'size' => count($base_fields),
      ],
    ];

    // Store the filtered and sorted base field definitions array.
    $this->baseFieldDefinitions = [];
    foreach ($base_fields as $field) {
      $this->baseFieldDefinitions[$field] = $all_base_field_definitions[$field];
    }
  }

  /**
   * Initializes the bundle field definitions for the plugin's entity type.
   *
   * @see \Drupal\commerce_sheets\EntityFormat\EntityFormatBase::initPropertyDefinitions()
   */
  protected function initBundleFieldDefinitions() {
    if (!$this->configuration['bundle_fields']) {
      return;
    }

    // Create the section for the bundle fields.
    $all_field_definitions = $this->entityFieldManager->getFieldDefinitions(
      $this->getEntityType()->id(),
      $this->configuration['entity_bundle']
    );
    $all_base_field_definitions = $this->entityFieldManager
      ->getBaseFieldDefinitions($this->getEntityType()->id());
    $all_bundle_field_definitions = array_diff_key(
      $all_field_definitions,
      $all_base_field_definitions
    );
    $all_bundle_fields = array_keys($all_bundle_field_definitions);

    $bundle_fields = array_values(
      $this->sortProperties(
        $this->filterProperties($all_bundle_fields)
      )
    );

    if (!$bundle_fields) {
      return;
    }

    $this->sections[] = [
      'type' => 'properties',
      'properties' => $bundle_fields,
      'start' => count($this->baseFieldDefinitions) + 1,
      'size' => count($bundle_fields),
    ];

    // Store the filtered and sorted bundle field definitions array.
    $this->bundleFieldDefinitions = [];
    foreach ($bundle_fields as $field) {
      $this->bundleFieldDefinitions[$field] = $all_field_definitions[$field];
    }
  }

  /**
   * {@inheritdoc}
   *
   * @I Use `protected` instead of `locked` everywhere to avoid confusion
   */
  protected function getPropertyPluginDefinitionForEntityKey(
    $property,
    array $definition
  ) {
    $type = NULL;
    $locked = empty($definition['protected']) ? NULL : $definition['protected'];

    // Special cases.
    switch ($property) {
      // @I Review decision to use Integer property plugin for entity IDs
      case $this->getEntityType()->getKey('id'):
        $type = 'integer';
        $locked = TRUE;
        break;

      // The Bundle property plugin makes the bundle property protected.
      case $this->getEntityType()->getKey('bundle'):
        $type = 'bundle';
        break;
    }

    return $this->createPropertyPluginDefinition($type, $locked);
  }

  /**
   * Returns the filtered and sorted base field definitions for the format.
   *
   * The field definitions array is initialized when the plugin object is
   * instantiated by the `initPropertyDefinitions` method.
   *
   * @return \Drupal\Core\Field\FieldDefinitionInterface[]
   *   The base field definitions.
   */
  public function getBaseFieldDefinitions() {
    return $this->baseFieldDefinitions;
  }

  /**
   * Returns the filtered and sorted bundle field definitions for the format.
   *
   * The field definitions array is initialized when the plugin object is
   * instantiated by the `initPropertyDefinitions` method.
   *
   * @param string $bundle
   *   The bundle of the entity being processed.
   *
   * @return \Drupal\Core\Field\FieldDefinitionInterface[]
   *   The bundle field definitions.
   */
  public function getBundleFieldDefinitions($bundle) {
    return $this->bundleFieldDefinitions;
  }

}
