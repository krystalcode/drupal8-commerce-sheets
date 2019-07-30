<?php

namespace Drupal\commerce_sheets_product\Plugin\Action;

use Drupal\commerce_sheets\Action\ExportBase;
use Drupal\commerce_product\Entity\ProductInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Session\AccountInterface;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Exports one or more products to a spreadsheet file.
 *
 * @Action(
 *   id = "commerce_sheets_export_product",
 *   label = @Translation("Export selected product"),
 *   type = "commerce_product"
 * )
 *
 * @I Review which functions should be included in the base class.
 */
class ExportProduct extends ExportBase {

  /**
   * {@inheritdoc}
   */
  public function access(
    $object,
    AccountInterface $account = NULL,
    $return_as_object = FALSE
  ) {
    /** @var \Drupal\commerce_product\Entity\ProductInterface $object */
    $result = $object
      ->access('update', $account, TRUE)
      ->andIf($object->status->access('edit', $account, TRUE));

    return $return_as_object ? $result : $result->isAllowed();
  }

  /**
   * {@inheritdoc}
   */
  protected function validateEntities(array $entities) {}

  /**
   * {@inheritdoc}
   */
  protected function writeHeader(
    Worksheet $sheet,
    array $entities,
    $row
  ) {
    $sheet->setCellValueByColumnAndRow($row, 0, 'Products');

    $row++;
    $column = 1;
    $entity = reset($entities);

    // Header values for base fields.
    $field_definitions = $this->getBaseFieldDefinitions($entity);
    $column = $this->writeHeaderForFields($sheet, $field_definitions, $row, $column);

    // Header values for bundle fields.
    $field_definitions = $this->getBundleFieldDefinitions($entity);
    $this->writeHeaderForFields($sheet, $field_definitions, $row, $column);

    return $row + 1;
  }

  /**
   * {@inheritdoc}
   */
  protected function writeEntity(
    Worksheet $sheet,
    EntityInterface $product,
    $row
  ) {
    $column = 1;

    list($row, $column) = $this->writeProductFields(
      $sheet,
      $product,
      $row,
      $column
    );

    return $row + 1;
  }

  /**
   * Converts the product to one or more rows and writes them to the sheet.
   *
   * @param \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $worksheet
   *   The sheet to which the rows will be written.
   * @param \Drupal\Core\Entity\EntityInterface $product
   *   The product being processed.
   * @param int $row
   *   The row at which to start writing for the product.
   * @param int $column
   *   The column at which to start writing for the product.
   *
   * @return int[]
   *   An array containing the last row and column written for the product.
   */
  protected function writeProductFields(
    Worksheet $sheet,
    ProductInterface $product,
    $row,
    $column
  ) {
    list($row, $column) = $this->writeProductBaseFields(
      $sheet,
      $product,
      $row,
      $column
    );
    list($row, $column) = $this->writeProductBundleFields(
      $sheet,
      $product,
      $row,
      $column
    );

    return [$row, $column];
  }

  /**
   * Converts the base fields for the product and writes them to the sheet.
   *
   * @param \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $worksheet
   *   The sheet to which the rows will be written.
   * @param \Drupal\Core\Entity\EntityInterface $product
   *   The product being processed.
   * @param int $row
   *   The row at which to start writing for the base fields.
   * @param int $column
   *   The column at which to start writing for the base fields.
   *
   * @return int[]
   *   An array containing the last row and column written for the base fields.
   */
  protected function writeProductBaseFields(
    Worksheet $sheet,
    ProductInterface $product,
    $row,
    $column
  ) {
    $field_definitions = $this->getBaseFieldDefinitions($product);

    foreach ($field_definitions as $field_definition) {
      list($row, $column) = $this->writeProductField(
        $sheet,
        $product->get($field_definition->getName()),
        $row,
        $column
      );
    }

    return [$row, $column];
  }


