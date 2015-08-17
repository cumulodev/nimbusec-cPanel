#!/bin/sh
###################################### Installation cPanel Plugin ######################################

########################################################################################################
################################################## x3 ##################################################

###################################################
################# Back-end files ##################

if [ -f /usr/local/cpanel/base/frontend/x3/nimbusec/index.livephp ]; then

	#######################################################
	################# Plugin-in conf file #################
	if [ -f /usr/local/cpanel/bin/nimbusec/nimbusec.cpanelplugin ]; then

		#######################################################
		################# Register Plugin-in ##################
		/usr/local/cpanel/bin/register_cpanelplugin /usr/local/cpanel/bin/nimbusec/nimbusec.cpanelplugin >/dev/null 2>&1
	else
		echo "0#Configuration files could not be copied. The cPanel installation process failed"
		exit 1
	fi

else
	echo "0#Back-end files for x3 could not be copied. The cPanel installation process failed"
	exit 1
fi

########################################################################################################
############################################# paper_lantern ############################################

###################################################
################# Back-end files ##################

##########################################################
## Check if paper_lantern theme is installed on the system
##########################################################
if [ -d /usr/local/cpanel/base/frontend/paper_lantern/nimbusec ]; then
	if [ -f /usr/local/cpanel/base/frontend/paper_lantern/nimbusec/index.livephp ]; then

		#######################################################
		################# Register Plugin-in ##################
		/usr/local/cpanel/scripts/install_plugin /usr/local/cpanel/bin/nimbusec/nimbusec.tar.gz --theme paper_lantern >/dev/null 2>&1
	else
		echo "0#Back-end files for paper_lantern could not be copied. The cPanel installation process failed"
		exit 1
	fi
fi

echo "1#cPanel Plugin installation process complete!"

