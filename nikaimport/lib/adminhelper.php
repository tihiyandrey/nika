<?php

namespace Bitrix\NikaImport;

class AdminHelper
{
    /**
     * Get parametrs array API
     *
     * @return array
     */
    public static function getParamsArray()
    {
        return array(
            'API_URL',
            'API_LOGIN',
            'API_PASSWORD',
            'INTERVAL',
            'CRC',
            'IBLOCK_PICTURE_SETTINGS',
            'GENERATE_PREVIEW',
            'PREVIEW_WIDTH',
            'PREVIEW_HEIGHT',
            'DETAIL_RESIZE',
            'DETAIL_WIDTH',
            'DETAIL_HEIGHT',
            'ADD_IBLOCK',
            'IBLOCK_ID_CATALOG',
            'IBLOCK_ID_OFFERS',
            'SECTIONS_AR',
            'EXTRA'
        );
    }

    /**
     * Save|Update parametrs
     *
     * @param  array $request
     * @return bool
     */
    public static function saveParamsNikaImport($request = array())
    {
        $sections_id = array();
        if (isset($request['SECTIONS'])) {
            if (count($request['SECTIONS'])) {
                foreach ($request['SECTIONS'] as $key => $value) {
                    if ($value == "Y") {
                        $sections_id[] = $key;
                    }
                }
                unset($key, $value);
            }
            if (count($sections_id) > 0 && isset($request['SECTIONS_AR'])) {
                if (@unserialize($request['SECTIONS_AR']) === false)
                {
                    $sections = $request['SECTIONS_AR'];
                }
                else
                {
                    $sections = unserialize($request['SECTIONS_AR']);
                }
                $sections = unserialize($request['SECTIONS_AR']);
                foreach ($sections as $k => $v)
                {
                    $sections[$k]['ACTIVE'] = ($request['SECTIONS'][$k] == 'Y') ? true : false;
                    if (isset($v['CHILD']))
                    {
                        foreach ($v['CHILD'] as $k_a => $v_a)
                        {
                            $sections[$k]['CHILD'][$k_a]['ACTIVE'] = ($request['SECTIONS'][$k_a] == 'Y') ? true : false;
                            if (isset($v_a['CHILD']))
                            {
                                foreach ($v_a['CHILD'] as $k_b => $v_b)
                                {
                                    $sections[$k]['CHILD'][$k_a]['CHILD'][$k_b]['ACTIVE'] = ($request['SECTIONS'][$k_b] == 'Y') ? true : false;
                                }
                            }
                        }
                    }
                }
                $request['SECTIONS_AR'] = serialize($sections);
            }
            else
            {
                unset($request['SECTIONS_AR']);
            }
        }
        $dataGet = http_build_query(
            array(
                'mode'     => 'auth',
                'settings' => 'upload',
                'sections' => serialize($sections_id),
                'extra'    => $request['EXTRA'],
                'login'    => isset($request['API_LOGIN']) ? $request['API_LOGIN'] : false,
                'password' => isset($request['API_PASSWORD']) ? $request['API_PASSWORD'] : false,
            )
        );
        file_get_contents($request['API_URL'] . '?' . $dataGet);
        $arDefault = self::getParamsArray();
        $arParams  = array();
        foreach ($arDefault as $key)
        {
    		$value     = isset($request[$key]) ? $request[$key] : '';
            $paramsRec = NikaImportTable::getList(
                array(
                    'select' => array(
                        'ID'
                    ),
                    'filter' => array(
                        'NAME' => $key
                    )
                )
            );
            if ($params = $paramsRec->fetch())
            {
                NikaImportTable::update(
                    $params['ID'],
                    array(
                        'VALUE' => $value
                    )
                );
            }
            else
            {
                NikaImportTable::add(
                    array(
                        'NAME'  => $key,
                        'VALUE' => $value
                    )
                );
            }
        }
        return true;
    }

    /**
     * Get parametrs
     *
     * @param  array $request
     * @return array
     */
    public static function getParamsNikaImport()
    {
        $arResult = array();
        $arDefault = self::getParamsArray();
        foreach ($arDefault as $name)
        {
            $paramsRec = NikaImportTable::getList(
                array(
                    'filter' => array(
                        'NAME' => $name
                    )
                )
            );
            if ($param = $paramsRec->fetch())
            {
                $arResult[$name] = $param['VALUE'];
            }
            else
            {
                $arResult[$name] = '';
            }
        }
        return $arResult;
    }

    /**
     * Get HTML sections
     *
     * @return string html
     */
    public static function getSectionsHtml($array)
    {
        $html = '<ul>';
        if (is_array($array) && count($array))
        {
            foreach ($array as $key => $value)
            {
                $html .= '<li class=\'js-li-sections\'>';
                    $html .= '<input type=\'hidden\' name=\'SECTIONS['.$value['ID'].']\' id=\'ADD_IBLOCK_'.$value['ID'].'_N\' value=\'N\'>';
                    $html .= '<input type=\'checkbox\' name=\'SECTIONS['.$value['ID'].']\' id=\'ADD_IBLOCK_'.$value['ID'].'\' value=\'Y\' class=\'adm-designed-checkbox\' '.(empty($value['ACTIVE'])?'':' checked').'>';
                    $html .= '<label class=\'adm-designed-checkbox-label\' for=\'ADD_IBLOCK_'.$value['ID'].'\' title=\''.$value['NAME'].'\'></label>';
                    $html .= ' ' . $value['NAME'];
                    if (isset($value['CHILD']) && count($value['CHILD'])) {
                        $html .= self::getSectionsHtml($value['CHILD']);
                    }
                $html .= '</li>';
            }
        }
        $html .= '</ul>';
        return $html;
    }
}