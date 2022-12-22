# 100retro
This is a demonstration of how to dynamically build a liveItem podcast feed from a radio scheduler.

This script is meant to be an example of how one could generate a dynamic podcast feed of liveItem's for
something like a radio station.  In a working, production system you would clearly want this tied into your
program guide/scheduling software properly.  Here I've just hard coded the schedule so that things are more
clear.

This script should be run at the top of the hour by CRON or something else.  I cribbed a lot from my other
and tried to make this script mostly self contained.  But, there are still some external calls.  They are
mostly obvious in what they do so should be easy to replicate. Specifically, some of the S3 upload calls were
just too big and are easy to pull in from other places.

You'll need to set your s3 credentials and your mastodon app token in the globals section.

The FreePod library included is found here:  [https://github.com/daveajones/freepod](https://github.com/daveajones/freepod)