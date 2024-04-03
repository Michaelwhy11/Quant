<?php
$old = ini_set('memory_limit', '16000M');
//This code is a simply demo for calculating some of the trading signals could be used in all-in-one screeners. 
include_once('ftpclass.php');
include_once "db_connection_class.php";
include_once "mail_server.php";
include_once ("../db_functions.php");
set_time_limit(0);
$date = date('Y-m-d',time());
$weekday = date('w',time());

//First, we get the stockid from stock_list
$start_time = time();
$sql_getid = "SELECT stockid FROM stock_list WHERE price > 0.001 and delist_date = '0000-00-00';";
$result_getid = mysql_query($sql_getid, DBConnection::getInstance() -> getDB('stock'));
$nrow_getid = mysql_num_rows($result_getid);
echo "\nThe total num of stocks in stock_list which have effective price is $nrow_getid.\n";
$stockid = array();
if ($nrow_getid !=0) 
{
    for ($r = 0; $r < $nrow_getid; $r++) 
    {
        $row_getid = mysql_fetch_assoc($result_getid);
        $stockid[substr($row_getid['stockid'],0,4)][] = $row_getid['stockid'];
    }
}
// $price_array = array();
// foreach($stockid as $file => $idarray)
// {   
//     $sql_getprice = "SELECT sec, date, high, low, close FROM price_all_".$file." WHERE date > '2022-10-01' ORDER BY sec, date desc;";
//     $result_getprice = mysql_query($sql_getprice, DBConnection::getInstance() -> getDB('price'));
//     $nrow_getprice = mysql_num_rows($result_getprice);
//     $price_array_small = array();
//     if ($nrow_getprice != 0)
//     {
//         for ($pr = 0; $pr < $nrow_getprice; $pr++)
//         {
//             $row_getprice = mysql_fetch_assoc($result_getprice);
//             $price_array_small[$row_getprice['sec']][$row_getprice['date']] = $row_getprice;
//         }
    
