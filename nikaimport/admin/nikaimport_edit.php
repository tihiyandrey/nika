<?php

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");

IncludeModuleLangFile(__FILE__);
Bitrix\Main\Loader::includeModule('nikaimport');

$MOD_RIGHT = $APPLICATION->getGroupRight('nikaimport');
if ($MOD_RIGHT < 'W')
	$APPLICATION->authForm(getMessage('ACCESS_DENIED'));

$ID = intval($ID);

$nikaimport = Bitrix\NikaImport\NikaImportTable::getById($ID)->fetch();
if (empty($nikaimport))
	$ID = 0;

$arSites = array();
$dbSites = Bitrix\Main\SiteTable::getList(array('order' => array('DEF' => 'DESC', 'SORT' => 'ASC')));
while ($arSite = $dbSites->fetch())
	$arSites[$arSite['LID']] = $arSite;

$arTemplates = array();
$dbTemplates = CSiteTemplate::getList(array('ID' => 'ASC'), array('TYPE' => ''), array('ID', 'NAME'));
while ($arTemplate = $dbTemplates->fetch())
	$arTemplates[$arTemplate['ID']] = $arTemplate;

$arEstDays = array();
foreach (Bitrix\NikaImport\AdminHelper::getSiteCapacity(array_keys($arSites)) as $lid => $value)
	$arEstDays[$lid] = $value['est'];


if ($REQUEST_METHOD == "POST" && (strlen($save) > 0 || strlen($apply) > 0) && check_bitrix_sessid())
{
	$arFields = array(
		'SITE_ID'  => $SITE,
		'NAME'     => $NAME,
		'DESCR'    => $DESCR,
		'DURATION' => intval($DURATION) < 0 ? -1 : intval($DURATION),
		'PORTION'  => intval($PORTION),
	);

	if ($ID > 0)
	{
		$arFields['TEST_DATA'] = $nikaimport['TEST_DATA'];
		$arFields['TEST_DATA']['list'] = array();
	}

	if (empty($arFields['SITE_ID']))
		$message = new CAdminMessage(array('MESSAGE' => getMessage('NIKAIMPORT_EMPTY_SITE')));
	else if (!is_set($arSites, $arFields['SITE_ID']))
		$message = new CAdminMessage(array('MESSAGE' => str_replace('#VALUE#', htmlspecialcharsbx($arFields['SITE_ID']), getMessage('NIKAIMPORT_UNKNOWN_SITE'))));

	if ($arFields['PORTION'] < 1 || $arFields['PORTION'] > 100)
		$message = new CAdminMessage(array('MESSAGE' => getMessage('NIKAIMPORT_PORTION_ERROR'), 'DETAILS' => getMessage('NIKAIMPORT_PORTION_HINT')));

	$errors = array();

	if (!empty($TEST_DATA['type']) && is_array($TEST_DATA['type']))
	{
		foreach ($TEST_DATA['type'] as $k => $type)
		{
			if (!in_array($type, array('template', 'page')))
				$errors[] = str_replace(array('#ID#', '#VALUE#'), array(intval($k)+1, htmlspecialcharsbx($type)), getMessage('NIKAIMPORT_UNKNOWN_TEST_TYPE'));

			if (empty($TEST_DATA['old_value'][$k]) || empty($TEST_DATA['new_value'][$k]))
			{
				$errors[] = str_replace('#ID#', intval($k)+1, getMessage(
					empty($TEST_DATA['old_value'][$k]) && empty($TEST_DATA['new_value'][$k])
						? 'NIKAIMPORT_EMPTY_TEST_VALUES' : 'NIKAIMPORT_EMPTY_TEST_VALUE'
				));
			}

			if (!empty($TEST_DATA['old_value'][$k]) || !empty($TEST_DATA['new_value'][$k]))
			{
				$docRoot = rtrim(Bitrix\Main\SiteTable::getDocumentRoot($arFields['SITE_ID']), '/');

				switch ($type)
				{
					case 'template':
						if (!empty($TEST_DATA['old_value'][$k]) && !is_set($arTemplates, $TEST_DATA['old_value'][$k]))
						{
							$errors[] = str_replace(
								array('#ID#', '#VALUE#'), array(intval($k)+1, htmlspecialcharsbx($TEST_DATA['old_value'][$k])),
								getMessage('NIKAIMPORT_UNKNOWN_TEST_TEMPLATE')
							);
						}
						if (!empty($TEST_DATA['new_value'][$k]) && !is_set($arTemplates, $TEST_DATA['new_value'][$k]))
						{
							$errors[] = str_replace(
								array('#ID#', '#VALUE#'), array(intval($k)+1, htmlspecialcharsbx($TEST_DATA['new_value'][$k])),
								getMessage('NIKAIMPORT_UNKNOWN_TEST_TEMPLATE')
							);
						}
						break;
					case 'page':
						if (!empty($TEST_DATA['old_value'][$k]))
						{
							$file = new Bitrix\Main\IO\File($docRoot.$TEST_DATA['old_value'][$k]);
							if (!$file->isExists())
							{
								$errors[] = str_replace(
									array('#ID#', '#VALUE#'), array(intval($k)+1, htmlspecialcharsbx($TEST_DATA['old_value'][$k])),
									getMessage('NIKAIMPORT_UNKNOWN_TEST_PAGE')
								);
							}
						}
						if (!empty($TEST_DATA['new_value'][$k]))
						{
							$file = new Bitrix\Main\IO\File($docRoot.$TEST_DATA['new_value'][$k]);
							if (!$file->isExists())
							{
								$errors[] = str_replace(
									array('#ID#', '#VALUE#'), array(intval($k)+1, htmlspecialcharsbx($TEST_DATA['new_value'][$k])),
									getMessage('NIKAIMPORT_UNKNOWN_TEST_PAGE')
								);
							}
						}
						break;
				}
			}

			$arFields['TEST_DATA']['list'][] = array(
				'type'      => $type,
				'old_value' => $TEST_DATA['old_value'][$k],
				'new_value' => $TEST_DATA['new_value'][$k],
			);
		}
	}
	else
	{
		$errors[] = getMessage('NIKAIMPORT_EMPTY_TEST_DATA');
	}

	if (!empty($errors))
		$message = new CAdminMessage(array('MESSAGE' => getMessage('NIKAIMPORT_TEST_DATA_ERROR'), 'DETAILS' => join('<br>', $errors)));

	if (empty($message))
	{
		$arFields['ENABLED'] = 'Y';

		if ($ID > 0)
		{
			$result = Bitrix\NikaImport\NikaImportTable::update($ID, $arFields);

			if ($result->isSuccess() && $nikaimport['ACTIVE'] == 'Y')
				Bitrix\NikaImport\Helper::clearCache($arFields['SITE_ID']);
		}
		else
		{
			$arFields['ACTIVE'] = 'N';

			$result = Bitrix\NikaImport\NikaImportTable::add($arFields);
			$ID = $result->isSuccess() ? $result->getId() : 0;
		}

		if (!$result->isSuccess())
		{
			unset($arFields['ENABLED']);

			$message = new CAdminMessage(array(
				'MESSAGE' => getMessage('NIKAIMPORT_SAVE_ERROR'),
				'DETAILS' => join('<br>', $result->getErrorMessages())
			));
		}
		else
		{
			if (strlen($save) > 0)
				LocalRedirect('nikaimport_admin.php?lang='.LANG);
			else
				LocalRedirect($APPLICATION->getCurPage().'?lang='.LANG.'&ID='.$ID);
		}
	}

	if ($ID > 0)
		$nikaimport = array_merge($nikaimport, $arFields);
	else
		$nikaimport = $arFields;
}


