#!/bin/sh
###################################### Installation Hooks ######################################

if [ ! -d /var/cpanel/nimbusec/ ]; then
	mkdir /var/cpanel/nimbusec/ 2>&1
fi

chmod 755 /var/cpanel/nimbusec/ 2>&1

install -m 0755 /usr/local/nimbusec/nimbusec/hooks/files/provisioningHook.php /var/cpanel/nimbusec/provisioningHook.php 2>&1
install -m 0755 /usr/local/nimbusec/nimbusec/hooks/files/changePackageHook.php /var/cpanel/nimbusec/changePackageHook.php 2>&1
install -m 0755 /usr/local/nimbusec/nimbusec/hooks/files/removeHook.php /var/cpanel/nimbusec/removeHook.php 2>&1

###################################################
################# Installing hooks #####################
if [ \( -f /var/cpanel/nimbusec/provisioningHook.php \) -a \( -f /var/cpanel/nimbusec/changePackageHook.php \) -a \( -f /var/cpanel/nimbusec/removeHook.php \) ]; then
	/usr/local/cpanel/bin/manage_hooks add script /var/cpanel/nimbusec/provisioningHook.php --manual --category Whostmgr --event 'Accounts::Create' --stage post 2>&1 >>/dev/null
	/usr/local/cpanel/bin/manage_hooks add script /var/cpanel/nimbusec/changePackageHook.php --manual --category Whostmgr --event 'Accounts::change_package' --stage post 2>&1 >>/dev/null
	/usr/local/cpanel/bin/manage_hooks add script /var/cpanel/nimbusec/removeHook.php --manual --category Whostmgr --event 'Accounts::Remove' --stage pre 2>&1 >>/dev/null
	echo "1#Successfully installed hooks."
else
	echo "0#Installing hooks failed. The neccessary hook scripts are not inside of the directory."
	exit 1
fi
