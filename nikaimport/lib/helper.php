<?php

namespace Bitrix\NikaImport;

use Bitrix\Main\Type;

class Helper
{






















    /**
     * Returns active A/B-test
     *
     * @return array|null
     */
    public static function getActiveTest()
    {
        static $nikaimport;
        static $defined;

        if (!defined('SITE_ID') || !SITE_ID)
            return null;

        if (empty($defined))
        {
            $cache = new \CPHPCache();

            if ($cache->initCache(30*24*3600, 'nikaimport_active_'.SITE_ID, '/nikaimport'))
            {
                $nikaimport = $cache->getVars();
            }
            else
            {
                $nikaimport = NikaImportTable::getList(array(
                    'order' => array('SORT' => 'ASC'),
                    'filter' => array(
                        'SITE_ID'      => SITE_ID,
                        'ACTIVE'       => 'Y',
                        '<=START_DATE' => new Type\DateTime()
                    )
                ))->fetch() ?: null;

                $cache->startDataCache();
                $cache->endDataCache($nikaimport);
            }

            $defined = true;

            if (!empty($nikaimport))
            {
                if (!$nikaimport['MIN_AMOUNT'])
                {
                    $capacity = AdminHelper::getSiteCapacity($nikaimport['SITE_ID']);
                    if ($capacity['min'] > 0)
                    {
                        $result = NikaImportTable::update($nikaimport['ID'], array('MIN_AMOUNT' => $capacity['min']));
                        if ($result->isSuccess())
                        {
                            $cache->clean('nikaimport_active_'.SITE_ID, '/nikaimport');
                            $nikaimport['MIN_AMOUNT'] = $capacity['min'];
                        }
                    }
                }

                if (intval($nikaimport['DURATION']) == -1)
                {
                    if (intval($nikaimport['MIN_AMOUNT']) > 0)
                    {
                        $capacity = AdminHelper::getTestCapacity($nikaimport['ID']);
                        if ($capacity['A'] >= $nikaimport['MIN_AMOUNT'] && $capacity['B'] >= $nikaimport['MIN_AMOUNT'])
                        {
                            Helper::stopTest($nikaimport['ID'], true);
                            $nikaimport = null;
                        }
                    }
                }
                else if (intval($nikaimport['DURATION']) > 0)
                {
                    $end = clone $nikaimport['START_DATE'];
                    $end->add(intval($nikaimport['DURATION']).' days');

                    if (time() > $end->format('U'))
                    {
                        Helper::stopTest($nikaimport['ID'], true);
                        $nikaimport = null;
                    }
                }
            }
        }

        return $nikaimport;
    }

    /**
     * Returns an A/B-test context array
     *
     * @param array $nikaimport A/B-test.
     * @param string $section Section.
     * @return array
     */
    private static function context($nikaimport, $section)
    {
        return array(
            'nikaimport'  => intval($nikaimport['ID']),
            'section' => $section,
            'data'    => $nikaimport['TEST_DATA']
        );
    }