if ($ID > 0)
{
	$APPLICATION->SetTitle(empty($nikaimport['NAME'])
		? str_replace('#ID#', $ID, getMessage('NIKAIMPORT_EDIT_TITLE1'))
		: str_replace('#NAME#', $nikaimport['NAME'], getMessage('NIKAIMPORT_EDIT_TITLE2'))
	);
}
else
{
	$APPLICATION->SetTitle(getMessage('NIKAIMPORT_ADD_TITLE'));
}

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_after.php");

$aMenu = array(
	array(
		"ICON" => "btn_list",
		"TEXT" => getMessage('NIKAIMPORT_GOTO_LIST'),
		"LINK" => "nikaimport_admin.php?lang=".LANG
	)
);

if ($ID > 0)
{
	$aMenu[] = array("SEPARATOR" => "Y");
	$aMenu[] = array(
		"ICON" => "btn_new",
		"TEXT" => getMessage('NIKAIMPORT_GOTO_ADD'),
		"LINK" => "nikaimport_edit.php?lang=".LANG
	);

	//if ($MOD_RIGHT == "W")
	{
		$aMenu[] = array(
			"ICON" => "btn_delete",
			"TEXT" => getMessage('NIKAIMPORT_DELETE'),
			"LINK" => "javascript:if(confirm('".CUtil::JSEscape(getMessage('NIKAIMPORT_DELETE_CONFIRM'))."')) window.location='nikaimport_admin.php?action=delete&ID=".$ID."&lang=".LANG."&".bitrix_sessid_get()."';",
		);
	}
}

$context = new CAdminContextMenu($aMenu);
$context->Show();


$aTabs = array(
	array('DIV' => 'edit1', 'TAB' => getMessage('NIKAIMPORT_TAB_NAME'), 'TITLE' => getMessage('NIKAIMPORT_TAB_TITLE')),
);
$tabControl = new CAdminTabControl("tabControl", $aTabs, false);


if ($message) echo $message->Show();

?>

<form method="POST" action="<?=$APPLICATION->GetCurPage(); ?>?lang=<?=LANG; ?>&amp;ID=<?=$ID; ?>" name="form1" enctype="multipart/form-data">
<?=bitrix_sessid_post(); ?>

