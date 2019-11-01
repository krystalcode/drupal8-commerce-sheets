<?php

namespace Drupal\commerce_sheets\Plugin\CommerceSheets\FieldHandler;

use Drupal\commerce_sheets\FieldHandler\FieldHandlerBase;

use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

/**
 * Provides a handler plugin for integer fields.
 *
 * @CommerceSheetsFieldHandler(
 *   id = "integer",
 *   label = @Translation("Integer"),
 *   field_types = {
 *     "integer"
 *   }
 * )
 */
class Integer extends FieldHandlerBase {

  /**
   * {@inheritdoc}
   */
  public function toCellDataType() {
    return DataType::TYPE_NUMERIC;
  }

  /**
   * {@inheritdoc}
   */
  public function toCellStyle($style) {
    parent::toCellStyle($style);

    $style->getNumberFormat()
      ->setFormatCode(NumberFormat::FORMAT_NUMBER);
    $style->getAlignment()
      ->setHorizontal(Alignment::HORIZONTAL_LEFT);
  }

}
