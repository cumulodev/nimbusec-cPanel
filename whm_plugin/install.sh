#!/bin/sh
###################################### Installation whm Plugin ######################################
LOGFILE="logs/whm_install.log"

if [ ! -d logs ];
then
	mkdir -p logs
fi

echo "INFO: Installing Nimbusec WHM - Plugin..." | tee $LOGFILE

###################################################
################# Directories #####################
if [ ! -d /var/cpanel/apps ];
then
	mkdir -p /var/cpanel/apps
fi
if [ ! -d /usr/local/cpanel/whostmgr/docroot/cgi/addons ];
then
	mkdir -p /usr/local/cpanel/whostmgr/docroot/cgi/addons
fi
if [ ! -d /usr/local/cpanel/whostmgr/docroot/cgi/addons/nimbusec ];
then
	mkdir -p /usr/local/cpanel/whostmgr/docroot/cgi/addons/nimbusec
fi
if [ ! -d /usr/local/nimbusec ];
then
	mkdir -p /usr/local/nimbusec
fi
if [ ! -d /usr/local/nimbusec/logs ];
then
	mkdir -p /usr/local/nimbusec/logs
fi
if [ ! -d /var/cpanel/packages/extensions ];
then
	mkdir -p /var/cpanel/packages/extensions
fi

echo "INFO: Creating directory at /var/cpanel/apps" | tee -a $LOGFILE
echo "INFO: Creating directory at /usr/local/cpanel/whostmgr/docroot/cgi/addons" | tee -a $LOGFILE
echo "INFO: Creating directory at /usr/local/nimbusec" | tee -a $LOGFILE

chmod 755 /var/cpanel/apps >>$LOGFILE 2>&1
chmod 755 /usr/local/cpanel/whostmgr/docroot/cgi/addons >>$LOGFILE 2>&1
chmod 755 /usr/local/cpanel/whostmgr/docroot/cgi/addons/nimbusec >>$LOGFILE 2>&1
chmod 755 /usr/local/nimbusec >>$LOGFILE 2>&1
chmod 755 /usr/local/nimbusec/logs >>$LOGFILE 2>&1
chmod 755 /var/cpanel/packages/extensions >>$LOGFILE 2>&1

###################################################
################# cPanel files #####################

rm -rf /usr/local/cpanel/base/frontend/x3/nimbusec >/dev/null 2>&1
cp -afr ../nimbusec/cpanel_plugin/x3/nimbusec /usr/local/cpanel/base/frontend/x3/ >/dev/null 2>&1
chmod -R 755 /usr/local/cpanel/base/frontend/x3/nimbusec

echo "INFO: Copy cPanel files for x3 to /usr/local/cpanel/base/frontend/x3/" | tee -a $LOGFILE

mkdir -p /usr/local/cpanel/bin/nimbusec
chmod 755 /usr/local/cpanel/bin/nimbusec
install -m 0755 ../nimbusec/cpanel_plugin/x3/conf/nimbusec.cpanelplugin /usr/local/cpanel/bin/nimbusec/nimbusec.cpanelplugin >/dev/null 2>&1
echo "INFO: Copy cPanel conf files for x3 to /usr/local/cpanel/bin/nimbusec/" | tee -a $LOGFILE

##########################################################
## Check if paper_lantern theme is installed on the system
##########################################################
if [ -d /usr/local/cpanel/base/frontend/paper_lantern ];
then
	rm -rf /usr/local/cpanel/base/frontend/paper_lantern/nimbusec >/dev/null 2>&1
	cp -afr ../nimbusec/cpanel_plugin/paper_lantern/nimbusec /usr/local/cpanel/base/frontend/paper_lantern/ >/dev/null 2>&1
	chmod -R 755 /usr/local/cpanel/base/frontend/paper_lantern/nimbusec

	install -m 0755 ../nimbusec/cpanel_plugin/paper_lantern/nimbusec.tar.gz /usr/local/cpanel/bin/nimbusec/nimbusec.tar.gz >/dev/null 2>&1

	echo "INFO: Copy cPanel files for paper_lantern to /usr/local/cpanel/base/frontend/paper_lantern/" | tee -a $LOGFILE
else
	echo "WARNING: Paper_lantern theme not installed on your system. The paper_lantern adapted Nimbusec cPanel plugin installation will be skipped" | tee -a $LOGFILE
fi

###################################################
################# Library files #####################

cp -rf ../lib /usr/local/nimbusec >>$LOGFILE 2>&1

echo "INFO: Copy libraries to /usr/local/nimbusec" | tee -a $LOGFILE
if [ `ls -l /usr/local/nimbusec/lib/*.php | wc -l` -eq 8 ];
then
	chmod -R 755 /usr/local/nimbusec/lib >>$LOGFILE 2>&1
	echo "INFO: Library files installed." | tee -a $LOGFILE
else
	echo "ERROR: Copying library files failed. The installation will be aborted." | tee -a $LOGFILE
	exit 1
fi

###################################################
################# Nimbusec Backend files #####################
cp -rf ../nimbusec /usr/local/nimbusec
echo "INFO: Copy backend files to /usr/local/nimbusec" | tee -a $LOGFILE
if [ -f /usr/local/nimbusec/nimbusec/install.php ];
then
	chmod -R 755 /usr/local/nimbusec/nimbusec >>$LOGFILE 2>&1
	echo "INFO: Backend files installed." | tee -a $LOGFILE