<? $tabControl->Begin(); ?>
<? $tabControl->BeginNextTab(); ?>

<?

if (empty($nikaimport))
{
	$lid      = current(array_keys($arSites));
	$duration = -1;
	$portion  = 30;
}
else
{
	$lid      = $nikaimport['SITE_ID'];
	$duration = $nikaimport['DURATION'];
	$portion  = $nikaimport['PORTION'];
}

?>

<tr class="adm-detail-required-field">
	<td style="width: 40%; "><?=getMessage('NIKAIMPORT_SITE_FIELD'); ?>:</td>
	<td style="width: 60%; ">
		<select id="site_id" name="SITE" onchange="NikaImportParams.Site.handle(this); " style="width: 200px; ">
			<? if (!empty($nikaimport) && empty($nikaimport['SITE_ID'])) : ?>
			<option selected></option>
			<? endif; ?>
			<? $siteDefined = false; ?>
			<? foreach ($arSites as $value => $site) : ?>
			<option value="<?=htmlspecialcharsbx($value); ?>"<? if ($lid == $value && ($siteDefined = true)) echo ' selected'; ?>>
			<?=htmlspecialcharsbx($site['NAME']); ?> (<?=htmlspecialcharsbx($value); ?>)
			</option>
			<? endforeach; ?>
			<? if (!empty($nikaimport['SITE_ID']) && !$siteDefined) : ?>
			<option value="<?=htmlspecialcharsbx($nikaimport['SITE_ID']); ?>" selected>* <?=htmlspecialcharsbx($nikaimport['SITE_ID']); ?></option>
			<? endif; ?>
		</select>
	</td>
</tr>

<tr>
	<td><?=getMessage('NIKAIMPORT_NAME_FIELD'); ?>:</td>
	<td><input type="text" name="NAME" style="width: 340px; " maxlength="255" value="<? if (!empty($nikaimport)) echo htmlspecialcharsbx($nikaimport['NAME']); ?>"></td>
</tr>

<tr>
	<td class="adm-detail-valign-top"><?=getMessage('NIKAIMPORT_DESCR_FIELD'); ?>:</td>
	<td><textarea name="DESCR" cols="80" rows="4"><? if (!empty($nikaimport)) echo htmlspecialcharsbx($nikaimport['DESCR']); ?></textarea></td>
</tr>

<?

$durations = array(
	1  => getMessage('NIKAIMPORT_DURATION_OPTION_1'),
	3  => getMessage('NIKAIMPORT_DURATION_OPTION_3'),
	5  => getMessage('NIKAIMPORT_DURATION_OPTION_5'),
	7  => getMessage('NIKAIMPORT_DURATION_OPTION_7'),
	14 => getMessage('NIKAIMPORT_DURATION_OPTION_14'),
	30 => getMessage('NIKAIMPORT_DURATION_OPTION_30'),
	0  => getMessage('NIKAIMPORT_DURATION_OPTION_0'),
);

?>

<tr class="adm-detail-required-field">
	<td><?=getMessage('NIKAIMPORT_DURATION_FIELD'); ?><span class="required" style="font-weight: normal; "><sup>1</sup></span>:</td>
	<td>
		<select name="DURATION" style="width: 200px; ">
			<? $durationDefined = false; ?>
			<option id="duration_auto" value="-1"<? if ($duration == -1 && ($durationDefined = true)) echo ' selected'; ?>>
			<? $value = (empty($arEstDays[$lid]) || $portion < 1 || $portion > 100)
				? getMessage('NIKAIMPORT_DURATION_OPTION_NA') : ceil(100 * $arEstDays[$lid] / $portion); ?>
			<?=str_replace('#NUM#', $value, getMessage('NIKAIMPORT_DURATION_OPTION_A')); ?>
			</option>
			<? foreach ($durations as $value => $title) : ?>
			<option value="<?=intval($value); ?>"<? if ($duration == $value && ($durationDefined = true)) echo ' selected'; ?>><?=htmlspecialcharsbx($title); ?></option>
			<? endforeach; ?>
			<? if (!empty($nikaimport) && !$durationDefined) : ?>
			<option value="<?=intval($duration); ?>" selected>* <?=str_replace('#NUM#', intval($duration), getMessage('NIKAIMPORT_DURATION_OPTION_C')); ?></option>
			<? endif; ?>
		</select>
	</td>
</tr>

<tr class="adm-detail-required-field">
	<td><?=getMessage('NIKAIMPORT_PORTION_FIELD'); ?>:</td>
	<td>
		<select id="portion" name="PORTION" onchange="NikaImportParams.Portion.handle(this); " style="width: 200px; ">
			<? $portionDefined = false; ?>
			<? foreach (array(10, 20, 30, 50, 100) as $value) : ?>
			<option value="<?=$value; ?>"<? if ($portion == $value && ($portionDefined = true)) echo ' selected'; ?>><?=$value; ?>%</option>
			<? endforeach; ?>
			<? if (!empty($nikaimport) && !$portionDefined) : ?>
			<option value="<?=intval($portion); ?>" selected>* <?=intval($portion); ?>%</option>
			<? endif; ?>
		</select>
	</td>