  /**
   * Converts the bundle fields for the product and writes them to the sheet.
   *
   * @param \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $worksheet
   *   The sheet to which the rows will be written.
   * @param \Drupal\Core\Entity\EntityInterface $product
   *   The product being processed.
   * @param int $row
   *   The row at which to start writing for the bundle fields.
   * @param int $column
   *   The column at which to start writing for the bundle fields.
   *
   * @return int[]
   *   An array containing the last row and column written for the bundle
   *   fields.
   */
  protected function writeProductBundleFields(
    Worksheet $sheet,
    ProductInterface $product,
    $row,
    $column
  ) {
    $field_definitions = $this->getBundleFieldDefinitions($product);

    foreach ($field_definitions as $field_definition) {
      list($row, $column) = $this->writeProductField(
        $sheet,
        $product->get($field_definition->getName()),
        $row,
        $column
      );
    }
    return [$row, $column];
  }

  /**
   * Filters base field definitions to exclude those that will not be exported.
   *
   * @param \Drupal\Core\Field\FieldDefinitionInterface[] $field_definitions
   *   The base field definitions being processed.
   *
   * @return \Drupal\Core\Field\FieldDefinitionInterface[]
   *   The filtered base field definitions.
   */
  protected function filterBaseFields(array $field_definitions) {
    $blacklisted_fields = [
      'uuid',
      'langcode',
      'uid',
      'created',
      'changed',
      'default_langcode',
      'metatag',
    ];

    return array_filter(
      $field_definitions,
      function ($field_name) use ($blacklisted_fields) {
        return !in_array($field_name, $blacklisted_fields);
      },
      ARRAY_FILTER_USE_KEY
    );
  }

  /**
   * Filters bundle field definitions to exclude those that will not be exported.
   *
   * @param \Drupal\Core\Field\FieldDefinitionInterface[] $field_definitions
   *   The bundle field definitions being processed.
   *
   * @return \Drupal\Core\Field\FieldDefinitionInterface[]
   *   The filtered bundle field definitions.
   */
  protected function filterBundleFields(array $field_definitions) {
    $blacklisted_fields = [
      'variations',
    ];

    return array_filter(
      $field_definitions,
      function ($field_name) use ($blacklisted_fields) {
        return !in_array($field_name, $blacklisted_fields);
      },
      ARRAY_FILTER_USE_KEY
    );
  }

  /**
   * Sorts field definitions in the order that they should be exported.
   *
   * The default order is alphabetical based on the fields' machine names, with
   * read-only fields going first.
   *
   * @param \Drupal\Core\Field\FieldDefinitionInterface[] $field_definitions
   *   The bundle field definitions being processed.
   *
   * @return \Drupal\Core\Field\FieldDefinitionInterface[]
   *   The filtered bundle field definitions.
   */
  protected function sortFields(array $field_definitions) {
    $read_only_field_names = [
      'product_id',
      'type',
    ];

    $read_only_field_definitions = array_filter(
      $field_definitions,
      function ($field_name) use ($read_only_field_names) {
        return in_array($field_name, $read_only_field_names);
      },
      ARRAY_FILTER_USE_KEY
    );
    ksort($read_only_field_definitions);

    $editable_field_definitions = array_filter(
      $field_definitions,
      function ($field_name) use ($read_only_field_names) {
        return !in_array($field_name, $read_only_field_names);
      },
      ARRAY_FILTER_USE_KEY
    );
    ksort($editable_field_definitions);

    return $read_only_field_definitions + $editable_field_definitions;
  }

  /**
   * Converts an individual field value and writes it to the given sheet.
   *
   * @param \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $worksheet
   *   The sheet to which the vlaue will be written.
   * @param int $row
   *   The row at which to write the value.
   * @param int $column
   *   The column at which to write the value.
   *
   * @return int[]
   *   An array containing the last row and column written for the value.
   */
  protected function writeProductField(
    Worksheet $sheet,
    FieldItemListInterface $field,
    $row,
    $column
  ) {
    $value = $field->value;

    $plugin = $this->getFieldPlugin($field->getFieldDefinition());
    if ($plugin) {
      $value = $plugin->toCellValue($field);
    }

    $sheet->setCellValueByColumnAndRow($column, $row, $value);

    return [$row , $column + 1];
  }

}
