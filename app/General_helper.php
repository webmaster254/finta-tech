<?php

if (!function_exists('determine_period_interest_rate')) {

    /**
     * @param $default_interest_rate
     * @param $repayment_frequency_type
     * @param $interest_rate_type
     * @param int $days_in_year
     * @param int $days_in_month
     * @param int $weeks_in_year
     * @param int $weeks_in_month
     * @return float
     */
    function determine_period_interest_rate($default_interest_rate, $repayment_frequency_type, $interest_rate_type, $repayment_frequency = 1, $days_in_year = 365, $days_in_month = 30, $weeks_in_year = 52, $weeks_in_month = 4)
    {
        $interest_rate = $default_interest_rate;
        if ($repayment_frequency_type == "days") {
            if ($interest_rate_type == 'year') {
                $interest_rate = $interest_rate / $days_in_year;
            }
            if ($interest_rate_type == 'month') {
                $interest_rate = $interest_rate / $days_in_month;
            }
            if ($interest_rate_type == 'week') {
                $interest_rate = $interest_rate / 7;
            }
        }
        if ($repayment_frequency_type == "weeks") {
            if ($interest_rate_type == 'year') {
                $interest_rate = $interest_rate / $days_in_year;
            }
            if ($interest_rate_type == 'month') {
                $interest_rate = $interest_rate / $weeks_in_month;
            }
            if ($interest_rate_type == 'day') {
                $interest_rate = $interest_rate * 7;
            }
        }
        if ($repayment_frequency_type == "months") {
            if ($interest_rate_type == 'year') {
                $interest_rate = $interest_rate / 12;
            }
            if ($interest_rate_type == 'week') {
                $interest_rate = $interest_rate * $weeks_in_month;
            }
            if ($interest_rate_type == 'day') {
                $interest_rate = $interest_rate * $days_in_month;
            }
        }
        if ($repayment_frequency_type == "years") {
            if ($interest_rate_type == 'month') {
                $interest_rate = $interest_rate * 12;
            }
            if ($interest_rate_type == 'week') {
                $interest_rate = $interest_rate * $weeks_in_year;
            }
            if ($interest_rate_type == 'day') {
                $interest_rate = $interest_rate * $days_in_year;
            }
        }
        return $interest_rate * $repayment_frequency / 100;
    }
}
if (!function_exists('determine_amortized_payment')) {

    /**
     * @param $default_interest_rate
     * @param $repayment_frequency_type
     * @param $interest_rate_type
     * @param int $days_in_year
     * @param int $days_in_month
     * @param int $weeks_in_year
     * @param int $weeks_in_month
     * @return float
     */
    function determine_amortized_payment($interest_rate, $balance, $period)
    {

        return ($interest_rate * $balance * pow((1 + $interest_rate), $period)) / (pow((1 + $interest_rate),
                    $period) - 1);
    }
}
if (!function_exists('compare_multi_dimensional_array')) {
    function compare_multi_dimensional_array($array1, $array2)
    {
        $result = array();
        foreach ($array1 as $key => $value) {
            if (!is_array($array2) || !array_key_exists($key, $array2)) {
                $result[$key] = $value;
                continue;
            }
            if (is_array($value)) {
                $recursiveArrayDiff = compare_multi_dimensional_array($value, $array2[$key]);
                if (count($recursiveArrayDiff)) {
                    $result[$key] = $recursiveArrayDiff;
                }
                continue;
            }
            if ($value != $array2[$key]) {
                $result[$key] = $value;
            }
        }
        return $result;
    }
}