</tr>

<tr class="heading">
	<td align="center" colspan="2"><?=getMessage('NIKAIMPORT_TEST_DATA'); ?></td>
</tr>

<?

$test_form_msg = array(
	'template' => array(
		'title'   => getMessage('NIKAIMPORT_TEST_TEMPLATE_TITLE'),
		'title_a' => getMessage('NIKAIMPORT_TEST_TEMPLATE_TITLE_A'),
		'title_b' => getMessage('NIKAIMPORT_TEST_TEMPLATE_TITLE_B'),
	),
	'page' => array(
		'title'   => getMessage('NIKAIMPORT_TEST_PAGE_TITLE'),
		'title_a' => getMessage('NIKAIMPORT_TEST_PAGE_TITLE_A'),
		'title_b' => getMessage('NIKAIMPORT_TEST_PAGE_TITLE_B'),
	)
);

?>

<tr>
	<td colspan="2">
		<div id="nikaimport_list" class="adm-ab-edit-list">
		<? if (!empty($nikaimport['TEST_DATA']['list']) && is_array($nikaimport['TEST_DATA']['list'])) : ?>
		<? if ($ID > 0 && empty($message)) :
			$check_url = '';
			if (!empty($arSites[$nikaimport['SITE_ID']]['SERVER_NAME']))
				$check_url = 'http://' . $arSites[$nikaimport['SITE_ID']]['SERVER_NAME'];
		endif; ?>
		<? foreach ($nikaimport['TEST_DATA']['list'] as $k => $item) : ?>

			<? if ($ID > 0 && empty($message)) :
				switch ($item['type']) :
					case 'template':
						$check_uri = $check_url . $arSites[$nikaimport['SITE_ID']]['DIR'] . '?nikaimport_mode=' . $ID;
						break;
					case 'page':
						$check_uri = $check_url . $item['old_value'] . '?nikaimport_mode=' . $ID;
						break;
				endswitch;
			endif; ?>

			<table class="internal test-item" style="width: 100%; margin-bottom: 10px; ">
				<tr class="heading">
					<td colspan="2" style="text-align: left !important; ">
						<div style="float: right; cursor: pointer; " onclick="BX.remove(BX.findParent(this, {'tag': 'table', 'class': 'test-item'}));">x</div>
						<?=str_replace('#TYPE#', $test_form_msg[$item['type']]['title'], getMessage('NIKAIMPORT_TEST_TITLE')); ?>
					</td>
				</tr>
				<tr>
					<td>
						<input type="hidden" name="TEST_DATA[type][]" value="<?=htmlspecialcharsbx($item['type']); ?>">
						<table style="width: 400px; float: right; ">
							<tr>
								<td style="width: 20px; background: #498ec5; text-shadow: none; color: #ffffff !important; font-weight: bold; text-align: center; padding: 10px !important;">A</td>
								<td style="background: rgb(203, 213, 220); font-weight: bold; "><?=$test_form_msg[$item['type']]['title_a']; ?></td>
							</tr>
							<tr>
								<td colspan="2" style="background: rgb(236, 239, 241); padding-left: 41px !important; ">
									<? switch ($item['type']) :
										case 'template': ?>
											<select class="value-input old-value-input" name="TEST_DATA[old_value][]" onchange="NikaImportList.Item.handle(this); " data-value="<?=htmlspecialcharsbx($item['old_value']); ?>" style="width: 320px; ">
												<option></option>
												<? $oldvalueDefined = false; ?>
												<? foreach ($arTemplates as $tmpl_id => $tmpl) : ?>
												<option value="<?=htmlspecialcharsbx($tmpl_id); ?>"<? if ($item['old_value'] == $tmpl_id && ($oldvalueDefined = true)) echo ' selected'; ?>><?=htmlspecialcharsbx($tmpl['NAME']); ?> (<?=htmlspecialcharsbx($tmpl_id); ?>)</option>
												<? endforeach; ?>
												<? if ($item['old_value'] && !$oldvalueDefined) : ?>
												<option value="<?=htmlspecialcharsbx($item['old_value']); ?>" selected>* <?=htmlspecialcharsbx($item['old_value']); ?></option>
												<? endif; ?>
											</select>
										<? break;
										case 'page': ?>
											<input class="value-input old-value-input" type="text" onchange="NikaImportList.Item.handle(this); " oninput="NikaImportList.Item.handle(this); " name="TEST_DATA[old_value][]" data-value="<?=htmlspecialcharsbx($item['old_value']); ?>" value="<?=htmlspecialcharsbx($item['old_value']); ?>" style="width: 230px; ">
											<input type="button" value="..." onclick="NikaImportList.Item.select(this); " title="<?=getMessage('NIKAIMPORT_TEST_SELECT_PAGE'); ?>">
											<input class="copy-value-btn" type="button" onclick="NikaImportList.Item.copy(this); " value="&gt;" title="<?=getMessage('NIKAIMPORT_TEST_COPY_PAGE'); ?>"<? if (empty($item['old_value'])) echo ' disabled'; ?>>
										<? break;
									endswitch; ?>
									<? if ($ID > 0 && $nikaimport['ENABLED'] == 'Y' && empty($message)) : ?>
									<br><br><input class="preview-btn" type="button" value="<?=getMessage('NIKAIMPORT_TEST_CHECK'); ?>" data-href="<?=htmlspecialcharsbx($check_uri); ?>|A" onclick="window.open(this.getAttribute('data-href')); ">
									<? endif; ?>
								</td>
							</tr>
						</table>
					</td>
					<td>
						<table style="width: 400px; ">
							<tr>
								<td style="width: 20px; background: rgb(255, 118, 36); text-shadow: none; color: #ffffff !important; font-weight: bold; text-align: center; padding: 10px !important;">B</td>
								<td style="background: rgb(203, 213, 220); font-weight: bold; "><?=$test_form_msg[$item['type']]['title_b']; ?></td>
							</tr>
							<tr>
								<td colspan="2" style="background: rgb(236, 239, 241); padding-left: 41px !important; ">
									<? switch ($item['type']) :
										case 'template': ?>
											<select class="value-input new-value-input" name="TEST_DATA[new_value][]" onchange="NikaImportList.Item.handle(this); " data-value="<?=htmlspecialcharsbx($item['new_value']); ?>" style="width: 320px; ">
												<? $newvalueDefined = false; ?>
												<option></option>
												<? foreach ($arTemplates as $tmpl_id => $tmpl) : ?>
												<option value="<?=htmlspecialcharsbx($tmpl_id); ?>"<? if ($item['new_value'] == $tmpl_id && ($newvalueDefined = true)) echo ' selected'; ?>><?=htmlspecialcharsbx($tmpl['NAME']); ?> (<?=htmlspecialcharsbx($tmpl_id); ?>)</option>
												<? endforeach; ?>
												<? if ($item['new_value'] && !$newvalueDefined) : ?>
												<option value="<?=htmlspecialcharsbx($item['new_value']); ?>" selected>* <?=htmlspecialcharsbx($item['new_value']); ?></option>
												<? endif; ?>
											</select>
										<? break;
										case 'page': ?>
											<input class="value-input new-value-input" type="text" onchange="NikaImportList.Item.handle(this); " oninput="NikaImportList.Item.handle(this); " name="TEST_DATA[new_value][]" data-value="<?=htmlspecialcharsbx($item['new_value']); ?>" value="<?=htmlspecialcharsbx($item['new_value']); ?>" style="width: 230px; ">
											<input type="button" onclick="NikaImportList.Item.select(this); " value="..." title="<?=getMessage('NIKAIMPORT_TEST_SELECT_PAGE'); ?>">
											<span class="edit-value-btn adm-btn<? if (empty($item['new_value'])) echo ' adm-btn-disabled'; ?>" onclick="NikaImportList.Item.edit(this); " title="<?=getMessage('NIKAIMPORT_TEST_EDIT_PAGE'); ?>">&nbsp;</span>
										<? break;
									endswitch; ?>
									<? if ($ID > 0 && $nikaimport['ENABLED'] == 'Y' && empty($message)) : ?>
									<br><br><input class="preview-btn" type="button" value="<?=getMessage('NIKAIMPORT_TEST_CHECK'); ?>" data-href="<?=htmlspecialcharsbx($check_uri); ?>|B" onclick="window.open(this.getAttribute('data-href')); ">
									<? endif; ?>
								</td>
							</tr>
						</table>
					</td>
				</tr>
			</table>

		<? endforeach; ?>
		<? endif; ?>
		</div>
	</td>
