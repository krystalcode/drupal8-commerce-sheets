<?php

namespace Drupal\commerce_sheets\EntityFormat;

use Drupal\commerce_sheets\FieldHandler\FieldHandlerManagerInterface;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\StringTranslation\TranslationInterface;

use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for all entity format plugins.
 *
 * @I Review order of methods
 * @I Add public methods to the interface
 */
abstract class EntityFormatBase extends PluginBase implements
  EntityFormatInterface {

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * The Commerce Sheets entity format plugin manager.
   *
   * @var \Drupal\commerce_sheets\EntityFormat\EntityFormatManagerInterface
   */
  protected $entityFormatManager;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The type of the entities that the format is managing.
   *
   * @var \Drupal\Core\Entity\EntityTypeInterface
   */
  protected $entityType;

  /**
   * The Commerce Sheets field handler plugin manager.
   *
   * @var \Drupal\commerce_sheets\FieldHandler\FieldHandlerManagerInterface
   */
  protected $fieldHandlerManager;

  /**
   * An array containing information about the properties of the entity type.
   *
   * @var array
   *
   * @I Add more documentation on the `propertyDefinitions` property
   */
  protected $propertyDefinitions;

  /**
   * An array containing the different sections of the format.
   *
   * @var array
   *
   * @I Add more documentation on the `sections` property
   */
  protected $sections;

  /**
   * Initializes the property definitions for the plugin's entity type.
   *
   * This method is called in the constructor when a plugin is instantiated. The
   * definitions should be stored in the `propertyDefinitions` property.
   *
   * The format sections for the properties and the associated entities should
   * also be initialized in this method and stored in the `sections` property.
   *
   * @I Move section initialization to a method `initSections`
   */
  abstract protected function initPropertyDefinitions();

  /**
   * Constructs a new EntityFormatBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field plugin manager.
   * @param \Drupal\commerce_sheets\EntityFormat\EntityFormatManagerInterface $entity_format_manager
   *   The entity format plugin manager.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\commerce_sheets\FieldHandler\FieldHandlerManagerInterface $field_handler_manager
   *   The Commerce Sheets field handler plugin manager.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation service.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    EntityFieldManagerInterface $entity_field_manager,
    EntityFormatManagerInterface $entity_format_manager,
    EntityTypeManagerInterface $entity_type_manager,
    FieldHandlerManagerInterface $field_handler_manager,
    TranslationInterface $string_translation
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->setConfiguration($configuration);
    if (!isset($this->configuration['entity_type_id'])) {
      throw new \Exception(
        'An entity type ID is required for creating an instance of an entity ' .
        'format plugin'
      );
    }
    $this->assertEntityType();

    // Store injected services.
    $this->entityFieldManager = $entity_field_manager;
    $this->entityFormatManager = $entity_format_manager;
    $this->entityTypeManager = $entity_type_manager;
    $this->fieldHandlerManager = $field_handler_manager;
    // Property defined by StringTranslationTrait.
    $this->stringTranslation = $string_translation;

    // Initialize.
    $this->initPropertyDefinitions();
    $this->initPropertyPluginDefinitions();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition
  ) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_field.manager'),
      $container->get('plugin.manager.commerce_sheets_entity_format'),
      $container->get('entity_type.manager'),
      $container->get('plugin.manager.commerce_sheets_field_handler'),
      $container->get('string_translation')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    return [
      'module' => [$this->pluginDefinition['provider']],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    return $this->configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    $this->configuration = NestedArray::mergeDeep(
      $this->defaultConfiguration(),
      $configuration
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'entity_type_id' => NULL,
      'excluded_properties' => [],
      'protected_properties' => [
        'entity_keys' => ['id'],
      ],
      'associated_entities' => [],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(
    array $form,
    FormStateInterface $form_state
  ) {
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(
    array &$form,
    FormStateInterface $form_state
  ) {}

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(
    array &$form,
    FormStateInterface $form_state
  ) {}

  /**
   * {@inheritdoc}
   */
  public function getEntityTypeId() {
    return $this->configuration['entity_type_id'];
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityType() {
    if (!$entityType) {
      // Load the entity type definition.
      $this->entityType = $this->entityTypeManager->getDefinition(
        $this->configuration['entity_type_id']
      );
    }

    return $this->entityType;
  }

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions() {
    return $this->propertyDefinitions;
  }

  /**
   * {@inheritdoc}
   */
  public function setPropertyDefinitions(array $property_definitions) {
    $this->propertyDefinitions = $property_definitions;
  }

  /**
   * Returns an instantiated property plugin for the given property.
   *
   * @param string $property
   *   The name of the property.
   *
   * @return \Drupal\commerce_sheets\FieldHandler\FieldHandlerInterface|null
   *   The property plugin instance, or NULL if no plugin definition was able to
   *   be detected.
   */
  public function getPropertyPlugin($property) {
    $plugin_definition = $this->getPropertyPluginDefinition(
      $property,
      $this->propertyMap[$property]
    );

    if (!$plugin_definition) {
      return;
    }

    return $this->createPropertyPlugin($plugin_definition);
  }

  /**
   * Returns all sections of the format.
   *
   * @return array
   *   An array containing the sections.
   *
   * @I Document the structure of the sections array
   */
  public function getSections() {
    return $this->sections;
  }

  /**
   * Sets the sections of the format.
   *
   * @param array $sections
   *   An array containing the sections.
   */
  public function setSections(array $sections) {
    $this->sections = $sections;
  }

  /**
   * Returns whether the format defines a section for associated entities.
   *
   * @return bool
   *   Whether the format defines a section for associated entities.
   *
   * @I Document associated entities
   */
  public function hasAssociatedEntities() {
    return empty($this->configuration['associated_entities']) ? FALSE : TRUE;
  }

  /**
   * Returns the section for the associated entities.
   *
   * @return array
   *   The section for the associated entities.
   */
  public function getAssociatedEntitiesSection() {
    foreach ($this->sections as $section) {
      if ($section['type'] === 'entities') {
        return $section;
      }
    }
  }

  /**
   * Returns the column index for the ID property.
   *
   * @return int
   *   The column index for the ID property.
   */
  public function getColumnForId() {
    return $this->getColumnForProperty($this->entityType->getKey('id'));
  }

  /**
   * Returns the property plugin for the ID property.
   *
   * @return \Drupal\commerce_sheets\FieldHandler\FieldHandlerInterface
   *   The property plugin instance.
   */
  public function getPropertyPluginForId() {
    return $this->getPropertyPlugin($this->entityType->getKey('id'));
  }

  /**
   * Returns the column index for the bundle property.
   *
   * @return int
   *   The column index for the bundle property.
   */
  public function getColumnForBundle() {
    return $this->getColumnForProperty($this->entityType->getKey('bundle'));
  }

  /**
   * Returns the property plugin for the bundle property.
   *
   * @return \Drupal\commerce_sheets\FieldHandler\FieldHandlerInterface
   *   The property plugin instance.
   */
  public function getPropertyPluginForBundle() {
    return $this->getPropertyPlugin($this->entityType->getKey('bundle'));
  }

  /**
   * Returns the column index for the given property.
   *
   * @param string $property
   *   The name of the property to get the column for.
   *
   * @return int
   *   The column index for the bundle property.
   */
  public function getColumnForProperty($property) {
    foreach ($this->sections as $section) {
      $index = array_search($property, $section['properties']);
      if ($index !== FALSE) {
        return $section['start'] + $index;
      }
    }

    return FALSE;
  }

  /**
   * Checks that the format's entity type is supported by the plugin.
   *
   * The plugin may restrict the entity types it supports using
   * annotations. Thie method is called when the plugin is instantiated to
   * enforce that restriction.
   *
   * @throws \InvalidArgumentException
   *   When an unsupported entity type is given in the plugin's configuration.
   */
  protected function assertEntityType() {
    if (!$this->pluginDefinition['entity_types']) {
      return;
    }

    $is_supported = in_array(
      $this->configuration['entity_type_id'],
      $this->pluginDefinition['entity_types']
    );
    if (!$is_supported) {
      throw new \InvalidArgumentException(
        sprintf(
          'The "%s" entity type is not supported by the "%s" entity format',
          $this->configuration['entity_type_id'],
          $this->pluginDefinition['id']
        )
      );
    }
  }

  /**
   * Initializes the property plugin definitions for the format's properties.
   *
   * It defines plugin definitions for all properties in the format's
   * definitions array. These plugin definitions will be used to instantiate the
   * plugins that will be used for reading/writing to the sheet cells that
   * correspond to the properties.
   */
  protected function initPropertyPluginDefinitions() {
    array_walk(
      $this->propertyMap,
      function (&$definition, $property) {
        $definition['plugin'] = $this->getPropertyPluginDefinition(
          $property,
          $definition
        );
      }
    );
  }

  /**
   * Returns the property plugin definitions for the given property.
   *
   * @param string $property
   *   The name of the property.
   * @param array $property_definition
   *   The definition of the property as defined in the format's
   *   `propertyDefinitions` array.
   *
   * @return array
   *   An associative array containing the property plugin's definition for the
   *   given property. Array elements by key are:
   *   - id: The ID of the property plugin.
   *   - configuration: An array containing the configuration of the plugin.
   */
  protected function getPropertyPluginDefinition(
    $property,
    array $property_definition
  ) {
    // First check if we have a plugin definition in the case the property is an
    // entity key.
    $plugin_definition = $this->getPropertyPluginDefinitionForEntityKey(
      $property,
      $property_definition
    );
    // If not, get the plugin definition based on the property's type.
    if (!$plugin_definition) {
      $plugin_definition = $this->getPropertyPluginDefinitionByType(
        $property,
        $property_definition
      );
    }

    return $plugin_definition;
  }

  /**
   * Returns the property plugin definition for the given entity key property.
   *
   * @param string $property
   *   The name of the property.
   * @param array $property_definition
   *   The definition of the property as defined in the format's
   *   `propertyDefinitions` array.
   *
   * @return array
   *   An associative array containing the property plugin's definition for the
   *   given property. Array elements by key are:
   *   - id: The ID of the property plugin.
   *   - configuration: An array containing the configuration of the plugin.
   */
  protected function getPropertyPluginDefinitionForEntityKey(
    $property,
    array $property_definition
  ) {
    return [];
  }

  /**
   * Returns the property plugin definition for a property based on its type.
   *
   * @param string $property
   *   The name of the property.
   * @param array $property_definition
   *   The definition of the property as defined in the format's
   *   `propertyDefinitions` array.
   *
   * @return array
   *   An associative array containing the property plugin's definition for the
   *   given property. Array elements by key are:
   *   - id: The ID of the property plugin.
   *   - configuration: An array containing the configuration of the plugin.
   */
  protected function getPropertyPluginDefinitionByType(
    $property,
    array $property_definition
  ) {
    $type = NULL;
    $locked = NULL;

    switch ($property_definition['type']) {
      case 'boolean':
        $type = 'boolean';
        break;

      case 'commerce_price':
        $type = 'price';
        break;

      case 'integer':
        $type = 'integer';
        break;

      case 'path':
        $type = 'path';
        break;

      case 'physical_measurement':
        $type = 'measurement';
        break;

      case 'label':
      case 'string':
      case 'string_long':
      case 'text':
      case 'text_long':
      case 'text_with_summary':
        $type = 'text';
        break;

      case 'entity_reference':
        $type = 'entity_reference';
        break;
    }

    return $this->createPropertyPluginDefinition($type, $locked);
  }

  /**
   * Creates a property plugin definition for the given parameters.
   *
   * Currently, the only configuration parameter supported by all plugins is
   * whether the property is locked (protected) or not.
   *
   * @param string $id
   *   The ID of the plugin that will be created.
   * @param bool|null $locked
   *   TRUE if the property that the plugin is intended for is locked
   *   (protected), FALSE if not. If NULL is given, the default setting of the
   *   plugin will be used.
   *
   * @return array
   *   An associative array containing the property plugin's definition for the
   *   given property. Array elements by key are:
   *   - id: The ID of the property plugin.
   *   - configuration: An array containing the configuration of the plugin.
   */
  protected function createPropertyPluginDefinition($id, $locked) {
    if (!$id) {
      return [];
    }

    $configuration = $locked === NULL ? [] : ['locked' => $locked];

    return [
      'id' => $id,
      'configuration' => $configuration,
    ];
  }

  /**
   * Returns an instance of a handler plugin of the given type.
   *
   * @param array $plugin_definition
   *   An associative array containing the definition for the plugin that will
   *   be created. Array elements are:
   *   - id: The ID of the property plugin.
   *   - configuration: An array containing the configuration of the plugin.
   *
   * @return \Drupal\commerce_sheets\FieldHandler\FieldHandlerInterface
   *   The property plugin instance for the given definition.
   */
  protected function createPropertyPlugin(array $plugin_definition) {
    return $this->fieldHandlerManager->createInstance(
      $plugin_definition['id'],
      $plugin_definition['configuration']
    );
  }

  /**
   * Filters the given properties to remove those that should be excluded.
   *
   * Properties that should be excluded are defined in the plugin's
   * configuration.
   *
   * @param array $properties
   *   The names of the properties to filter.
   *
   * @return array
   *   The filtered properties.
   */
  protected function filterProperties(array $properties) {
    if (!$this->configuration['excluded_properties']) {
      return $properties;
    }

    $excluded_properties = $this->mergeEntityKeysIntoProperties(
      $this->configuration['excluded_properties']
    );

    return array_filter(
      $properties,
      function ($property) use ($excluded_properties) {
        return !in_array($property, $excluded_properties);
      }
    );
  }

  /**
   * Sorts the given properties.
   *
   * Protected properties go first, then unprotected. Within each of these
   * groups properties are sorted alphabetically.
   *
   * @param array $properties
   *   The names of the properties to filter.
   *
   * @return array
   *   The filtered properties.
   *
   * @I Add the ability to customize sorting
   */
  protected function sortProperties(array $properties) {
    $protected_properties = [];
    if ($this->configuration['protected_properties']) {
      $protected_properties = $this->mergeEntityKeysIntoProperties(
        $this->configuration['protected_properties']
      );
    }

    $protected_properties = array_filter(
      $properties,
      function ($properties) use ($protected_properties) {
        return in_array($property, $protected_properties);
      }
    );
    ksort($protected_properties);

    $unprotected_properties = array_filter(
      $properties,
      function ($property) use ($protected_properties) {
        return !in_array($property, $protected_properties);
      }
    );
    ksort($unprotected_properties);

    return $protected_properties + $unprotected_properties;
  }

  /**
   * Returns the property name for the given entity key.
   *
   * @param string $entity_key
   *   The entity key.
   *
   * @return string|bool
   *   The corresponding property name, or FALSE if it does not exist.
   */
  protected function entityKeyToProperty($entity_key) {
    return $this->entityType()->getKey($entity_key);
  }

  /**
   * Returns all property names contained in the given configuration array.
   *
   * Certain plugin configuration sub-array elements, such as
   * `excluded_properties`, define properties either by their names or by their
   * entity keys. This method combines all defined properties and returns them
   * in an array.
   *
   * @param array $configuration
   *   The array containing the properties by name or by entity key.
   *
   * @return array
   *   The array containing all property names.
   */
  protected function mergeEntityKeysIntoProperties(array $configuration) {
    if (empty($configuration['entity_keys']) && empty($configuration['names'])) {
      return [];
    }

    $merged_properties = [];
    if (!empty($configuration['names'])) {
      $merged_properties = $configuration['names'];
    }

    if (empty($configuration['entity_keys'])) {
      return $merged_properties;
    }

    foreach ($configuration['entity_keys'] as $entity_key) {
      $property = $this->entityKeyToProperty($entity_key);
      if (!in_array($property, $merged_properties)) {
        $merged_properties[] = $property;
      }
    }

    return $merged_properties;
  }

  /**
   * Creates and returns the section for the associated entities.
   *
   * It is one section as recognized by this format. The associated entities
   * then have their own format plugin that may define more than one section.
   *
   * @param array $configuration
   *   An array containing the `associated_entities` sub-array element of the
   *   format plugin configuration.
   * @param int $start_column
   *   The column at which the associated entities section starts.
   *
   * @return array
   *   An array defining the section for the associated entities.
   */
  protected function createAssociatedEntitiesSection(
    array $configuration,
    $start_column
  ) {
    $format = NULL;

    switch ($configuration['type']) {
      case 'content':
        $plugin_id = 'content';
        break;

      case 'entity_reference':
        $plugin_id = 'entity_reference';
        break;

      default:
        throw new \Exception(
          sprintf(
            'Unknown associated entities type "%s"',
            $configuration['type']
          )
        );
    }

    $format = $this->createAssociatedEntitiesFormat(
      $plugin_id,
      $configuration['format']
    );

    $associated_size = 0;
    $associated_sections = $format->getSections();
    foreach ($associated_sections as $associated_section) {
      $associated_size += $associated_section['size'];
    }

    return [
      'type' => 'entities',
      'format' => $format,
      'start' => $start_column,
      'size' => $associated_size,
    ];
  }

  /**
   * Creates and returns the section for the `content` associated entities.
   *
   * @param string $id
   *   The ID of the format plugin that will be created.
   * @param array $configuration
   *   An array containing the format plugin configuration for the associated
   *   entities section.
   *
   * @return array
   *   An array defining the section for the associated entities.
   */
  protected function createAssociatedEntitiesFormat(
    $id,
    array $configuration
  ) {
    return $this->entityFormatManager->createInstance(
      $id,
      $configuration
    );
  }

}
