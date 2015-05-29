#!/bin/sh
eval 'if [ -x /usr/local/cpanel/3rdparty/bin/perl ]; then exec /usr/local/cpanel/3rdparty/bin/perl -x -- $0 ${1+"$@"}; else exec /usr/bin/perl -x -- $0 ${1+"$@"};fi'
if 0;
#!/usr/bin/perl

#WHMADDON:nimbusec:Nimbusec Seccurity Monitor:nimbusec-icon.png
####################################################
##################################################### 

use lib '/usr/local/cpanel/';
use Whostmgr::ACLS                      ();
Whostmgr::ACLS::init_acls();

print "Content-Type: text/html\n\n";

if (!Whostmgr::ACLS::hasroot()) {
    print "You don't have the permissions to enter the Nimbusec WHM Plugin.\n";
    exit();
}

print "<meta http-equiv=\"refresh\" content=\"0;url=nimbusec.php\"/>" ;
