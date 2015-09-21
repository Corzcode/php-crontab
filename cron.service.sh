#!/bin/sh
DESC="MALL CLI"
NAME="PHP - CronTab"

LOGPATH=/data/logs/cron.`date +%Y%m%d`.log
PHPBIN=/usr/local/php55/bin/php
PHPSCRIPT=/mnt/htdocs/php-crontab/cron.php
PIDFILE=/var/run/php-crontab.pid

do_start() {
	nohup $PHPBIN $PHPSCRIPT $PHPARGS >> $LOGPATH 2>&1 & 
	echo $! > $PIDFILE
}

do_stop() {
	kill -INT `cat $PIDFILE` || echo -n "$DESC not running"
}

case "$1" in
	start)
		echo -n "Starting $NAME:"
		do_start
		echo "."
		;;
	stop)
		echo -n "Stopping $NAME:"
		do_stop
		echo "."
		;;
	restart)
		echo -n "Restarting $NAME:"
		do_stop
		do_start
		echo "."
		;;
	*)
		echo "Usage: $0 {start|stop|restart}" >&2
		exit 3
		;;
esac

exit 0