    /**
     * Returns current A/B-test context
     *
     * @return array|null
     */
    public static function getContext()
    {
        global $USER, $APPLICATION;

        static $context;

        if (!defined('SITE_ID') || !SITE_ID)
            return null;

        if (empty($context))
        {
            $activeTest = Helper::getActiveTest();

            if ($USER->canDoOperation('view_other_settings') && !empty($_SESSION['NIKAIMPORT_MODE']))
            {
                if ($_SESSION['NIKAIMPORT_MODE'] == 'reset')
                {
                    if (!empty($activeTest))
                        $context = Helper::context($activeTest, 'N');

                    unset($_SESSION['NIKAIMPORT_MODE']);
                }
                else if (preg_match('/^(\d+)\|(A|B|N)$/', $_SESSION['NIKAIMPORT_MODE'], $matches))
                {
                    if (!empty($activeTest) && $activeTest['ID'] == intval($matches[1]))
                    {
                        $context = Helper::context($activeTest, $matches[2]);

                        unset($_SESSION['NIKAIMPORT_MODE']);
                    }
                    else
                    {
                        $nikaimport = NikaImportTable::getList(array(
                            'filter' => array('=ID' => intval($matches[1]), 'ENABLED' => 'Y')
                        ))->fetch();

                        if (!empty($nikaimport) && $nikaimport['SITE_ID'] == SITE_ID)
                            $context = Helper::context($nikaimport, $matches[2]);
                    }
                }
            }

            if (empty($context) && !empty($activeTest))
            {
                $nikaimport = $activeTest;

                if ($cookie = $APPLICATION->get_cookie('NIKAIMPORT_'.SITE_ID))
                {
                    if (preg_match('/^'.intval($nikaimport['ID']).'\|(A|B|N)$/i', $cookie, $matches))
                        $section = $matches[1];
                }

                if (empty($section))
                {
                    $dice = mt_rand(1, 100);

                    if ($dice <= intval($nikaimport['PORTION'])/2)
                        $section = 'A';
                    else if ($dice <= intval($nikaimport['PORTION']))
                        $section = 'B';
                    else
                        $section = 'N';
                }

                $context = Helper::context($nikaimport, $section);
            }

            if (empty($activeTest))
                $APPLICATION->set_cookie('NIKAIMPORT_'.SITE_ID, null);
            else if ($activeTest['ID'] == $context['nikaimport'])
                $APPLICATION->set_cookie('NIKAIMPORT_'.SITE_ID, intval($context['nikaimport']).'|'.$context['section']);
        }

        return $context;
    }

    /**
     * Returns alternative test value for current A/B-test context
     *
     * @param string $type Test type.
     * @param string $value Test original value.
     * @return string|null
     */
    public static function getAlternative($type, $value)
    {
        $result = null;

        if ($context = Helper::getContext())
        {
            foreach ($context['data']['list'] as $item)
            {
                if ($item['type'] == $type && $item['old_value'] == $value)
                {
                    $result = $item['new_value'];
                    break;
                }
            }
        }

        return $result;
    }

    /**
     * Starts an A/B-test
     *
     * @param int $id A/B-test ID.
     * @return bool
     */
    public static function startTest($id)
    {
        global $USER;

        if ($nikaimport = NikaImportTable::getById($id)->fetch())
        {
            $fields = array(
                'START_DATE' => new Type\DateTime(),
                'STOP_DATE'  => null,
                'ACTIVE'     => 'Y',
                'USER_ID'    => $USER->getID()
            );

            if (!$nikaimport['MIN_AMOUNT'])
            {
                $capacity = AdminHelper::getSiteCapacity($nikaimport['SITE_ID']);
                if ($capacity['min'] > 0)
                    $fields['MIN_AMOUNT'] = $capacity['min'];
            }

            $result = NikaImportTable::update(intval($id), $fields);

            if ($result->isSuccess())
            {
                Helper::clearCache($nikaimport['SITE_ID']);

                return true;
            }
        }

        return false;
    }

    /**
     * Stops an A/B-test
     *
     * @param int $id A/B-test ID.
     * @param bool $auto Auto-stop flag.
     * @return bool
     */
    public static function stopTest($id, $auto = false)
    {
        global $USER;

        if ($nikaimport = NikaImportTable::getById($id)->fetch())
        {
            $fields = array(
                'STOP_DATE' => new Type\DateTime(),
                'ACTIVE'    => 'N',
            );

            if (!$auto)
                $fields['USER_ID'] = $USER->getID();

            $result = NikaImportTable::update(intval($id), $fields);

            if ($result->isSuccess())
            {
                Helper::clearCache($nikaimport['SITE_ID']);

                return true;
            }
        }

        return false;
    }

    /**
     * Deletes an A/B-test
     *
     * @param int $id A/B-test ID.
     * @return bool
     */
    public static function deleteTest($id)
    {
        if ($nikaimport = NikaImportTable::getById($id)->fetch())
        {
            $result = NikaImportTable::delete(intval($id));

            if ($result->isSuccess())
            {
                if ($nikaimport['ACTIVE'] == 'Y')
                    Helper::clearCache($nikaimport['SITE_ID']);

                return true;
            }
        }

        return false;
    }

    /**
     * Cleans active A/B-test cache
     *
     * @param int $siteId Site ID.
     * @return void
     */
    public static function clearCache($siteId)
    {
        $cache = new \CPHPCache();
        $cache->clean('nikaimport_active_'.$siteId, '/nikaimport');
    }

}
