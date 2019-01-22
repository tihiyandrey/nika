<?php
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");
IncludeModuleLangFile(__FILE__);
\Bitrix\Main\Loader::includeModule("nikaimport");
$arLang    = $APPLICATION->getLang();
$MOD_RIGHT = $APPLICATION->getGroupRight("nikaimport");
if ($MOD_RIGHT < "R") {
    $APPLICATION->authForm(getMessage("ACCESS_DENIED"));
}
if ($MOD_RIGHT >= "W") {
    if ((isset($_REQUEST["save"]) || isset($_REQUEST["apply"]))) {
        if (\Bitrix\NikaImport\AdminHelper::saveParamsNikaImport($_REQUEST)) {
            LocalRedirect($APPLICATION->GetCurUri());
        }
    }
}
$paramsApi = \Bitrix\NikaImport\AdminHelper::getParamsNikaImport();
$APPLICATION->setTitle(getMessage("NIKAIMPORT_LIST_TITLE"));
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_admin_after.php");
?>
<div style="background-color:#fff;border:1px solid #ced7d8;padding:20px;">
    <table style="border-spacing:0px;"><tr>
        <td style="border:none;padding:15px;"><img src="/bitrix/images/nikaimport/ab-icon-big.png"></td>
        <td style="border:none;padding:15px;max-width:800px;"><?=getMessage("NIKAIMPORT_LIST_DESCR");?></td>
    </tr></table>
