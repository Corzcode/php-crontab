<?php
/**
 * 自动任务配置 以时间整点计算
 * 'cron名称'=> array('interval' => 执行时间秒整数, 'offset'=> 时间偏移值秒整数);
 *             时间起点为周一  每周二执行可以设'interval' => 86400*7, 'offset'=> 86400*2
 * 'cron名称'=> array('crontab' =>
 *     '0  1  2  3  4  5');
 *      *  *  *  *  *  *
 *      |  |  |  |  |  |
 *      |  |  |  |  |  +------ day of week (0 - 6) (Sunday=0)
 *      |  |  |  |  +--------- month (1 - 12)
 *      |  |  |  +------------ day of month (1 - 31)
 *      |  |  +--------------- hour (0 - 23)
 *      |  +------------------ min (0 - 59)
 *      +--------------------- sec (0-59)
 * 不支援week day同时设定
 */
return array(
    'Interval' => array('interval' => 10, 'offset' => 3), 
    'Crontab' => array('crontab' => '20-50/3 */5 0-18 * * 3,4'));