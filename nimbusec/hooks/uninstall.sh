#!/bin/sh
###################################### Uninstallation Hooks ######################################

/usr/local/cpanel/bin/manage_hooks delete script /var/cpanel/nimbusec/provisioningHook.php --manual --category Whostmgr --event 'Accounts::Create' --stage post 2>&1 >>/dev/null
/usr/local/cpanel/bin/manage_hooks delete script /var/cpanel/nimbusec/changePackageHook.php --manual --category Whostmgr --event 'Accounts::change_package' --stage post 2>&1 >>/dev/null
/usr/local/cpanel/bin/manage_hooks delete script /var/cpanel/nimbusec/removeHook.php --manual --category Whostmgr --event 'Accounts::Remove' --stage pre 2>&1 >>/dev/null

rm -rf /var/cpanel/nimbusec >/dev/null 2>&1

if [ -d /var/cpanel/nimbusec ]; then
	echo "0#Uninstalling hooks failed."
	exit 1
fi

echo "1#Successfully uninstalled hooks"
