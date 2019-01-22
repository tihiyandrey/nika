<?php
namespace Bitrix\NikaImport;
use Bitrix\Main\Entity;
use Bitrix\Main\Localization;
Localization\Loc::loadMessages(__FILE__);

class NikaImportTable extends Entity\DataManager {
    /**
     * Returns DB table name for entity
     *
     * @return string
     */
    public static function getTableName() {
        return "b_nikaimport";
    }

    /**
     * Returns entity map definition
     *
     * @return array
     */
    public static function getMap() {
        return array(
            "ID" => array(
                "data_type"    => "integer",
                "primary"      => true,
                "autocomplete" => true
            ),
            "NAME" => array(
                "data_type" => "string",
                "title"     => Localization\Loc::getMessage("nikaimport_entity_name_field"),
            ),
            "VALUE" => array(
                "data_type" => "text",
                "title"     => Localization\Loc::getMessage("nikaimport_entity_value_field"),
            ),
        );
    }
}