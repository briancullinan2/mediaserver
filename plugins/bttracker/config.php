<?php

include_once dirname(__FILE__) . '/../../include/common.php';

/* Tracker Configuration
 *
 *  This file provides configuration informatino for
 *  the tracker. The user-editable variables are at the top. It is
 *  recommended that you do not change the database settings (at the bottom)
 *  unless your database settings actually change, or this is your
 *  initial installation.
 */

// Maximum reannounce interval, in seconds. 1800 == 30 minutes
$GLOBALS["report_interval"] = 1800;

// Minimum reannounce interval. Optional. Also in seconds.
// 300 == 5 minutes
$GLOBALS["min_interval"] = 300;

// Number of peers to send in one request.
// Some logic will break if you set this to more than 300, so please
// don't do that. 100 is the most you should set anyway.
$GLOBALS["maxpeers"] = 50;

// If set to true, then the tracker will accept any and all
// torrents given to it. Not recommended, but available if you need it.
$GLOBALS["dynamic_torrents"] = false;

// If set to true, NAT checking will be performed.
// This may cause trouble with some providers, so it's
// off by default. And paranoid people with paranoid firewalls.
$GLOBALS["NAT"] = false;

// Persistent connections: true or false.
// Check with your webmaster to see if you're allowed to use these.
// Highly recommended, especially for higher loads, but generally
// not allowed unless it's a dedicated machine.
$GLOBALS["persist"] = false;

// Allow users to override ip= ?
// Enable this if you know people have a legit reason to use
// this function. Leave disabled otherwise.
$GLOBALS["ip_override"] = false;

// For heavily loaded trackers, set this to false. It will stop count the number
// of downloaded bytes and the speed of the torrent, but will significantly reduce
// the load.
$GLOBALS["countbytes"] = true;

// Table caches!
// Lowers the load on all systems, but takes up more disk space.
// You win some, you lose some. But since the load is the big problem,
// grab this.
//
// Warning! Enable this BEFORE making torrents, or else run makecache.php
// immediately, or else you'll be in deep trouble. The tables will lose
// sync and the database will be in a somewhat "stale" state.
$GLOBALS["peercaching"] = true;


// Username and password for the torrent adder.
// YOU MUST SET THESE!
$upload_username = ADMIN_USER;
$upload_password = ADMIN_PASS;


/////////// End of User Configuration ///////////

// These are usually filled in by install.php. 
// But if it fails to make a config.php for itself,
// you'll have to set these.

$dbhost = DB_SERVER;
$dbuser = DB_USER;
$dbpass = DB_PASS;
$database = DB_NAME;

