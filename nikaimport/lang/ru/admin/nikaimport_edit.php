<?php

$MESS['NIKAIMPORT_ADD_TITLE'] = "Создание A/B-теста";
$MESS['NIKAIMPORT_EDIT_TITLE1'] = "Редактирование A/B-теста ##ID#";
$MESS['NIKAIMPORT_EDIT_TITLE2'] = "Редактирование A/B-теста: #NAME#";

$MESS['NIKAIMPORT_GOTO_LIST'] = "Список тестов";
$MESS['NIKAIMPORT_GOTO_ADD'] = "Новый тест";
$MESS['NIKAIMPORT_DELETE'] = "Удалить";

$MESS['NIKAIMPORT_DELETE_CONFIRM'] = "Вы действительно хотите удалить тест?";

$MESS['NIKAIMPORT_EMPTY_SITE'] = "Не задан сайт";
$MESS['NIKAIMPORT_UNKNOWN_SITE'] = "Неизвестный сайт: #VALUE#";

$MESS['NIKAIMPORT_PORTION_ERROR'] = "Недопустимое значение для поля \"трафик на тест\"";
$MESS['NIKAIMPORT_TEST_DATA_ERROR'] = "Ошибка списка тестов";

$MESS['NIKAIMPORT_PORTION_HINT'] = "Доля трафика не может быть меньше 1% или больше 100%.";
$MESS['NIKAIMPORT_EMPTY_TEST_DATA'] = "Не задано ни одного теста.";

$MESS['NIKAIMPORT_UNKNOWN_TEST_TYPE'] = "Неизвестный тип теста ##ID#: #VALUE#.";
$MESS['NIKAIMPORT_EMPTY_TEST_VALUES'] = "Не заданы значения для теста ##ID#.";
$MESS['NIKAIMPORT_EMPTY_TEST_VALUE'] = "Не задано значение для теста ##ID#.";

$MESS['NIKAIMPORT_UNKNOWN_TEST_TEMPLATE'] = "Неизвестный шаблон для теста ##ID#: #VALUE#";
$MESS['NIKAIMPORT_UNKNOWN_TEST_PAGE'] = "Несуществующая страница для теста ##ID#: #VALUE#";


$MESS['NIKAIMPORT_SAVE_ERROR'] = "Ошибка сохранения теста";

$MESS['NIKAIMPORT_TAB_NAME'] = "A/B-тест";
$MESS['NIKAIMPORT_TAB_TITLE'] = "Параметры теста";

$MESS['NIKAIMPORT_SITE_FIELD'] = "Сайт";
$MESS['NIKAIMPORT_NAME_FIELD'] = "Название";
$MESS['NIKAIMPORT_DESCR_FIELD'] = "Описание";
$MESS['NIKAIMPORT_DURATION_FIELD'] = "Длительность теста";
$MESS['NIKAIMPORT_PORTION_FIELD'] = "Трафик на тест";

$MESS['NIKAIMPORT_DURATION_OPTION_1'] = "1 день";
$MESS['NIKAIMPORT_DURATION_OPTION_3'] = "3 дня";
$MESS['NIKAIMPORT_DURATION_OPTION_5'] = "5 дней";
$MESS['NIKAIMPORT_DURATION_OPTION_7'] = "Неделя";
$MESS['NIKAIMPORT_DURATION_OPTION_14'] = "2 недели";
$MESS['NIKAIMPORT_DURATION_OPTION_30'] = "Месяц";
$MESS['NIKAIMPORT_DURATION_OPTION_0'] = "До ручной остановки теста";

$MESS['NIKAIMPORT_DURATION_OPTION_C'] = "Дней: #NUM#";
$MESS['NIKAIMPORT_DURATION_OPTION_A'] = "Авто (примерно дней: #NUM#)";
$MESS['NIKAIMPORT_DURATION_OPTION_NA'] = "н/д";

$MESS['NIKAIMPORT_TEST_DATA'] = "Тесты";

$MESS['NIKAIMPORT_TEST_TEMPLATE_TITLE'] = "Шаблон сайта";
$MESS['NIKAIMPORT_TEST_TEMPLATE_TITLE_A'] = "Текущий шаблон";
$MESS['NIKAIMPORT_TEST_TEMPLATE_TITLE_B'] = "Тестовый шаблон";

$MESS['NIKAIMPORT_TEST_PAGE_TITLE'] = "Страница";
$MESS['NIKAIMPORT_TEST_PAGE_TITLE_A'] = "Текущая страница";
$MESS['NIKAIMPORT_TEST_PAGE_TITLE_B'] = "Путь к новой странице";

$MESS['NIKAIMPORT_TEST_ADD'] = "Добавить тест";
$MESS['NIKAIMPORT_TEST_TITLE'] = "Тест<span class=\"test-num\">:</span> #TYPE#";

$MESS['NIKAIMPORT_TEST_SELECT_PAGE'] = "Выбрать файл";
$MESS['NIKAIMPORT_TEST_COPY_PAGE'] = "Скопировать страницу";
$MESS['NIKAIMPORT_TEST_EDIT_PAGE'] = "Редактировать страницу";
$MESS['NIKAIMPORT_AJAX_ERROR'] = "Ошибка при выполнении запроса";

$MESS['NIKAIMPORT_UNKNOWN_PAGE'] = "Страница не существует";

$MESS['NIKAIMPORT_TEST_CHECK'] = "Посмотреть";

$MESS['NIKAIMPORT_TEST_EDIT_WARNING'] = "<b>Внимание!</b> Изменение активного A/B-теста может привести к искажению результатов!";

$MESS['NIKAIMPORT_DURATION_AUTO_HINT'] = 'Автоматическая длительность теста &mdash; прогнозируется на основе текущей посещаемости и величины выборки, необходимой для достижения статистической мощности 80%. Тест будет завершен автоматически после получения необходимой выборки в обоих группах.';
$MESS['NIKAIMPORT_MATH_POWER_HINT'] = 'Статистическая мощность &mdash; вероятность того, что тест определит разницу между двумя вариантами, если эта разница действительно существует. Статистическая мощность увеличивается при увеличении размера выборки. Если статистическая мощность меньше 80%, то доверять результатам теста нельзя.';