</tr>

<? if (!$ID || in_array($nikaimport['ENABLED'], array('T', 'Y'))) : ?>
<tr>
	<td colspan="2">
		<a id="new_test_button" href="#" hidefocus="true" class="adm-btn adm-btn-add adm-btn-save adm-btn-menu" title="<?=getMessage('NIKAIMPORT_TEST_ADD'); ?>"><?=getMessage('NIKAIMPORT_TEST_ADD'); ?></a>
	</td>
</tr>
<? endif; ?>

<? $tabControl->EndTab(); ?>
<? $tabControl->Buttons(array('disabled' => $ID > 0 && !in_array($nikaimport['ENABLED'], array('T', 'Y')), 'back_url' => 'nikaimport_admin.php?lang='.LANG)); ?>
<? if ($ID > 0 && $nikaimport['ACTIVE'] == 'Y') : ?>
<span style="margin-left: 25px; color: #e70000; text-decoration: underline; "><?=getMessage('NIKAIMPORT_TEST_EDIT_WARNING'); ?></span>
<? endif; ?>
<? $tabControl->End(); ?>

</form>

<table id="nikaimport_sample_template" class="internal test-item" style="width: 100%; display: none; margin-bottom: 10px; ">
	<tr class="heading">
		<td colspan="2" style="text-align: left !important; ">
			<div style="float: right; cursor: pointer; " onclick="BX.remove(BX.findParent(this, {'tag': 'table', 'class': 'test-item'}));">x</div>
			<?=str_replace('#TYPE#', $test_form_msg['template']['title'], getMessage('NIKAIMPORT_TEST_TITLE')); ?>
		</td>
	</tr>
	<tr>
		<td>
			<input type="hidden" name="TEST_DATA[type][]" value="template">
			<table style="width: 400px; float: right; ">
				<tr>
					<td style="width: 20px; background: #498ec5; text-shadow: none; color: #ffffff !important; font-weight: bold; text-align: center; padding: 10px !important;">A</td>
					<td style="background: rgb(203, 213, 220); font-weight: bold; "><?=$test_form_msg['template']['title_a']; ?></td>
				</tr>
				<tr>
					<td colspan="2" style="background: rgb(236, 239, 241); padding-left: 41px !important; ">
						<select name="TEST_DATA[old_value][]" style="width: 320px; ">
							<option></option>
							<? foreach ($arTemplates as $tmpl_id => $tmpl) : ?>
							<option value="<?=htmlspecialcharsbx($tmpl_id); ?>"><?=htmlspecialcharsbx($tmpl['NAME']); ?> (<?=htmlspecialcharsbx($tmpl_id); ?>)</option>
							<? endforeach; ?>
						</select>
					</td>
				</tr>
			</table>
		</td>
		<td>
			<table style="width: 400px; ">
				<tr>
					<td style="width: 20px; background: rgb(255, 118, 36); text-shadow: none; color: #ffffff !important; font-weight: bold; text-align: center; padding: 10px !important;">B</td>
					<td style="background: rgb(203, 213, 220); font-weight: bold; "><?=$test_form_msg['template']['title_b']; ?></td>
				</tr>
				<tr>
					<td colspan="2" style="background: rgb(236, 239, 241); padding-left: 41px !important; ">
						<select name="TEST_DATA[new_value][]" style="width: 320px; ">
							<option></option>
							<? foreach ($arTemplates as $tmpl_id => $tmpl) : ?>
							<option value="<?=htmlspecialcharsbx($tmpl_id); ?>"><?=htmlspecialcharsbx($tmpl['NAME']); ?> (<?=htmlspecialcharsbx($tmpl_id); ?>)</option>
							<? endforeach; ?>
						</select>
					</td>
				</tr>
			</table>
		</td>
	</tr>