</div><br>
<?
$aTabs = array(
    array(
        "DIV"   => "edit1",
        "TAB"   => getMessage("NIKAIMPORT_TAB_NAME"),
        "TITLE" => getMessage("NIKAIMPORT_TAB_TITLE")
    ),
    array(
        "DIV"   => "edit2",
        "TAB"   => getMessage("NIKAIMPORT_TAB_NAME_1"),
        "TITLE" => getMessage("NIKAIMPORT_TAB_TITLE_1")
    ),
    array(
        "DIV"   => "edit3",
        "TAB"   => getMessage("NIKAIMPORT_TAB_NAME_2"),
        "TITLE" => getMessage("NIKAIMPORT_TAB_TITLE_2")
    ),
    array(
        "DIV"   => "edit4",
        "TAB"   => getMessage("NIKAIMPORT_TAB_NAME_3"),
        "TITLE" => getMessage("NIKAIMPORT_TAB_TITLE_3")
    ),
);
$tabControl = new CAdminTabControl("tabControl", $aTabs, false);
if ($message) {
    echo $message->Show();
}
$arIBlock     = array();
$iblockFilter = !empty($arCurrentValues["IBLOCK_TYPE"]) ? array("TYPE" => $arCurrentValues["IBLOCK_TYPE"], "ACTIVE" => "Y") : array("ACTIVE" => "Y");
$rsIBlock     = CIBlock::GetList(array("SORT" => "ASC"), $iblockFilter);
while ($arr = $rsIBlock->Fetch()) {
    $arIBlock[$arr["ID"]] = "[".$arr["ID"]."] ".$arr["NAME"];
}
unset($arr, $rsIBlock, $iblockFilter);
$settings_html = "";
if (isset($paramsApi["SECTIONS_AR"]) && !empty($paramsApi["SECTIONS_AR"])) {
    $settings_html .= '<b>' . getMessage("NIKAIMPORT_API_SECTIONS_HEADER") . '</b>';
    $settings_html .= \Bitrix\NikaImport\AdminHelper::getSectionsHtml(unserialize($paramsApi["SECTIONS_AR"]));
} else {
    $paramsApi["SECTIONS_AR"] = array();
    $settings_html .= getMessage("NIKAIMPORT_API_NO_SECTIONS");
}
$settings_html .= "<input type=\"hidden\" name=\"SECTIONS_AR\" value='".$paramsApi["SECTIONS_AR"]."'>";
$extra = '';
if (isset($paramsApi["EXTRA"]) && !empty($paramsApi["EXTRA"])) {
    $settings_html .= '<br><br><b>' . getMessage("NIKAIMPORT_API_EXTRA") . '</b>';
    $extra          = $paramsApi["EXTRA"];
} else {
    $settings_html .= '<br><br>' . getMessage("NIKAIMPORT_API_NO_EXTRA");
}
$settings_html .= '<br><input type="text" name="EXTRA" id="EXTRA" size="50" maxlength="255" value="'.$extra.'" placeholder="$extra=$extra*1.50;">';
$settings_html .= getMessage("NIKAIMPORT_API_EXTRA_DESCR");
?>
<form method="POST" action="<?=$APPLICATION->GetCurPage();?>?lang=<?=LANG;?>" name="form1" enctype="multipart/form-data">
    <?=bitrix_sessid_post();?>
    <? $tabControl->Begin();?>
    <? $tabControl->BeginNextTab();?>
        <tr class="adm-detail-required-field">
            <td style="width:40%;"><?=getMessage("NIKAIMPORT_API_URL");?>:</td>
            <td style="width:60%;">
                <input type="text" name="API_URL" id="API_URL" size="50" maxlength="255" value="<?=$paramsApi["API_URL"];?>">
            </td>
        </tr>
        <tr class="adm-detail-required-field">
            <td><?=getMessage("NIKAIMPORT_API_LOGIN");?>:</td>
            <td>
                <input type="text" name="API_LOGIN" id="API_LOGIN" size="50" maxlength="255" value="<?=$paramsApi["API_LOGIN"];?>">
            </td>
        </tr>
        <tr class="adm-detail-required-field">
            <td><?=getMessage("NIKAIMPORT_API_PASSWORD");?>:</td>
            <td>
                <input type="text" name="API_PASSWORD" id="API_PASSWORD" size="50" maxlength="255" value="<?=$paramsApi["API_PASSWORD"];?>">
            </td>
        </tr>
        <tr class="adm-detail-required-field">
            <td><?=getMessage("NIKAIMPORT_API_SETTINGS_LOADING");?>:</td>
            <td>
                <input type="button" name="save" onclick="testApi();" value="<?=getMessage("NIKAIMPORT_API_SETTINGS_LOADING_BTN");?>" title="<?=getMessage("NIKAIMPORT_API_SETTINGS_LOADING_BTN");?>">
            </td>
        </tr>
        <tr class="adm-detail-required-field">
            <td></td>
            <td id="settings_block">
                <div id="error_block" style="color:green;">
                    <?=$settings_html;?>
                </div>
            </td>
        </tr>
    <? $tabControl->BeginNextTab();?>
        <tr class="adm-detail-required-field">
            <td style="width:40%;"><?=getMessage("NIKAIMPORT_API_XML_INTERVAL");?>:</td>
            <td style="width:60%;">
                <input type="text" name="INTERVAL" id="INTERVAL" size="20" maxlength="255" value="<?=(isset($paramsApi["INTERVAL"]) && intval($paramsApi["INTERVAL"]) > 0 ? $paramsApi["INTERVAL"] : 30);?>">
            </td>
        </tr>
        <tr class="adm-detail-required-field">
            <td><?=getMessage("NIKAIMPORT_API_XML_CRC");?>:</td>
            <td>
                <input type="hidden" name="CRC" id="CRC_N" value="N">
                <input type="checkbox" name="CRC" id="CRC" value="Y"<?=(isset($paramsApi["CRC"]) && $paramsApi["CRC"] == "Y") ? " checked" : "";?>>
            </td>
        </tr>
        <? $hide_img = (isset($paramsApi["IBLOCK_PICTURE_SETTINGS"]) && $paramsApi["IBLOCK_PICTURE_SETTINGS"] == "Y") ? true : false; ?>
        <tr class="adm-detail-required-field">
            <td><?=getMessage("NIKAIMPORT_API_XML_USE_PICTURE");?>:</td>
            <td>
                <input type="hidden" name="IBLOCK_PICTURE_SETTINGS" id="IBLOCK_PICTURE_SETTINGS_N" value="N">
                <input type="checkbox" name="IBLOCK_PICTURE_SETTINGS" id="IBLOCK_PICTURE_SETTINGS" value="Y"<?=($hide_img ? " checked" : "");?>>
            </td>
        </tr>
        <tr>
            <td><?=getMessage("NIKAIMPORT_API_XML_GENERATE_PREVIEW");?>:</td>
            <td>
                <input type="hidden" name="GENERATE_PREVIEW" id="GENERATE_PREVIEW_N" value="N">
                <input type="checkbox" name="GENERATE_PREVIEW" id="GENERATE_PREVIEW" value="Y" class="adm-designed-checkbox"<?=($hide_img ? " disabled" : "");?><?=(isset($paramsApi["GENERATE_PREVIEW"]) ? " checked" : "");?>><label class="adm-designed-checkbox-label" for="GENERATE_PREVIEW" title=""></label>
            </td>
        </tr>
        <tr>
            <td><?=getMessage("NIKAIMPORT_API_XML_PREVIEW_WIDTH");?>:</td>
            <td>
                <input type="text" name="PREVIEW_WIDTH" id="PREVIEW_WIDTH" size="20" maxlength="255" value="<?=(isset($paramsApi["PREVIEW_WIDTH"]) ? $paramsApi["PREVIEW_WIDTH"] : 100);?>"<?=($hide_img ? " disabled" : "");?>>
            </td>
        </tr>
        <tr>
            <td><?=getMessage("NIKAIMPORT_API_XML_PREVIEW_HEIGHT");?>:</td>
            <td>
                <input type="text" name="PREVIEW_HEIGHT" id="PREVIEW_HEIGHT" size="20" maxlength="255" value="<?=(isset($paramsApi["PREVIEW_HEIGHT"]) ? $paramsApi["PREVIEW_HEIGHT"] : 100);?>"<?=($hide_img ? " disabled" : "");?>>
            </td>
        </tr>
        <tr>
            <td><?=getMessage("NIKAIMPORT_API_XML_DETAIL_RESIZE");?>:</td>
            <td>
                <input type="hidden" name="DETAIL_RESIZE" id="DETAIL_RESIZE_N" value="N">
                <input type="checkbox" name="DETAIL_RESIZE" id="DETAIL_RESIZE" value="Y" class="adm-designed-checkbox"<?=($hide_img ? " disabled" : "");?><?=(isset($paramsApi["DETAIL_RESIZE"]) ? " checked" : "");?>><label class="adm-designed-checkbox-label" for="DETAIL_RESIZE" title=""></label>
            </td>
        </tr>
        <tr>
            <td><?=getMessage("NIKAIMPORT_API_XML_DETAIL_WIDTH");?>:</td>
            <td>
                <input type="text" name="DETAIL_WIDTH" id="DETAIL_WIDTH" size="20" maxlength="255" value="<?=(isset($paramsApi["DETAIL_WIDTH"]) ? $paramsApi["DETAIL_WIDTH"] : 300);?>"<?=($hide_img ? " disabled" : "");?>>
            </td>
        </tr>
        <tr>
            <td><?=getMessage("NIKAIMPORT_API_XML_DETAIL_HEIGHT");?>:</td>
            <td>
                <input type="text" name="DETAIL_HEIGHT" id="DETAIL_HEIGHT" size="20" maxlength="255" value="<?=(isset($paramsApi["DETAIL_HEIGHT"]) ? $paramsApi["DETAIL_HEIGHT"] : 300);?>"<?=($hide_img ? " disabled" : "");?>>
            </td>
        </tr>
        <? $hide_iblock = (isset($paramsApi["ADD_IBLOCK"]) && $paramsApi["ADD_IBLOCK"] == "Y") ? true : false; ?>
        <tr class="adm-detail-required-field">
            <td style="width:40%;"><?=getMessage("NIKAIMPORT_API_XML_ADD_IBLOCK");?>:</td>
            <td style="width:60%;">
                <input type="hidden" name="ADD_IBLOCK" id="ADD_IBLOCK_N" value="N">
                <input type="checkbox" name="ADD_IBLOCK" id="API_XML_ADD_IBLOCK" value="Y"<?=($hide_iblock ? " checked" : "");?>>
            </td>
        </tr>
        <tr class="adm-detail-required-field">
            <td style="width:40%;"><?=getMessage("NIKAIMPORT_API_XML_IBLOCK_CATALOG");?>:</td>
            <td style="width:60%;">
                <select name="IBLOCK_ID_CATALOG" id="API_XML_IBLOCK_CATALOG" class="typeselect"<?=($hide_iblock ? " disabled" : "");?>>
                    <? foreach ($arIBlock as $k_iblock => $v_iblock) : ?>
                        <option value="<?=$k_iblock;?>"<?=($paramsApi["IBLOCK_ID_CATALOG"] == $k_iblock ? " selected" : "");?>><?=$v_iblock;?></option>
                    <? endforeach; ?>
                </select>
            </td>
        </tr>
        <tr class="adm-detail-required-field">
            <td style="width:40%;"><?=getMessage("NIKAIMPORT_API_XML_IBLOCK_OFFERS");?>:</td>
            <td style="width:60%;">
                <select name="IBLOCK_ID_OFFERS" id="API_XML_IBLOCK_OFFERS" class="typeselect"<?=($hide_iblock ? " disabled" : "");?>>
                    <? foreach ($arIBlock as $k_iblock => $v_iblock) : ?>
                        <option value="<?=$k_iblock;?>"<?=($paramsApi["IBLOCK_ID_OFFERS"] == $k_iblock ? " selected" : "");?>><?=$v_iblock;?></option>
                    <? endforeach; ?>
                </select>
            </td>
        </tr>
    <? $tabControl->BeginNextTab();?>
        <tr class="adm-detail-required-field">
            <td style="width:100%;">
                <div id="start_result_block" style="height:160px;overflow:overlay;border:1px solid #ebecec;max-height:160px;"></div>
            </td>
        </tr>
        <tr class="adm-detail-required-field">
            <td>&nbsp;</td>
        </tr>
        <tr class="adm-detail-required-field">
            <td>
                <a id="btn_import" onclick="importNika(); return false;" class="adm-btn adm-btn-save"><?=getMessage("NIKAIMPORT_API_IMPORT_BTN");?></a>
                <input type="button" name="stop" onclick="importNika('stop', 'start'); return false;" value="<?=getMessage("NIKAIMPORT_BTN_STOP");?>" title="<?=getMessage("NIKAIMPORT_BTN_STOP");?>">
            </td>
        </tr>
    <? $tabControl->BeginNextTab();?>
        <tr>
            <td align="center">
                <div class="adm-info-message-wrap" align="center">
                    <div class="adm-info-message">
                        <?=getMessage("NIKAIMPORT_TAB_TITLE_3_DESC");?>
                    </div>
                </div>
            </td>
        </tr>
        <tr class="adm-detail-required-field">
            <td style="width:1000%;">
                <div id="products_result_block" style="height:160px;overflow:overlay;border:1px solid #ebecec;max-height:160px;"></div>
            </td>
        </tr>
        <tr class="adm-detail-required-field">
            <td>&nbsp;</td>
        </tr>
        <tr class="adm-detail-required-field">
            <td>
                <a id="btn_import_product" onclick="importNika('products');" class="adm-btn adm-btn-save"><?=getMessage("NIKAIMPORT_API_IMPORT_BTN");?></a>
                <input type="button" name="stop" onclick="importNika('stop', 'products');" value="<?=getMessage("NIKAIMPORT_BTN_STOP");?>" title="<?=getMessage("NIKAIMPORT_BTN_STOP");?>">
            </td>
        </tr>
    <? $tabControl->EndTab();?>
    <? $tabControl->Buttons(
        array(
            "disabled" => false,
            "btnApply" => false,
            "back_url" => false,
        )
    ); ?>
    <? $tabControl->End();?>
