<?php

/**
 * 解析Interval配置
 *
 * @author    Corz<combo_k@126.com>
 * @since     2015年9月2日
 * @version   1.0
 */
class ParseInterval
{

    /**
     * 1420387200代表2015-1-5 00:00:00号星期一作为偏移值的始点
     * @var int
     */
    const TIME_OFFSET = 1420387200;

    /**
     * 解析自动任务
     * 返回下次执行的时间戳
     *
     * @param  array $conf
     * @param  int   $timestamp
     * @return int
     */
    public static function parse(array $conf, $timestamp = null)
    {
        $time = $timestamp - self::TIME_OFFSET;
        $time = self::TIME_OFFSET + $time - ($time % $conf['interval']) + $conf['interval'];
        isset($conf['offset']) && $time += $conf['offset'];
        return $time;
    }
}