<?php
IncludeModuleLangFile(__FILE__);
$aMenu = array();
if ($APPLICATION->getGroupRight("nikaImportTemplates") >= "R") {
    $aMenu[] = array(
        "parent_menu" => "global_menu_store",
        "section"     => "nikaImportTemplates",
        "sort"        => 300,
        "text"        => GetMessage("NIKAIMPORT_MENU_TEXT"),
        "title"       => GetMessage("NIKAIMPORT_MENU_TITLE"),
        "url"         => "nikaimport_admin.php?lang=".LANG,
        "icon"        => "workflow_menu_icon",
        "page_icon"   => "workflow_menu_icon",
        "items_id"    => "menu_nikaimport",
        "more_url"    => array("nikaimport_admin.php", "nikaimport_edit.php", "nikaimport_report.php"),
        "items"       => array()
    );
}
return !empty($aMenu) ? $aMenu : false;