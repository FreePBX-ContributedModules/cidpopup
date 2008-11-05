#!/usr/bin/perl -w

# Copyright 2006 Philippe Lindheimer - Astrogen LLC
# This program is free software; you can redistribute it and/or
# modify it under the terms of the GNU General Public License
# as published by the Free Software Foundation; version 2
# of the License
# 
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
# 

#use LWP::Simple;
use LWP; 
#use XML::Simple;
use Asterisk::AGI;
use Data::Dumper;

#-------------------------------------------------
#
#  main routine
#
#

$AGI = new Asterisk::AGI;

my $loops = 0;
my %input = $AGI->ReadParse();
my $DONE = 0;
my $browser = LWP::UserAgent->new;

open(DBGF, ">/tmp/test/cidtest" . time());
print DBGF "AGI Environment Dump:\n";

foreach $i (sort keys %input)
{
    print DBGF " -- $i = $input{$i}\n";
}

my $cid = $AGI->get_variable('SAVEDCID');
my $chan = $input{channel};
my $ext = $AGI->get_variable('FROM_DID');
my $server_ipaddr = $AGI->get_variable('POSTIPADDR');

$chan =~ s/^SIP\///;
$chan =~ s/-.*$//;

print DBGF "CID:  $cid\n";
print DBGF "Chan: $chan\n";
print DBGF "Ext:  $ext\n";

$_ = $cid;
if (/unknown/)
{
  $url = "http://$server_ipaddr/communication/asterisk_call2.php?x=" .
	"<message><callername></callername><callerid></callerid>" .
	"<did>$ext</did><line>geek-$chan</line></message>";
}

$_ = $cid;
if (/"/)
{
  $cidnum = $cid;
  $cidnum =~ s/.*<(.*)>.*/$1/;

  $cidnam = $cid;
  $cidnam =~ s/.*"(.*)".*/$1/;
  $cidnam =~ s/ /%20/g;

  print DBGF "cidnum: =$cidnum=\n";
  print DBGF "cidnam: =$cidnam=\n";

  $url = "http://$server_ipaddr/communication/asterisk_call2.php?x=" .
	"<message><callername>$cidnam</callername>" .
	"<callerid>$cidnum</callerid>" .
	"<did>$ext</did><line>geek-$chan</line></message>";
}
else 
{
  $url = "http://$server_ipaddr/communication/asterisk_call2.php?x=" .
	"<message><callername></callername><callerid>$cid</callerid>" .
	"<did>$ext</did><line>geek-$chan</line></message>";
}

print DBGF "url =$url=\n";

$browser->timeout(10);
my $content = $browser->get($url);
print DBGF "main code - get(", $url, ") => \n";

if ($content->is_error) {
    print DBGF "ERROR!\n";
}
else
{
    print DBGF "Response:", $content->content, "\n\n";
}

# my $response = XMLin($content);
# print DBGF Dumper($response);

