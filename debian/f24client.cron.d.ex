#
# Regular cron jobs for the f24client package
#
0 4	* * *	root	[ -x /usr/bin/f24client_maintenance ] && /usr/bin/f24client_maintenance
