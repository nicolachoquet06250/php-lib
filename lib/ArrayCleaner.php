<?php


namespace PhpLib;


trait ArrayCleaner
{
    private function cleanArray(array &$array, bool $start = false, $both = false) {
        $array = array_reduce($array, function ($r, $c) use ($start, $both) {
            if (!is_null($c)) {
                $r['value'][] = $c;
                $r['start'] = false;
                return $r;
            }

            if (!$both) {
                if (($r['start'] && !$start) || (!$r['start'] && $start)) {
                    $r['value'][] = $c;
                }
            }

            return $r;
        }, [
            'value' => [],
            'start' => true
        ])['value'];
    }
}