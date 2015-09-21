<?php
declare(ticks = 1);

/**
 * 计划任务
 * 
 * @author    Corz<combo_k@126.com>
 * @since     2015年9月21日
 * @version   1.0
 */
class Cron
{

    /**
     * 任务路径
     * @var string
     */
    protected $cronPath = '';

    /**
     * 配置
     * @var array
     */
    protected $config = array();

    /**
     * 构造方法
     */
    public function __construct($setting)
    {
        $this->config = $setting['config'];
        $this->cronPath = $setting['cron_path'];
        
        if (isset($setting['group'])) {
            $groupinfo = posix_getpwnam($setting['group']);
            posix_setgid($groupinfo['gid']);
        }
        if (isset($setting['user'])) {
            $userinfo = posix_getgrnam($setting['user']);
            posix_setuid($groupinfo['uid']);
        }
        
        include __DIR__ . '/ParseCrontab.php';
        include __DIR__ . '/ParseInterval.php';
    }

    /**
     * 执行任务系统
     */
    public function run()
    {
        global $argv;
        //指定执行模式
        if (isset($argv[2])) {
            $method = isset($argv[3]) ? $argv[3] : 'run';
            return $this->doCron($argv[2], $method);
        }
        $this->registerSignal();
        $runTime = array();
        
        while (false != ($data = $this->loadConf())) {
            $time = time();
            //echo "mainProc : " . posix_getpid() . " : run time $time \n";
            foreach ($data as $cron => $conf) {
                //初始时间到上次整点
                $interval = $conf['interval'];
                ! isset($runTime[$cron]) && $runTime[$cron] = $this->getRunTime($conf);
                if ($time >= $runTime[$cron]) {
                    $runTime[$cron] = $this->getRunTime($conf);
                    $this->fork($cron);
                }
            }
            unset($data);
            sleep(1);
        }
    }

    /**
     * 读取配置文件
     * 
     * @return array
     */
    protected function loadConf()
    {
        return include $this->config;
    }

    /**
     * 检查时间是否运行
     *
     * @param array $time
     * @param int
     */
    protected function getRunTime(array $conf)
    {
        $timestamp = time();
        if (isset($conf['interval'])) {
            return ParseInterval::parse($conf, $timestamp);
        } elseif (isset($conf['crontab'])) {
            //var_dump($conf['crontab']);
            //while (true) {
            //$t = microtime(true);
            $time = ParseCrontab::parse($conf, $timestamp);
            //echo "nowIs :" . date('Y-m-d H:i:s', $timestamp) . "\t" . $time . "  :  " . date('Y-m-d H:i:s', $time) . "\tuseTime:" .
            //     (microtime(true) - $t) . "\n";
            return $time;
            //$timestamp += 1;
            //usleep(50000);
            //}
        }
        return 9999999999;
    }

    /**
     * 派生子进程处理cron
     * 
     * @param string $cron
     * @param string $config
     */
    protected function fork($cron)
    {
        $pid = pcntl_fork();
        if ($pid == - 1) {
            //错误处理：创建子进程失败时返回-1.
            die("could not fork" . posix_getpid() . "\n");
        } else if ($pid) {
            return $pid;
        }
        //子进程得到的$pid为0, 所以这里是子进程执行的逻辑。
        cli_set_process_title('php ' . $GLOBALS['argv'][0] . ' - cron : ' . $cron);
        echo "Time : " . date('H:i:s') . "\t\tPID : " . posix_getpid() . "\tCron : $cron\n";
        $this->doCron($cron, 'run');
        exit(0);
    }

    /**
     * 执行Cron
     * 
     * @param  string $cron
     * @param  string $method
     * @param  array  $config
     * @return boolean
     */
    protected function doCron($cron, $method = 'run')
    {
        include $this->cronPath . '/' . $cron . '.cron.php';
        $className = ucfirst($cron) . 'Cron';
        $instance = new $className();
        $instance->$method();
        return true;
    }

    /**
     * 回收进程
     */
    protected function registerSignal()
    {
        pcntl_signal(SIGCHLD, SIG_IGN);
        pcntl_signal(SIGINT, function () {
            exit();
        });
    }
}
