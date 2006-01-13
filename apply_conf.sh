#!/bin/bash

if [ "$1" == "-h" ]
then
	echo "Usage: "
	echo "   "$0" [config]"
	echo
	echo "If config file is not specified, default is /etc/amportal.conf"
	echo
	exit
fi

if [ -n "$1" ]
then
	AMPCONFIG=$1
else
	AMPCONFIG=/etc/amportal.conf
fi

if [ ! -e $AMPCONFIG ]
then
	echo "Cannot find $AMPCONFIG"
	exit
fi

# include config file
echo "Reading $AMPCONFIG"
source $AMPCONFIG


echo "Updating configuration..."

echo "/etc/asterisk/cdr_mysql.conf"
sed -r -i "s/user=[a-zA-Z0-9]*/user=$AMPDBUSER/" /etc/asterisk/cdr_mysql.conf
sed -r -i "s/password=[a-zA-Z0-9]*/password=$AMPDBPASS/" /etc/asterisk/cdr_mysql.conf
sed -r -i "s/hostname=[a-zA-Z0-9]*/hostname=$AMPDBHOST/" /etc/asterisk/cdr_mysql.conf

# do a bunch at once here
find $AMPBIN/ -name retrieve\*.pl
sed -r -i "s/username = \"[a-zA-Z0-9]*\";/username = \"$AMPDBUSER\";/" `find $AMPBIN/ -name retrieve\*.pl`
sed -r -i "s/password = \"[a-zA-Z0-9]*\";/password = \"$AMPDBPASS\";/" `find $AMPBIN/ -name retrieve\*.pl`
sed -r -i "s/hostname = \"[a-zA-Z0-9]*\";/hostname = \"$AMPDBHOST\";/" `find $AMPBIN/ -name retrieve\*.pl`

# sort option for FOP
sed -r -i "s/sortoption = \"[a-zA-Z0-9]*\";/sortoption = \"$FOPSORT\";/" $AMPBIN/retrieve_op_conf_from_mysql.pl

sed -r -i "s!op_conf = \"[^\"]*\";!op_conf = \"$AMPWEBROOT\/panel\/op_buttons_additional.cfg\";!" $AMPBIN/retrieve_op_conf_from_mysql.pl

echo "/etc/asterisk/manager.conf"
sed -r -i "s/secret = [a-zA-Z0-9]*/secret = $AMPMGRPASS/" /etc/asterisk/manager.conf
sed -r -i "/\[general\]/!s/\[[a-zA-Z0-9]+\]/[$AMPMGRUSER]/" /etc/asterisk/manager.conf

echo "/var/lib/asterisk/agi-bin/dialparties.agi"
sed -r -i "s/mgrUSERNAME='[a-zA-Z0-9]*';/mgrUSERNAME='$AMPMGRUSER';/" /var/lib/asterisk/agi-bin/dialparties.agi
sed -r -i "s/mgrSECRET='[a-zA-Z0-9]*';/mgrSECRET='$AMPMGRPASS';/" /var/lib/asterisk/agi-bin/dialparties.agi

echo $AMPWEBROOT"/panel/op_server.cfg"
sed -r -i "s/manager_user=[a-zA-Z0-9]*/manager_user=$AMPMGRUSER/" $FOPWEBROOT/op_server.cfg
sed -r -i "s/manager_secret=[a-zA-Z0-9]*/manager_secret=$AMPMGRPASS/" $FOPWEBROOT/op_server.cfg
sed -r -i "s/web_hostname=[a-zA-Z0-9_\-\.]*/web_hostname=$AMPWEBADDRESS/" $FOPWEBROOT/op_server.cfg
sed -r -i "s/security_code=[a-zA-Z0-9]*/security_code=$FOPPASSWORD/" $FOPWEBROOT/op_server.cfg
sed -r -i "s!flash_dir=[a-zA-Z0-9_\-\.\/\\]*!flash_dir=$FOPWEBROOT!" $FOPWEBROOT/op_server.cfg
sed -r -i "s!web_hostname=[a-zA-Z0-9\.]*!web_hostname=$AMPWEBADDRESS!" $FOPWEBROOT/op_server.cfg
sed -r -i "s!web_hostname=[a-zA-Z0-9\.]*!web_hostname=$AMPWEBADDRESS!" $FOPWEBROOT/op_server.cfg

echo "/etc/asterisk/vm_email.inc"
sed -r -i "s!http://.*/cgi-bin!http://$AMPWEBADDRESS/cgi-bin!" /etc/asterisk/vm_email.inc

echo "Done"
echo
echo "Adjusting File Permissions.."
/usr/sbin/amportal chown
echo "Done"
exit

