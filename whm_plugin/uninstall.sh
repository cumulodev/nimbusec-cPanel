#!/bin/sh
###################################### Uninstallation whm Plugin ######################################
LOGFILE="logs/whm_uninstall.log"

if [ ! -d logs ];
then
	mkdir -p logs
fi

echo "INFO: Uninstalling Nimbusec WHM - Plugin..." | tee $LOGFILE

###################################################
################# Directories #####################
rm -rf /usr/local/cpanel/whostmgr/docroot/cgi/addons/nimbusec >>$LOGFILE 2>&1
rm -rf /usr/local/cpanel/whostmgr/docroot/cgi/addons/nimbusec/images >>$LOGFILE 2>&1
rm -rf /usr/local/cpanel/whostmgr/docroot/cgi/images >>$LOGFILE 2>&1
rm -rf /usr/local/nimbusec >>$LOGFILE 2>&1

rm -f /usr/local/cpanel/whostmgr/docroot/cgi/addon_nimbusec.cgi >>$LOGFILE 2>&1
rm -f /usr/local/cpanel/whostmgr/docroot/cgi/nimbusec.php >>$LOGFILE 2>&1
rm -f /usr/local/cpanel/whostmgr/docroot/cgi/controller.php >>$LOGFILE 2>&1
rm -f /usr/local/cpanel/whostmgr/docroot/cgi/whm-plugin.css >>$LOGFILE 2>&1

rm -f /usr/local/cpanel/whostmgr/docroot/addon_plugins/nimbusec-icon.png >>$LOGFILE 2>&1

echo "INFO: Removing whm backend files directory at /usr/local/cpanel/whostmgr/docroot/cgi/addons/nimbusec" | tee -a $LOGFILE
echo "INFO: Removing nimbusec backend files directory at /usr/local/nimbusec" | tee -a $LOGFILE
echo "INFO: Removing icon at /usr/local/cpanel/whostmgr/docroot/addon_plugins/" | tee -a $LOGFILE

if [ ! -d /usr/local/nimbusec ];
then
	echo "INFO: Back-end files removed." | tee -a $LOGFILE
else
	echo "WARNING: Deleting back-end files failed." | tee -a $LOGFILE
fi

###################################################
################# cPanel files #####################

rm -rf /usr/local/cpanel/base/frontend/x3/nimbusec >/dev/null 2>&1
echo "INFO: Removing cPanel files for x3 at /usr/local/cpanel/base/frontend/x3/" | tee -a $LOGFILE
rm -rf /usr/local/cpanel/bin/nimbusec >/dev/null 2>&1
echo "INFO: Removing cPanel conf files at /usr/local/cpanel/bin/nimbusec" | tee -a $LOGFILE

##########################################################
## Check if paper_lantern theme is installed on the system
##########################################################
if [ -d /usr/local/cpanel/base/frontend/paper_lantern ];
then
	rm -rf /usr/local/cpanel/base/frontend/paper_lantern/nimbusec >/dev/null 2>&1
	echo "INFO: Removing cPanel files for paper_lantern at /usr/local/cpanel/base/frontend/paper_lantern/" | tee -a $LOGFILE
else
	echo "WARNING: Paper_lantern theme not installed on your system. The uninstallation will be skipped" | tee -a $LOGFILE
fi

#####################################################
################# WHM Plugin backend files #####################

if [ -x /usr/local/cpanel/bin/unregister_appconfig ];
then
		if [ `/usr/local/cpanel/bin/unregister_appconfig conf/nimbusec.conf | tee -a $LOGFILE  | wc -l` -eq 1 ];
		then
			echo "INFO: WHM plugin backend files uninstalled." | tee -a $LOGFILE
		else
			echo "ERROR: Uninstalling backend files failed. The uninstallation will be aborted." | tee -a $LOGFILE
			exit 1
		fi
		if [ -f /var/cpanel/apps/nimbusec.conf ];
		then
			rm -f /var/cpanel/apps/nimbusec.conf >> $LOGFILE 2>&1
		fi
else
	if [ \( ! -f /usr/local/cpanel/whostmgr/docroot/cgi/addon_nimbusec.cgi \) -a \( ! -f /usr/local/cpanel/whostmgr/docroot/cgi/nimbusec.php \) ];
	then
		if [ -f /var/cpanel/apps/nimbusec.conf ];
		then
			rm -f /var/cpanel/apps/nimbusec.conf >> $LOGFILE 2>&1
		fi

		echo "INFO: WHM plugin backend files uninstalled." | tee -a $LOGFILE
	else
		echo "ERROR: WHM plugin backend files could not be deleted. The uninstallation will be aborted." | tee -a $LOGFILE
		exit 1
	fi
fi

echo "INFO: Successfully uninstalled Nimbusec WHM - Plugin..." | tee -a $LOGFILE
