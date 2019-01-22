<?php
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");
IncludeModuleLangFile(__FILE__);
\Bitrix\Main\Loader::includeModule("nikaimport");
$error      = GetMessage("NIKAIMPORT_ACCESS_DENIED");
$restart    = false;
$start_time = time();
if ($APPLICATION->getGroupRight("nikaimport") >= "W") {
    $error = false;
    if (!CModule::IncludeModule("iblock")) {
        $result = GetMessage("NIKAIMPORT_ERROR_IBLOCK_NOT_MODULE");
    }
    if (!CModule::IncludeModule("catalog")) {
        $result = GetMessage("NIKAIMPORT_ERROR_CATALOG_NOT_MODULE");
    }
    switch ($_REQUEST["action"]) {
        /**
         * запрос авторизация пользователя API
         */
        case "settingsloading":
            $dataGet  = http_build_query(
                array(
                    "mode"     => "auth",
                    "settings" => "loading",
                    "login"    => isset($_REQUEST["login"])    ? $_REQUEST["login"]    : false,
                    "password" => isset($_REQUEST["password"]) ? $_REQUEST["password"] : false,
                )
            );
            $result_query  = file_get_contents($url . "?" . $dataGet);
            $settings      = \Bitrix\Main\Web\Json::decode($result_query);
            $sections      = isset($settings["SECTIONS"]) && is_array($settings["SECTIONS"]) ? $settings["SECTIONS"] : array();
            $extra         = isset($settings["EXTRA"]) ? $settings["EXTRA"] : '';
            $sections_html = \Bitrix\NikaImport\AdminHelper::getSectionsHtml($sections);
            $result_ar     = explode("\n", $result_query);
            $result        = "";
            if (isset($result_ar[0]) && $result_ar[0] == "failure") {
                if ($result_ar[0] == "failure") {
                    $error   = isset($result_ar[1]) ? $result_ar[1] : GetMessage("NIKAIMPORT_NOT_LOGIN_PASS");
                    $restart = "";
                } else {
                    $error   = GetMessage("NIKAIMPORT_NOT_LOGIN_PASS");
                    $restart = "";
                }
            } else {
                if (count($sections)) {
                    $result .= '<b>' . getMessage("NIKAIMPORT_API_SECTIONS_HEADER") . '</b>';
                    $result .= $sections_html;
                } else {
                    $result .= getMessage("NIKAIMPORT_API_NO_SECTIONS");
                }
                $result .= "<input type=\"hidden\" name=\"SECTIONS_AR\" value='".serialize($sections)."'>";
                if (strlen($extra) > 5) {
                    $result .= '<br><br><b>' . getMessage("NIKAIMPORT_API_EXTRA") . '</b>';
                } else {
                    $result .= '<br><br>' . getMessage("NIKAIMPORT_API_NO_EXTRA");
                }
                $result .= '<br><input type="text" name="EXTRA" id="EXTRA" size="50" maxlength="255" value="'.$extra.'" placeholder="$extra=$extra*1.50;">';
                $result .= getMessage("NIKAIMPORT_API_EXTRA_DESCR");
            }
            break;
        /**
         * стоп импорта
         */
        case "stop_import":
            if (isset($_SESSION["NIKAIMPORT"])) {
                unset($_SESSION["NIKAIMPORT"]);
            }
            $result = GetMessage("NIKAIMPORT_STATE_STOPPED");
            $tab    = 3;
            if (isset($_REQUEST["tab"]) && $_REQUEST["tab"] == "products") {
                $tab = 4;
            }
            $restart = "window.location.href = \"/bitrix/admin/nikaimport_admin.php?lang=ru&tabControl_active_tab=edit$tab\"";
            break;
        /**
         * старт импорта
         */
        case "start_import":
            if (!isset($_SESSION["NIKAIMPORT"])) {
                $_SESSION["NIKAIMPORT"] = array(
                    "START_IMPORT"   => time(),
                    "STEP"           => 0,
                    "ACTION"         => "",
                    "PARAMS"         => \Bitrix\NikaImport\AdminHelper::getParamsNikaImport(),
                    "SESSION_NAME"   => false,
                    "SESSION_ID"     => false,
                    "SESSION_HASH"   => false,
                    "TEMP_DIR"       => false,
                    "PARAMS_IMPORT"  => array(),
                    "COUNT_ELEMENTS" => 0,
                    "LOAD_ELEMENTS"  => 0,
                    "FILES_XML"      => array(),
                    "INDEX"          => array(
                        "DONE"    => 0,
                        "TOTAL"   => 0,
                        "DEL"     => 0,
                        "ADD"     => 0,
                        "LAST_ID" => 0,
                    )
                );
            }
            switch ($_SESSION["NIKAIMPORT"]["ACTION"]) {
                case "IMPORT_XML":
                    $step          = $_SESSION["NIKAIMPORT"]["STEP"];
                    $ABS_FILE_NAME = isset($_SESSION["NIKAIMPORT"]["FILES_XML"][$step]) ? $_SESSION["NIKAIMPORT"]["FILES_XML"][$step] : false;
                    if (!$ABS_FILE_NAME) {
                        $result  = GetMessage("NIKAIMPORT_IMPORT_OK");
                        $restart = "document.getElementById(\"btn_import\").classList.remove(\"adm-btn-disabled\");document.getElementById(\"btn_import_product\").classList.remove(\"adm-btn-disabled\")";
                        DeleteDirFilesEx(substr($_SESSION["NIKAIMPORT"]["TEMP_DIR"], strlen($_SERVER["DOCUMENT_ROOT"])));
                        DeleteDirFilesEx("/bitrix/cache/".$_SESSION["NIKAIMPORT"]["PARAMS"]["SITE_ID"]."/bitrix/menu/");
                        unset($_SESSION["NIKAIMPORT"]);
                        break;
                    }
                    $WORK_DIR_NAME = $_SESSION["NIKAIMPORT"]["WORK_DIR_NAME"][$step];
                    if (!isset($_SESSION["NIKAIMPORT"]["BX_CML2_IMPORT"])) {
                        $_SESSION["NIKAIMPORT"]["BX_CML2_IMPORT"] = array(
                            "ABS_FILE_NAME" => $ABS_FILE_NAME,
                            "WORK_DIR_NAME" => $WORK_DIR_NAME,
                            "TEMP_DIR"      => $_SESSION["NIKAIMPORT"]["TEMP_DIR"],
                            "SECTION_MAP"   => false,
                            "PRICES_MAP"    => false,
                            "NS"            => array(
                                "STEP" => 0
                            ),
                        );
                    }
                    $arParams = array(
                        "IBLOCK_TYPE"                 => "",
                        "USE_CRC"                     => (isset($_SESSION["NIKAIMPORT"]["PARAMS"]["CRC"]) && $_SESSION["NIKAIMPORT"]["PARAMS"]["CRC"] == "Y") ? true : false, // Использовать контрольные суммы элементов для оптимизации обновления каталога
                        "USE_OFFERS"                  => "Y", // Загружать торговые предложения (характеристики) в отдельный инфоблок
                        "FORCE_OFFERS"                => "Y", // Цены только в инфоблоке торговых предложений
                        "INTERVAL"                    => isset($_SESSION["NIKAIMPORT"]["PARAMS"]["INTERVAL"]) ? $_SESSION["NIKAIMPORT"]["PARAMS"]["INTERVAL"] : 30, // Интервал одного шага в секундах (0 - выполнять загрузку за один шаг)
                        "USE_IBLOCK_TYPE_ID"          => "Y", // При выгрузке учитывать тип инфоблока
                        "TRANSLIT_ON_ADD"             => "Y", // Транслитерировать символьный код из названия при добавлении элемента (товара) или раздела (групп товаров)
                        "TRANSLIT_ON_UPDATE"          => "N", // Транслитерировать символьный код из названия при обновлении элемента (товара) или раздела (групп товаров)
                        "SKIP_ROOT_SECTION"           => "Y", // Не импортировать верхний уровень группы товаров, если он единственный
                        "SECTION_ACTION"              => "N", // Что делать с группами, отсутствующими в файле импорта:
                        "SITE_LIST"                   => "s1",
                        "DISABLE_CHANGE_PRICE_NAME"   => "N", // Не менять код (название) типа цены, если используется внешний код (XML_ID)
                        "USE_IBLOCK_PICTURE_SETTINGS" => isset($_SESSION["NIKAIMPORT"]["PARAMS"]["IBLOCK_PICTURE_SETTINGS"]) ? $_SESSION["NIKAIMPORT"]["PARAMS"]["IBLOCK_PICTURE_SETTINGS"] : "Y", // Использовать настройки инфоблока для обработки изображений
                        "GENERATE_PREVIEW"            => isset($_SESSION["NIKAIMPORT"]["PARAMS"]["GENERATE_PREVIEW"]) ? $_SESSION["NIKAIMPORT"]["PARAMS"]["GENERATE_PREVIEW"] : "Y", // Автоматически генерировать картинку анонса
                        "PREVIEW_WIDTH"               => isset($_SESSION["NIKAIMPORT"]["PARAMS"]["PREVIEW_WIDTH"]) ? $_SESSION["NIKAIMPORT"]["PARAMS"]["PREVIEW_WIDTH"] : 100, // Максимально допустимая ширина картинки анонса
                        "PREVIEW_HEIGHT"              => isset($_SESSION["NIKAIMPORT"]["PARAMS"]["PREVIEW_HEIGHT"]) ? $_SESSION["NIKAIMPORT"]["PARAMS"]["PREVIEW_HEIGHT"] : 100, // Максимально допустимая высота картинки анонса
                        "DETAIL_RESIZE"               => isset($_SESSION["NIKAIMPORT"]["PARAMS"]["DETAIL_RESIZE"]) ? $_SESSION["NIKAIMPORT"]["PARAMS"]["DETAIL_RESIZE"] : "Y", // Изменять детальную картинку,
                        "DETAIL_WIDTH"                => isset($_SESSION["NIKAIMPORT"]["PARAMS"]["DETAIL_WIDTH"]) ? $_SESSION["NIKAIMPORT"]["PARAMS"]["DETAIL_WIDTH"] : 300, // Максимально допустимая ширина детальной картинки
                        "DETAIL_HEIGHT"               => isset($_SESSION["NIKAIMPORT"]["PARAMS"]["DETAIL_HEIGHT"]) ? $_SESSION["NIKAIMPORT"]["PARAMS"]["DETAIL_HEIGHT"] : 300, // Максимально допустимая высота детальной картинки
                        "TRANSLIT_PARAMS"             => array(
                            "max_len"               => 100,
                            "change_case"           => "L",
                            "replace_space"         => "_",
                            "replace_other"         => "_",
                            "delete_repeat_replace" => true,
                        )
                    );
                    $ar_params  = array_merge($_SESSION["NIKAIMPORT"]["PARAMS_IMPORT"], array("files_dir" => $WORK_DIR_NAME));
                    $NS         = &$_SESSION["NIKAIMPORT"]["BX_CML2_IMPORT"]["NS"];
                    $strError   = "";
                    $strMessage = "";
                    if (strpos($ABS_FILE_NAME, "offers") !== false) {
                        $NS["IBLOCK_ID"] = $_SESSION["NIKAIMPORT"]["PARAMS"]["IBLOCK_ID_OFFERS"];
                    } else {
                        $NS["IBLOCK_ID"] = $_SESSION["NIKAIMPORT"]["PARAMS"]["IBLOCK_ID_CATALOG"];
                    }
                    if (isset($_SESSION["NIKAIMPORT"]["PARAMS"]["IBLOCK_TYPE"])) {
                        $arParams["IBLOCK_TYPE"] = $_SESSION["NIKAIMPORT"]["PARAMS"]["IBLOCK_TYPE"];
                    }
                    $_SESSION["NIKAIMPORT"]["BX_CML2_IMPORT"]["NS"]["IBLOCK_ID"] = $NS["IBLOCK_ID"];
                    if ($NS["STEP"] < 1) { // очищаем таблицу b_xml_tree
                        CIBlockXMLFile::DropTemporaryTables();
                        $strMessage = GetMessage("NIKAIMPORT_TABLES_DROPPED");
                        $NS["STEP"] = 1;
                    } elseif ($NS["STEP"] == 1) { // заполняем таблицу b_xml_tree
                        if (CIBlockXMLFile::CreateTemporaryTables()) {
                            $strMessage = GetMessage("NIKAIMPORT_TABLES_CREATED");
                            $NS["STEP"] = 2;
                            foreach (GetModuleEvents("catalog", "OnBeforeCatalogImport1C", true) as $arEvent) {
                                $strError = ExecuteModuleEventEx($arEvent, array($arParams, $ABS_FILE_NAME));
                            }
                        } else {
                            $strError = GetMessage("NIKAIMPORT_TABLE_CREATE_ERROR");
                        }
                    } elseif ($NS["STEP"] == 2) { // читаем файл и заполняем таблицу b_xml_tree
                        $fp = fopen($ABS_FILE_NAME, "rb");
                        $total = filesize($ABS_FILE_NAME);
                        if (($total > 0) && is_resource($fp)) {
                            $obXMLFile = new CIBlockXMLFile;
                            if ($obXMLFile->ReadXMLToDatabase($fp, $NS, $arParams["INTERVAL"])) {
                                $NS["STEP"] = 3;
                                $strMessage = GetMessage("NIKAIMPORT_FILE_READ");
                            } else {
                                $strMessage = GetMessage("NIKAIMPORT_FILE_PROGRESS", array("#PERCENT#" => $total > 0 ? round($obXMLFile->GetFilePosition() / $total * 100, 2) : 0));
                            }
                            $strMessage .= " " . str_replace($WORK_DIR_NAME, "", $ABS_FILE_NAME);
                            fclose($fp);
                        } else {
                            $result = "Error";
                        }
                    } elseif ($NS["STEP"] == 3) { // индексируем таблицу для ускорения доступа b_xml_tree
                        $obXMLFile = new CIBlockXMLFile;
                        if ($obXMLFile->IndexTemporaryTables()) {
                            $strMessage = GetMessage("NIKAIMPORT_INDEX_CREATED");
                            $NS["STEP"] = 4;
                        } else {
                            $strError = GetMessage("NIKAIMPORT_INDEX_CREATE_ERROR");
                        }
                    } elseif ($NS["STEP"] == 4) {
                        $obCatalog = new CIBlockCMLImportCustom;
                        $obCatalog->InitEx(
                            $NS,
                            $ar_params
                        );
                        $result = $obCatalog->ImportMetaData(array(1,2), $arParams["IBLOCK_TYPE"], $arParams["SITE_LIST"]);
                        if ($result === true) {
                            $strMessage = GetMessage("NIKAIMPORT_METADATA_IMPORTED"); // Метаданные импортированы успешно.
                            $NS["STEP"] = 5;
                        } elseif (is_array($result)) {
                            $strError = GetMessage("NIKAIMPORT_METADATA_ERROR") . implode("\n", $result);
                        } else {
                            $strError = GetMessage("NIKAIMPORT_METADATA_ERROR") . $result;
                        }
                    } elseif ($NS["STEP"] == 5) {
                        if (isset($_SESSION["NIKAIMPORT"]["SECTIONS_AR"]) && isset($_SESSION["NIKAIMPORT"]["SECTIONS_IMPORT_LAST"]) && end($_SESSION["NIKAIMPORT"]["SECTIONS_AR"]) == $_SESSION["NIKAIMPORT"]["SECTIONS_IMPORT_LAST"]) {
                            $strMessage = GetMessage("NIKAIMPORT_SECTIONS_CONTINUE");
                            $NS["STEP"] = 7;
                        } else {
                            $obCatalog = new CIBlockCMLImportCustom;
                            $obCatalog->InitEx(
                                $NS,
                                $ar_params
                            );
                            $result  = $obCatalog->ImportSectionsCustom($start_time, $arParams["INTERVAL"]);
                            if ($result <= 0) {
                                $strMessage = GetMessage("NIKAIMPORT_SECTIONS_IMPORTED");
                                $NS["STEP"] = 6;
                            } else {
                                $strMessage = GetMessage("NIKAIMPORT_SECTIONS_IMPORTED_STEP", array("#DONE#" => $result));
                            }
                        }
                    } elseif ($NS["STEP"] == 6) {
                        $obCatalog = new CIBlockCMLImportCustom;
                        $obCatalog->InitEx(
                            $NS,
                            $ar_params
                        );
                        $obCatalog->DeactivateSections($arParams["SECTION_ACTION"]);
                        $obCatalog->SectionsResort();
                        $strMessage = GetMessage("NIKAIMPORT_SECTION_DEA_DONE");
                        $NS["STEP"] = 7;
                    } elseif ($NS["STEP"] == 7) {
                        if (($NS["DONE"]["ALL"] <= 0) && $NS["XML_ELEMENTS_PARENT"]) {
                            if (CIBlockXMLFile::IsExistTemporaryTable()) {
                                $NS["DONE"]["ALL"] = CIBlockXMLFile::GetCountItemsWithParent($NS["XML_ELEMENTS_PARENT"]);
                            } else {
                                $strError = GetMessage("NIKAIMPORT_TABLE_EXIST_ERROR");
                            }
                        }
                        if ($strError == "") {
                            $obCatalog = new CIBlockCMLImportCustom;
                            $obCatalog->InitEx(
                                $NS,
                                $ar_params
                            );
                            $obCatalog->ReadCatalogData($_SESSION["NIKAIMPORT"]["BX_CML2_IMPORT"]["SECTION_MAP"], $_SESSION["NIKAIMPORT"]["BX_CML2_IMPORT"]["PRICES_MAP"]);
                            $result  = $obCatalog->ImportElements($start_time, $arParams["INTERVAL"]);
                            $counter = 0;
                            foreach ($result as $key => $value) {
                                $NS["DONE"][$key] += $value;
                                $counter += $value;
                            }
                            if (!$counter) {
                                $strMessage = GetMessage("NIKAIMPORT_DONE");
                                $NS["STEP"] = 8;
                            } elseif (strlen($obCatalog->LAST_ERROR)) {
                                $strError = $obCatalog->LAST_ERROR;
                            } else {
                                $strMessage = GetMessage(
                                    "NIKAIMPORT_PROGRESS",
                                    array(
                                        "#TOTAL#" => $NS["DONE"]["ALL"],
                                        "#DONE#"  => intval($NS["DONE"]["CRC"])
                                    )
                                );
                            }
                        }
                    } elseif ($NS["STEP"] == 8) {
                        if (isset($_SESSION["NIKAIMPORT"]["BX_CML2_IMPORT"]["ABS_FILE_NAME"]) && $_SESSION["NIKAIMPORT"]["BX_CML2_IMPORT"]["ABS_FILE_NAME"] == end($_SESSION["NIKAIMPORT"]["FILES_XML"])) {
                            $obCatalog = new CIBlockCMLImportCustom;
                            $obCatalog->Init($NS);
                            $result  = $obCatalog->DeactivateElements($start_time, $arParams["INTERVAL"], $_SESSION["NIKAIMPORT"]["PARAMS"]["IBLOCK_ID_CATALOG"]);
                            $counter = 0;
                            foreach ($result as $key => $value) {
                                $NS["DONE"][$key] += $value;
                                $counter += $value;
                            }
                            if (!$counter) {
                                $strMessage = GetMessage("NIKAIMPORT_DEA_DONE_PROD");
                                $NS["STEP"] = 9;
                            } else {
                                $_SESSION["NIKAIMPORT"]["DELETED"]["DEL"] += $NS["DONE"]["DEL"];
                                if (!isset($_SESSION["NIKAIMPORT"]["DELETED"]["ALL"])) {
                                    $_SESSION["NIKAIMPORT"]["DELETED"]["ALL"] = 0;
                                }
                                $_SESSION["NIKAIMPORT"]["DELETED"]["DONE"] += $counter;
                                $strMessage = GetMessage(
                                    "NIKAIMPORT_PROGRESS_CATALOG",
                                    array(
                                        "#DONE#"  => $_SESSION["NIKAIMPORT"]["DELETED"]["DONE"],
                                        "#TOTAL#" => $_SESSION["NIKAIMPORT"]["DELETED"]["ALL"],
                                        "#DEL#"   => $_SESSION["NIKAIMPORT"]["DELETED"]["DEL"],
                                    )
                                );
                            }
                        } else {
                            $strMessage = GetMessage("NIKAIMPORT_CONTINUE_PROD");
                            $NS["STEP"] = 9;
                        }
                    } elseif ($NS["STEP"] == 9) {
                        if (isset($_SESSION["NIKAIMPORT"]["BX_CML2_IMPORT"]["ABS_FILE_NAME"]) && $_SESSION["NIKAIMPORT"]["BX_CML2_IMPORT"]["ABS_FILE_NAME"] == end($_SESSION["NIKAIMPORT"]["FILES_XML"])) {
                            $obCatalog = new CIBlockCMLImportCustom;
                            $obCatalog->Init($NS);
                            $result_inx = $obCatalog->IndexedElements($start_time, $arParams["INTERVAL"]);
                            foreach ($result_inx as $key => $value) {
                                switch ($key) {
                                    case "TOTAL":
                                        $_SESSION["NIKAIMPORT"]["INDEX"]["TOTAL"] = ($value > 0) ? $value : 0;
                                        break;
                                    case "DONE":
                                        $_SESSION["NIKAIMPORT"]["INDEX"]["DONE"] += $value;
                                        break;
                                    default:
                                        $_SESSION["NIKAIMPORT"]["INDEX"][$key] = $value;
                                        break;
                                }
                            }
                            if ($_SESSION["NIKAIMPORT"]["INDEX"]["DONE"] >= $_SESSION["NIKAIMPORT"]["INDEX"]["TOTAL"]) {
                                $strMessage = GetMessage("NIKAIMPORT_DEA_DONE_INDEX");
                                $NS["STEP"] = 10;
                            } else {
                                $strMessage = GetMessage(
                                    "NIKAIMPORT_PROGRESS_INDEX",
                                    array(
                                        "#DONE#"  => $_SESSION["NIKAIMPORT"]["INDEX"]["DONE"],
                                        "#TOTAL#" => $_SESSION["NIKAIMPORT"]["INDEX"]["TOTAL"],
                                        "#DEL#"   => $_SESSION["NIKAIMPORT"]["INDEX"]["DEL"],
                                        "#ADD#"   => $_SESSION["NIKAIMPORT"]["INDEX"]["ADD"],
                                    )
                                );
                            }
                        } else {
                            $strMessage = GetMessage("NIKAIMPORT_CONTINUE_INDEX");
                            $NS["STEP"] = 10;
                        }
                    } else {
                        $NS["STEP"]++;
                    }
                    if ($strError) {
                        $error   = str_replace("<br>", "", $strError);
                        $restart = "";
                    } elseif ($NS["STEP"] <= 10) {
                        $result  = str_replace("<br>", "", $strMessage);
                        $restart = "importNika();";
                    } else {
                        foreach (GetModuleEvents("catalog", "OnSuccessCatalogImport1C", true) as $arEvent) {
                            ExecuteModuleEventEx($arEvent, array($arParams, $ABS_FILE_NAME));
                        }
                        $_SESSION["NIKAIMPORT"]["STEP"] = intval($_SESSION["NIKAIMPORT"]["STEP"]) + 1;
                        $result  = GetMessage("NIKAIMPORT_IMPORT_SUCCESS");
                        $restart = "importNika();";
                        unset($_SESSION["NIKAIMPORT"]["BX_CML2_IMPORT"]);
                    }
                    break;
                case "LOAD_XML":
                    $result_query = file_get_contents(
                        $_SESSION["NIKAIMPORT"]["PARAMS"]["API_URL"]
                        . "?" .
                        http_build_query(
                            array(
                                "mode" => "query"
                            )
                        ),
                        false,
                        stream_context_create(
                            array(
                                "http" => array(
                                    "method"  => "GET",
                                    "header"  => "Cookie: " . $_SESSION["NIKAIMPORT"]["SESSION_NAME"] . "=" . $_SESSION["NIKAIMPORT"]["SESSION_ID"]
                                )
                            )
                        )
                    );
                    if (strripos($result_query, "finished=yes") !== false) {
                        $_SESSION["NIKAIMPORT"]["ACTION"] = "IMPORT_XML";
                        $_SESSION["NIKAIMPORT"]["STEP"]   = 0;
                        $result  = GetMessage("NIKAIMPORT_IMPORT_START");
                        $restart = "importNika();";
                    } else {
                        $info   = stristr($result_query, "<", true);
                        $result = trim($info);
                        $xml    = str_replace($info, "", $result_query);
                        preg_match_all('/[0-9]+/', $info, $matches);
                        $load_elements  = isset($matches[0][0]) ? $matches[0][0] : 0;
                        $count_elements = isset($matches[0][1]) ? $matches[0][1] : 0;
                        if (!isset($_SESSION["NIKAIMPORT"]["COUNT_ELEMENTS"]) && $count_elements > 0) {
                            $_SESSION["NIKAIMPORT"]["COUNT_ELEMENTS"] = $count_elements;
                        }
                        if ($load_elements > 0) {
                            $_SESSION["NIKAIMPORT"]["LOAD_ELEMENTS"] = $load_elements;
                        }
                        $inx = "products";
                        /**
                         * признак обработки торговых предложений
                         */
                        if (strpos($info, GetMessage("NIKAIMPORT_I_OFFERS")) !== false) {
                            $_SESSION["NIKAIMPORT"]["BX_CML2_IMPORT"]["IMPORT_OFFERS"] = true;
                            $inx = "offers";
                        }
                        $index         = count($_SESSION["NIKAIMPORT"]["FILES_XML"]);
                        $filename      = "import_" . $inx . "_" . $index . ".xml";
                        $ABS_FILE_NAME = false;
                        $WORK_DIR_NAME = false;
                        $DIR_NAME      = $_SESSION["NIKAIMPORT"]["TEMP_DIR"];
                        if (strlen($DIR_NAME) > 0) {
                            $filename = preg_replace("#^(/tmp/|upload/1c/webdata)#", "", $filename);
                            $filename = trim(str_replace("\\", "/", trim($filename)), "/");
                            $io       = CBXVirtualIo::GetInstance();
                            $bBadFile = HasScriptExtension($filename) || IsFileUnsafe($filename) || !$io->ValidatePathString("/" . $filename);
                            if (!$bBadFile) {
                                $filename = trim(str_replace("\\", "/", trim($filename)), "/");
                                $FILE_NAME = rel2abs($DIR_NAME, "/" . $filename);
                                if ((strlen($FILE_NAME) > 1) && ($FILE_NAME === "/" . $filename)) {
                                    $ABS_FILE_NAME = $DIR_NAME . $FILE_NAME;
                                    $WORK_DIR_NAME = substr($ABS_FILE_NAME, 0, strrpos($ABS_FILE_NAME, "/") + 1);
                                }
                            }
                        }
                        $ABS_FILE_NAME = str_replace("//", "/", $ABS_FILE_NAME);
                        CheckDirPath($ABS_FILE_NAME);
                        if ($fp = fopen($ABS_FILE_NAME, "ab")) {
                            $result_xml = fwrite($fp, $xml);
                            if ($result_xml === (function_exists("mb_strlen") ? mb_strlen($xml, "latin1") : strlen($xml))) {
                                /**
                                 * success
                                 */
                                $_SESSION["NIKAIMPORT"]["FILES_XML"][$index]     = str_replace("//", "/", $ABS_FILE_NAME);
                                $_SESSION["NIKAIMPORT"]["WORK_DIR_NAME"][$index] = str_replace("//", "/", $WORK_DIR_NAME);
                                $result  = $result . "\r" . GetMessage("NIKAIMPORT_LOAD_XML_SUCCESS", array("#FILE_NAME#" => $filename));
                                $restart = "importNika();";
                            } else {
                                $error   = GetMessage("NIKAIMPORT_ERROR_FILE_WRITE", array("#FILE_NAME#" => $filename));
                                $restart = "";
                            }
                            fclose($fp);
                        } else {
                            $error   = GetMessage("NIKAIMPORT_ERROR_FILE_OPEN", array("#FILE_NAME#" => $filename));
                            $restart = "";
                        }
                    }
                    break;
                case "INIT":
                    if (!$_SESSION["NIKAIMPORT"]["SESSION_ID"]) {
                        $_SESSION["NIKAIMPORT"]["ACTION"] = "AUTH_USER";
                        $result  = GetMessage("NIKAIMPORT_AUTH_USER");
                        $restart = "importNika();";
                    } else {
                        $result_query = file_get_contents(
                            $_SESSION["NIKAIMPORT"]["PARAMS"]["API_URL"]
                            . "?" .
                            http_build_query(
                                array(
                                    "mode" => "init"
                                )
                            ),
                            false,
                            stream_context_create(
                                array(
                                    "http" => array(
                                        "method"  => "GET",
                                        "header"  => "Cookie: " . $_SESSION["NIKAIMPORT"]["SESSION_NAME"] . "=" . $_SESSION["NIKAIMPORT"]["SESSION_ID"]
                                    )
                                )
                            )
                        );
                        $result_query = trim($result_query);
                        $result_ar    = explode("\n", $result_query);
                        if (isset($result_ar[0])) {
                            if ($result_ar[0] == "success") {
                                $_SESSION["NIKAIMPORT"]["ACTION"] = "IMPORT";
                                $result  = GetMessage("NIKAIMPORT_SUCCESS_INIT_AJAX");
                                $restart = "importNika();";
                            } else {
                                $error   = GetMessage("NIKAIMPORT_ERROR_INIT_AJAX");
                                $restart = "";
                            }
                        } else {
                            $error   = GetMessage("NIKAIMPORT_AJAX_ERROR");
                            $restart = "";
                        }
                    }
                    break;
                case "AUTH_USER":
                    $dataGet = http_build_query(
                        array(
                            "mode"     => "auth",
                            "login"    => $_SESSION["NIKAIMPORT"]["PARAMS"]["API_LOGIN"],
                            "password" => $_SESSION["NIKAIMPORT"]["PARAMS"]["API_PASSWORD"],
                        )
                    );
                    $result_query = file_get_contents($_SESSION["NIKAIMPORT"]["PARAMS"]["API_URL"] . "?" . $dataGet);
                    $result_ar    = explode("\n", $result_query);
                    if (isset($result_ar[0])) {
                        if ($result_ar[0] == "success") {
                            $_SESSION["NIKAIMPORT"]["ACTION"]       = "INIT";
                            $_SESSION["NIKAIMPORT"]["SESSION_NAME"] = isset($result_ar[1]) ? $result_ar[1] : false;
                            $_SESSION["NIKAIMPORT"]["SESSION_ID"]   = isset($result_ar[2]) ? $result_ar[2] : false;
                            $_SESSION["NIKAIMPORT"]["SESSION_HASH"] = isset($result_ar[3]) ? $result_ar[3] : false;
                            $result  = GetMessage("NIKAIMPORT_SUCCESS_AUTH_USER");
                            $restart = "importNika();";
                        } else {
                            $error   = GetMessage("NIKAIMPORT_ERROR_AUTH_USER");
                            $restart = "";
                        }
                    } else {
                        $error   = GetMessage("NIKAIMPORT_AJAX_ERROR");
                        $restart = "";
                    }
                    break;
                case "IMPORT":
                    switch ($_SESSION["NIKAIMPORT"]["STEP"]) {
                        case 2:
                            $_SESSION["NIKAIMPORT"]["ACTION"] = "LOAD_XML";
                            $_SESSION["NIKAIMPORT"]["STEP"]   = 0;
                            $result  = GetMessage("NIKAIMPORT_LOAD_XML");
                            $restart = "importNika();";
                            break;
                        case 1:
                            /**
                             * получаем IBLOCK для импорта
                             */
                            $ibl = CIBlock::GetByID($_SESSION["NIKAIMPORT"]["PARAMS"]["IBLOCK_ID_CATALOG"])->fetch();
                            $ibo = CIBlock::GetByID($_SESSION["NIKAIMPORT"]["PARAMS"]["IBLOCK_ID_OFFERS"])->fetch();
                            if (!$ibl) {
                                $_SESSION["NIKAIMPORT"]["ACTION"] = "ADD_IBLOCK";
                                $_SESSION["NIKAIMPORT"]["STEP"]   = 1;
                                $error   = GetMessage("NIKAIMPORT_ERROR_NOT_FOUND_CATALOG") . "<br>";
                                $restart = "importNika();";
                                break;
                            }
                            if (!$ibo) {
                                $_SESSION["NIKAIMPORT"]["ACTION"] = "ADD_IBLOCK";
                                $_SESSION["NIKAIMPORT"]["STEP"]   = 2;
                                $error   = GetMessage("NIKAIMPORT_ERROR_NOT_FOUND_OFFERS") . "<br>";
                                $restart = "importNika();";
                                break;
                            }
                            $_SESSION["NIKAIMPORT"]["PARAMS"]["SITE_ID"]     = isset($ibl["LID"]) && !empty($ibl["LID"]) ? $ibl["LID"] : "s1";
                            $_SESSION["NIKAIMPORT"]["PARAMS"]["IBLOCK_ID"]   = isset($ibl["ID"]) ? $ibl["ID"] : trim(COption::GetOptionString("catalog", "1C_IBLOCK_ID"));
                            $_SESSION["NIKAIMPORT"]["PARAMS"]["XML_ID"]      = $ibl["XML_ID"];
                            $_SESSION["NIKAIMPORT"]["PARAMS"]["CODE"]        = $ibl["CODE"];
                            $_SESSION["NIKAIMPORT"]["PARAMS"]["IBLOCK_TYPE"] = $ibl["IBLOCK_TYPE_ID"];
                            $DIR_NAME = $_SERVER["DOCUMENT_ROOT"] . "/" . COption::GetOptionString("main", "upload_dir", "upload") . "/nika_exchange/";
                            DeleteDirFilesEx(substr($DIR_NAME, strlen($_SERVER["DOCUMENT_ROOT"])));
                            CheckDirPath($DIR_NAME);
                            if (!is_dir($DIR_NAME)) {
                                $error   = GetMessage("NIKAIMPORT_ERROR_INIT");
                                $restart = "";
                            } else {
                                $ht_name = $DIR_NAME . ".htaccess";
                                if (!file_exists($ht_name)) {
                                    $fp = fopen($ht_name, "w");
                                    if ($fp) {
                                        fwrite($fp, "Deny from All");
                                        fclose($fp);
                                        @chmod($ht_name, BX_FILE_PERMISSIONS);
                                    }
                                }
                                $_SESSION["NIKAIMPORT"]["TEMP_DIR"] = $DIR_NAME;
                            }
                            $_SESSION["NIKAIMPORT"]["PARAMS_IMPORT"] = array(
                                "files_dir"                 => false,
                                "use_crc"                   => (isset($_SESSION["NIKAIMPORT"]["PARAMS"]["CRC"]) && $_SESSION["NIKAIMPORT"]["PARAMS"]["CRC"] == "Y") ? true : false,
                                "preview"                   => false,
                                "detail"                    => false,
                                "use_offers"                => "Y", // Загружать торговые предложения (характеристики) в отдельный инфоблок
                                "force_offers"              => "Y", // Цены только в инфоблоке торговых предложений
                                "use_iblock_type_id"        => "Y", // При выгрузке учитывать тип инфоблока
                                "translit_on_add"           => "Y", // Транслитерировать символьный код из названия при добавлении элемента (товара) или раздела (групп товаров)
                                "translit_on_update"        => "N", // Транслитерировать символьный код из названия при обновлении элемента (товара) или раздела (групп товаров)
                                "translit_params"           => array(
                                    "max_len"               => 100,
                                    "change_case"           => "L",
                                    "replace_space"         => "_",
                                    "replace_other"         => "_",
                                    "delete_repeat_replace" => true,
                                ),
                                "skip_root_section"         => "Y", // Не импортировать верхний уровень группы товаров, если он единственный
                                "disable_change_price_name" => "N", // Не менять код (название) типа цены, если используется внешний код (XML_ID)
                            );
                            $preview = false;
                            $detail  = false;
                            if ($_SESSION["NIKAIMPORT"]["PARAMS"]["IBLOCK_PICTURE_SETTINGS"] == "Y") {
                                $preview = true;
                                $detail  = true;
                            } else {
                                if ($_SESSION["NIKAIMPORT"]["PARAMS"]["GENERATE_PREVIEW"] == "Y") {
                                    $preview = array(
                                        intval($_SESSION["NIKAIMPORT"]["PARAMS"]["PREVIEW_WIDTH"])  > 1 ? intval($_SESSION["NIKAIMPORT"]["PARAMS"]["PREVIEW_WIDTH"])  : 100,
                                        intval($_SESSION["NIKAIMPORT"]["PARAMS"]["PREVIEW_HEIGHT"]) > 1 ? intval($_SESSION["NIKAIMPORT"]["PARAMS"]["PREVIEW_HEIGHT"]) : 100,
                                    );
                                } else {
                                    $preview = false;
                                }
                                if ($_SESSION["NIKAIMPORT"]["PARAMS"]["DETAIL_RESIZE"] == "Y") {
                                    $detail = array(
                                        intval($_SESSION["NIKAIMPORT"]["PARAMS"]["DETAIL_WIDTH"])  > 1 ? intval($_SESSION["NIKAIMPORT"]["PARAMS"]["DETAIL_WIDTH"])  : 300,
                                        intval($_SESSION["NIKAIMPORT"]["PARAMS"]["DETAIL_HEIGHT"]) > 1 ? intval($_SESSION["NIKAIMPORT"]["PARAMS"]["DETAIL_HEIGHT"]) : 300,
                                    );
                                } else {
                                    $detail = false;
                                }
                            }
                            $_SESSION["NIKAIMPORT"]["PARAMS_IMPORT"]["preview"] = $preview;
                            $_SESSION["NIKAIMPORT"]["PARAMS_IMPORT"]["detail"]  = $detail;
                            $_SESSION["NIKAIMPORT"]["STEP"] = 2;
                            $result  = GetMessage("NIKAIMPORT_TEST_PARAMS_SUCCESS");
                            $restart = "importNika();";
                            break;
                        default:
                            $_SESSION["NIKAIMPORT"]["STEP"] = 1;
                            $result  = GetMessage("NIKAIMPORT_TEST_PARAMS");
                            $restart = "importNika();";
                            break;
                    }
                    break;
                case "ADD_IBLOCK":
                    switch (intval($_SESSION["NIKAIMPORT"]["STEP"])) {
                        case 3:
                            $_SESSION["NIKAIMPORT"]["PARAMS"]["ADD_IBLOCK"] = 0;
                            if (\Bitrix\NikaImport\AdminHelper::saveParamsNikaImport($_SESSION["NIKAIMPORT"]["PARAMS"])) {
                                $_SESSION["NIKAIMPORT"]["PARAMS"] = \Bitrix\NikaImport\AdminHelper::getParamsNikaImport();
                                $_SESSION["NIKAIMPORT"]["STEP"]   = 0;
                                $_SESSION["NIKAIMPORT"]["ACTION"] = "INIT";
                                $result  = GetMessage("NIKAIMPORT_UPLOAD_PARAMS");
                                $restart = "importNika();";
                            } else {
                                $error   = $ib->LAST_ERROR;
                                $restart = false;
                                unset($_SESSION["NIKAIMPORT"]);
                            }
                            break;
                        case 2:
                            $ib = new CIBlock;
                            $arFields = Array(
                                "ACTIVE"           => "Y",
                                "NAME"             => GetMessage("NIKAIMPORT_NAME_OFFERS"),
                                "CODE"             => "offers_nika",
                                "SITE_ID"          => "s1",
                                "IBLOCK_TYPE_ID"   => "offers",
                                "INDEX_ELEMENT"    => "Y",
                                "INDEX_SECTION"    => "Y",
                                "LIST_PAGE_URL"    => "#SITE_DIR#/catalog/",
                                "SECTION_PAGE_URL" => "#SITE_DIR#/catalog/",
                                "DETAIL_PAGE_URL"  => "#PRODUCT_URL#",
                                "GROUP_ID"         => array(
                                    1 => "X",
                                    2 => "R"
                                ),
                            );
                            $ID = $ib->add($arFields);
                            if ($ib->LAST_ERROR) {
                                $error   = $ib->LAST_ERROR;
                                $restart = false;
                                unset($_SESSION["NIKAIMPORT"]);
                            } else {
                                $ibp    = new CIBlockProperty;
                                $PropID = $ibp->Add(
                                    array(
                                        "IBLOCK_ID"     => $ID,
                                        "NAME"          => GetMessage("NIKAIMPORT_NAME_ELEMENT"),
                                        "ACTIVE"        => "Y",
                                        "SORT"          => 100,
                                        "CODE"          => "CML2_LINK",
                                        "PROPERTY_TYPE" => "E:SKU"
                                    )
                                );
                                \Bitrix\Catalog\CatalogIblockTable::add(
                                    array(
                                        "IBLOCK_ID"         => $ID,
                                        "PRODUCT_IBLOCK_ID" => $_SESSION["NIKAIMPORT"]["PARAMS"]["IBLOCK_ID_CATALOG"],
                                        "SKU_PROPERTY_ID"   => $PropID
                                    )
                                );
                                $_SESSION["NIKAIMPORT"]["PARAMS"]["IBLOCK_ID_OFFERS"] = $ID;
                                $_SESSION["NIKAIMPORT"]["ACTION"] = "ADD_IBLOCK";
                                $_SESSION["NIKAIMPORT"]["STEP"] = 3;
                                $result  = GetMessage("NIKAIMPORT_ADD_IBLOCK_OK", array("#IBLOCK_ID#" => $ID));
                                $restart = "importNika();";
                            }
                            break;
                        case 1:
                        default:
                            $ib = new CIBlock;
                            $arFields = Array(
                                "ACTIVE"           => "Y",
                                "NAME"             => GetMessage("NIKAIMPORT_NAME_IBLOCK"),
                                "CODE"             => "catalog",
                                "SITE_ID"          => "s1",
                                "IBLOCK_TYPE_ID"   => "catalog",
                                "INDEX_ELEMENT"    => "Y",
                                "INDEX_SECTION"    => "Y",
                                "LIST_PAGE_URL"    => "#SITE_DIR#/catalog/",
                                "SECTION_PAGE_URL" => "#SITE_DIR#/catalog/#SECTION_CODE#/",
                                "DETAIL_PAGE_URL"  => "#SITE_DIR#/catalog/#SECTION_CODE#/#ELEMENT_CODE#/",
                                "GROUP_ID"         => array(
                                    1 => "X",
                                    2 => "R"
                                ),
                            );
                            $ID = $ib->add($arFields);
                            if ($ib->LAST_ERROR) {
                                $error   = $ib->LAST_ERROR;
                                $restart = false;
                                unset($_SESSION["NIKAIMPORT"]);
                            } else {
                                \Bitrix\Catalog\CatalogIblockTable::add(
                                    array(
                                        "IBLOCK_ID" => $ID
                                    )
                                );
                                $_SESSION["NIKAIMPORT"]["PARAMS"]["IBLOCK_ID_CATALOG"] = $ID;
                                $_SESSION["NIKAIMPORT"]["ACTION"] = "ADD_IBLOCK";
                                $_SESSION["NIKAIMPORT"]["STEP"] = 2;
                                $result  = GetMessage("NIKAIMPORT_ADD_IBLOCK_OK", array("#IBLOCK_ID#" => $ID));
                                $restart = "importNika();";
                            }
                            break;
                    }
                default:
                    if (empty($_SESSION["NIKAIMPORT"]["ACTION"])) {
                        /**
                         * создать новые инфоблоки
                         */
                        if (isset($_SESSION["NIKAIMPORT"]["PARAMS"]["ADD_IBLOCK"]) && $_SESSION["NIKAIMPORT"]["PARAMS"]["ADD_IBLOCK"] == "Y") {
                            $_SESSION["NIKAIMPORT"]["STEP"]   = 1;
                            $_SESSION["NIKAIMPORT"]["ACTION"] = "ADD_IBLOCK";
                            $result  = GetMessage("NIKAIMPORT_ADD_IBLOCKS");
                            $restart = "importNika();";
                        }
                        /**
                         * запускаем импорт с импорта категорий
                         */
                        else {
                            $_SESSION["NIKAIMPORT"]["STEP"]   = 0;
                            $_SESSION["NIKAIMPORT"]["ACTION"] = "INIT";
                            $result  = GetMessage("NIKAIMPORT_UPLOAD_PARAMS");
                            $restart = "importNika();";
                        }
                    } else {
                        unset($_SESSION["NIKAIMPORT"]);
                        $restart = "importNika();";
                    }
                    break;
            }
            break;
        /**
         * собирает товары
         */
        case "products_import":
            if ((isset($_SESSION["NIKAIMPORT"]) && $_SESSION["NIKAIMPORT"]["ACTION"] != "PRODUCTS") || !isset($_SESSION["NIKAIMPORT"])) {
                unset($_SESSION["NIKAIMPORT"]);
                $_SESSION["NIKAIMPORT"] = array(
                    "START_IMPORT" => time(),
                    "STEP"         => 0,
                    "ACTION"       => "PRODUCTS",
                    "PARAMS"       => \Bitrix\NikaImport\AdminHelper::getParamsNikaImport(),
                    "DELETED"      => array(
                        "ID"   => 0,
                        "ALL"  => 0,
                        "DONE" => 0,
                        "DEL"  => 0,
                    ),
                    "INDEX"     => array(
                        "DONE"  => 0,
                        "TOTAL" => 0,
                        "DEL"   => 0,
                        "ADD"   => 0,
                    ),
                );
            }
            switch ($_SESSION["NIKAIMPORT"]["STEP"]) {
                case 3:
                    $restart    = "importNika('products');";
                    $obCatalog  = new CIBlockCMLImportCustom;
                    $result_inx = $obCatalog->IndexedElements(
                        $start_time,
                        $_SESSION["NIKAIMPORT"]["PARAMS"]["INTERVAL"]
                    );
                    foreach ($result_inx as $key => $value) {
                        switch ($key) {
                            case "TOTAL":
                                $_SESSION["NIKAIMPORT"]["INDEX"]["TOTAL"] = ($value > 0) ? $value : 0;
                                break;
                            case "DONE":
                                $_SESSION["NIKAIMPORT"]["INDEX"]["DONE"] += $value;
                                break;
                            default:
                                $_SESSION["NIKAIMPORT"]["INDEX"][$key] = $value;
                                break;
                        }
                    }
                    if ($_SESSION["NIKAIMPORT"]["INDEX"]["DONE"] >= $_SESSION["NIKAIMPORT"]["INDEX"]["TOTAL"]) {
                        $result  = GetMessage("NIKAIMPORT_DEA_DONE_INDEX");
                        $restart = "document.getElementById(\"btn_import\").classList.remove(\"adm-btn-disabled\");document.getElementById(\"btn_import_product\").classList.remove(\"adm-btn-disabled\")";
                    } else {
                        $result = GetMessage(
                            "NIKAIMPORT_PROGRESS_INDEX",
                            array(
                                "#DONE#"  => $_SESSION["NIKAIMPORT"]["INDEX"]["DONE"],
                                "#TOTAL#" => $_SESSION["NIKAIMPORT"]["INDEX"]["TOTAL"],
                                "#DEL#"   => $_SESSION["NIKAIMPORT"]["INDEX"]["DEL"],
                                "#ADD#"   => $_SESSION["NIKAIMPORT"]["INDEX"]["ADD"],
                            )
                        );
                    }
                    break;
                case 2:
                    $restart = "importNika('products');";
                    $result  = GetMessage("NIKAIMPORT_LOAD_INDEX");
                    $_SESSION["NIKAIMPORT"]["STEP"] = 3;
                    break;
                case 1:
                    $restart   = "importNika('products');";
                    $counter   = 0;
                    $obCatalog = new CIBlockCMLImportCustom;
                    $result_d  = $obCatalog->DeactivateElements(
                        $start_time,
                        $_SESSION["NIKAIMPORT"]["PARAMS"]["INTERVAL"],
                        $_SESSION["NIKAIMPORT"]["PARAMS"]["IBLOCK_ID_CATALOG"]
                    );
                    foreach ($result_d as $key => $value) {
                        $counter += $value;
                    }
                    if (!$counter) {
                        $result  = GetMessage("NIKAIMPORT_DEA_DONE_PROD");
                        $_SESSION["NIKAIMPORT"]["STEP"] = 2;
                    } else {
                        $_SESSION["NIKAIMPORT"]["DELETED"]["DONE"] += $counter;
                        $_SESSION["NIKAIMPORT"]["DELETED"]["DEL"]  += $result["DEL"];
                        $result = GetMessage(
                            "NIKAIMPORT_PROGRESS_CATALOG",
                            array(
                                "#DONE#"  => $_SESSION["NIKAIMPORT"]["DELETED"]["DONE"],
                                "#TOTAL#" => $_SESSION["NIKAIMPORT"]["DELETED"]["ALL"],
                                "#DEL#"   => $_SESSION["NIKAIMPORT"]["DELETED"]["DEL"],
                            )
                        );
                    }
                    break;
                default:
                    $restart = "importNika('products');";
                    $result  = GetMessage("NIKAIMPORT_LOAD_PROD");
                    $_SESSION["NIKAIMPORT"]["STEP"] = 1;
                    break;
            }
            break;
        default:
            $error = GetMessage("NIKAIMPORT_AJAX_ERROR");
    }
}
if ($error === false) {
    $data = array(
        "restart" => $restart,
        "result"  => \Bitrix\Main\Text\Encoding::convertEncoding($result, LANG_CHARSET, "UTF-8"),
        "error"   => false
    );
} else {
    $data = array(
        "restart" => $restart,
        "result"  => \Bitrix\Main\Text\Encoding::convertEncoding($error, LANG_CHARSET, "UTF-8"),
        "error"   => true
    );
}
$APPLICATION->RestartBuffer();
header("Content-Type: application/x-javascript; charset=".LANG_CHARSET);
echo json_encode($data);
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin_after.php");
