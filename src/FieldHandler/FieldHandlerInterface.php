<?php

namespace Drupal\commerce_sheets\FieldHandler;

use Drupal\Component\Plugin\ConfigurableInterface;
use Drupal\Component\Plugin\DependentPluginInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Plugin\PluginFormInterface;

use PhpOffice\PhpSpreadsheet\Style\Style;

/**
 * The interface for Commerce Sheets field handlers.
 *
 * Field handlers are responsible for the following operations on supported
 * field types:
 * - Convert values from spreadsheet cell values to values appropriate for
 *   storing in fields.
 * - Convert field values into cell values for storing in the exported
 *   spreadsheet.
 */
interface FieldHandlerInterface extends
  ConfigurableInterface,
  DependentPluginInterface,
  PluginFormInterface,
  PluginInspectionInterface {

  public function validate($value);

  /**
   * Returns the cell value for the given field.
   *
   * @param \Drupal\Core\Field\FieldItemListInterface $field
   *   The field object.
   *
   * @return string
   *   The converted value.
   */
  public function toCellValue(FieldItemListInterface $field);

  /**
   * Sets the styles of the cell.
   *
   * @param \PhpOffice\PhpSpreadsheet\Style\Style $style
   *   The style object of the cell.
   */
  public function toCellStyle(Style $style);

}