</form>
<script type="text/javascript">
    var sections;
    function sectionsUpload() {
        BX.findChild(
            BX("settings_block"),
            {
                tag : "input",
                type: "checkbox"
            },
            true,
            true
        ).forEach(function(input){
            BX.bind(
                input,
                "click",
                function() {
                    var checked = this.checked;
                    BX.findChild(
                        this.parentElement,
                        {
                            tag : "li"
                        },
                        true,
                        true
                    ).forEach(function(child){
                        BX.findChild(
                            child,
                            {
                                tag : "input",
                                type: "checkbox"
                            },
                            true,
                            true
                        ).forEach(function(child_input){
                            child_input.checked = checked;
                        });
                    });
                }
            );
        });
    }
    function testApi() {
        BX.showWait("edit1_edit_table");
        BX.ajax({
            method: "POST",
            url: "/bitrix/admin/nikaimport_ajax.php",
            dataType: "json",
            data: {
                action: "settingsloading",
                url: BX("API_URL").value,
                login: BX("API_LOGIN").value,
                password: BX("API_PASSWORD").value,
            },
            onsuccess: function(json)
            {
                if (json.error == false) {
                    BX("error_block").style.color = "green";
                } else {
                    BX("error_block").style.color = "red";
                }
                BX("error_block").innerHTML = json.result;
                BX.closeWait("edit1_edit_table");
                sectionsUpload();
            }
        });
    }
    BX.bind(
        BX("IBLOCK_PICTURE_SETTINGS"),
        "click",
        function() {
            if (this.checked == true) {
                BX("GENERATE_PREVIEW").disabled = true;
                BX("PREVIEW_WIDTH").disabled    = true;
                BX("PREVIEW_HEIGHT").disabled   = true;
                BX("DETAIL_RESIZE").disabled    = true;
                BX("DETAIL_WIDTH").disabled     = true;
                BX("DETAIL_HEIGHT").disabled    = true;
            } else {
                BX("GENERATE_PREVIEW").disabled = false;
                BX("PREVIEW_WIDTH").disabled    = false;
                BX("PREVIEW_HEIGHT").disabled   = false;
                BX("DETAIL_RESIZE").disabled    = false;
                BX("DETAIL_WIDTH").disabled     = false;
                BX("DETAIL_HEIGHT").disabled    = false;
            }
        }
    );
    BX.bind(
        BX("API_XML_ADD_IBLOCK"),
        "click",
        function() {
            if (this.checked == true) {
                BX("API_XML_IBLOCK_CATALOG").disabled = true;
                BX("API_XML_IBLOCK_OFFERS").disabled  = true;
            } else {
                BX("API_XML_IBLOCK_CATALOG").disabled = false;
                BX("API_XML_IBLOCK_OFFERS").disabled  = false;
            }
        }
    );
    BX.findChild(
        BX("tabControl_tabs"),
        {
            class: "adm-detail-tab"
        },
        true,
        true
    ).forEach(function(ele){
        BX.bind(
            ele,
            "click",
            function() {
                if (this.id == "tab_cont_edit3" || this.id == "tab_cont_edit4") {
                    BX("edit3").style.paddingBottom = "12px";
                    BX("edit4").style.paddingBottom = "12px";
                    BX("tabControl_buttons_div").style.display = "none";
                } else {
                    BX("edit3").style.paddingBottom = "0px";
                    BX("edit4").style.paddingBottom = "0px";
                    BX("tabControl_buttons_div").style.display = "block";
                }
            }
        );
    });
    BX.ready(function(){
        if (BX.hasClass(BX("tab_cont_edit3"), "adm-detail-tab-active")) {
            BX("edit3").style.paddingBottom = "12px";
            BX("tabControl_buttons_div").style.display = "none";
        }
        if (BX.hasClass(BX("tab_cont_edit4"), "adm-detail-tab-active")) {
            BX("edit4").style.paddingBottom = "12px";
            BX("tabControl_buttons_div").style.display = "none";
        }
        sectionsUpload();
    });
    function importNika(type, block) {
        type = type || "start";
        var btn = document.getElementById("btn_import");
        var btn_p = document.getElementById("btn_import_product");
        setTimeout(function() {
            BX.ajax({
                method: "POST",
                url: "/bitrix/admin/nikaimport_ajax.php",
                dataType: "json",
                data: {
                    action: type + "_import",
                    tab: block
                },
                onsuccess: function(json)
                {
                    var str = document.createElement("p");
                    str.style.color = "green";
                    if (json.error == true) {
                        str.style.color = "red";
                    }
                    str.innerHTML = json.result;
                    if (json.restart != false) {
                        eval(json.restart);
                    }
                    if (block) {
                        type = block;
                    }
                    BX(type + "_result_block").insertBefore(str, BX(type + "_result_block").firstChild);
                }
            });
        }, 500);
    }
</script>
<? require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin.php");