else
	echo "ERROR: Copying nimbusec backend files failed. The installation will be aborted." | tee -a $LOGFILE
	exit 1
fi

#####################################################
################# WHM Plugin backend files #####################

if [ -x /usr/local/cpanel/bin/register_appconfig ];
then
	install -m 0755 nimbusec/nimbusec.cgi /usr/local/cpanel/whostmgr/docroot/cgi/addons/nimbusec/nimbusec.cgi >>$LOGFILE 2>&1
	install -m 0755 nimbusec/nimbusec.php /usr/local/cpanel/whostmgr/docroot/cgi/addons/nimbusec/nimbusec.php >>$LOGFILE 2>&1
	install -m 0755 nimbusec/controller.php /usr/local/cpanel/whostmgr/docroot/cgi/addons/nimbusec/controller.php >>$LOGFILE 2>&1
	install -m 0755 nimbusec/whm-plugin.css /usr/local/cpanel/whostmgr/docroot/cgi/addons/nimbusec/whm-plugin.css >>$LOGFILE 2>&1
	cp -rf ./images /usr/local/cpanel/whostmgr/docroot/cgi/addons/nimbusec

	echo "INFO: Copy WHM backend files to /usr/local/cpanel/whostmgr/docroot/cgi/addons/nimbusec/" | tee -a $LOGFILE

	if [ \( -f /usr/local/cpanel/whostmgr/docroot/cgi/addons/nimbusec/nimbusec.cgi \) -a \( -f /usr/local/cpanel/whostmgr/docroot/cgi/addons/nimbusec/nimbusec.php \) ];
	then
		rm -f /usr/local/cpanel/whostmgr/docroot/cgi/addon_nimbusec.cgi >>$LOGFILE 2>&1
		rm -f /usr/local/cpanel/whostmgr/docroot/cgi/nimbusec.php >>$LOGFILE 2>&1

	    	if [ `/usr/local/cpanel/bin/register_appconfig conf/nimbusec.conf | tee -a $LOGFILE  | wc -l` -eq 1 ];
			then
			echo "INFO: WHM plugin backend files installed" | tee -a $LOGFILE
		else
			echo "ERROR: Installing backend files failed. The installation will be aborted." | tee -a $LOGFILE
			exit 1
		fi
   	else
		echo "ERROR: Copying backend files failed. The installation will be aborted." | tee -a $LOGFILE
		exit 1
	fi
else
	install -m 0755 nimbusec/nimbusec.cgi /usr/local/cpanel/whostmgr/docroot/cgi/addon_nimbusec.cgi >>$LOGFILE 2>&1
	install -m 0755 nimbusec/nimbusec.php /usr/local/cpanel/whostmgr/docroot/cgi/nimbusec.php >>$LOGFILE 2>&1
	install -m 0755 nimbusec/controller.php /usr/local/cpanel/whostmgr/docroot/cgi/controller.php >>$LOGFILE 2>&1
	install -m 0755 nimbusec/whm-plugin.css /usr/local/cpanel/whostmgr/docroot/cgi/whm-plugin.css >>$LOGFILE 2>&1
	install -m 0755 conf/nimbusec.conf /var/cpanel/apps/nimbusec.conf >>$LOGFILE 2>&1
	cp -rf ./images /usr/local/cpanel/whostmgr/docroot/cgi/

	echo "INFO: Copy WHM backend files to /usr/local/cpanel/whostmgr/docroot/cgi/" | tee -a $LOGFILE

	if [ \( -f /usr/local/cpanel/whostmgr/docroot/cgi/addon_nimbusec.cgi \) -a \( -f /usr/local/cpanel/whostmgr/docroot/cgi/nimbusec.php \) -a \( -f /var/cpanel/apps/nimbusec.conf \) ];
	then
		echo "INFO: Backend files installed" | tee -a $LOGFILE
	else
		echo "ERROR: Copying backend files failed! The installation will be aborted." | tee -a $LOGFILE
		exit 1
	fi
fi

#####################################################
################# Icon ##############################

if [ ! -d /usr/local/cpanel/whostmgr/docroot/addon_plugins ];
then
	mkdir -p /usr/local/cpanel/whostmgr/docroot/addon_plugins
fi

echo "INFO: Creating directory at /usr/local/cpanel/whostmgr/docroot/addon_plugins" | tee -a $LOGFILE
install -m 0755 icon/nimbusec-icon.png /usr/local/cpanel/whostmgr/docroot/addon_plugins/nimbusec-icon.png >>$LOGFILE 2>&1
echo "INFO: Copy WHM icon files to /usr/local/cpanel/whostmgr/docroot/addon_plugins/" | tee -a $LOGFILE
if [ ! -f /usr/local/cpanel/whostmgr/docroot/addon_plugins/nimbusec-icon.png ];
then
	echo "WARNING: Copying icon file failed." | tee -a $LOGFILE
fi

echo "INFO: Sucessfully installed Nimbusec WHM - Plugin..." | tee -a $LOGFILE