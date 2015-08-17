#!/usr/bin/perl
#WHMADDON:nimbusec:<b>Nimbusec Seccurity Monitor</b>:nimbusec-icon.png

use lib '/usr/local/cpanel/';
use Whostmgr::ACLS();
use strict;
use warnings;

my $url = "nimbusec.php";

Whostmgr::ACLS::init_acls();
print "Content-type: text/html\r\n\r\n";

if (!Whostmgr::ACLS::hasroot()) {
    print "You don't have the permissions to enter the Nimbusec WHM Plugin.\n";
    exit();
}

print qq[
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
<title>Redirecting...</title>
<meta http-equiv="refresh" content="0;url=$url"/>
</head>
<body>
</body>
</html>
];