</table>
<table id="nikaimport_sample_page" class="internal test-item" style="width: 100%; display: none; margin-bottom: 10px; ">
	<tr class="heading">
		<td colspan="2" style="text-align: left !important; ">
			<div style="float: right; cursor: pointer; " onclick="BX.remove(BX.findParent(this, {'tag': 'table', 'class': 'test-item'}));">x</div>
			<?=str_replace('#TYPE#', $test_form_msg['page']['title'], getMessage('NIKAIMPORT_TEST_TITLE')); ?>
		</td>
	</tr>
	<tr>
		<td>
			<input type="hidden" name="TEST_DATA[type][]" value="page">
			<table style="width: 400px; float: right; ">
				<tr>
					<td style="width: 20px; background: #498ec5; text-shadow: none; color: #ffffff !important; font-weight: bold; text-align: center; padding: 10px !important;">A</td>
					<td style="background: rgb(203, 213, 220); font-weight: bold; "><?=$test_form_msg['page']['title_a']; ?></td>
				</tr>
				<tr>
					<td colspan="2" style="background: rgb(236, 239, 241); padding-left: 41px !important; ">
						<input class="value-input old-value-input" type="text" onchange="NikaImportList.Item.handle(this); " oninput="NikaImportList.Item.handle(this); " name="TEST_DATA[old_value][]" style="width: 230px; ">
						<input type="button" onclick="NikaImportList.Item.select(this); " value="..." title="<?=getMessage('NIKAIMPORT_TEST_SELECT_PAGE'); ?>">
						<input class="copy-value-btn" type="button" onclick="NikaImportList.Item.copy(this); " value="&gt;" title="<?=getMessage('NIKAIMPORT_TEST_COPY_PAGE'); ?>" disabled>
					</td>
				</tr>
			</table>
		</td>
		<td>
			<table style="width: 400px; ">
				<tr>
					<td style="width: 20px; background: rgb(255, 118, 36); text-shadow: none; color: #ffffff !important; font-weight: bold; text-align: center; padding: 10px !important;">B</td>
					<td style="background: rgb(203, 213, 220); font-weight: bold; "><?=$test_form_msg['page']['title_b']; ?></td>
				</tr>
				<tr>
					<td colspan="2" style="background: rgb(236, 239, 241); padding-left: 41px !important; ">
						<input class="value-input new-value-input" type="text" onchange="NikaImportList.Item.handle(this); " oninput="NikaImportList.Item.handle(this); " name="TEST_DATA[new_value][]" style="width: 230px; ">
						<input type="button" onclick="NikaImportList.Item.select(this); " value="..." title="<?=getMessage('NIKAIMPORT_TEST_SELECT_PAGE'); ?>">
						<span class="edit-value-btn adm-btn adm-btn-disabled" onclick="NikaImportList.Item.edit(this); " title="<?=getMessage('NIKAIMPORT_TEST_EDIT_PAGE'); ?>">&nbsp;</span>
					</td>
				</tr>
			</table>
		</td>
	</tr>
