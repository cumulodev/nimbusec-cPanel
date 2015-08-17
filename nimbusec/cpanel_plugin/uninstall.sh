#!/bin/sh
###################################### Deinstallation cPanel Plugin ######################################

#######################################################
################# Uninstall Plugin-in for x3 ##################
/usr/local/cpanel/bin/unregister_cpanelplugin /usr/local/cpanel/bin/nimbusec/nimbusec.cpanelplugin >/dev/null 2>&1

###################################################
################# Back-end files ##################

rm -rf /usr/local/cpanel/base/frontend/x3/nimbusec >/dev/null 2>&1
if [ ! -d /usr/local/cpanel/base/frontend/x3/nimbusec ]; then

	#######################################################
	################# Plugin-in conf file #################
	rm -rf /usr/local/cpanel/bin/nimbusec >/dev/null 2>&1
	if [ -d /usr/local/cpanel/bin/nimbusec ]; then
		echo "0#Configuration files could not be deleted. The cPanel installation process failed"
		exit 1
	fi

else
	echo "0#Back-end files could not be deleted. The cPanel uninstallation process failed"
	exit 1
fi

#######################################################
################# Uninstall Plugin-in for paper_lantern ##################
# Deinstallation script not provided by cPanel yet.

###################################################
################# Back-end files ##################

##########################################################
## Check if paper_lantern theme is installed on the system
##########################################################
if [ -d /usr/local/cpanel/base/frontend/paper_lantern/nimbusec ]; then
	rm -rf /usr/local/cpanel/base/frontend/paper_lantern/nimbusec >/dev/null 2>&1

	rm -f /usr/local/cpanel/base/frontend/paper_lantern/styled/basic/icons/nimbusec.png >/dev/null 2>&1
	rm -f /usr/local/cpanel/base/frontend/paper_lantern/dynamicui/dynamicui_nimbusec.conf >/dev/null 2>&1

	if [ \( -d /usr/local/cpanel/base/frontend/paper_lantern/nimbusec \) -o \( -f /usr/local/cpanel/base/frontend/paper_lantern/styled/basic/icons/nimbusec.png \) -o \( -f /usr/local/cpanel/base/frontend/paper_lantern/dynamicui/dynamicui_nimbusec.conf \) ]; then
		echo "0#Configuration files could not be deleted. The cPanel installation process failed"
		exit 1
	fi
fi

echo "1#cPanel Plugin deinstallation process complete!"

