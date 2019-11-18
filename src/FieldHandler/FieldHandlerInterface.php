<?php

namespace Drupal\commerce_sheets\FieldHandler;

use Drupal\Component\Plugin\ConfigurableInterface;
use Drupal\Component\Plugin\DependentPluginInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Plugin\PluginFormInterface;

use PhpOffice\PhpSpreadsheet\Cell\Cell;
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
 *
 * @I Rename to PropertyHandler
 */
interface FieldHandlerInterface extends
  ConfigurableInterface,
  DependentPluginInterface,
  PluginFormInterface,
  PluginInspectionInterface {

  /**
   * Validates that the given value is in the format expected by the field.
   *
   * @param mixed $value
   *   The value to validate.
   */
  public function validate($value);

  /**
   * Returns the value of the given cell.
   *
   * The value is returned in the most reasonable format for the data contained
   * in the cell.
   *
   * @param \PhpOffice\PhpSpreadsheet\Cell\Cell $cell
   *   The cell to get the value from.
   *
   * @return mixed
   *   The value.
   */
  public function fromCellGetValue(Cell $cell);

  /**
   * Stores the value of the cell to the given field.
   *
   * @param \PhpOffice\PhpSpreadsheet\Cell\Cell $cell
   *   The cell to get the value from.
   * @param \Drupal\Core\Field\FieldItemListInterface $field
   *   The field where to store the value to.
   */
  public function fromCellToField(Cell $cell, FieldItemListInterface $field);

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
   * Returns the data type value for the cell.
   *
   * @return string
   *   The cell data type.
   *
   * @see \PhpOffice\PhpSpreadsheet\Cell\DataType
   */
  public function toCellDataType();

  /**
   * Sets the styles of the cell.
   *
   * @param \PhpOffice\PhpSpreadsheet\Style\Style $style
   *   The style object of the cell.
   */
  public function toCellStyle(Style $style);

}
