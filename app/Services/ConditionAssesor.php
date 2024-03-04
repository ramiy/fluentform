<?php

namespace FluentForm\App\Services;

use FluentForm\App\Helpers\Str;
use FluentForm\Framework\Helpers\ArrayHelper as Arr;

class ConditionAssesor
{
    public static function evaluate(&$field, &$inputs)
    {
        $status = Arr::get($field, 'conditionals.status');


        $conditionals =  $status ? Arr::get($field, 'conditionals.conditions') : false;


        $hasConditionMet = true;

        if ($conditionals) {
            $toMatch = Arr::get($field, 'conditionals.type');


            foreach ($conditionals as $conditional) {

                if (!Arr::get($conditional, 'field')) {
                    continue;
                }

                $hasConditionMet = static::assess($conditional, $inputs);

                if ($hasConditionMet && $toMatch == 'any') {
                    return true;
                }

                if ($toMatch === 'all' && !$hasConditionMet) {
                    return false;
                }
            }
        }

        return $hasConditionMet;
    }

    public static function assess(&$conditional, &$inputs)
    {
        if ($conditional['field']) {
            $accessor = rtrim(str_replace(['[', ']', '*'], ['.'], $conditional['field']), '.');

            if (!Arr::has($inputs, $accessor)) {
                return false;
            }
            $inputValue = Arr::get($inputs, $accessor);

            if ($numericFormatter = Arr::get($conditional, 'numeric_formatter')) {
                $inputValue = self::numberFormatting($numericFormatter, $inputValue);
            }

            switch ($conditional['operator']) {
                case '=':
                    if(is_array($inputValue)) {
                       return in_array($conditional['value'], $inputValue);
                    }
                    return $inputValue == $conditional['value'];
                    break;
                case '!=':
                    if(is_array($inputValue)) {
                        return !in_array($conditional['value'], $inputValue);
                    }
                    return $inputValue != $conditional['value'];
                    break;
                case '>':
                    return $inputValue > $conditional['value'];
                    break;
                case '<':
                    return $inputValue < $conditional['value'];
                    break;
                case '>=':
                    return $inputValue >= $conditional['value'];
                    break;
                case '<=':
                    return $inputValue <= $conditional['value'];
                    break;
                case 'startsWith':
                    return Str::startsWith($inputValue, $conditional['value']);
                    break;
                case 'endsWith':
                    return Str::endsWith($inputValue, $conditional['value']);
                    break;
                case 'contains':
                    return Str::contains($inputValue, $conditional['value']);
                    break;
                case 'doNotContains':
                    return !Str::contains($inputValue, $conditional['value']);
                    break;
                case 'length_equal':
                    if(is_array($inputValue)) {
                        return count($inputValue) == $conditional['value'];
                    }
                    $inputValue = strval($inputValue);
                    return strlen($inputValue) == $conditional['value'];
                    break;
                case 'length_less_than':
                    if(is_array($inputValue)) {
                        return count($inputValue) < $conditional['value'];
                    }
                    $inputValue = strval($inputValue);
                    return strlen($inputValue) < $conditional['value'];
                    break;
                case 'length_greater_than':
                    if(is_array($inputValue)) {
                        return count($inputValue) > $conditional['value'];
                    }
                    $inputValue = strval($inputValue);
                    return strlen($inputValue) > $conditional['value'];
                    break;
                case 'test_regex':
                    if(is_array($inputValue)) {
                        $inputValue = implode(' ', $inputValue);
                    }
                    $result = preg_match('/'.$conditional['value'].'/', $inputValue);
                    return !!$result;
                    break;
            }
        }

        return false;
    }

    /**
     * @param $numericFormatter
     * @param $inputValue
     *
     * @return false|float|int|mixed
     */
    public static function numberFormatting($numericFormatter, $inputValue)
    {
        if ($numericFormatter == 'comma_dot_style') {
            $parsedNumber = str_replace(',', '', $inputValue);
            $locale = 'en_US';
            $decimalSeparator = '.';
            $groupingSeparator = ',';
            $fractionDigits = 2;
        } elseif ($numericFormatter == 'dot_comma_style_zero') {
            $parsedNumber = str_replace(',', '', $inputValue);
            $locale = 'en_US';
            $decimalSeparator = '.';
            $groupingSeparator = ',';
            $fractionDigits = 0;
        } elseif ($numericFormatter == 'dot_comma_style') {
            $parsedNumber = str_replace('.', '', $inputValue);
            $locale = 'de_DE';
            $decimalSeparator = ',';
            $groupingSeparator = '.';
            $fractionDigits = 2;
        } elseif ($numericFormatter == 'comma_dot_style_zero') {
            $parsedNumber = str_replace('.', '', $inputValue);
            $locale = 'de_DE';
            $decimalSeparator = ',';
            $groupingSeparator = '.';
            $fractionDigits = 0;
        }

        $formatter = new \NumberFormatter($locale, \NumberFormatter::DECIMAL);
        $formatter->setAttribute(\NumberFormatter::DECIMAL_SEPARATOR_SYMBOL, $decimalSeparator);
        $formatter->setAttribute(\NumberFormatter::GROUPING_SEPARATOR_SYMBOL, $groupingSeparator);
        $formatter->setAttribute(\NumberFormatter::FRACTION_DIGITS, $fractionDigits);

        return $formatter->parse($parsedNumber);
    }
}
