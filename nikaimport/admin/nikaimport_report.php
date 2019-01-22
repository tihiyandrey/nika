<?php

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");

IncludeModuleLangFile(__FILE__);
Bitrix\Main\Loader::includeModule('nikaimport');
$conversionAvailable = Bitrix\Main\Loader::includeModule('conversion');

$arLang = $APPLICATION->getLang();

$MOD_RIGHT = $APPLICATION->getGroupRight('nikaimport');
if ($MOD_RIGHT < 'R')
	$APPLICATION->authForm(getMessage('ACCESS_DENIED'));

$ID = intval($ID);
$nikaimport = Bitrix\NikaImport\NikaImportTable::getList(array(
	'filter' => array('=ID' => $ID),
	'select' => array('*', 'USER_NAME' => 'USER.NAME', 'USER_LAST_NAME' => 'USER.LAST_NAME', 'USER_SECOND_NAME' => 'USER.SECOND_NAME', 'USER_TITLE' => 'USER.TITLE', 'USER_LOGIN' => 'USER.LOGIN')
))->fetch();

if (empty($nikaimport) || $nikaimport['ENABLED'] != 'Y')
{
	$APPLICATION->setTitle(empty($nikaimport['NAME'])
		? str_replace('#ID#', $ID, getMessage('NIKAIMPORT_REPORT_TITLE1'))
		: str_replace('#NAME#', $nikaimport['NAME'], getMessage('NIKAIMPORT_REPORT_TITLE2'))
	);

	$message = new CAdminMessage(array(
		'MESSAGE' => getMessage('NIKAIMPORT_REPORT_NOTFOUND'),
		'DETAILS' => getMessage('NIKAIMPORT_REPORT_DISABLED')
	));

	require($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_admin_after.php');

	echo $message->Show();

	require($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/epilog_admin.php');
	return;
}

if ($nikaimport['ACTIVE'] == 'Y')
{
	$active_test = $nikaimport;
}
else
{
	$active_test = Bitrix\NikaImport\NikaImportTable::getList(array(
		'order'  => array('SORT' => 'ASC'),
		'filter' => array('SITE_ID' => $nikaimport['SITE_ID'], 'ACTIVE' => 'Y')
	))->fetch();
}

$arGraphData = array();

if ($conversionAvailable)
	$conversionRates = Bitrix\Conversion\RateManager::getTypes(array('ACTIVE' => true));

if (!empty($conversionRates))
{
	reset($conversionRates);
	$baseRate = key($conversionRates);
	$allRates = array_keys($conversionRates);

	usort($allRates, function($a, $b) use ($conversionRates) {
		$a = $conversionRates[$a];
		$b = $conversionRates[$b];
		return $a['MODULE'] == $b['MODULE'] ? $b['SORT'] - $a['SORT'] : $a['SORT'] - $b['SORT'];
	});

	$funnelRates = array('sale_cart', 'sale_order', 'sale_payment');
	if (array_diff($funnelRates, $allRates))
		$funnelRates = null;

	$reportContext = new Bitrix\Conversion\ReportContext;

	$reportContext->setAttribute('conversion_site', $nikaimport['SITE_ID']);
	$reportContext->setAttribute('nikaimport', $ID);

	$reportContext->setAttribute('nikaimport_section', 'A');
	$arGroupAData = $reportContext->getRatesDeprecated($conversionRates, array(), array('FORMAT' => 'Y-m-d', 'SELECT' => 'RATE'));

	$reportContext->unsetAttribute('nikaimport_section', 'A');
	$reportContext->setAttribute('nikaimport_section', 'B');
	$arGroupBData = $reportContext->getRatesDeprecated($conversionRates, array(), array('FORMAT' => 'Y-m-d', 'SELECT' => 'RATE'));

	foreach ($arGroupAData as $type => $data)
		$arGroupAData[$type]['TYPE'] = $conversionRates[$type];
	foreach ($arGroupBData as $type => $data)
		$arGroupBData[$type]['TYPE'] = $conversionRates[$type];

	$arGroupABaseRate =& $arGroupAData[$baseRate];
	$arGroupBBaseRate =& $arGroupBData[$baseRate];

	if (!empty($arGroupABaseRate))
	{
		foreach ($arGroupABaseRate['STEPS'] as $date => $rate)
		{
			if (!isset($arGraphData[$date]))
				$arGraphData[$date] = array('date' => $date);

			$arGraphData[$date]['value_a'] = round($rate*100, 2);
		}
	}

	if (!empty($arGroupBBaseRate))
	{
		foreach ($arGroupBBaseRate['STEPS'] as $date => $rate)
		{
			if (!isset($arGraphData[$date]))
				$arGraphData[$date] = array('date' => $date);

			$arGraphData[$date]['value_b'] = round($rate*100, 2);
		}

	if (!empty($arGraphData))
		ksort($arGraphData);
		$arGraphData = array_values($arGraphData);
	}
}


function get_plural_messages($prefix)
{
	global $MESS;

	$result = array();

	$k = 0;
	while ($form = getMessage($prefix.'PLURAL_'.++$k))
		$result[] = $form;

	return $result;
}

// http://localization-guide.readthedocs.org/en/latest/l10n/pluralforms.html
function plural_form($n, $forms)
{
	switch (LANG)
	{
		case 'ru':
		case 'ua':
			$p = $n%10 == 1 && $n%100 != 11 ? 0 : ($n%10 >= 2 && $n%10 <= 4 && ($n%100 < 10 || $n%100 >= 20) ? 1 : 2);
			break;
		case 'en':
		case 'de':
		case 'es':
			$p = $n == 1 ? 0 : 1;
			break;
	}

	return isset($forms[$p]) ? $forms[$p] : end($forms);
}

if (!empty($conversionRates))
	CUtil::initJSCore(array('amcharts', 'amcharts_funnel', 'amcharts_serial'));

$APPLICATION->setTitle(empty($nikaimport['NAME'])
	? str_replace('#ID#', $ID, getMessage('NIKAIMPORT_REPORT_TITLE1'))
	: str_replace('#NAME#', $nikaimport['NAME'], getMessage('NIKAIMPORT_REPORT_TITLE2'))
);
require($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_admin_after.php');

$aMenu = array(
	array(
		'ICON' => 'btn_list',
		'TEXT' => getMessage('NIKAIMPORT_GOTO_LIST'),
		'LINK' => 'nikaimport_admin.php?lang='.LANG
	)
);

$context = new CAdminContextMenu($aMenu);
$context->Show();

$aTabs = array(array('DIV' => 'edit1', 'TAB' => getMessage('NIKAIMPORT_TAB_NAME'), 'TITLE' => getMessage('NIKAIMPORT_TAB_TITLE')));
$tabControl = new CAdminTabControl("tabControl", $aTabs, false);

?>

<? $tabControl->Begin(); ?>
<? $tabControl->BeginNextTab(); ?>


<?

$estDays = null;
if ($nikaimport['MIN_AMOUNT'] > 0 && $nikaimport['PORTION'] > 0)
{
	$siteCapacity = Bitrix\NikaImport\AdminHelper::getSiteCapacity($nikaimport['SITE_ID']);
	$testCapacity = Bitrix\NikaImport\AdminHelper::getTestCapacity($nikaimport['ID']);

	if ($siteCapacity['daily'] > 0)
	{
		$rem = $nikaimport['MIN_AMOUNT'] - min($testCapacity);
		$est = $rem > 0 ? $rem / ($siteCapacity['daily'] / 2) : 0;

		$estDays = ceil(100 * $est / $nikaimport['PORTION']);
	}
}

$end_date = null;
if ($nikaimport['ACTIVE'] == 'Y' && $nikaimport['DURATION'] != 0)
{
	if ($nikaimport['DURATION'] > 0)
	{
		$end = clone $nikaimport['START_DATE'];
		$end->add(intval($nikaimport['DURATION']).' days');

		$end_date = $end->format(Bitrix\Main\Type\Date::convertFormatToPhp($arLang['FORMAT_DATE']));
	}
	else
	{
		if (isset($estDays))
		{
			$end = new Bitrix\Main\Type\DateTime();
			$end->add($estDays.' days');

			$end_date = $end->format(Bitrix\Main\Type\Date::convertFormatToPhp($arLang['FORMAT_DATE']));
		}
		else
		{
			$end_date = getMessage('NIKAIMPORT_DURATION_NA');
		}
	}
}

function pvalue($p1, $p2, $n1, $n2)
{
	$dx = array(
		1.0000000000,
		0.0498673470,
		0.0211410061,
		0.0032776263,
		0.0000380036,
		0.0000488906,
		0.0000053830
	);

	$stdError = sqrt(($p1 * (1 - $p1) / $n1) + ($p2 * (1 - $p2) / $n2));

	$zval = abs($p2-$p1) / $stdError;

	for ($pval = 0, $i = 6; $i >= 0; $i--)
		$pval = $pval * $zval + $dx[$i];
	$pval = pow($pval, -16);
	$pval = 0.5 - abs($pval-0.5);

	return $pval;
}

if ($nikaimport['START_DATE'] || $nikaimport['STOP_DATE'])
{
	$math = array('pwr' => false, 'sgn' => false);

	if ($nikaimport['MIN_AMOUNT'] > 0)
	{
		if (min($arGroupABaseRate['DENOMINATOR'], $arGroupBBaseRate['DENOMINATOR']) >= $nikaimport['MIN_AMOUNT'])
		{
			$pval = pvalue(
				$arGroupABaseRate['RATE'], $arGroupBBaseRate['RATE'],
				$arGroupABaseRate['DENOMINATOR'], $arGroupBBaseRate['DENOMINATOR']
			);

			$math = array('pwr' => true, 'sgn' => $pval < 0.05);
		}
	}
}

$user_name = $nikaimport['USER_ID'] ? CUser::formatName(
	CSite::getNameFormat(),
	array(
		'TITLE'       => $nikaimport['USER_TITLE'],
		'NAME'        => $nikaimport['USER_NAME'],
		'SECOND_NAME' => $nikaimport['USER_SECOND_NAME'],
		'LAST_NAME'   => $nikaimport['USER_LAST_NAME'],
		'LOGIN'       => $nikaimport['USER_LOGIN'],
	),
	true, true
) : false;

?>

<tr><td>

<div class="stat-item-block-container nikaimport-report-container">
	<div class="stat-item-container item-test-info">
		<? if ($user_name) : ?>
		<span class="ab-test-info ab-test-info-right"><?=getMessage($nikaimport['ACTIVE'] == 'Y' ? 'NIKAIMPORT_STARTED_BY' : 'NIKAIMPORT_STOPPED_BY'); ?>: <a href="/bitrix/admin/user_edit.php?ID=<?=intval($nikaimport['USER_ID']); ?>&amp;lang=<?=LANG; ?>"><?=$user_name; ?></a></span>
		<? endif; ?>
		<img style="float: left; margin-right: 15px; " src="/bitrix/images/nikaimport/ab-test-<?=($nikaimport['ACTIVE'] == 'Y' ? 'on' : 'off');?>.gif">
		<? if ($MOD_RIGHT >= 'W') : ?>
		<? if ($nikaimport['ACTIVE'] == 'Y') : ?>
		<span class="adm-btn" style="vertical-align: baseline; " onclick="if (confirm('<?=CUtil::JSEscape(getMessage('NIKAIMPORT_STOP_CONFIRM')); ?>')) window.location='nikaimport_admin.php?action=stop&amp;ID=<?=intval($nikaimport['ID']); ?>&amp;lang=<?=LANG; ?>&amp;<?=bitrix_sessid_get(); ?>'; "><?=getMessage('NIKAIMPORT_BTN_STOP'); ?></span>
		<? elseif (empty($active_test)) : ?>
		<span class="adm-btn adm-btn-green" style="vertical-align: baseline; " onclick="if (confirm('<?=CUtil::JSEscape(getMessage('NIKAIMPORT_START_CONFIRM')); ?>')) window.location='nikaimport_admin.php?action=start&amp;ID=<?=intval($nikaimport['ID']); ?>&amp;lang=<?=LANG; ?>&amp;<?=bitrix_sessid_get(); ?>'; "><?=getMessage('NIKAIMPORT_BTN_START'); ?></span>
		<? else : ?>
		<span class="adm-btn adm-btn-disabled" style="vertical-align: baseline; margin-right: 0px; " onclick="alert('<?=CUtil::JSEscape(getMessage('NIKAIMPORT_ONLYONE_WARNING')); ?>'); "><?=getMessage('NIKAIMPORT_BTN_START'); ?></span>
		<? endif; ?>
		<? endif; ?>
		<? if ($nikaimport['ACTIVE'] == 'Y') : ?>
		<span class="ab-test-info">
			<?=getMessage('NIKAIMPORT_START_DATE'); ?>:
			<span><?=$nikaimport['START_DATE']->format(Bitrix\Main\Type\Date::convertFormatToPhp($arLang['FORMAT_DATE'])); ?></span>
		</span>
		<? if ($end_date) : ?>
		<span class="ab-test-info">
			<?=getMessage('NIKAIMPORT_STOP_DATE2'); ?>:
			<span><?=htmlspecialcharsbx($end_date); ?></span>
		</span>
		<? endif; ?>
		<? else : ?>
		<? if ($nikaimport['START_DATE']) : ?>
		<span class="ab-test-info">
			<?=getMessage('NIKAIMPORT_START_DATE2'); ?>:
			<span><?=$nikaimport['START_DATE']->format(Bitrix\Main\Type\Date::convertFormatToPhp($arLang['FORMAT_DATE'])); ?></span>
		</span>
		<? endif; ?>
		<? if ($nikaimport['STOP_DATE']) : ?>
		<span class="ab-test-info">
			<?=getMessage('NIKAIMPORT_STOP_DATE'); ?>:
			<span><?=$nikaimport['STOP_DATE']->format(Bitrix\Main\Type\Date::convertFormatToPhp($arLang['FORMAT_DATE'])); ?></span>
		</span>
		<? endif; ?>
		<? if (!$nikaimport['START_DATE'] && !$nikaimport['STOP_DATE']) : ?>
		<span class="ab-test-info"><?=getMessage('NIKAIMPORT_NEVER_LAUNCHED'); ?></span>
		<? endif; ?>
		<? endif; ?>
	</div>

	<? if (!empty($math)) : ?>
	<div class="stat-item-container item-test-info">
		<div class="adm-input-wrap adm-input-help-icon-wrap">
			<a class="adm-input-help-icon" href="#math-hint"></a>
		</div>
		<span class="ab-test-info" style="padding: 0px; color: <?=($math['pwr'] ? '#729e00' : '#c70000'); ?>; font-weight: bold; ">
			&bull; <?=getMessage($math['pwr'] ? 'NIKAIMPORT_MATH_POWER_YES' : 'NIKAIMPORT_MATH_POWER_NO'); ?>
			<? if (!$math['pwr'] && $estDays) : ?>
			(<?=str_replace(
				array('#NUM#', '#UNIT#'),
				array($estDays, plural_form($estDays, get_plural_messages('NIKAIMPORT_DURATION_DAYS1_'))),
				getMessage('NIKAIMPORT_DURATION_EST')
			); ?>)
			<? endif; ?>
		</span> <sup>1</sup><br>
		<span class="ab-test-info" style="padding: 0px; color: <?=($math['sgn'] ? '#729e00' : '#c70000'); ?>; font-weight: bold; ">
			&bull; <?=getMessage($math['sgn'] ? 'NIKAIMPORT_MATH_SIGNIFICANCE_YES' : 'NIKAIMPORT_MATH_SIGNIFICANCE_NO'); ?>
		</span> <sup>2</sup>
	</div>
	<? endif; ?>

	<? if (empty($conversionRates)) : ?>
	<div class="stat-item-container item-conversion-block">
		<?=getMessage($conversionAvailable ? 'NIKAIMPORT_CONVRATES_UNAVAILABLE' : 'NIKAIMPORT_CONVERSION_UNAVAILABLE'); ?>
	</div>
	<? else : ?>
	<div class="ab-item-container">
		<div class="stat-item-container">
			<span class="ab-item-desc"><span>50%</span> <?=getMessage('NIKAIMPORT_VISITS'); ?></span>
			<span class="ab-letter ab-letter-a">A</span>
			<span class="ab-item" style="min-width: 250px; "><?=getMessage('NIKAIMPORT_TEST_TITLE_A'); ?></span>
		</div>
		<div class="stat-item-container">
			<span class="ab-item-desc"><span>50%</span> <?=getMessage('NIKAIMPORT_VISITS'); ?></span>
			<span class="ab-letter ab-letter-b">B</span>
			<span class="ab-item" style="min-width: 250px; "><?=getMessage('NIKAIMPORT_TEST_TITLE_B'); ?></span>
		</div>
	</div>
	<div class="stat-item-container item-conversion-block">
		<div class="item-conversion-block-title"><?=(empty($nikaimport['NAME'])
			? str_replace('#ID#', $ID, getMessage('NIKAIMPORT_CONVERSION_GRAPH_TITLE1'))
			: str_replace('#NAME#', htmlspecialcharsbx($nikaimport['NAME']), getMessage('NIKAIMPORT_CONVERSION_GRAPH_TITLE2'))
		); ?></div>
		<div class="item-conversion-block-subtitle"><?=getMessage('NIKAIMPORT_CONVERSION_GRAPH_DESCR'); ?></div>
		<div id="chart_container" class="item-conversion-graph-block">
			<div style="position: relative; top: 50%; margin-top: -0.5em; color: #808080; ">
				<?=getMessage(empty($arGraphData) ? 'NIKAIMPORT_CONVERSION_GRAPH_EMPTY' : 'NIKAIMPORT_CONVERSION_GRAPH_LOADING'); ?>
			</div>
		</div>

		<? if (!empty($arGraphData)) : ?>
		<script type="text/javascript">

		AmCharts.ready(function()
		{
			var chartData = <?=CUtil::PhpToJSObject($arGraphData); ?>;

			var chart = new AmCharts.AmSerialChart();

			chart.path         = BX.message('AMCHARTS_PATH') || '/bitrix/js/main/amcharts/3.13/'; // TODO
			chart.pathToImages = BX.message('AMCHARTS_IMAGES_PATH') || '/bitrix/js/main/amcharts/3.13/images/'; // TODO

			var monthNames = [];
			var shortMonthNames = [];
			for(var m = 1; m <= 12; m++)
			{
				monthNames.push(BX.message['MONTH_'+m.toString()]);
				shortMonthNames.push(BX.message['MONTH_'+m.toString()+'_S']);
			}
			AmCharts.monthNames = monthNames;
			AmCharts.shortMonthNames = shortMonthNames;

			chart.zoomOutText   = '<?=CUtil::JSEscape(getMessage('NIKAIMPORT_CONVERSION_GRAPH_SHOW_ALL')); ?>';
			chart.dataProvider  = chartData;
			chart.categoryField = 'date';
			chart.theme         = 'none';
			chart.decimalSeparator = ',';
			chart.autoMargins   = false;
			chart.marginLeft    = 60;
			chart.marginRight   = 20;
			chart.marginTop     = 20;
			chart.marginBottom  = 40;
			chart.chartCursor = {
				enabled: true,
				cursorColor: '#808080',
				oneBalloonOnly: true,
				categoryBalloonEnabled: true,
				categoryBalloonColor: '#000000',
				categoryBalloonDateFormat: '<?=CUtil::JSEscape($arLang['FORMAT_DATE']); ?>'
			};

			chart.chartScrollbar = {};
			chart.dataDateFormat = 'YYYY-MM-DD';
			chart.valueAxes = [{'unit': '%'}];
			chart.categoryAxis =
			{
				parseDates: true,
				minPeriod: 'DD',
				equalSpacing: true,
				markPeriodChange: false,
				autoGridCount: false,
				gridCount: 0,
				dateFormats: [
					{period: 'DD', format: 'D MMM'},
					{period: 'WW', format: 'D MMM'},
					{period: 'MM', format: 'MMMM'},
					{period: 'YYYY', format: 'YYYY'}
				]
			};

			chart.addListener('zoomed', function(params) {
				var chart = params.chart;
				var axis  = chart.categoryAxis;

				while (axis.guides.length > 0)
					axis.removeGuide(axis.guides[0]);

				var step = Math.ceil((1+params.endIndex-params.startIndex) / 10);
				for (var i = params.startIndex; i <= params.endIndex; i = i + step)
				{
					axis.addGuide(new AmCharts.Guide({Guide: {
						date: chart.dataProvider[i].date,
						label: AmCharts.formatDate(chart.chartData[i].category, 'D MMM')
					}}));
				}

				chart.validateNow();
			});

			var graph_a = new AmCharts.AmGraph();
			var graph_b = new AmCharts.AmGraph();

			graph_a.connect    = false;
			graph_a.balloonText = '<?=CUtil::JSEscape(getMessage('NIKAIMPORT_CONVERSION_GRAPH_HINT_A')); ?>: [[value]]%';
			graph_a.valueField = 'value_a';
			graph_a.type       = 'smoothedLine';
			graph_a.lineThickness = 2;
			graph_a.bullet     = 'round';
			graph_a.lineColor  = '#33b4ea';

			graph_b.connect    = false;
			graph_b.balloonText = '<?=CUtil::JSEscape(getMessage('NIKAIMPORT_CONVERSION_GRAPH_HINT_B')); ?>: [[value]]%';
			graph_b.valueField = 'value_b';
			graph_b.type       = 'smoothedLine';
			graph_b.lineThickness = 2;
			graph_b.bullet     = 'round';
			graph_b.lineColor  = '#ea9a00';

			chart.addGraph(graph_a);
			chart.addGraph(graph_b);

			chart.write('chart_container');
		});
		</script>
		<? endif; ?>

		<? $groupARate = round($arGroupABaseRate['RATE']*100, 2); ?>
		<? $groupBRate = round($arGroupBBaseRate['RATE']*100, 2); ?>
		<? $rateDiff   = round($groupBRate-$groupARate, 2); ?>
		<div class="stat-item-data-container stat-item-data-container-a">
			<div class="stat-item-container stat-item-data">
				<div class="stat-item-container-title">
					<span class="stat-item-container-title-letter">A</span>
					<span class="stat-item-container-title-name"><?=getMessage('NIKAIMPORT_CONVERSION_VALUE_TITLE'); ?></span>
				</div>
				<div class="stat-item">
					<div class="stat-item-block">
						<span class="stat-item-block-inner">
							<span id="a-graph-rate" class="stat-item-block-digit"><?=str_replace('.', ',', $groupARate); ?><span>%</span></span>
						</span>
					</div>
				</div>
			</div>
		</div>
		<div class="stat-item-data-container<? if ($rateDiff != 0) : ?> stat-item-data-container-dynamics<? endif; ?>">
			<div class="stat-item-container stat-item-data<? if ($rateDiff != 0) : ?> stat-item-block-<?=($rateDiff > 0 ? 'incr' : 'fall'); ?><? endif; ?>">
				<div class="stat-item-container-title">
					<span class="stat-item-container-title-name"><?=getMessage('NIKAIMPORT_CONVERSION_DIFF_TITLE'); ?></span>
				</div>
				<div class="stat-item">
					<div class="stat-item-block">
						<span class="stat-item-block-inner">
							<span id="graph-rate-diff" class="stat-item-block-digit">
								<span class="stat-item-block-digit-arrow"></span><?=str_replace('.', ',', abs($rateDiff)); ?><span>%</span>
							</span>
						</span>
					</div>
				</div>
			</div>
		</div>
		<div class="stat-item-data-container stat-item-data-container-b">
			<div class="stat-item-container stat-item-data">
				<div class="stat-item-container-title">
					<span class="stat-item-container-title-letter">B</span>
					<span class="stat-item-container-title-name"><?=getMessage('NIKAIMPORT_CONVERSION_VALUE_TITLE'); ?></span>
				</div>
				<div class="stat-item">
					<div class="stat-item-block">
						<span class="stat-item-block-inner">
							<span id="b-graph-rate" class="stat-item-block-digit"><?=str_replace('.', ',', $groupBRate); ?><span>%</span></span>
						</span>
					</div>
				</div>
			</div>
		</div>
	</div>

	<? if (!empty($funnelRates)) : ?>
	<? $funnelBaseRate = end($funnelRates); ?>
	<div class="adm-detail-toolbar">
		<span class="adm-detail-toolbar-title"><?=getMessage('NIKAIMPORT_CONVERSION_FUNNEL_TITLE'); ?></span>
	</div>
	<div id="funnel-a" class="stat-item-data-container stat-item-data-container-a">
		<div class="stat-item-container stat-item-data stat-item-data-funnel">
			<div class="stat-item-container-title">
				<span class="stat-item-container-title-letter">A</span>
				<span class="stat-item-container-title-name"><?=htmlspecialcharsbx($arGroupAData[$funnelBaseRate]['TYPE']['NAME']); ?></span>
			</div>
			<div class="stat-item">
				<div class="stat-item-block stat-item-block-conversion">
					<span class="stat-item-block-inner">
						<span class="stat-item-block-title"><?=getMessage('NIKAIMPORT_CONVERSION_VALUE_TITLE'); ?></span>
						<span class="stat-item-block-digit"><?=str_replace('.', ',', $groupARate); ?><span>%</span></span>
					</span>
				</div>
				<div class="stat-item-block stat-item-block-first">
					<span class="stat-item-block-inner">
						<span class="stat-item-block-title"><?=getMessage('NIKAIMPORT_CONVERSION_SUM_TITLE'); ?></span>
						<span class="stat-item-block-digit scale-sum-fnl scale-num-30-3">
							<?=number_format(floatval($arGroupAData[$funnelBaseRate]['SUM']), 0, '', ' '); ?>
							<span><?=htmlspecialcharsbx($arGroupAData[$funnelBaseRate]['TYPE']['UNITS']['SUM']); ?></span>
						</span>
					</span>
				</div>
				<? $sum = array_reduce($funnelRates, function($sum, $type) use ($arGroupAData) {
					return $sum + $arGroupAData[$type]['SUM'];
				}, 0); ?>
				<div id="funnel_a_container">
					<div style="position: relative; top: 50%; margin-top: -0.5em; color: #808080; ">
						<?=getMessage($sum > 0 ? 'NIKAIMPORT_CONVERSION_GRAPH_LOADING' : 'NIKAIMPORT_CONVERSION_GRAPH_EMPTY'); ?>
					</div>
				</div>

				<? if ($sum > 0) : ?>
				<script type="text/javascript">

				AmCharts.ready(function()
				{
					var chart = new AmCharts.AmFunnelChart();

					chart.dataProvider = [
						<? $k = 0; ?>
						<? foreach ($funnelRates as $type) : ?>
						<? $data = $arGroupAData[$type]; ?>
						<? if ($k++ > 0) echo ','; ?>{'title': '<?=CUtil::JSEscape($data['TYPE']['NAME']); ?>', 'value': <?=sprintf('%.0f', $data['SUM']); ?>, 'unit': '<?=CUtil::JSEscape($data['TYPE']['UNITS']['SUM']); ?>'}
						<? endforeach; ?>
					];
					chart.theme        = 'none';
					chart.labelText    = ' ';
					chart.balloonText  = '[[title]]: <span style="white-space: nowrap; ">[[value]] [[unit]]</span>';
					chart.titleField   = 'title';
					chart.valueField   = 'value';
					chart.thousandsSeparator = ' ';
					chart.depth3D      = 160;
					chart.angle        = 23;
					chart.outlineAlpha = 2;
					chart.outlineColor = '#FFFFFF';
					chart.outlineThickness = 2;
					chart.marginRight  = 70;
					chart.marginLeft   = 70;
					chart.balloon      = {'fixedPosition': true};

					chart.write('funnel_a_container');
				});
				</script>
				<? endif; ?>

			</div>
		</div>
	</div>
	<div id="funnel-b" class="stat-item-data-container stat-item-data-container-b">
		<div class="stat-item-container stat-item-data stat-item-data-funnel">
			<div class="stat-item-container-title">
				<span class="stat-item-container-title-letter">B</span>
				<span class="stat-item-container-title-name"><?=htmlspecialcharsbx($arGroupBData[$funnelBaseRate]['TYPE']['NAME']); ?></span>
			</div>
			<div class="stat-item">
				<div class="stat-item-block stat-item-block-conversion<? if ($rateDiff != 0) : ?> stat-item-block-conversion-<?=($rateDiff > 0 ? 'incr' : 'fall'); endif; ?>">
					<span class="stat-item-block-inner">
						<span class="stat-item-block-title"><?=getMessage('NIKAIMPORT_CONVERSION_VALUE_TITLE'); ?></span>
						<span class="stat-item-block-digit">
							<? if ($rateDiff != 0) : ?><span class="stat-item-block-digit-arrow"></span><? endif; ?><?=str_replace('.', ',', $groupBRate); ?><span>%</span>
						</span>
					</span>
				</div>
				<div class="stat-item-block stat-item-block-first">
					<span class="stat-item-block-inner">
						<span class="stat-item-block-title"><?=getMessage('NIKAIMPORT_CONVERSION_SUM_TITLE'); ?></span>
						<span class="stat-item-block-digit scale-sum-fnl scale-num-30-3">
							<?=number_format(floatval($arGroupBData[$funnelBaseRate]['SUM']), 0, '', ' '); ?>
							<span><?=htmlspecialcharsbx($arGroupBData[$funnelBaseRate]['TYPE']['UNITS']['SUM']); ?></span>
						</span>
					</span>
				</div>
				<? $sum = array_reduce($funnelRates, function($sum, $type) use ($arGroupBData) {
					return $sum + $arGroupBData[$type]['SUM'];
				}, 0); ?>
				<div id="funnel_b_container">
					<div style="position: relative; top: 50%; margin-top: -0.5em; color: #808080; ">
						<?=getMessage($sum > 0 ? 'NIKAIMPORT_CONVERSION_GRAPH_LOADING' : 'NIKAIMPORT_CONVERSION_GRAPH_EMPTY'); ?>
					</div>
				</div>

				<? if ($sum > 0) : ?>
				<script type="text/javascript">

				AmCharts.ready(function()
				{
					var chart = new AmCharts.AmFunnelChart();

					chart.dataProvider = [
						<? $k = 0; ?>
						<? foreach ($funnelRates as $type) : ?>
						<? $data = $arGroupBData[$type]; ?>
						<? if ($k++ > 0) echo ','; ?>{'title': '<?=CUtil::JSEscape($data['TYPE']['NAME']); ?>', 'value': <?=sprintf('%.0f', $data['SUM']); ?>, 'unit': '<?=CUtil::JSEscape($data['TYPE']['UNITS']['SUM']); ?>'}
						<? endforeach; ?>
					];
					chart.theme        = 'none';
					chart.labelText    = ' ';
					chart.balloonText  = '[[title]]: <span style="white-space: nowrap; ">[[value]] [[unit]]</span>';
					chart.titleField   = 'title';
					chart.valueField   = 'value';
					chart.thousandsSeparator = ' ';
					chart.depth3D      = 160;
					chart.angle        = 23;
					chart.outlineAlpha = 2;
					chart.outlineColor = '#FFFFFF';
					chart.outlineThickness = 2;
					chart.marginRight  = 70;
					chart.marginLeft   = 70;
					chart.balloon      = {'fixedPosition': true};

					chart.write('funnel_b_container');
				});
				</script>
				<? endif; ?>

			</div>
		</div>
	</div>
	<? endif; ?>

	<div class="adm-detail-toolbar">
		<span class="adm-detail-toolbar-title"><?=getMessage('NIKAIMPORT_CONVERSION_COUNTERS_TITLE'); ?></span>
	</div>
	<? $arGroupARates = array(); ?>
	<div id="counters-a" class="stat-item-data-container stat-item-data-container-a">
		<? $cnt = count($arGroupAData); $k = 0; ?>
		<? foreach ($allRates as $type) : ?>
		<? $data = $arGroupAData[$type]; ?>
		<? $rate = round($data['RATE']*100, 2); ?>
		<? $arGroupARates[$type] = $rate; ?>
		<div class="stat-item-container stat-item-data<? if (!is_set($data, 'SUM')) : ?> stat-item-data-short<? endif; ?>">
			<div class="stat-item-container-title">
				<span class="stat-item-container-title-letter">A</span>
				<span class="stat-item-container-title-name"><?=htmlspecialcharsbx($data['TYPE']['NAME']); ?></span>
			</div>
			<div class="stat-item">
				<div class="stat-item-block stat-item-block-conversion">
					<span class="stat-item-block-inner">
						<span class="stat-item-block-title"><?=getMessage('NIKAIMPORT_CONVERSION_VALUE_TITLE'); ?></span>
						<span class="stat-item-block-digit"><?=str_replace('.', ',', $rate); ?><span>%</span></span>
					</span>
				</div>
				<? if (is_set($data, 'SUM')) : ?>
				<div class="stat-item-block stat-item-block-first">
					<span class="stat-item-block-inner">
						<span class="stat-item-block-title"><?=getMessage('NIKAIMPORT_CONVERSION_SUM_TITLE'); ?></span>
						<span class="stat-item-block-digit scale-sum-cnt scale-num-30-1">
							<?=number_format(floatval($data['SUM']), 0, '', ' '); ?>
							<span><?=htmlspecialcharsbx($data['TYPE']['UNITS']['SUM']); ?></span>
						</span>
					</span>
				</div>
				<? endif; ?>
				<? $value = floatval(isset($data['QUANTITY']) ? $data['QUANTITY'] : $data['NUMERATOR']); ?>
				<div class="stat-item-block">
					<span class="stat-item-block-inner">
						<span class="stat-item-block-title"><?=getMessage('NIKAIMPORT_CONVERSION_CNT_TITLE'); ?></span>
						<span class="stat-item-block-digit scale-num-cnt scale-num-30-2"><?=number_format($value, 0, '', ' '); ?></span>
					</span>
				</div>
			</div>
			<? if (++$k < $cnt) : ?><span class="stat-item-chain"></span><? endif; ?>
		</div>
		<? endforeach; ?>
	</div>
	<div id="counters-b" class="stat-item-data-container stat-item-data-container-b">
		<? $cnt = count($arGroupBData); $k = 0; ?>
		<? foreach ($allRates as $type) : ?>
		<? $data = $arGroupBData[$type]; ?>
		<? $rate = round($data['RATE']*100, 2); ?>
		<? $rateDiff = round($rate-$arGroupARates[$type], 2); ?>
		<div class="stat-item-container stat-item-data<? if (!is_set($data, 'SUM')) : ?> stat-item-data-short<? endif; ?>">
			<div class="stat-item-container-title">
				<span class="stat-item-container-title-letter">B</span>
				<span class="stat-item-container-title-name"><?=htmlspecialcharsbx($data['TYPE']['NAME']); ?></span>
			</div>
			<div class="stat-item">
				<div class="stat-item-block stat-item-block-conversion<? if ($rateDiff != 0) : ?> stat-item-block-conversion-<?=($rateDiff > 0 ? 'incr' : 'fall'); endif; ?>">
					<span class="stat-item-block-inner">
						<span class="stat-item-block-title"><?=getMessage('NIKAIMPORT_CONVERSION_VALUE_TITLE'); ?></span>
						<span class="stat-item-block-digit"><? if ($rateDiff != 0) : ?><span class="stat-item-block-digit-arrow"></span><? endif; ?><?=str_replace('.', ',', $rate); ?><span>%</span></span>
					</span>
				</div>
				<? if (is_set($data, 'SUM')) : ?>
				<div class="stat-item-block stat-item-block-first">
					<span class="stat-item-block-inner">
						<span class="stat-item-block-title"><?=getMessage('NIKAIMPORT_CONVERSION_SUM_TITLE'); ?></span>
						<span class="stat-item-block-digit scale-sum-cnt scale-num-30-1">
							<?=number_format(floatval($data['SUM']), 0, '', ' '); ?>
							<span><?=htmlspecialcharsbx($data['TYPE']['UNITS']['SUM']); ?></span>
						</span>
					</span>
				</div>
				<? endif; ?>
				<? $value = floatval(isset($data['QUANTITY']) ? $data['QUANTITY'] : $data['NUMERATOR']); ?>
				<div class="stat-item-block">
					<span class="stat-item-block-inner">
						<span class="stat-item-block-title"><?=getMessage('NIKAIMPORT_CONVERSION_CNT_TITLE'); ?></span>
						<span class="stat-item-block-digit scale-num-cnt scale-num-30-2"><?=number_format($value, 0, '', ' '); ?></span>
					</span>
				</div>
			</div>
			<? if (++$k < $cnt) : ?><span class="stat-item-chain"></span><? endif; ?>
		</div>
		<? endforeach; ?>
	</div>
	<script type="text/javascript">

	var scale_30_1_list = [].concat(
		BX.findChildrenByClassName(BX('counters-a'), 'scale-num-30-1', true),
		BX.findChildrenByClassName(BX('counters-b'), 'scale-num-30-1', true)
	);
	for (var i in scale_30_1_list)
		scale_30_1_list[i] = {node: scale_30_1_list[i], maxFontSize: 30, smallestValue: true};

	var scale_30_2_list = [].concat(
		BX.findChildrenByClassName(BX('counters-a'), 'scale-num-30-2', true),
		BX.findChildrenByClassName(BX('counters-b'), 'scale-num-30-2', true)
	);
	for (var i in scale_30_2_list)
		scale_30_2_list[i] = {node: scale_30_2_list[i], maxFontSize: 30, smallestValue: true};

	var graphFixSize = new BX.FixFontSize({objList: scale_30_1_list, onresize: true});
	var graphFixSize = new BX.FixFontSize({objList: scale_30_2_list, onresize: true});

	<? if (!empty($funnelRates)) : ?>
	var scale_30_3_list = [].concat(
		BX.findChildrenByClassName(BX('funnel-a'), 'scale-num-30-3', true),
		BX.findChildrenByClassName(BX('funnel-b'), 'scale-num-30-3', true)
	);
	for (var i in scale_30_3_list)
		scale_30_3_list[i] = {node: scale_30_3_list[i], maxFontSize: 30, smallestValue: true};

	var graphFixSize = new BX.FixFontSize({objList: scale_30_3_list, onresize: true});
	<? endif; ?>

	</script>
	<? endif; ?>
</div>

</td></tr>

<? $tabControl->EndTab(); ?>
<? $tabControl->End(); ?>

<div class="adm-info-message-wrap">
	<div class="adm-info-message" id="math-hint">
		<span class="required"><sup>1</sup></span> <?=getMessage('NIKAIMPORT_MATH_POWER_HINT'); ?><br></br>
		<span class="required"><sup>2</sup></span> <?=getMessage('NIKAIMPORT_MATH_SIGNIFICANCE_HINT'); ?>
	</div>
</div>

<?

require($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/epilog_admin.php');
