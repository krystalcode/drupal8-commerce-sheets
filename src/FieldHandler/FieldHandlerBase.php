<?php

namespace Drupal\commerce_sheets\FieldHandler;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginBase;

use PhpOffice\PhpSpreadsheet\Style\Protection;
use PhpOffice\PhpSpreadsheet\Style\Style;

/**
 * Base class for all field handler plugins.
 */
abstract class FieldHandlerBase extends PluginBase implements
  FieldHandlerInterface {

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
    // The default configuration seems to not be applied at all until the
    // `setConfiguration` method is called. That causes configuration to always
    // be empty unless there is a configuration array passed in the constructor
    // when creating the plugin. Let's make sure here that configuration is
    // initialized properly.
    // @I Investigate the right way to initialize plugin configuration
    if (!$this->configuration) {
      $this->setConfiguration([]);
    }

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
      'locked' => FALSE,
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
  public function validate($value) {}

  /**
   * {@inheritdoc}
   */
  public function fromCellGetValue($cell) {
    return (string) $cell->getValue();
  }

  /**
   * {@inheritdoc}
   */
  public function fromCellToField($cell, FieldItemListInterface $field) {
    if ($this->getLocked()) {
      return;
    }

    $field->setValue($this->fromCellGetValue($cell));
  }

  /**
   * {@inheritdoc}
   */
  public function toCellValue($property_value) {
    if ($property_value instanceof FieldItemListInterface) {
      return $property_value->value;
    }

    return $property_value;
  }

  /**
   * {@inheritdoc}
   */
  public function toCellDataType() {
  }

  /**
   * {@inheritdoc}
   */
  public function toCellStyle(Style $style) {
    if ($this->getConfiguration()['locked']) {
      $style->getProtection()->setLocked(Protection::PROTECTION_PROTECTED);
    }
  }

  /**
   * Returns the `locked` setting for the field handler.
   *
   * @return bool
   *   The value of the `locked` setting.
   */
  protected function getLocked() {
    return $this->getConfiguration()['locked'];
  }

}
