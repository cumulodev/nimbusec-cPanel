# nimbusec-cPanel
nimbusec cPanel / WHM plugin for hosters.

## Installation
### WHM plugin
Extract the plugin on the server and execute the installation shell script at
```shell
$ ./nimbusec/whm_plugin/install.sh
```
__Note__:  If the installation was suddenly aborted or errors have occurred, it is advised to review the log files at `$ ./nimbusec/whm_plugin/logs/` to get more information about possible causes.

Once the installation is complete, a new entry will be available in the sidebar under plugins. Here you can configure nimbusec for your environment.

### nimbusec
To install nimbusec on your system, enter your credentials provided by nimbusec and click on __'Install nimbusec'__. It might take a while for the process to finish, therefore it is not advised leave the page or shutdown the system.

__Note__: If the installation was suddenly aborted or errors have occurred, it is advised to review the log files at `$ /usr/local/nimbusec/logs` to get more information about possible causes.

Once the installation is complete, you will be able to continue and using nimbsusec's main functions to match your needs.

## Uninstallation
It is advised to start with the nimbusec plugin itself as you will not be able to continue with the uninstallation when deleting the WHM plugin.
### nimbusec
To uninstall nimbusec from your system click on __'Uninstall nimbusec'__ in the 'Settings' tab.
It might take a while for the process to finish, therefore it is not advised to leave the page or shutdown the system.

While uninstalling, nimbusec will remove __any__ of the plugin's components leaving your system at the same state as prior to the installation.

__Note__: If the uninstallation was suddenly aborted or errors have occurred, it is advised to review the log files at `$ /usr/local/nimbusec/logs` to get more information about possible causes.

Once the uninstallation is finished, nimbusec will be completely removed from your system.

### WHM plugin
Remove the WHM plugin by executing the uninstallation script at
```shell
$ ./nimbusec/whm_plugin/uninstall.sh
```
__Note__:  If the uninstallation was suddenly aborted or errors have occurred, it is advised to review the log files at `$ ./nimbusec/whm_plugin/logs/` to get more information about possible causes.

## Main Functions
The plugin provides the following functionality:

- Configuration of nimbusec in WHM
- Adding nimbusec bundles to hosting packages
- Provisioning nimbusec for domains and users
- Single Sign On (SSO) directly from cPanel customer frontend
- Overview of all packages including nimbusec and the corresponding users

For further questions ask at office@cumulo.at.