</table>

<div class="adm-info-message-wrap">
	<div class="adm-info-message">
		<span class="required"><sup>1</sup></span>
		<?=getMessage('NIKAIMPORT_DURATION_AUTO_HINT'); ?><br></br>
		<?=getMessage('NIKAIMPORT_MATH_POWER_HINT'); ?>
	</div>
</div>

<? CAdminFileDialog::ShowScript(array(
	'event'         => 'openFileDialog',
	'arResultDest'  => array('FUNCTION_NAME' => 'fileDialogCallback'),
	'arPath'        => array('SITE' => $siteDefined ? $nikaimport['SITE_ID'] : '', 'PATH' => '/'),
	'select'        => 'F',
	'operation'     => 'O',
	'fileFilter'    => 'php',
	'allowAllFiles' => true,
	'saveConfig'    => true
)); ?>

<script type="text/javascript">

	var initialSite = '<?=CUtil::jsEscape($nikaimport['SITE_ID']); ?>';
	var siteDirs = <?=CUtil::phpToJSObject(array_map(function($site) {
		return $site['DIR'];
	}, $arSites)); ?>;

	var estDays = <?=CUtil::phpToJSObject($arEstDays); ?>;

	var fileDialogTarget = null;

	var fileDialogCallback = function(filename, path)
	{
		fileDialogTarget.value = (path+'/'+filename).replace(/\/+/, '/');
		fileDialogTarget.style.color = '';
		NikaImportList.Item.handle(fileDialogTarget, true);

		fileDialogTarget = null;
	}

	var NikaImportParams = {
		Site: {
			handle: function()
			{
				var inputs = BX.findChildrenByClassName(BX('nikaimport_list'), 'value-input', true);

				for (var i in inputs)
					NikaImportList.Item.check(inputs[i], true);

				NikaImportParams.Duration.updateAuto();
			}
		},
		Duration: {
			updateAuto: function()
			{
				BX.html(BX('duration_auto'), (function() {
					var portion = parseInt(BX('portion').value);
					var est = parseFloat(estDays[BX('site_id').value]);
					var days = !est || portion < 1 || portion > 100
						? '<?=CUtil::jsEscape(getMessage('NIKAIMPORT_DURATION_OPTION_NA')); ?>'
						: Math.ceil(100 * est / portion);
					return '<?=CUtil::jsEscape(getMessage('NIKAIMPORT_DURATION_OPTION_A')); ?>'.replace('#NUM#', days);
				})());
			}
		},
		Portion: {
			handle: function()
			{
				NikaImportParams.Duration.updateAuto();
			}
		}
	};

	var NikaImportList = {
		add: function(type)
		{
			var sample = BX('nikaimport_sample_'+type);
			var new_test = sample.cloneNode(true);

			new_test.removeAttribute('id');
			new_test.style.display = '';

			BX('nikaimport_list').appendChild(new_test);
		},
		Item: {
			handle: function(input, skipCheck)
			{
				if (!skipCheck)
					NikaImportList.Item.check(input);

				NikaImportList.Item.toggleCopy(input);
				NikaImportList.Item.toggleEdit(input);
				NikaImportList.Item.togglePreview(input);
			},
			toggleCopy: function(input)
			{
				if (BX.hasClass(input, 'old-value-input'))
				{
					var btn = BX.findChild(BX.findParent(input, {'class': 'test-item'}), {'class': 'copy-value-btn'}, true);

					if (btn)
						btn.disabled = !input.value;
				}
			},
			toggleEdit: function(input)
			{
				if (BX.hasClass(input, 'new-value-input'))
				{
					var btn = BX.findChild(BX.findParent(input, {'class': 'test-item'}), {'class': 'edit-value-btn'}, true);

					if (btn)
					{
						if (input.value)
							BX.removeClass(btn, 'adm-btn-disabled');
						else
							BX.addClass(btn, 'adm-btn-disabled');
					}
				}
			},
			togglePreview: function(input)
			{
				if (BX.hasClass(input, 'value-input'))
				{
					var item = BX.findParent(input, {'class': 'test-item'});

					var old_value = BX.findChild(item, {'class': 'old-value-input'}, true);
					var new_value = BX.findChild(item, {'class': 'new-value-input'}, true);

					var old_btn = BX.findChild(old_value.parentNode, {'class': 'preview-btn'}, true);
					var new_btn = BX.findChild(new_value.parentNode, {'class': 'preview-btn'}, true);

					var old_btn_disabled = old_value.value != old_value.getAttribute('data-value') || BX('site_id').value != initialSite;
					var new_btn_disabled = new_value.value != new_value.getAttribute('data-value') || old_btn_disabled;

					if (old_btn)
						old_btn.disabled = old_btn_disabled;
					if (new_btn)
						new_btn.disabled = new_btn_disabled;
				}
			},
			check: function(input, force)
			{
				if (input.nodeName.toLowerCase() != 'input')
				{
					NikaImportList.Item.handle(input, true);
					return;
				}

				if (force || input.value != input.chkValue)
				{
					input.chkTimeout = clearTimeout(input.chkTimeout);
					if (typeof input.chkAjax == 'object')
					{
						input.chkAjax.abort();
						input.chkAjax = false;
					}

					input.style.color = '';
					if (input.value)
					{
						input.chkValue = input.value;
						input.chkTimeout = setTimeout(function() {
							input.style.color = '#808080';
							input.chkAjax = BX.ajax({
								method: 'POST',
								url: '/bitrix/admin/nikaimport_ajax.php?action=check&type=page',
								data: {
									site: BX('site_id').value,
									value: input.value
								},
								dataType: 'json',
								onsuccess: function(json)
								{
									if (json.result != 'error')
									{
										if (input.value != json.result)
										{
											input.value = json.result;
											input.chkValue = input.value;
										}

										input.style.color = '';
										NikaImportList.Item.handle(input, true);
									}
									else
									{
										//alert(json.error);
										input.style.color = '#f00000';
									}
								}
							});
						}, force ? 0 : 500);
					}
				}
			},
			select: function(btn)
			{
				var path = null;

				fileDialogTarget = BX.findChild(btn.parentNode, {'class': 'value-input'}, false);

				// @TODO: define path
				path = '/';
				var params = {
					site: BX('site_id').value,
					path: typeof siteDirs[BX('site_id').value] != 'undefined' ? siteDirs[BX('site_id').value] : '/'
				};

				openFileDialog(true, params);
			},
			copy: function(btn)
			{
				var item = BX.findParent(btn, {'class': 'test-item'});

				var old_value = BX.findChild(item, {'class': 'old-value-input'}, true);
				var new_value = BX.findChild(item, {'class': 'new-value-input'}, true);

				if (old_value.style.color != '')
				{
					alert('<?=CUtil::JSEscape(getMessage('NIKAIMPORT_UNKNOWN_PAGE')); ?>');
					return;
				}

				btn.disabled = true;
				BX.ajax({
					method: 'POST',
					url: '/bitrix/admin/nikaimport_ajax.php?action=copy&type=page',
					data: '<?=bitrix_sessid_get(); ?>&site='+encodeURIComponent(BX('site_id').value)+'&source='+encodeURIComponent(old_value.value),
					dataType: 'json',
					onsuccess: function(json)
					{
						if (json.result != 'error')
						{
							new_value.value = json.result;
							NikaImportList.Item.handle(new_value, true);
						}
						else
						{
							alert(json.error);
						}

						NikaImportList.Item.toggleCopy(old_value);
					},
					onfailure: function()
					{
						alert('<?=CUtil::jsEscape(getMessage('NIKAIMPORT_AJAX_ERROR')); ?>');

						NikaImportList.Item.toggleCopy(old_value);
					}
				});
			},
			edit: function(btn)
			{
				if (BX.hasClass(btn, 'adm-btn-disabled'))
					return false;

				var value = BX.findChild(btn.parentNode, {'class': 'value-input'}, false);

				if (value.style.color != '')
				{
					alert('<?=CUtil::JSEscape(getMessage('NIKAIMPORT_UNKNOWN_PAGE')); ?>');
					return;
				}

				window.open('/bitrix/admin/fileman_html_edit.php?path='+encodeURIComponent(value.value)+'&lang=<?=LANG; ?>');
			}
		}
	};

	BX('new_test_button').onclick = function()
	{
		this.blur();

		BX.adminShowMenu(this, [
			{'TEXT': '<?=CUtil::JSEscape($test_form_msg['template']['title']); ?>', 'ONCLICK': 'NikaImportList.add(\'template\');'},
			{'TEXT': '<?=CUtil::JSEscape($test_form_msg['page']['title']); ?>', 'ONCLICK': 'NikaImportList.add(\'page\');'}
		], {active_class: 'adm-btn-save-active'});

		return false;
	}

</script>


<?

require($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/include/epilog_admin.php");
