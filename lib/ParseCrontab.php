<?php

/**
 * 解析crontab的字符串
 *
 * @author    Corz<combo_k@126.com>
 * @since     2015年9月2日
 * @version   1.0
 */
class ParseCrontab
{

    /**
     * 是否要进一
     * @var boolean
     */
    protected static $isCeil = true;

    /**
     * 时间数组
     * @var array
     */
    protected static $time = array();

    /**
     * 解析自动任务
     * 返回下次执行的时间戳
     *
     * @param  string $crontab
     * @param  int    $timestamp
     * @return int
     */
    public static function parse($crontab, $timestamp = null)
    {
        $times = preg_split('/\s+/', $crontab);
        isset($timestamp) || $timestamp = time();
        $timeInfo = getdate($timestamp);
        
        self::$time[0] = self::field($times[0], $timeInfo['seconds'], 0, 59);
        self::$time[1] = self::field($times[1], $timeInfo['minutes'], 0, 59);
        self::$time[2] = self::field($times[2], $timeInfo['hours'], 0, 23);
        if ($times[5] == '*') {
            self::$time[3] = self::field($times[3], $timeInfo['mday'], 1, date('t'));
        } else {
            self::$time[3] = self::fieldWeek($times[5], $timeInfo['mday'], $timeInfo['wday']);
        }
        self::$time[4] = self::field($times[4], $timeInfo['mon'], 1, 12);
        
        $year = self::$isCeil ? $timeInfo['year'] + 1 : $timeInfo['year'];
        
        $time = mktime(current(self::$time[2]), current(self::$time[1]), current(self::$time[0]), current(self::$time[4]), 
            current(self::$time[3]), $year);
        
        //重设变量
        self::$time = array();
        self::$isCeil = true;
        return $time;
    }

    /**
     *  重设到起始时间
     *
     * @param boolean $ceil
     */
    protected static function reset($ceil = false)
    {
        foreach (self::$time as $k => &$value) {
            reset($value);
        }
        self::$isCeil = $ceil;
    }

    /**
     * 分析crontab文本
     *
     * @param  string $set
     * @param  string $now
     * @param  string $min
     * @param  string $max
     * @return array
     */
    protected static function field($set, $now, $min, $max)
    {
        self::$isCeil && $now ++;
        self::$isCeil = false;
        //返回格式为0=>当前执行 1=>最初执行
        $retval = [];
        if (is_numeric($set)) {
            $set = intval($set);
            if ($now >= $set) {
                self::reset(true);
            } else {
                self::reset(false);
            }
            $retval = array($set);
        } elseif ('*' == $set) {
            $retval = [$min, $now];
            end($retval);
        } else {
            //echo $set;
            $parse = self::parseCronNumbers($set, $min, $max);
            $retval = array($parse[0]);
            $in = false;
            foreach ($parse as $value) {
                if ($now <= $value) {
                    $in = $value;
                    break;
                }
            }
            if (false === $in) {
                self::reset(true);
            } else {
                $in > $now && self::reset(false);
                $retval[] = $in;
                end($retval);
            }
            //echo var_export($retval, true);
            //exit();
        }
        return $retval;
    }

    /**
     * 解析星期
     *
     * @param  string $set
     * @param  string $nowd
     * @param  string $noww
     * @return array
     */
    protected static function fieldWeek($set, $nowd, $noww)
    {
        if (self::$isCeil) {
            $noww ++;
            $nowd ++;
        }
        $noww %= 7;
        self::$isCeil = false;
        if (! is_numeric($set)) {
            $parse = self::parseCronNumbers($set, 0, 6);
            $set = $parse[0];
            foreach ($parse as $value) {
                if ($noww <= $value) {
                    $set = $value;
                    break;
                }
            }
        }
        $set = intval($set);
        if ($noww > $set) {
            self::reset(false);
            $offset += 7 - ($noww - $set);
        } elseif ($noww < $set) {
            self::reset(false);
            $offset += ($set - $noww);
        } else {
            $offset = $noww - $set;
        }
        $retval = array($nowd + $offset);
        return $retval;
    }

    /**
     * 解析单个配置的含义
     * @param string $s     cron string element
     * @param int    $min   minimum possible value
     * @param int    $max   maximum possible value
     * @return array
     */
    protected static function parseCronNumbers($s, $min, $max)
    {
        $result = array();
        $v = explode(',', $s);
        foreach ($v as $vv) {
            $vvv = explode('/', $vv);
            $step = empty($vvv[1]) ? 1 : $vvv[1];
            $vvvv = explode('-', $vvv[0]);
            $_min = count($vvvv) == 2 ? $vvvv[0] : ($vvv[0] == '*' ? $min : $vvv[0]);
            $_max = count($vvvv) == 2 ? $vvvv[1] : ($vvv[0] == '*' ? $max : $vvv[0]);
            for ($i = $_min; $i <= $_max; $i += $step) {
                $result[] = intval($i);
            }
        }
        ksort($result);
        return $result;
    }
}