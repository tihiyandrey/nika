<?php

includeModuleLangFile(__FILE__);
if (class_exists('nikaimport'))
	return;

class NikaImport extends CModule
{
	var $MODULE_ID = 'nikaimport';
	var $MODULE_VERSION;
	var $MODULE_VERSION_DATE;
	var $MODULE_NAME;
	var $MODULE_DESCRIPTION;
	var $MODULE_GROUP_RIGHTS = 'Y';

	public function __construct()
	{
		$arModuleVersion = array();

		$path = str_replace('\\', '/', __FILE__);
		$path = substr($path, 0, strlen($path) - strlen('/index.php'));
		include($path.'/version.php');

		if (is_array($arModuleVersion) && array_key_exists('VERSION', $arModuleVersion))
		{
			$this->MODULE_VERSION = $arModuleVersion['VERSION'];
			$this->MODULE_VERSION_DATE = $arModuleVersion['VERSION_DATE'];
		}

		$this->MODULE_NAME = getMessage('NIKAIMPORT_MODULE_NAME');
		$this->MODULE_DESCRIPTION = getMessage('NIKAIMPORT_MODULE_DESCRIPTION');
	}

	function doInstall()
	{
		global $DB, $APPLICATION;

		$this->installFiles();
		$this->installDB();

		$GLOBALS['APPLICATION']->includeAdminFile(
			getMessage('NIKAIMPORT_INSTALL_TITLE'),
			$_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/nikaimport/install/step1.php'
		);
	}

	function installDB()
	{
		global $DB, $APPLICATION;

		$this->errors = false;
		if (!$DB->query("SELECT 'x' FROM b_nikaimport", true))
		{
			$createTestTemplates = true;
			$this->errors = $DB->runSQLBatch($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/nikaimport/install/db/'.strtolower($DB->type).'/install.sql');
		}

		if ($this->errors !== false)
		{
			$APPLICATION->throwException(implode('', $this->errors));

			return false;
		}

		registerModule($this->MODULE_ID);

		return true;
	}

	function installEvents()
	{
		return true;
	}

	function installFiles()
	{
		copyDirFiles(
			$_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/nikaimport/install/admin',
			$_SERVER['DOCUMENT_ROOT'] . '/bitrix/admin',
			true, true
		);
		return true;
	}

	function doUninstall()
	{
		global $DOCUMENT_ROOT, $APPLICATION, $step;

		$step = intval($step);
		if ($step < 2)
		{
			$APPLICATION->includeAdminFile(
				getMessage('NIKAIMPORT_UNINSTALL_TITLE'),
				$DOCUMENT_ROOT . '/bitrix/modules/nikaimport/install/unstep1.php'
			);
		}
		elseif ($step == 2)
		{
			$this->uninstallDB(array('savedata' => $_REQUEST['savedata']));
			$this->uninstallFiles();
			$APPLICATION->includeAdminFile(
				getMessage('NIKAIMPORT_UNINSTALL_TITLE'),
				$DOCUMENT_ROOT . '/bitrix/modules/nikaimport/install/unstep2.php'
			);
		}
	}

	function uninstallDB($arParams = array())
	{
		global $APPLICATION, $DB, $errors;

		$this->errors = false;

		if (!$arParams['savedata'])
		{
			$this->errors = $DB->runSQLBatch(
				$_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/nikaimport/install/db/'.strtolower($DB->type).'/uninstall.sql'
			);
		}

		if ($this->errors !== false)
		{
			$APPLICATION->throwException(implode('', $this->errors));

			return false;
		}

		unregisterModule($this->MODULE_ID);

		return true;
	}

	function uninstallEvents()
	{
		return true;
	}

	function uninstallFiles()
	{
		deleteDirFiles(
			$_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/nikaimport/install/admin',
			$_SERVER['DOCUMENT_ROOT'] . '/bitrix/admin'
		);

		return true;
	}

}