//         foreach($idarray as $sid)
//         {
//             $sec = substr($sid, 4, 2);
//             if(isset($price_array_small[$sec]))
//             {
//                 $price_array[$sid] = $price_array_small[$sec];
//             }
//         }
//     }
//     echo "$file has finished \r";
// }
// foreach($price_array as $sid => $p_array)
// {   
//     $price = array();
//     foreach($p_array as $no => $p_detial)
//     {
//         $price[$p_detail['date']] = $p_detail['close'];
//     }
// }
//If go with the above code, it will exceed the memory limit. Try to unset after finshing a security
$cross_array_sma = array();
$cross_array_macd = array();
$indicator_HL = array();
$signal_meanreversion = array();
$cross_array_mfi = array();
$eom_indicator_signal = array();
$cc = 0;//This is for controlling the numbers of stocks runnning in this code only, could be deleted.
foreach($stockid as $file => $idarray)
{
    foreach($idarray as $sid)
    {   
        unset($price_array_small);
        $sec = substr($sid, 4, 2);
        $sec = '3J';
        $sql_getprice = "SELECT sec, date, high, low, close, volume FROM price_all_".$file." WHERE date > '2022-10-01' and sec = '$sec' ORDER BY sec, date ASC;";// As early as 2022-10-01, it is limilted by memory limit.
        $result_getprice = mysql_query($sql_getprice, DBConnection::getInstance() -> getDB('price'));
        $nrow_getprice = mysql_num_rows($result_getprice);
        $price_array_small = array();
        if ($nrow_getprice != 0)
        {
            for ($pr = 0; $pr < $nrow_getprice; $pr++)
            {
                $row_getprice = mysql_fetch_assoc($result_getprice);
                $price_array_small[$row_getprice['date']] = $row_getprice['close'];
            }
            ksort($price_array_small);//Put the earlier date at the front to get moving average
            //SMA Cold Corss and Dead Cross
            $sma_50 = Get_SMA($price_array_small, 50);
            $sma_20 = Get_SMA($price_array_small, 20);
            if(isset($sma_50) && isset($sma_20))
            {
                $is_cross = Get_Cross($sma_50, $sma_20);
                if(isset($is_cross))$cross_array_sma[$sid] = $is_cross;
            }
            // //MACD
            // $ema_26 = Get_EMA($price_array_small, 26);
            // $ema_12 = Get_EMA($price_array_small, 12);
            // if(isset($ema_26) && isset($ema_12))
            // {   
            //     $macd_array = MACD($ema_26, $ema_12, 9);
            //     $dea = $macd_array[0];
            //     $diff = $macd_array[1];
            //     $macd = $macd_array[2];
            //     $is_cross = Get_Cross($dea, $diff);
            //     if(isset($is_cross))$cross_array_macd[$sid] = $is_cross;
            // }
            // print_r($macd);
            // //Higherhigh, Lowerlow
            $total_array = HighLow($price_array_small);
            $indicator = HandL($total_array);
            foreach($price_array_small as $date_p => $price_p)
            {
                $signal_hl[$date_p] = 0;
                if(isset($indicator['H_date']))
                {
                    foreach($indicator['H_date'] as $date_harray)
                    {
                        if($date_p > min($date_harray) && $date_p < max($date_harray))
                        {   
                            $signal_hl[$date_p] = 1;
                        }
                    }
                }
                if(isset($indicator['L_date']))
                {
                    foreach($indicator['L_date'] as $date_larray)
                    {
                        if($date_p > min($date_larray) && $date_p < max($date_larray))
                        {   
                            $signal_hl[$date_p] = -1;
                        }
                    }
                }
            }
            print_r($signal_hl);exit;
            // if(isset($indicator))$indicator_HL[$sid] = $indicator;            
            // //MeanReversion
            // $signal = MeanReversion($price_array_small);
            // if(isset($signal))$signal_meanreversion[$sid] = $signal;
        }
        
        // //MFI, we use MFI combined with a filter of 0.5 to figure out long and short signal
        // $mfi_array = MFI($sql_getprice);
        // if($mfi_array)
        // {   
        //     ksort($mfi_array);
        //     $sma_30 = Get_SMA($mfi_array, 30);
        //     if($sma_30)
        //     {   
        //         $date_1 = array_keys($sma_30);//Only need the longer one, we do not need the dates not included in long-period ma.
        //         $cross = array();
        //         for($i = 0; $i < count($date_1); $i++)
        //         {   
        //             if(isset($mfi_array[$date_1[$i]]) && isset($sma_30[$date_1[$i]]))
        //             {
        //                 if($mfi_array[$date_1[$i]] >= $sma_30[$date_1[$i]])
        //                 {
        //                     $cross[$i] = 1;//'Indicator' introduced before.
        //                 }
        //                 else
        //                 {
        //                     $cross[$i] = -1;
        //                 }
        //             }
        //         }
        //         $yest = array_slice($cross, 0 , -1);
        //         $tod = array_slice($cross, 1);
        //         for($ii = 0; $ii < count($yest); $ii++)
        //         {  
        //             if($yest[$ii] + $tod[$ii] == 0)//A cross-over happens
        //             {
        //                 if($tod[$ii] == 1 && $mfi_array[$date_1[$ii]] < 0.5)//Filter with 0.5
        //                 {
        //                     $is_cross[$date_1[$ii+1]] = 1;
        //                 }
        //                 elseif($tod[$ii] == -1 && $mfi_array[$date_1[$ii]] > 0.5)
        //                 {
        //                     $is_cross[$date_1[$ii+1]] = -1;
        //                 }
        //                 else
        //                 {
        //                     $is_cross[$date_1[$ii+1]] = 0;
        //                 }
        //             }
        //             else//Other situation, if the previous cross is gold cross, hold, if the previous is dead cross, hold no position.
        //             {
        //                 $is_cross[$date_1[$ii+1]] = 0;
        //             }
        //         }
        //         if(isset($is_cross))$cross_array_mfi[$sid] = $is_cross;
        //     }
        // }
        // $returns = backtesting($is_cross, $price_array_small);
        // echo $returns;exit;
        // //EOM combined with SMA Gold Cross
        // $eom_indicator = EOM($sql_getprice);
        // if(isset($eom_indicator))
        // {
        //     ksort($eom_indicator);
        //     $sma_50 = Get_SMA($price_array_small, 50);
        //     $sma_20 = Get_SMA($price_array_small, 20);
        //     if(isset($sma_50) && isset($sma_20))
        //     {
        //         $is_cross = Get_Cross($sma_50, $sma_20);
        //         $total_date_inter = array_intersect(array_keys($eom_indicator), array_keys($is_cross));
        //         $total_date = array_values($total_date_inter);
        //         for($d_num = 1; $d_num < count($total_date); $d_num++)
        //         {   
        //             $date_inter = $total_date[$d_num];
        //             if($is_cross[$date_inter] == 1 && $eom_indicator[$total_date[$d_num - 1]] >= 0)
        //             {
        //                 $eom_signal[$date_inter] = 1;
        //             }
        //             elseif($is_cross[$date_inter] == -1 && $eom_indicator[$total_date[$d_num - 1]] < 0)
        //             {
        //                 $eom_signal[$date_inter] = -1;
        //             }
        //             else
        //             {
        //                 $eom_signal[$date_inter] = 0;
        //             }
        //         }
        //         if(isset($eom_signal))$eom_indicator_signal[$sid] = $eom_signal;
        //     }
        // }
        // $returns = backtesting($eom_signal, $price_array_small);
        // echo $returns;exit;
        
    #echo "\r $sid calculation is finished. | total $cc \r";
    }
    $cc++;
    if($cc > 1)break;
}
echo "\rArray is\r";
print_r($eom_indicator_signal);
$end_time = time();
$time_diff = $end_time - $start_time;
echo "We use $time_diff seconds for the whole part.\n";
/*
//Below is a test code using S&P 500.
$start_time = time();
$sql_spy = "SELECT date, value FROM gurufocu_data.economic_indicator_details WHERE id = 63;";
$result_spy = mysql_query($sql_spy, DBConnection::getInstance() -> getDB('data'));
$nrow_spy = mysql_num_rows($result_spy);
echo $nrow_spy;
$spy = array();
if ($nrow_spy !=0) 
{
    for ($r = 0; $r < $nrow_spy; $r++) 
    {
        $row_spy = mysql_fetch_assoc($result_spy);
        $spy[$row_spy['date']] = $row_spy['value'];
    }
}
//Higher-high, higher-low indicates a uptrend, lower-low, lower-high indicates a downtrend.
$total_array = HighLow($spy);
$indicator = HandL($total_array);
print_r($indicator);
//Mean reversion, prices always goes to their means.
$signal = MeanReversion($spy);
print_r($signal);
$end_time = time();
$time_diff = $end_time - $start_time;
echo "We use $time_diff \nseconds. for part 2";
*/
//This function is for getting Simple Moving Average with a period of 'period'
//Should get an array like array('date' => moving average)
function Get_SMA($price_array_small, $period)
{
    $dates = array_keys($price_array_small);
    $prices = array_values($price_array_small);
    $priceCount = count($prices);
    if ($priceCount < $period) 
    {
        return false; // Not enough data to calculate SMA.
    }
    $sma = array();
    for ($i = $period - 1; $i < $priceCount; $i++) 
    {
        // Calculate the SMA for the current period.
        $sum = 0;
        for ($j = 0; $j < $period; $j++) 
        {
            $sum += $prices[$i - $j];
        }
        $average = $sum / $period;
        $sma[$dates[$i]] = $average;
    }
    return $sma;
}
//This function is for getting Exponential Moving Average with a period of 'period'
//Should get an array like array('date' => moving average)
function MACD($ema_26, $ema_12, $period = 9)
{
    $dates = array_keys($ema_26);
    $counts = count($ema_26);
    $diff = array();
    $dea = array();
    $macd = array();
    $sum = 0;
    for ($j = 0; $j < $period; $j++) 
    {
        $diff_prev = $ema_12[$dates[$j]] - $ema_26[$dates[$j]];
        $sum += $diff_prev;
    }
    $average = $sum / $period;
    $diff[$dates[$period - 1]] = $diff_prev;
    $dea[$dates[$period - 1]] = $average;
    $macd[$dates[$period - 1]] = 2 * ($diff[$dates[$period - 1]] - $dea[$dates[$period - 1]]);
    $N = 2 / ($period + 1);
    for($i = $period; $i < $counts; $i++)
    {
        $d = $dates[$i];
        $diff[$d] = $ema_12[$d] - $ema_26[$d];
        $dea[$d] = $N * $diff[$d] + (1 - $N) * $dea[$dates[$i - 1]];
        $macd[$d] = 2 * ($diff[$d] - $dea[$d]);
    }
    $macd_array = array($dea, $diff, $macd);
    return $macd_array;
}
//This function is using as find the signal with two moving average lines. If short(diff) goes above long(dea),
//the sum of today's indicator and yesterday's should be zero. Here the indicator means if short(diff) > long(dea), 1,otherwise, 0. SO if the sum is zero, a cross-over happens and we use today's indicator to determine whether it's a gold cross or dead cross.
function Get_Cross($ma_l, $ma_s)
{
    $date_1 = array_keys($ma_l);//Only need the longer one, we do not need the dates not included in long-period ma.
    $cross = array();
    for($i = 0; $i < count($date_1); $i++)
    {   
        if(isset($ma_s[$date_1[$i]]) && isset($ma_l[$date_1[$i]]))
        {
            if($ma_s[$date_1[$i]] >= $ma_l[$date_1[$i]])
            {
                $cross[$i] = 1;//'Indicator' introduced before.
            }
            else
            {
                $cross[$i] = -1;
            }
        }
    }
    $yest = array_slice($cross, 0 , -1);
    $tod = array_slice($cross, 1);
    for($ii = 0; $ii < count($yest); $ii++)
    {   
        if($yest[$ii] + $tod[$ii] == 0)//A cross-over happens
        {
            if($tod[$ii] == 1)
            {
                $is_cross[$date_1[$ii+1]] = 1;
            }
            else
            {
                $is_cross[$date_1[$ii+1]] = -1;
            }
        }
        else//Other situation, if the previous cross is gold cross, hold, if the previous is dead cross, hold no position.
        {
            $is_cross[$date_1[$ii+1]] = 0;
        }
    }
    return $is_cross;
}
//This function is using as find the signal with two moving average lines. If short(diff) goes above long(dea),
//the sum of today's indicator and yesterday's should be zero. Here the indicator means if short(diff) > long(dea), 1,otherwise, 0. SO if the sum is zero, a cross-over happens and we use today's indicator to determine whether it's a gold cross or dead cross.
function Get_Cross($ma_l, $ma_s)
{
    $date_1 = array_keys($ma_l);//Only need the longer one, we do not need the dates not included in long-period ma.
    $cross = array();
    for($i = 0; $i < count($date_1); $i++)
    {   
        if(isset($ma_s[$date_1[$i]]) && isset($ma_l[$date_1[$i]]))
        {
            if($ma_s[$date_1[$i]] >= $ma_l[$date_1[$i]])
            {
                $cross[$i] = 1;//'Indicator' introduced before.
            }
            else
            {
                $cross[$i] = -1;
            }
        }
    }
    $yest = array_slice($cross, 0 , -1);
    $tod = array_slice($cross, 1);
    for($ii = 0; $ii < count($yest); $ii++)
    {   
        if($yest[$ii] + $tod[$ii] == 0)//A cross-over happens
        {
            if($tod[$ii] == 1)
            {
                $is_cross[$date_1[$ii+1]] = 1;
            }
            else
            {
                $is_cross[$date_1[$ii+1]] = -1;
            }
        }
        else//Other situation, if the previous cross is gold cross, hold, if the previous is dead cross, hold no position.
        {
            $is_cross[$date_1[$ii+1]] = 0;
        }
    }
    return $is_cross;
}
//HH, HL, LL, LH.
//Find the local high and low first, to prevent getting a fake peak, or, nadir, we set the range at 11.
//Look the front 5 and behind 5, find the max and min
function HighLow($price_array_small)
{
    $dates = array_keys($price_array_small);
    $prices = array_values($price_array_small);
    $priceCount = count($prices);
    if($priceCount < 100)return false;
    $highs = array();
    $lows = array();
    $num = 5;
    $window = 11;
    while($num < $priceCount - 5 && ($num + $window) < $priceCount)
    {
        $price_slice = array_slice($prices, $num - 5, 11, true);
        $pos_h = array_search(max($price_slice), $price_slice);
        $pos_l = array_search(min($price_slice), $price_slice);
        $highs[$dates[$pos_h]] = max($price_slice);
        $lows[$dates[$pos_l]] = min($price_slice);
        $num += $window;
    }
    $total_array = array($highs, $lows);
    return $total_array;
}
//This function find the HH, HL, LL, LH. Only counts if get 5 consecutive ones.
function HandL($total_array)
{
    $highs = array_values($total_array[0]);
    $lows = array_values($total_array[1]);
    $high_date = array_keys($total_array[0]);
    $low_date = array_keys($total_array[1]);
    $higherhs = array();
    $lowerhs = array();
    $higherls = array();
    $lowerls = array();
    foreach($highs as $num => $high)
    {
        if($num == 0)
        {
            $higherh[$high_date[$num]] = $high;
            $lowerh[$high_date[$num]] = $high;
            continue;
        } 
        if($highs[$num] < $highs[$num-1])
        {
            unset($higherh);
        }
        else
        {
            unset($lowerh);
        }
        $higherh[$high_date[$num]] = $high;
        $lowerh[$high_date[$num]] = $high;
        if(count($higherh) == 5)
        {
            $higherhs[] = $higherh;
            unset($higherh);
        }
        if(count($lowerh) == 5)
        {
            $lowerhs[] = $lowerh;
            unset($lowerh);
        }
    }
    foreach($lows as $num => $low)
    {
        if($num == 0)
        {
            $higherl[$low_date[$num]] = $low;
            $lowerl[$low_date[$num]] = $low;
            continue;
        } 
        if($lows[$num] < $lows[$num-1])
        {
            unset($higherl);
        }
        else
        {
            unset($lowerl);
        }
        $higherl[$low_date[$num]] = $low;
        $lowerl[$low_date[$num]] = $low;
        if(count($higherl) == 5)
        {
            $higherls[] = $higherl;
            unset($higherl);
        }
        if(count($lowerl) == 5)
        {
            $lowerls[] = $lowerl;
            unset($lowerl);
        }
    }
    $h_date = array();
    if(isset($higherhs))
    {
        foreach($higherhs as $hh)
        {
            $h_date[] = array(max(array_keys($hh)), min(array_keys($hh)));
        }
    }
    if(isset($higherls))
    {
        foreach($higherls as $hl)
        {
            $h_date[] = array(max(array_keys($hl)), min(array_keys($hl)));
        }
    }
    $l_date = array();
    if(isset($lowerhs))
    {
        foreach($lowerhs as $lh)
        {
            $l_date[] = array(max(array_keys($lh)), min(array_keys($lh)));
        }
    }
    if(isset($lowerls))
    {
        foreach($lowerls as $ll)
        {
            $l_date[] = array(max(array_keys($ll)), min(array_keys($ll)));
        }
    }
    $indicator = array('H_date' => $h_date, 'L_date' => $l_date);
    return $indicator;
}
//Mean reversion, using 20 SMA to get the upper and lower bound. It is a dynamic bound, compared to some of the
//researches using consistent bound like 15%/85%, it is more effective when we use a long period of time and the price fluctuated firmly. We also combine with RSI to make the trading signals more efficient. 
function MeanReversion($price_array_small)
{
    $dates = array_keys($price_array_small);
    $prices = array_values($price_array_small);
    $priceCount = count($prices);
    $sma_20 = Get_SMA($price_array_small, 20);
    $sma_20_value = array_values($sma_20);
    if(!isset($sma_20)) return false;
    for($ii = 19; $ii < count($prices); $ii++)
    {
        $sub_arr = array_slice($prices, $ii - 19, 20);
        $mean = array_sum($sub_arr) / count($sub_arr);
        $squares = array_map(function ($x) use ($mean) {
            return pow($x - $mean, 2);
        }, $sub_arr);
        $var = array_sum($squares) / (count($sub_arr) - 1);
        $std[$dates[$ii]] = sqrt($var);
    }
    foreach($sma_20 as $date => $value)
    {
        $upper[$date] = $value + 2 * $std[$date];
        $lower[$date] = $value - 2 * $std[$date];
    }
    $u = array();
    $d = array();
    foreach($prices as $i => $price)
    {
        if($i == 0)continue;
        $price_diff = $price - $prices[$i - 1];
        if($price_diff >= 0)
        {
            $u[$dates[$i]] = $price_diff;
            $d[$dates[$i]] = 0;
        }
        else
        {
            $u[$dates[$i]] = 0;
            $d[$dates[$i]] = -$price_diff;
        }
    }
    $ema_u = Get_EMA($u, 6);
    $ema_d = Get_EMA($d, 6);
    $signal = array();
    $dates_u = array_keys($ema_u);
    $ema_value = array_values($ema_u);
    foreach($ema_value as $n => $ema)
    {
        $date = $dates_u[$n];
        $rsi = $ema / ($ema_d[$date] + $ema);
        if($rsi < 0.3 && $price_array_small[$date] < $lower[$date])
        {
            $signal[$dates_u[$n + 1]] = 1;
        }
        elseif($rsi > 0.7 && $price_array_small[$date] > $upper[$date])
        {
            $signal[$dates_u[$n + 1]] = -1;
        }
        else
        {
            $signal[$dates_u[$n + 1]] = 0;
        }
    }
    return $signal;
}
//Money Flow Index, considering the volumn in the calculation, also used as RSI.
function MFI($sql_getprice)
{
    $result_getprice = mysql_query($sql_getprice, DBConnection::getInstance() -> getDB('price'));
    $nrow_getprice = mysql_num_rows($result_getprice);
    $mfi_array = array();
    if ($nrow_getprice != 0)
    {
        for ($pr = 0; $pr < $nrow_getprice; $pr++)
        {
            $row_getprice = mysql_fetch_assoc($result_getprice);
            $fair_price = ($row_getprice['high'] + $row_getprice['low'] + $row_getprice['close']) / 3;
            $volume = $row_getprice['volume'];
            $money_flow = $fair_price * $volume;
            $money_array_small['fair_price'][] = $fair_price;
            $money_array_small['money_flow'][] = $money_flow;
            $date_array[] = $row_getprice['date'];
        }
    }
    for($i = 1; $i < count($date_array); $i++)
    {
        if($money_array_small['fair_price'][$i] >= $money_array_small['fair_price'][$i - 1])
        {
            $sign = 1;
        }
        else
        {
            $sign = -1;
        }
        $sign_money = $money_array_small['money_flow'][$i] * $sign;
        if($sign_money >= 0)
        {
            $pos_mf[$date_array[$i]] = $sign_money;
            $neg_mf[$date_array[$i]] = 0;
        }
        else
        {
            $pos_mf[$date_array[$i]] = 0;
            $neg_mf[$date_array[$i]] = -$sign_money;
        }
    }
    $gain_array = Get_SMA($pos_mf, 14);
    $loss_array = Get_SMA($neg_mf, 14);
    foreach($gain_array as $mon_date => $gain)
    {
        if($gain + $loss_array[$mon_date] == 0)
        {
            $mfi_array[$mon_date] = 0;
        }
        else
        {
            $mfi_array[$mon_date] = $gain / ($gain + $loss_array[$mon_date]);
        }
    }
    return $mfi_array;
}
//EOM, invented by Richard Arms with a full name of 'Ease of Movement'. It measures whether the stock price increases easily or difficultly. With EOM above 0 line, it is easy for this stock to rise.
function EOM($sql_getprice)
{
    $result_getprice = mysql_query($sql_getprice, DBConnection::getInstance() -> getDB('price'));
    $nrow_getprice = mysql_num_rows($result_getprice);
    if ($nrow_getprice != 0)
    {
        for ($pr = 0; $pr < $nrow_getprice; $pr++)
        {
            $row_getprice = mysql_fetch_assoc($result_getprice);
            $raw_array['date'][$pr] = $row_getprice['date'];
            $raw_array['high'][$pr] = $row_getprice['high'];
            $raw_array['low'][$pr] = $row_getprice['low'];
            $raw_array['volume'][$pr] = $row_getprice['volume'];
        }
        $eom_array = array();
        for($num = 1; $num < count($raw_array['date']); $num++)
        {
            $current_date = $raw_array['date'][$num];
            $distance = (($raw_array['high'][$num] + $raw_array['low'][$num]) - ($raw_array['high'][$num - 1] + $raw_array['low'][$num - 1])) / 2;
            $box_ratio = ($raw_array['volume'][$num] / 100000000) / ($raw_array['high'][$num] - $raw_array['low'][$num]);
            $eom_array[$current_date] = $distance / $box_ratio;
        }
        $eom_indicator = Get_SMA($eom_array, 14);
    }
    return $eom_indicator;
}
function backtesting($signal_array, $price_array_small)
{
    $unbalance = array_sum(array_values($signal_array));
    foreach($signal_array as $date => $signal)
    {
        $current_price = $price_array_small[$date];
        $incre_value[] = $signal * $current_price;
    }
    $total_value = -array_sum($incre_value);
    $close_value = $current_price * $unbalance;
    return($total_value + $close_value);
}
function backtesting2($signal_array, $price_array_small)
{
    $date_array = array_keys($price_array_small);
    for($i =1; $i < count($price_array_small); $i++)
    {
        $return = $price_array_small[$date_array[$i]] / $price_array_small[$date_array[$i - 1]];
        $return_array[$date_array[$i]] = $return;
    }
    $return_array[$date_array[0]] = 1;
    $pos = 0;
    foreach($signal_array as $date => $signal)
    {
        if(isset($return_array[$date]))
        {
            $pos = $pos + $signal;
            $flag = $pos * $return_array[$date];
            if( $flag < 0)
            {
                $port_return[$date] = -$pos / $return_array[$date];
            }
            elseif($flag > 0)
            {
                $port_return[$date] = $pos * $return_array[$date];
            }
            elseif($flag == 0)
            {
                $port_return[$date] = 1;
            }
        }
    }
    $total_return = array_product(array_values($port_return));
    return $total_return;
}
?>
