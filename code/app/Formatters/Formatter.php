<?php

/*
    I Formatters permettono di serializzare diverse tipologie di oggetti in
    semplici array, selettivamente accedendo agli attributi desiderati.
    Utile per formattare poi documenti esportati (PDF e CSV) o tabelle HTML.
*/

namespace App\Formatters;

use Log;

abstract class Formatter
{
    public static function getHeaders($fields)
    {
        $columns = static::formattableColumns();
        $headers = [];

        foreach($fields as $field) {
            $headers[] = $columns[$field]->name;
        }

        return $headers;
    }

    public static function format($obj, $fields, $context = null)
    {
        $columns = static::formattableColumns();
        $ret = [];

        foreach($fields as $f) {
            try {
                $format = $columns[$f]->format ?? null;

                if ($format) {
                    $ret[] = call_user_func($format, $obj, $context);
                }
                else {
                    $ret[] = accessAttr($obj, $f);
                }
            }
            catch(\Exception $e) {
                Log::error('Formattazione: impossibile accedere al campo ' . $f . ' di ' . $obj->id . ': ' . $e->getMessage());
                $ret[] = '';
            }
        }

        return $ret;
    }

    public static function formatArray($objs, $fields, $context = null)
    {
        $ret = [];

        foreach($objs as $obj) {
            $rows = self::format($obj, $fields, $context);
            $ret = array_merge($ret, [$rows]);
        }

        return $ret;
    }

    public static abstract function formattableColumns($type = null);
}
