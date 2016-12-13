#!/bin/bash

for i in $(seq 2 $END);
	do
		echo "Lancement $i"
		/usr/bin/php5 /root/send-ident/crontab.filter.php > /dev/null
		sleep 30
done
