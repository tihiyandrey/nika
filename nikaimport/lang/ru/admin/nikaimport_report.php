<?php

$MESS['NIKAIMPORT_REPORT_NOTFOUND'] = "Отчет не найден";
$MESS['NIKAIMPORT_REPORT_DISABLED'] = "А/B-тест не существует или не настроен";

$MESS['NIKAIMPORT_REPORT_TITLE1'] = "Выполнение А/B-теста ##ID#";
$MESS['NIKAIMPORT_REPORT_TITLE2'] = "Выполнение А/B-теста: #NAME#";

$MESS['NIKAIMPORT_GOTO_LIST'] = "Список тестов";

$MESS['NIKAIMPORT_TAB_NAME'] = "A/B-тест";
$MESS['NIKAIMPORT_TAB_TITLE'] = "Результаты теста";

$MESS['NIKAIMPORT_STARTED_BY'] = "Запустил";
$MESS['NIKAIMPORT_STOPPED_BY'] = "Остановил";
$MESS['NIKAIMPORT_DURATION'] = "Работает";
$MESS['NIKAIMPORT_DURATION2'] = "Работал";
$MESS['NIKAIMPORT_DURATION_NA'] = "нет данных";

$MESS['NIKAIMPORT_START_DATE'] = 'Тест запущен';
$MESS['NIKAIMPORT_STOP_DATE'] = 'Тест завершен';
$MESS['NIKAIMPORT_START_DATE2'] = 'Дата последнего запуска';
$MESS['NIKAIMPORT_STOP_DATE2'] = 'Ожидаемая дата завершения';
$MESS['NIKAIMPORT_NEVER_LAUNCHED'] = 'Никогда не запускался';

$MESS['NIKAIMPORT_BTN_START'] = "Запустить";
$MESS['NIKAIMPORT_BTN_STOP'] = "Остановить";

$MESS['NIKAIMPORT_ONLYONE_WARNING'] = "Уже запущено активное тестирование, для запуска нового, дождитесь окончания или остановите тест вручную.";
$MESS['NIKAIMPORT_START_CONFIRM'] = "Вы действительно хотите запустить тест?";
$MESS['NIKAIMPORT_STOP_CONFIRM'] = "Вы действительно хотите остановить тест?";

$MESS['NIKAIMPORT_DURATION_DAYS1_PLURAL_1'] = 'день';
$MESS['NIKAIMPORT_DURATION_DAYS1_PLURAL_2'] = 'дня';
$MESS['NIKAIMPORT_DURATION_DAYS1_PLURAL_3'] = 'дней';

$MESS['NIKAIMPORT_DURATION_DAYS2_PLURAL_1'] = 'дня';
$MESS['NIKAIMPORT_DURATION_DAYS2_PLURAL_2'] = 'дней';
$MESS['NIKAIMPORT_DURATION_DAYS2_PLURAL_3'] = 'дней';

$MESS['NIKAIMPORT_DURATION_HOURS_PLURAL_1'] = 'час';
$MESS['NIKAIMPORT_DURATION_HOURS_PLURAL_2'] = 'часа';
$MESS['NIKAIMPORT_DURATION_HOURS_PLURAL_3'] = 'часов';

$MESS['NIKAIMPORT_DURATION_HOURS_0'] = 'менее <span>1</span> часа';
$MESS['NIKAIMPORT_DURATION_OF'] = 'из';

$MESS['NIKAIMPORT_TEST_TITLE_A'] = 'Группа A';
$MESS['NIKAIMPORT_TEST_TITLE_B'] = 'Группа B';

$MESS['NIKAIMPORT_CONVERSION_GRAPH_HINT_A'] = 'Конверсия группы A';
$MESS['NIKAIMPORT_CONVERSION_GRAPH_HINT_B'] = 'Конверсия группы B';

$MESS['NIKAIMPORT_CONVERSION_GRAPH_SHOW_ALL'] = 'Показать все';

$MESS['NIKAIMPORT_VISITS'] = 'посетителей';

$MESS['NIKAIMPORT_CONVERSION_VALUE_TITLE'] = 'Конверсия';
$MESS['NIKAIMPORT_CONVERSION_DIFF_TITLE'] = 'Динамика';

$MESS['NIKAIMPORT_CONVERSION_CNT_TITLE'] = 'Количество';
$MESS['NIKAIMPORT_CONVERSION_SUM_TITLE'] = 'Сумма';
$MESS['NIKAIMPORT_CONVERSION_SUM_UNIT'] = 'руб';

$MESS['NIKAIMPORT_CONVERSION_UNAVAILABLE'] = 'Для просмотра данных по конверсии необходим модуль конверсии.';
$MESS['NIKAIMPORT_CONVRATES_UNAVAILABLE'] = 'Нет данных для формирования отчета по конверсии.';

$MESS['NIKAIMPORT_CONVERSION_RESULT_TITLE'] = 'Результат теста';

$MESS['NIKAIMPORT_CONVERSION_GRAPH_TITLE1'] = 'A/B-тестирование "Тест ##ID#"';
$MESS['NIKAIMPORT_CONVERSION_GRAPH_TITLE2'] = 'A/B-тестирование "#NAME#"';
$MESS['NIKAIMPORT_CONVERSION_GRAPH_DESCR'] = 'Убедитесь в том, что показатели конверсии в группе B выше.';

$MESS['NIKAIMPORT_CONVERSION_GRAPH_EMPTY'] = 'Нет данных';
$MESS['NIKAIMPORT_CONVERSION_GRAPH_LOADING'] = 'Загрузка данных';

$MESS['NIKAIMPORT_CONVERSION_DETAILS'] = 'Смотреть сводные данные';

$MESS['NIKAIMPORT_CONVERSION_FUNNEL_TITLE'] = 'Основной показатель конверсии';
$MESS['NIKAIMPORT_CONVERSION_COUNTERS_TITLE'] = 'Все показатели конверсии';

$MESS['NIKAIMPORT_MATH_POWER_YES'] = 'Достигнута достаточная статистическая мощность';
$MESS['NIKAIMPORT_MATH_POWER_NO'] = 'Не достигнута достаточная статистическая мощность';
$MESS['NIKAIMPORT_MATH_SIGNIFICANCE_YES'] = 'Результат теста является статистически значимым';
$MESS['NIKAIMPORT_MATH_SIGNIFICANCE_NO'] = 'Результат теста не является статистически значимым';

$MESS['NIKAIMPORT_MATH_POWER_HINT'] = 'Статистическая мощность &mdash; вероятность того, что тест определит разницу между двумя вариантами, если эта разница действительно существует. Статистическая мощность увеличивается при увеличении размера выборки. Если статистическая мощность меньше 80%, то доверять результатам теста нельзя.';
$MESS['NIKAIMPORT_MATH_SIGNIFICANCE_HINT'] = 'Статистическая значимость &mdash; вероятность того, что результат теста не является случайным. Результат A/B-теста считается статистически значимым, если мала вероятность увидеть разницу там, где ее на самом деле нет (или мала вероятность получить подобный результат при проведении A/A-теста). Уровень статистической значимости не должен быть меньше 95%.';

$MESS['NIKAIMPORT_DURATION_EST'] = 'осталось примерно: #NUM# #UNIT#';
