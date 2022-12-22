<?php

include_once "Podcast.php";
date_default_timezone_set('CET');

//##: Notes ------------------------------------------------------------------------------------------------------------
//----------------------------------------------------------------------------------------------------------------------
//
//    This script is meant to be an example of how one could generate a dynamic podcast feed of liveItem's for
//    something like a radio station.  In a working, production system you would clearly want this tied into your
//    program guide/scheduling software properly.  Here I've just hard coded the schedule so that things are more
//    clear.
//
//    This script should be run at the top of the hour by CRON or something else.  I cribbed a lot from my other
//    and tried to make this script mostly self contained.  But, there are still some external calls.  They are
//    mostly obvious in what they do so should be easy to replicate. Specifically, some of the S3 upload calls were
//    just too big and are easy to pull in from other places.
//
//    You'll need to set your s3 credentials and your mastodon app token in the globals section.
//
//    The FreePod library included is found here:  https://github.com/daveajones/freepod
//
//    Feel free to ask for help:  @dave@podcastindex.social
//
//----------------------------------------------------------------------------------------------------------------------


//##: Globals ----------------------------------------------------------------------------------------------------------
//----------------------------------------------------------------------------------------------------------------------
$lg_s3_key = "";
$lg_s3_secret = "";
$lg_s3_bucket = "feeds.podcastindex.org";
$lg_s3_filename = "100retro.xml";
$lg_mastodon_auth = "";
$lg_mastodon_host = "podcastindex.social";
$lg_mastodon_user = "100retro";
$lg_external_url = "https://feeds.podcastindex.org/100retro.xml";
$lg_title = "Welcome to 100% Retro Live 24/7";
$lg_chat = "https://kiwiirc.com/nextclient/irc.zeronode.net/#100retro";
$lg_link = "https://100percentretro.com/";
$lg_feed_art_url = "https://feeds.podcastindex.org/100retro.png";
$lg_live_guid = "1733C4C8-47BE-4EBA-A759-DBF6ED7ABF9B-";
$lg_live_stream_url = "https://play.adtonos.com/100-percent-retro";
$lg_podcast_guid = "27293ad7-c199-5047-8135-a864fb546492";
$lg_email_address = "info@100percentretro.com";
$lg_description = "100% Retro is a 24/7 Live Radio stream with hosts broadcasting live from all over the globe with 
                       music that makes you feel young again! Now live in the Podverse App with Live Chat and Boosts 
                       and on Dektop in Curiocaster.";
$lg_new_show_started = FALSE;
$lg_toot_text = "";


//##: Schedule ---------------------------------------------------------------------------------------------------------
//----------------------------------------------------------------------------------------------------------------------
//The top level index of this array is the day of the week (CRON numbering) and the second level is the hour (24-hour)
//----------------------------------------------------------------------------------------------------------------------
$schedule = [
    1 => [
        0 => [
            "title" => "Forever Young",
            "author" => "George Weinberg",
            "description" => "Broadcasting from Melbourne, Australia, the land Down Under, George Weinburg is your 
                              master of ceremonies in “Forever Young”. With historical news, celebrity gossip and the 
                              latest updates from your favorite retro stars, George starts your day the right way. 
                              Hosting the morning drive time in Australia and Oceania… while the sun sets in the UK, 
                              Europe and Africa… as New Yorker’s are on their way home from work and the West Coast of 
                              America is finishing lunch! George Weinburg keeps you “Forever Young.”",
            "location" => "Melbourne, AU",
            "image" => "https://100percentretro.com/wp-content/uploads/2022/09/George-Weinburg-Mon-Fri.jpg",
            "bio" => "https://100percentretro.com/schedule-monday-friday/",
            "duration" => 3
        ],
        3 => [
            "title" => "Celebration",
            "author" => "Bob Shaw",
            "description" => "From the City of Angels, Los Angeles, California… Bob Shaw plays your all-time favorites 
                              from the 60s, 70s and 80s. As the West Coast winds down its workday… the night sky shines 
                              over the UK, Europe and Africa… while Australia and Oceania are waking up to the classic 
                              sounds of Bob Shaw. Playing the biggest hit songs ever! News Flashbacks, Today In Music 
                              History, Celebrations of the Day and more… “Let’s celebrate good times! … wherever you 
                              are… and all across the planet!",
            "location" => "Los Angeles, US",
            "image" => "https://100percentretro.com/wp-content/uploads/2022/09/Bob-Shaw-Mon-Fri.jpg",
            "bio" => "https://100percentretro.com/schedule-monday-friday/",
            "duration" => 3
        ],
        6 => [
            "title" => "Angel of the Morning",
            "author" => "Joëlle Harper",
            "description" => "Your “Angel Of The Morning” in the UK, Europe, Africa and the Middle East. Joëlle Harper, 
                              from Beirut, Lebanon and straight into the land of memories: 100% RETRO. Joëlle is your 
                              shining breakfast star! Waking you up across the Atlantic, while North and South America 
                              transitions from day to night. With the brightest smile and the best taste in classic 
                              music, Joëlle wakes you up in the East, as the sun sets in the West.",
            "location" => "Beirut, LB",
            "image" => "https://100percentretro.com/wp-content/uploads/2022/07/Joelle-Harper-Mon-Fri.jpg",
            "bio" => "https://100percentretro.com/schedule-monday-friday/",
            "duration" => 2
        ],
        8 => [
            "title" => "Beautiful Noise",
            "author" => "Dan Harper",
            "description" => "Dan Harper hosts two flavors of the same show. For listeners in the UK, Europe, Africa 
                              and the Middle East, we call it “The Big Retro Breakfast Show” while listeners in North 
                              and South America know it as “The Big Retro Late Night Show.” Dan is an early bird who 
                              produces Joëlle’s show (06:00-08:00 CET) and receives her editorial assistance with his 
                              program. Together, they’re 100% RETRO’s morning or evening power team. Joëlle and Dan will 
                              inspire you to start or finish your day the right way. Let’s call it… a beautiful noise!",
            "location" => "Beirut, LB",
            "image" => "https://100percentretro.com/wp-content/uploads/2022/07/Dan-Harper-Mon-Fri.jpg",
            "bio" => "https://100percentretro.com/schedule-monday-friday/",
            "duration" => 2
        ],
        10 => [
            "title" => "Rewind",
            "author" => "Steve Penk",
            "description" => "From mid-morning in the UK, Europe, Africa and the Middle East, to the early morning dawn 
                              in North and South America… broadcasting from London, UK… Steve Penk hosts his daily 
                              REWIND show on 100% RETRO. You’ll hear candid jokes, controversial talks and Steve’s 
                              unique brand of entertainment. Join us for your daily dose of music, news and artist 
                              interviews that you won’t hear anywhere else. No matter where you are in the world, Steve 
                              will be there to wake you up and keep you up!",
            "location" => "London, UK",
            "image" => "https://100percentretro.com/wp-content/uploads/2022/07/Steve-Penk-Mon-Fri.jpg",
            "bio" => "https://100percentretro.com/schedule-monday-friday/",
            "duration" => 1
        ],
        11 => [
            "title" => "Let the Music Play",
            "author" => "Polly Winder",
            "description" => "Polly Winder plays your favorite songs in “Let The Music Play”. You select the songs and 
                              we go back in time. WhatsApp, facebook or email your favorite classics from the past... 
                              and let us know where you were when your song was hitting the charts. From the UK and 
                              broadcasting worldwide, Polly serves up your daily taste of retro before another lady 
                              (Nádine) takes over in Johanneburg! Send us your retro requests from all across the 
                              planet and “Let The Music Play” with Polly Winder.",
            "location" => "North England, UK",
            "image" => "https://100percentretro.com/wp-content/uploads/2022/07/Polly-Winder-Mon-Fri.jpg",
            "bio" => "https://100percentretro.com/schedule-monday-friday/",
            "duration" => 1
        ],
        12 => [
            "title" => "Memories",
            "author" => "Nadine",
            "description" => "“Memories”… your flashback to the past! Historical events, famous happenings, weddings, 
                              deaths, celebrity gossip. Broadcasting from Johannesburg, South Africa, Nádine tells all 
                              in a 3-hour appointment with the past. Memories and moments in time. What was the No.1 
                              song on the day you were born? What happened today in music history? We’ll bring you all 
                              the facts and figures. Plus, the most important news from yesterday. Nádine with 
                              “Memories”… every weekday on 100% RETRO.",
            "location" => "Johannesberg, SA",
            "image" => "https://100percentretro.com/wp-content/uploads/2022/07/Nadine-Mon-Fri.jpg",
            "bio" => "https://100percentretro.com/schedule-monday-friday/",
            "duration" => 3
        ],
        15 => [
            "title" => "A Real Good Feeling",
            "author" => "Soroya Torrens",
            "description" => "Soraya Torrens plays your favorite retro requests in “A Real Good Feeling”. She’s our 
                              spokesperson representing 100% RETRO all over the planet. Soraya is a world traveller 
                              and global citizen. Let Soraya know what you want to hear and she’ll go back in time to 
                              spin your all-time classics! Broadcasting from Tel Aviv, Israel one week and from São 
                              Paulo, Brazil the next, she’s our global brand ambassador. Soraya brings our listeners 
                              joy and happiness every day with “A Real Good Feeling.”",
            "location" => "São Paulo, BR",
            "image" => "https://100percentretro.com/wp-content/uploads/2022/07/Soroya-Torrens-Mon-Fri.jpg",
            "bio" => "https://100percentretro.com/schedule-monday-friday/",
            "duration" => 1
        ],
        16 => [
            "title" => "Drive",
            "author" => "Andy and Steph",
            "description" => "Who’s gonna drive you home in the UK, Europe, Africa and the Middle East? And who’s 
                              serving up lunch in North and South America? Broadcasting from the UK and across the 
                              planet, Andy G and Steph Langford are your hosts in “Drive”… a daily request show. Tell 
                              them what you want to hear and they’ll bring you back in time. Spinning your favorite 
                              retro requests and revisiting the most memorable moments in history. They bring fun and 
                              fabulous to the afternoon drive time. Every weekday on 100% RETRO.",
            "location" => "North England, UK",
            "image" => "https://100percentretro.com/wp-content/uploads/2022/09/Steph-Andy-Mon-Fri.jpg",
            "bio" => "https://100percentretro.com/schedule-monday-friday/",
            "duration" => 3
        ],
        19 => [
            "title" => "I Can Hear Music",
            "author" => "Gerard Teuma",
            "description" => "Broadcasting from The Rock of Gibraltar, Gerard Teuma takes you on his European Tour. 
                              Join us every weekday for “I Can Hear Music,” as Gerard plays timeless classics from 
                              France, Greece, Belgium, and the Netherlands. Hidden gems that reached the top of the 
                              national charts! Relive the most beautiful music and memories from Germany, 
                              Czech Republic, Portugal, Russia and so many more countries… Experience the biggest 
                              European hits every weekday with Gerard Teuma, only on 100% RETRO.",
            "location" => "The Rock of Gibraltar",
            "image" => "https://100percentretro.com/wp-content/uploads/2022/09/Gerard-Teuma-Mon-Fri.jpg",
            "bio" => "https://100percentretro.com/schedule-monday-friday/",
            "duration" => 2
        ],
        21 => [
            "title" => "Driver's Seat",
            "author" => "Rick Rooster",
            "description" => "It’s Rick Rooster in the “Driver’s Seat”. Broadcasting from Toronto, Canada and across 
                              the planet! He’s your radio pilot during the North American drive time. Rick takes a bite 
                              out of traffic and spins the best in 100% RETRO classics. Tune in every weekday for the 
                              soundtrack to your rush hour, getting you home safe and sound. He navigates traffic on 
                              one side of the Atlantic and drinks a nightcap with you on the other. We’re going old 
                              school with Rick Rooster in the “Driver’s Seat”… your retro radio on the road.",
            "location" => "Toronto, CA",
            "image" => "https://100percentretro.com/wp-content/uploads/2022/09/Rick-Rooster-Mon-Fri.jpg",
            "bio" => "https://100percentretro.com/schedule-monday-friday/",
            "duration" => 3
        ]
    ],
    2 => [
        0 => [
            "title" => "Forever Young",
            "author" => "George Weinberg",
            "description" => "Broadcasting from Melbourne, Australia, the land Down Under, George Weinburg is your 
                              master of ceremonies in “Forever Young”. With historical news, celebrity gossip and the 
                              latest updates from your favorite retro stars, George starts your day the right way. 
                              Hosting the morning drive time in Australia and Oceania… while the sun sets in the UK, 
                              Europe and Africa… as New Yorker’s are on their way home from work and the West Coast of 
                              America is finishing lunch! George Weinburg keeps you “Forever Young.”",
            "location" => "Melbourne, AU",
            "image" => "https://100percentretro.com/wp-content/uploads/2022/09/George-Weinburg-Mon-Fri.jpg",
            "bio" => "https://100percentretro.com/schedule-monday-friday/",
            "duration" => 3
        ],
        3 => [
            "title" => "Celebration",
            "author" => "Bob Shaw",
            "description" => "From the City of Angels, Los Angeles, California… Bob Shaw plays your all-time favorites 
                              from the 60s, 70s and 80s. As the West Coast winds down its workday… the night sky shines 
                              over the UK, Europe and Africa… while Australia and Oceania are waking up to the classic 
                              sounds of Bob Shaw. Playing the biggest hit songs ever! News Flashbacks, Today In Music 
                              History, Celebrations of the Day and more… “Let’s celebrate good times! … wherever you 
                              are… and all across the planet!",
            "location" => "Los Angeles, US",
            "image" => "https://100percentretro.com/wp-content/uploads/2022/09/Bob-Shaw-Mon-Fri.jpg",
            "bio" => "https://100percentretro.com/schedule-monday-friday/",
            "duration" => 3
        ],
        6 => [
            "title" => "Angel of the Morning",
            "author" => "Joëlle Harper",
            "description" => "Your “Angel Of The Morning” in the UK, Europe, Africa and the Middle East. Joëlle Harper, 
                              from Beirut, Lebanon and straight into the land of memories: 100% RETRO. Joëlle is your 
                              shining breakfast star! Waking you up across the Atlantic, while North and South America 
                              transitions from day to night. With the brightest smile and the best taste in classic 
                              music, Joëlle wakes you up in the East, as the sun sets in the West.",
            "location" => "Beirut, LB",
            "image" => "https://100percentretro.com/wp-content/uploads/2022/07/Joelle-Harper-Mon-Fri.jpg",
            "bio" => "https://100percentretro.com/schedule-monday-friday/",
            "duration" => 2
        ],
        8 => [
            "title" => "Beautiful Noise",
            "author" => "Dan Harper",
            "description" => "Dan Harper hosts two flavors of the same show. For listeners in the UK, Europe, Africa 
                              and the Middle East, we call it “The Big Retro Breakfast Show” while listeners in North 
                              and South America know it as “The Big Retro Late Night Show.” Dan is an early bird who 
                              produces Joëlle’s show (06:00-08:00 CET) and receives her editorial assistance with his 
                              program. Together, they’re 100% RETRO’s morning or evening power team. Joëlle and Dan will 
                              inspire you to start or finish your day the right way. Let’s call it… a beautiful noise!",
            "location" => "Beirut, LB",
            "image" => "https://100percentretro.com/wp-content/uploads/2022/07/Dan-Harper-Mon-Fri.jpg",
            "bio" => "https://100percentretro.com/schedule-monday-friday/",
            "duration" => 2
        ],
        10 => [
            "title" => "Rewind",
            "author" => "Steve Penk",
            "description" => "From mid-morning in the UK, Europe, Africa and the Middle East, to the early morning dawn 
                              in North and South America… broadcasting from London, UK… Steve Penk hosts his daily 
                              REWIND show on 100% RETRO. You’ll hear candid jokes, controversial talks and Steve’s 
                              unique brand of entertainment. Join us for your daily dose of music, news and artist 
                              interviews that you won’t hear anywhere else. No matter where you are in the world, Steve 
                              will be there to wake you up and keep you up!",
            "location" => "London, UK",
            "image" => "https://100percentretro.com/wp-content/uploads/2022/07/Steve-Penk-Mon-Fri.jpg",
            "bio" => "https://100percentretro.com/schedule-monday-friday/",
            "duration" => 1
        ],
        11 => [
            "title" => "Let the Music Play",
            "author" => "Polly Winder",
            "description" => "Polly Winder plays your favorite songs in “Let The Music Play”. You select the songs and 
                              we go back in time. WhatsApp, facebook or email your favorite classics from the past... 
                              and let us know where you were when your song was hitting the charts. From the UK and 
                              broadcasting worldwide, Polly serves up your daily taste of retro before another lady 
                              (Nádine) takes over in Johanneburg! Send us your retro requests from all across the 
                              planet and “Let The Music Play” with Polly Winder.",
            "location" => "North England, UK",
            "image" => "https://100percentretro.com/wp-content/uploads/2022/07/Polly-Winder-Mon-Fri.jpg",
            "bio" => "https://100percentretro.com/schedule-monday-friday/",
            "duration" => 1
        ],
        12 => [
            "title" => "Memories",
            "author" => "Nadine",
            "description" => "“Memories”… your flashback to the past! Historical events, famous happenings, weddings, 
                              deaths, celebrity gossip. Broadcasting from Johannesburg, South Africa, Nádine tells all 
                              in a 3-hour appointment with the past. Memories and moments in time. What was the No.1 
                              song on the day you were born? What happened today in music history? We’ll bring you all 
                              the facts and figures. Plus, the most important news from yesterday. Nádine with 
                              “Memories”… every weekday on 100% RETRO.",
            "location" => "Johannesberg, SA",
            "image" => "https://100percentretro.com/wp-content/uploads/2022/07/Nadine-Mon-Fri.jpg",
            "bio" => "https://100percentretro.com/schedule-monday-friday/",
            "duration" => 3
        ],
        15 => [
            "title" => "A Real Good Feeling",
            "author" => "Soroya Torrens",
            "description" => "Soraya Torrens plays your favorite retro requests in “A Real Good Feeling”. She’s our 
                              spokesperson representing 100% RETRO all over the planet. Soraya is a world traveller 
                              and global citizen. Let Soraya know what you want to hear and she’ll go back in time to 
                              spin your all-time classics! Broadcasting from Tel Aviv, Israel one week and from São 
                              Paulo, Brazil the next, she’s our global brand ambassador. Soraya brings our listeners 
                              joy and happiness every day with “A Real Good Feeling.”",
            "location" => "São Paulo, BR",
            "image" => "https://100percentretro.com/wp-content/uploads/2022/07/Soroya-Torrens-Mon-Fri.jpg",
            "bio" => "https://100percentretro.com/schedule-monday-friday/",
            "duration" => 1
        ],
        16 => [
            "title" => "Drive",
            "author" => "Andy and Steph",
            "description" => "Who’s gonna drive you home in the UK, Europe, Africa and the Middle East? And who’s 
                              serving up lunch in North and South America? Broadcasting from the UK and across the 
                              planet, Andy G and Steph Langford are your hosts in “Drive”… a daily request show. Tell 
                              them what you want to hear and they’ll bring you back in time. Spinning your favorite 
                              retro requests and revisiting the most memorable moments in history. They bring fun and 
                              fabulous to the afternoon drive time. Every weekday on 100% RETRO.",
            "location" => "North England, UK",
            "image" => "https://100percentretro.com/wp-content/uploads/2022/09/Steph-Andy-Mon-Fri.jpg",
            "bio" => "https://100percentretro.com/schedule-monday-friday/",
            "duration" => 3
        ],
        19 => [
            "title" => "I Can Hear Music",
            "author" => "Gerard Teuma",
            "description" => "Broadcasting from The Rock of Gibraltar, Gerard Teuma takes you on his European Tour. 
                              Join us every weekday for “I Can Hear Music,” as Gerard plays timeless classics from 
                              France, Greece, Belgium, and the Netherlands. Hidden gems that reached the top of the 
                              national charts! Relive the most beautiful music and memories from Germany, 
                              Czech Republic, Portugal, Russia and so many more countries… Experience the biggest 
                              European hits every weekday with Gerard Teuma, only on 100% RETRO.",
            "location" => "The Rock of Gibraltar",
            "image" => "https://100percentretro.com/wp-content/uploads/2022/09/Gerard-Teuma-Mon-Fri.jpg",
            "bio" => "https://100percentretro.com/schedule-monday-friday/",
            "duration" => 2
        ],
        21 => [
            "title" => "Driver's Seat",
            "author" => "Rick Rooster",
            "description" => "It’s Rick Rooster in the “Driver’s Seat”. Broadcasting from Toronto, Canada and across 
                              the planet! He’s your radio pilot during the North American drive time. Rick takes a bite 
                              out of traffic and spins the best in 100% RETRO classics. Tune in every weekday for the 
                              soundtrack to your rush hour, getting you home safe and sound. He navigates traffic on 
                              one side of the Atlantic and drinks a nightcap with you on the other. We’re going old 
                              school with Rick Rooster in the “Driver’s Seat”… your retro radio on the road.",
            "location" => "Toronto, CA",
            "image" => "https://100percentretro.com/wp-content/uploads/2022/09/Rick-Rooster-Mon-Fri.jpg",
            "bio" => "https://100percentretro.com/schedule-monday-friday/",
            "duration" => 3
        ]
    ],
    3 => [
        0 => [
            "title" => "Forever Young",
            "author" => "George Weinberg",
            "description" => "Broadcasting from Melbourne, Australia, the land Down Under, George Weinburg is your 
                              master of ceremonies in “Forever Young”. With historical news, celebrity gossip and the 
                              latest updates from your favorite retro stars, George starts your day the right way. 
                              Hosting the morning drive time in Australia and Oceania… while the sun sets in the UK, 
                              Europe and Africa… as New Yorker’s are on their way home from work and the West Coast of 
                              America is finishing lunch! George Weinburg keeps you “Forever Young.”",
            "location" => "Melbourne, AU",
            "image" => "https://100percentretro.com/wp-content/uploads/2022/09/George-Weinburg-Mon-Fri.jpg",
            "bio" => "https://100percentretro.com/schedule-monday-friday/",
            "duration" => 3
        ],
        3 => [
            "title" => "Celebration",
            "author" => "Bob Shaw",
            "description" => "From the City of Angels, Los Angeles, California… Bob Shaw plays your all-time favorites 
                              from the 60s, 70s and 80s. As the West Coast winds down its workday… the night sky shines 
                              over the UK, Europe and Africa… while Australia and Oceania are waking up to the classic 
                              sounds of Bob Shaw. Playing the biggest hit songs ever! News Flashbacks, Today In Music 
                              History, Celebrations of the Day and more… “Let’s celebrate good times! … wherever you 
                              are… and all across the planet!",
            "location" => "Los Angeles, US",
            "image" => "https://100percentretro.com/wp-content/uploads/2022/09/Bob-Shaw-Mon-Fri.jpg",
            "bio" => "https://100percentretro.com/schedule-monday-friday/",
            "duration" => 3
        ],
        6 => [
            "title" => "Angel of the Morning",
            "author" => "Joëlle Harper",
            "description" => "Your “Angel Of The Morning” in the UK, Europe, Africa and the Middle East. Joëlle Harper, 
                              from Beirut, Lebanon and straight into the land of memories: 100% RETRO. Joëlle is your 
                              shining breakfast star! Waking you up across the Atlantic, while North and South America 
                              transitions from day to night. With the brightest smile and the best taste in classic 
                              music, Joëlle wakes you up in the East, as the sun sets in the West.",
            "location" => "Beirut, LB",
            "image" => "https://100percentretro.com/wp-content/uploads/2022/07/Joelle-Harper-Mon-Fri.jpg",
            "bio" => "https://100percentretro.com/schedule-monday-friday/",
            "duration" => 2
        ],
        8 => [
            "title" => "Beautiful Noise",
            "author" => "Dan Harper",
            "description" => "Dan Harper hosts two flavors of the same show. For listeners in the UK, Europe, Africa 
                              and the Middle East, we call it “The Big Retro Breakfast Show” while listeners in North 
                              and South America know it as “The Big Retro Late Night Show.” Dan is an early bird who 
                              produces Joëlle’s show (06:00-08:00 CET) and receives her editorial assistance with his 
                              program. Together, they’re 100% RETRO’s morning or evening power team. Joëlle and Dan will 
                              inspire you to start or finish your day the right way. Let’s call it… a beautiful noise!",
            "location" => "Beirut, LB",
            "image" => "https://100percentretro.com/wp-content/uploads/2022/07/Dan-Harper-Mon-Fri.jpg",
            "bio" => "https://100percentretro.com/schedule-monday-friday/",
            "duration" => 2
        ],
        10 => [
            "title" => "Rewind",
            "author" => "Steve Penk",
            "description" => "From mid-morning in the UK, Europe, Africa and the Middle East, to the early morning dawn 
                              in North and South America… broadcasting from London, UK… Steve Penk hosts his daily 
                              REWIND show on 100% RETRO. You’ll hear candid jokes, controversial talks and Steve’s 
                              unique brand of entertainment. Join us for your daily dose of music, news and artist 
                              interviews that you won’t hear anywhere else. No matter where you are in the world, Steve 
                              will be there to wake you up and keep you up!",
            "location" => "London, UK",
            "image" => "https://100percentretro.com/wp-content/uploads/2022/07/Steve-Penk-Mon-Fri.jpg",
            "bio" => "https://100percentretro.com/schedule-monday-friday/",
            "duration" => 1
        ],
        11 => [
            "title" => "Let the Music Play",
            "author" => "Polly Winder",
            "description" => "Polly Winder plays your favorite songs in “Let The Music Play”. You select the songs and 
                              we go back in time. WhatsApp, facebook or email your favorite classics from the past... 
                              and let us know where you were when your song was hitting the charts. From the UK and 
                              broadcasting worldwide, Polly serves up your daily taste of retro before another lady 
                              (Nádine) takes over in Johanneburg! Send us your retro requests from all across the 
                              planet and “Let The Music Play” with Polly Winder.",
            "location" => "North England, UK",
            "image" => "https://100percentretro.com/wp-content/uploads/2022/07/Polly-Winder-Mon-Fri.jpg",
            "bio" => "https://100percentretro.com/schedule-monday-friday/",
            "duration" => 1
        ],
        12 => [
            "title" => "Memories",
            "author" => "Nadine",
            "description" => "“Memories”… your flashback to the past! Historical events, famous happenings, weddings, 
                              deaths, celebrity gossip. Broadcasting from Johannesburg, South Africa, Nádine tells all 
                              in a 3-hour appointment with the past. Memories and moments in time. What was the No.1 
                              song on the day you were born? What happened today in music history? We’ll bring you all 
                              the facts and figures. Plus, the most important news from yesterday. Nádine with 
                              “Memories”… every weekday on 100% RETRO.",
            "location" => "Johannesberg, SA",
            "image" => "https://100percentretro.com/wp-content/uploads/2022/07/Nadine-Mon-Fri.jpg",
            "bio" => "https://100percentretro.com/schedule-monday-friday/",
            "duration" => 3
        ],
        15 => [
            "title" => "A Real Good Feeling",
            "author" => "Soroya Torrens",
            "description" => "Soraya Torrens plays your favorite retro requests in “A Real Good Feeling”. She’s our 
                              spokesperson representing 100% RETRO all over the planet. Soraya is a world traveller 
                              and global citizen. Let Soraya know what you want to hear and she’ll go back in time to 
                              spin your all-time classics! Broadcasting from Tel Aviv, Israel one week and from São 
                              Paulo, Brazil the next, she’s our global brand ambassador. Soraya brings our listeners 
                              joy and happiness every day with “A Real Good Feeling.”",
            "location" => "São Paulo, BR",
            "image" => "https://100percentretro.com/wp-content/uploads/2022/07/Soroya-Torrens-Mon-Fri.jpg",
            "bio" => "https://100percentretro.com/schedule-monday-friday/",
            "duration" => 1
        ],
        16 => [
            "title" => "Drive",
            "author" => "Andy and Steph",
            "description" => "Who’s gonna drive you home in the UK, Europe, Africa and the Middle East? And who’s 
                              serving up lunch in North and South America? Broadcasting from the UK and across the 
                              planet, Andy G and Steph Langford are your hosts in “Drive”… a daily request show. Tell 
                              them what you want to hear and they’ll bring you back in time. Spinning your favorite 
                              retro requests and revisiting the most memorable moments in history. They bring fun and 
                              fabulous to the afternoon drive time. Every weekday on 100% RETRO.",
            "location" => "North England, UK",
            "image" => "https://100percentretro.com/wp-content/uploads/2022/09/Steph-Andy-Mon-Fri.jpg",
            "bio" => "https://100percentretro.com/schedule-monday-friday/",
            "duration" => 3
        ],
        19 => [
            "title" => "I Can Hear Music",
            "author" => "Gerard Teuma",
            "description" => "Broadcasting from The Rock of Gibraltar, Gerard Teuma takes you on his European Tour. 
                              Join us every weekday for “I Can Hear Music,” as Gerard plays timeless classics from 
                              France, Greece, Belgium, and the Netherlands. Hidden gems that reached the top of the 
                              national charts! Relive the most beautiful music and memories from Germany, 
                              Czech Republic, Portugal, Russia and so many more countries… Experience the biggest 
                              European hits every weekday with Gerard Teuma, only on 100% RETRO.",
            "location" => "The Rock of Gibraltar",
            "image" => "https://100percentretro.com/wp-content/uploads/2022/09/Gerard-Teuma-Mon-Fri.jpg",
            "bio" => "https://100percentretro.com/schedule-monday-friday/",
            "duration" => 2
        ],
        21 => [
            "title" => "Driver's Seat",
            "author" => "Rick Rooster",
            "description" => "It’s Rick Rooster in the “Driver’s Seat”. Broadcasting from Toronto, Canada and across 
                              the planet! He’s your radio pilot during the North American drive time. Rick takes a bite 
                              out of traffic and spins the best in 100% RETRO classics. Tune in every weekday for the 
                              soundtrack to your rush hour, getting you home safe and sound. He navigates traffic on 
                              one side of the Atlantic and drinks a nightcap with you on the other. We’re going old 
                              school with Rick Rooster in the “Driver’s Seat”… your retro radio on the road.",
            "location" => "Toronto, CA",
            "image" => "https://100percentretro.com/wp-content/uploads/2022/09/Rick-Rooster-Mon-Fri.jpg",
            "bio" => "https://100percentretro.com/schedule-monday-friday/",
            "duration" => 3
        ]
    ],
    4 => [
        0 => [
            "title" => "Forever Young",
            "author" => "George Weinberg",
            "description" => "Broadcasting from Melbourne, Australia, the land Down Under, George Weinburg is your 
                              master of ceremonies in “Forever Young”. With historical news, celebrity gossip and the 
                              latest updates from your favorite retro stars, George starts your day the right way. 
                              Hosting the morning drive time in Australia and Oceania… while the sun sets in the UK, 
                              Europe and Africa… as New Yorker’s are on their way home from work and the West Coast of 
                              America is finishing lunch! George Weinburg keeps you “Forever Young.”",
            "location" => "Melbourne, AU",
            "image" => "https://100percentretro.com/wp-content/uploads/2022/09/George-Weinburg-Mon-Fri.jpg",
            "bio" => "https://100percentretro.com/schedule-monday-friday/",
            "duration" => 3
        ],
        3 => [
            "title" => "Celebration",
            "author" => "Bob Shaw",
            "description" => "From the City of Angels, Los Angeles, California… Bob Shaw plays your all-time favorites 
                              from the 60s, 70s and 80s. As the West Coast winds down its workday… the night sky shines 
                              over the UK, Europe and Africa… while Australia and Oceania are waking up to the classic 
                              sounds of Bob Shaw. Playing the biggest hit songs ever! News Flashbacks, Today In Music 
                              History, Celebrations of the Day and more… “Let’s celebrate good times! … wherever you 
                              are… and all across the planet!",
            "location" => "Los Angeles, US",
            "image" => "https://100percentretro.com/wp-content/uploads/2022/09/Bob-Shaw-Mon-Fri.jpg",
            "bio" => "https://100percentretro.com/schedule-monday-friday/",
            "duration" => 3
        ],
        6 => [
            "title" => "Angel of the Morning",
            "author" => "Joëlle Harper",
            "description" => "Your “Angel Of The Morning” in the UK, Europe, Africa and the Middle East. Joëlle Harper, 
                              from Beirut, Lebanon and straight into the land of memories: 100% RETRO. Joëlle is your 
                              shining breakfast star! Waking you up across the Atlantic, while North and South America 
                              transitions from day to night. With the brightest smile and the best taste in classic 
                              music, Joëlle wakes you up in the East, as the sun sets in the West.",
            "location" => "Beirut, LB",
            "image" => "https://100percentretro.com/wp-content/uploads/2022/07/Joelle-Harper-Mon-Fri.jpg",
            "bio" => "https://100percentretro.com/schedule-monday-friday/",
            "duration" => 2
        ],
        8 => [
            "title" => "Beautiful Noise",
            "author" => "Dan Harper",
            "description" => "Dan Harper hosts two flavors of the same show. For listeners in the UK, Europe, Africa 
                              and the Middle East, we call it “The Big Retro Breakfast Show” while listeners in North 
                              and South America know it as “The Big Retro Late Night Show.” Dan is an early bird who 
                              produces Joëlle’s show (06:00-08:00 CET) and receives her editorial assistance with his 
                              program. Together, they’re 100% RETRO’s morning or evening power team. Joëlle and Dan will 
                              inspire you to start or finish your day the right way. Let’s call it… a beautiful noise!",
            "location" => "Beirut, LB",
            "image" => "https://100percentretro.com/wp-content/uploads/2022/07/Dan-Harper-Mon-Fri.jpg",
            "bio" => "https://100percentretro.com/schedule-monday-friday/",
            "duration" => 2
        ],
        10 => [
            "title" => "Rewind",
            "author" => "Steve Penk",
            "description" => "From mid-morning in the UK, Europe, Africa and the Middle East, to the early morning dawn 
                              in North and South America… broadcasting from London, UK… Steve Penk hosts his daily 
                              REWIND show on 100% RETRO. You’ll hear candid jokes, controversial talks and Steve’s 
                              unique brand of entertainment. Join us for your daily dose of music, news and artist 
                              interviews that you won’t hear anywhere else. No matter where you are in the world, Steve 
                              will be there to wake you up and keep you up!",
            "location" => "London, UK",
            "image" => "https://100percentretro.com/wp-content/uploads/2022/07/Steve-Penk-Mon-Fri.jpg",
            "bio" => "https://100percentretro.com/schedule-monday-friday/",
            "duration" => 1
        ],
        11 => [
            "title" => "Let the Music Play",
            "author" => "Polly Winder",
            "description" => "Polly Winder plays your favorite songs in “Let The Music Play”. You select the songs and 
                              we go back in time. WhatsApp, facebook or email your favorite classics from the past... 
                              and let us know where you were when your song was hitting the charts. From the UK and 
                              broadcasting worldwide, Polly serves up your daily taste of retro before another lady 
                              (Nádine) takes over in Johanneburg! Send us your retro requests from all across the 
                              planet and “Let The Music Play” with Polly Winder.",
            "location" => "North England, UK",
            "image" => "https://100percentretro.com/wp-content/uploads/2022/07/Polly-Winder-Mon-Fri.jpg",
            "bio" => "https://100percentretro.com/schedule-monday-friday/",
            "duration" => 1
        ],
        12 => [
            "title" => "Memories",
            "author" => "Nadine",
            "description" => "“Memories”… your flashback to the past! Historical events, famous happenings, weddings, 
                              deaths, celebrity gossip. Broadcasting from Johannesburg, South Africa, Nádine tells all 
                              in a 3-hour appointment with the past. Memories and moments in time. What was the No.1 
                              song on the day you were born? What happened today in music history? We’ll bring you all 
                              the facts and figures. Plus, the most important news from yesterday. Nádine with 
                              “Memories”… every weekday on 100% RETRO.",
            "location" => "Johannesberg, SA",
            "image" => "https://100percentretro.com/wp-content/uploads/2022/07/Nadine-Mon-Fri.jpg",
            "bio" => "https://100percentretro.com/schedule-monday-friday/",
            "duration" => 3
        ],
        15 => [
            "title" => "A Real Good Feeling",
            "author" => "Soroya Torrens",
            "description" => "Soraya Torrens plays your favorite retro requests in “A Real Good Feeling”. She’s our 
                              spokesperson representing 100% RETRO all over the planet. Soraya is a world traveller 
                              and global citizen. Let Soraya know what you want to hear and she’ll go back in time to 
                              spin your all-time classics! Broadcasting from Tel Aviv, Israel one week and from São 
                              Paulo, Brazil the next, she’s our global brand ambassador. Soraya brings our listeners 
                              joy and happiness every day with “A Real Good Feeling.”",
            "location" => "São Paulo, BR",
            "image" => "https://100percentretro.com/wp-content/uploads/2022/07/Soroya-Torrens-Mon-Fri.jpg",
            "bio" => "https://100percentretro.com/schedule-monday-friday/",
            "duration" => 1
        ],
        16 => [
            "title" => "Drive",
            "author" => "Andy and Steph",
            "description" => "Who’s gonna drive you home in the UK, Europe, Africa and the Middle East? And who’s 
                              serving up lunch in North and South America? Broadcasting from the UK and across the 
                              planet, Andy G and Steph Langford are your hosts in “Drive”… a daily request show. Tell 
                              them what you want to hear and they’ll bring you back in time. Spinning your favorite 
                              retro requests and revisiting the most memorable moments in history. They bring fun and 
                              fabulous to the afternoon drive time. Every weekday on 100% RETRO.",
            "location" => "North England, UK",
            "image" => "https://100percentretro.com/wp-content/uploads/2022/09/Steph-Andy-Mon-Fri.jpg",
            "bio" => "https://100percentretro.com/schedule-monday-friday/",
            "duration" => 3
        ],
        19 => [
            "title" => "I Can Hear Music",
            "author" => "Gerard Teuma",
            "description" => "Broadcasting from The Rock of Gibraltar, Gerard Teuma takes you on his European Tour. 
                              Join us every weekday for “I Can Hear Music,” as Gerard plays timeless classics from 
                              France, Greece, Belgium, and the Netherlands. Hidden gems that reached the top of the 
                              national charts! Relive the most beautiful music and memories from Germany, 
                              Czech Republic, Portugal, Russia and so many more countries… Experience the biggest 
                              European hits every weekday with Gerard Teuma, only on 100% RETRO.",
            "location" => "The Rock of Gibraltar",
            "image" => "https://100percentretro.com/wp-content/uploads/2022/09/Gerard-Teuma-Mon-Fri.jpg",
            "bio" => "https://100percentretro.com/schedule-monday-friday/",
            "duration" => 2
        ],
        21 => [
            "title" => "Driver's Seat",
            "author" => "Rick Rooster",
            "description" => "It’s Rick Rooster in the “Driver’s Seat”. Broadcasting from Toronto, Canada and across 
                              the planet! He’s your radio pilot during the North American drive time. Rick takes a bite 
                              out of traffic and spins the best in 100% RETRO classics. Tune in every weekday for the 
                              soundtrack to your rush hour, getting you home safe and sound. He navigates traffic on 
                              one side of the Atlantic and drinks a nightcap with you on the other. We’re going old 
                              school with Rick Rooster in the “Driver’s Seat”… your retro radio on the road.",
            "location" => "Toronto, CA",
            "image" => "https://100percentretro.com/wp-content/uploads/2022/09/Rick-Rooster-Mon-Fri.jpg",
            "bio" => "https://100percentretro.com/schedule-monday-friday/",
            "duration" => 3
        ]
    ],
    5 => [
        0 => [
            "title" => "Forever Young",
            "author" => "George Weinberg",
            "description" => "Broadcasting from Melbourne, Australia, the land Down Under, George Weinburg is your 
                              master of ceremonies in “Forever Young”. With historical news, celebrity gossip and the 
                              latest updates from your favorite retro stars, George starts your day the right way. 
                              Hosting the morning drive time in Australia and Oceania… while the sun sets in the UK, 
                              Europe and Africa… as New Yorker’s are on their way home from work and the West Coast of 
                              America is finishing lunch! George Weinburg keeps you “Forever Young.”",
            "location" => "Melbourne, AU",
            "image" => "https://100percentretro.com/wp-content/uploads/2022/09/George-Weinburg-Mon-Fri.jpg",
            "bio" => "https://100percentretro.com/schedule-monday-friday/",
            "duration" => 3
        ],
        3 => [
            "title" => "Celebration",
            "author" => "Bob Shaw",
            "description" => "From the City of Angels, Los Angeles, California… Bob Shaw plays your all-time favorites 
                              from the 60s, 70s and 80s. As the West Coast winds down its workday… the night sky shines 
                              over the UK, Europe and Africa… while Australia and Oceania are waking up to the classic 
                              sounds of Bob Shaw. Playing the biggest hit songs ever! News Flashbacks, Today In Music 
                              History, Celebrations of the Day and more… “Let’s celebrate good times! … wherever you 
                              are… and all across the planet!",
            "location" => "Los Angeles, US",
            "image" => "https://100percentretro.com/wp-content/uploads/2022/09/Bob-Shaw-Mon-Fri.jpg",
            "bio" => "https://100percentretro.com/schedule-monday-friday/",
            "duration" => 3
        ],
        6 => [
            "title" => "Angel of the Morning",
            "author" => "Joëlle Harper",
            "description" => "Your “Angel Of The Morning” in the UK, Europe, Africa and the Middle East. Joëlle Harper, 
                              from Beirut, Lebanon and straight into the land of memories: 100% RETRO. Joëlle is your 
                              shining breakfast star! Waking you up across the Atlantic, while North and South America 
                              transitions from day to night. With the brightest smile and the best taste in classic 
                              music, Joëlle wakes you up in the East, as the sun sets in the West.",
            "location" => "Beirut, LB",
            "image" => "https://100percentretro.com/wp-content/uploads/2022/07/Joelle-Harper-Mon-Fri.jpg",
            "bio" => "https://100percentretro.com/schedule-monday-friday/",
            "duration" => 2
        ],
        8 => [
            "title" => "Beautiful Noise",
            "author" => "Dan Harper",
            "description" => "Dan Harper hosts two flavors of the same show. For listeners in the UK, Europe, Africa 
                              and the Middle East, we call it “The Big Retro Breakfast Show” while listeners in North 
                              and South America know it as “The Big Retro Late Night Show.” Dan is an early bird who 
                              produces Joëlle’s show (06:00-08:00 CET) and receives her editorial assistance with his 
                              program. Together, they’re 100% RETRO’s morning or evening power team. Joëlle and Dan will 
                              inspire you to start or finish your day the right way. Let’s call it… a beautiful noise!",
            "location" => "Beirut, LB",
            "image" => "https://100percentretro.com/wp-content/uploads/2022/07/Dan-Harper-Mon-Fri.jpg",
            "bio" => "https://100percentretro.com/schedule-monday-friday/",
            "duration" => 2
        ],
        10 => [
            "title" => "Rewind",
            "author" => "Steve Penk",
            "description" => "From mid-morning in the UK, Europe, Africa and the Middle East, to the early morning dawn 
                              in North and South America… broadcasting from London, UK… Steve Penk hosts his daily 
                              REWIND show on 100% RETRO. You’ll hear candid jokes, controversial talks and Steve’s 
                              unique brand of entertainment. Join us for your daily dose of music, news and artist 
                              interviews that you won’t hear anywhere else. No matter where you are in the world, Steve 
                              will be there to wake you up and keep you up!",
            "location" => "London, UK",
            "image" => "https://100percentretro.com/wp-content/uploads/2022/07/Steve-Penk-Mon-Fri.jpg",
            "bio" => "https://100percentretro.com/schedule-monday-friday/",
            "duration" => 1
        ],
        11 => [
            "title" => "Let the Music Play",
            "author" => "Polly Winder",
            "description" => "Polly Winder plays your favorite songs in “Let The Music Play”. You select the songs and 
                              we go back in time. WhatsApp, facebook or email your favorite classics from the past... 
                              and let us know where you were when your song was hitting the charts. From the UK and 
                              broadcasting worldwide, Polly serves up your daily taste of retro before another lady 
                              (Nádine) takes over in Johanneburg! Send us your retro requests from all across the 
                              planet and “Let The Music Play” with Polly Winder.",
            "location" => "North England, UK",
            "image" => "https://100percentretro.com/wp-content/uploads/2022/07/Polly-Winder-Mon-Fri.jpg",
            "bio" => "https://100percentretro.com/schedule-monday-friday/",
            "duration" => 1
        ],
        12 => [
            "title" => "Memories",
            "author" => "Nadine",
            "description" => "“Memories”… your flashback to the past! Historical events, famous happenings, weddings, 
                              deaths, celebrity gossip. Broadcasting from Johannesburg, South Africa, Nádine tells all 
                              in a 3-hour appointment with the past. Memories and moments in time. What was the No.1 
                              song on the day you were born? What happened today in music history? We’ll bring you all 
                              the facts and figures. Plus, the most important news from yesterday. Nádine with 
                              “Memories”… every weekday on 100% RETRO.",
            "location" => "Johannesberg, SA",
            "image" => "https://100percentretro.com/wp-content/uploads/2022/07/Nadine-Mon-Fri.jpg",
            "bio" => "https://100percentretro.com/schedule-monday-friday/",
            "duration" => 3
        ],
        15 => [
            "title" => "A Real Good Feeling",
            "author" => "Soroya Torrens",
            "description" => "Soraya Torrens plays your favorite retro requests in “A Real Good Feeling”. She’s our 
                              spokesperson representing 100% RETRO all over the planet. Soraya is a world traveller 
                              and global citizen. Let Soraya know what you want to hear and she’ll go back in time to 
                              spin your all-time classics! Broadcasting from Tel Aviv, Israel one week and from São 
                              Paulo, Brazil the next, she’s our global brand ambassador. Soraya brings our listeners 
                              joy and happiness every day with “A Real Good Feeling.”",
            "location" => "São Paulo, BR",
            "image" => "https://100percentretro.com/wp-content/uploads/2022/07/Soroya-Torrens-Mon-Fri.jpg",
            "bio" => "https://100percentretro.com/schedule-monday-friday/",
            "duration" => 1
        ],
        16 => [
            "title" => "Drive",
            "author" => "Andy and Steph",
            "description" => "Who’s gonna drive you home in the UK, Europe, Africa and the Middle East? And who’s 
                              serving up lunch in North and South America? Broadcasting from the UK and across the 
                              planet, Andy G and Steph Langford are your hosts in “Drive”… a daily request show. Tell 
                              them what you want to hear and they’ll bring you back in time. Spinning your favorite 
                              retro requests and revisiting the most memorable moments in history. They bring fun and 
                              fabulous to the afternoon drive time. Every weekday on 100% RETRO.",
            "location" => "North England, UK",
            "image" => "https://100percentretro.com/wp-content/uploads/2022/09/Steph-Andy-Mon-Fri.jpg",
            "bio" => "https://100percentretro.com/schedule-monday-friday/",
            "duration" => 3
        ],
        19 => [
            "title" => "I Can Hear Music",
            "author" => "Gerard Teuma",
            "description" => "Broadcasting from The Rock of Gibraltar, Gerard Teuma takes you on his European Tour. 
                              Join us every weekday for “I Can Hear Music,” as Gerard plays timeless classics from 
                              France, Greece, Belgium, and the Netherlands. Hidden gems that reached the top of the 
                              national charts! Relive the most beautiful music and memories from Germany, 
                              Czech Republic, Portugal, Russia and so many more countries… Experience the biggest 
                              European hits every weekday with Gerard Teuma, only on 100% RETRO.",
            "location" => "The Rock of Gibraltar",
            "image" => "https://100percentretro.com/wp-content/uploads/2022/09/Gerard-Teuma-Mon-Fri.jpg",
            "bio" => "https://100percentretro.com/schedule-monday-friday/",
            "duration" => 2
        ],
        21 => [
            "title" => "Driver's Seat",
            "author" => "Rick Rooster",
            "description" => "It’s Rick Rooster in the “Driver’s Seat”. Broadcasting from Toronto, Canada and across 
                              the planet! He’s your radio pilot during the North American drive time. Rick takes a bite 
                              out of traffic and spins the best in 100% RETRO classics. Tune in every weekday for the 
                              soundtrack to your rush hour, getting you home safe and sound. He navigates traffic on 
                              one side of the Atlantic and drinks a nightcap with you on the other. We’re going old 
                              school with Rick Rooster in the “Driver’s Seat”… your retro radio on the road.",
            "location" => "Toronto, CA",
            "image" => "https://100percentretro.com/wp-content/uploads/2022/09/Rick-Rooster-Mon-Fri.jpg",
            "bio" => "https://100percentretro.com/schedule-monday-friday/",
            "duration" => 3
        ]
    ],
    6 => [
        0 => [
            "title" => "Absolutely 80's",
            "author" => "Nina Blackwood",
            "description" => "100% RETRO’s “Absolutely 80s” is a weekly radio show that transports its audience back to 
                              the 1980s. No one knows the ’80s like original MTV VJ Nina Blackwood. She helped set the 
                              musical tastes and trends that are being celebrated today. The show’s features, such as 
                              The ’80s Game, When Did That Happen, The Rock Vault, The One Hit Wonder and The Total 
                              Recall Top 5 satisfy the audience’s curiosity for trivia about ’80s memories, styles and 
                              events. Experience the best in RETRO 80s every Saturday.",
            "location" => "Hollywood, US",
            "image" => "https://100percentretro.com/wp-content/uploads/2022/07/Nina-Blackwood-Sat.jpg",
            "bio" => "https://100percentretro.com/schedule-saturday/",
            "duration" => 2
        ],
        2 => [
            "title" => "Top 10 Now and Then",
            "author" => "Rick Nuhn",
            "description" => "From the entertainment capital of the world, Los Angeles, California, Rick Nuhn hosts Top 
                              10 Now and Then. Heard globally on 100% RETRO, Rick showcases all of the things that make 
                              the “Old School” Cool ! He digs into specific topics and builds a musical palette that 
                              compliments every show. Whether it’s the stars you share your birthday month with or 
                              trips across the USA to hear the music of Atlanta or Detroit, Top 10 Now and Then gives 
                              you a musical look to your past with an occasional look to your present.",
            "location" => "Los Angeles, US",
            "image" => "https://100percentretro.com/wp-content/uploads/2022/09/Rick-Nuhn-Sat.jpg",
            "bio" => "https://100percentretro.com/schedule-saturday/",
            "duration" => 2
        ],
        4 => [
            "title" => "Live In Concert",
            "author" => "Lisa Berigan",
            "description" => "Lisa Berigan hosts “Live in Concert” and goes behind the scenes to explore the icons of 
                              Rock and Roll, their legendary performances, and exclusive interviews with the bands. 
                              Experience the concert of a lifetime on 100% RETRO and put yourself in the front row for 
                              each performance every Saturday night. Feel the emotional connection. Lisa’s extensive 
                              knowledge of music and familiarity with the artists allows her to share personal insight 
                              into the significance and importance of these classic rock concerts.",
            "location" => "Pennsylvania, US",
            "image" => "https://100percentretro.com/wp-content/uploads/2022/07/Lisa-Berigan-Sat.jpg",
            "bio" => "https://100percentretro.com/schedule-saturday/",
            "duration" => 2
        ],
        6 => [
            "title" => "Calling America",
            "author" => "PJ Butta",
            "description" => "PJ Butta is your weekend wake-up call in the UK, Europe, Africa and the Middle East. 
                              Join us for “Calling America”… 100% RETRO’s breakfast show for London, Brussels, 
                              Barcelona, Johannesburg… your European drive time in the morning! When the stars are 
                              shining in North and South America, it’s tea or coffee with PJ Butta, and a taste of your 
                              favorite classics. Broadcasting from Los Angeles, California and across the planet. PJ 
                              Butta serves up sweet and savory every weekend on 100% RETRO.",
            "location" => "Los Angeles, US",
            "image" => "https://100percentretro.com/wp-content/uploads/2022/07/PJ-Butta-Sat.jpg",
            "bio" => "https://100percentretro.com/schedule-saturday/",
            "duration" => 3
        ],
        9 => [
            "title" => "Turn Back the Clock",
            "author" => "James Hall",
            "description" => "James Hall hosts “Turn Back The Clock” weekend mornings in the UK, Europe, Africa and the 
                              Middle East. Broadcasting from Manchester, UK and across the planet, James serves up your 
                              favorite classics with 100% RETRO’s weekend midday show for London, Brussels, Barcelona, 
                              Johannesburg… your European brunch time! Join us for a taste of the fun and laidback 
                              vibes every Saturday and Sunday with James Hall. He goes back in time and spins your 
                              all-time favorites, every weekend on 100% RETRO.",
            "location" => "Manchester, UK",
            "image" => "https://100percentretro.com/wp-content/uploads/2022/07/James-Hall-Sat.jpg",
            "bio" => "https://100percentretro.com/schedule-saturday/",
            "duration" => 3
        ],
        12 => [
            "title" => "Totally 80's",
            "author" => "Gary King",
            "description" => "Broadcasting from London, UK… Gary King takes you back in time, every Saturday and 
                              Sunday, in “Totally 80s”. Gary focuses on what the stars of the “decadent decade” are 
                              doing today! Which means there’s always a constant stream of new and interesting stories. 
                              Each week, Gary King is joined by a Star Guest who reveals their story and memories of 
                              the 1980s. Weekly contemporary features like “The ’80s Mash Up” and the guess-the-year 
                              segment “Total Recall”. If you experienced the 80s as the best time of your life… Gary 
                              King’s “Totally 80s” is your weekend appointment with the past!",
            "location" => "London, UK",
            "image" => "https://100percentretro.com/wp-content/uploads/2022/07/Gary-King-Sat-1.jpg",
            "bio" => "https://100percentretro.com/schedule-saturday/",
            "duration" => 1
        ],
        13 => [
            "title" => "Totally 90's",
            "author" => "Gary King",
            "description" => "Every Saturday and Sunday, you can relive the decade that brought us the PlayStation, 
                              Friends and Girl Power! Broadcasting from London, UK, it’s Gary King with “Totally 90s.” 
                              Join us every weekend when Gary interviews a Star Guest who reveals their best memories 
                              of the 1990s. Features like “The 90s Mash Up” or “Remember The Time”… where you 
                              “guess-the-year” in pop culture. Plus, entertainment news on the biggest names from the 
                              90s. It’s Gary King with “Totally 90s”…your flashback to the past…every weekend on 100% 
                              RETRO.",
            "location" => "London, UK",
            "image" => "https://100percentretro.com/wp-content/uploads/2022/07/Gary-King-Sat-1.jpg",
            "bio" => "https://100percentretro.com/schedule-saturday/",
            "duration" => 1
        ],
        14 => [
            "title" => "So You Win Again",
            "author" => "Andy G",
            "description" => "TUNE IN TO WIN every Saturday for the opportunity of a lifetime! Broadcasting from North 
                              England, UK, Andy G hosts 100% RETRO’s biggest competition show…”So You Win Again.” It’s 
                              your chance to win concert tickets to see your favorite retro artists, exclusive music 
                              collections, tickets to the unique 100% RETRO festival and other amazing prizes… and 
                              tune in every last Saturday of the month for THE JACKPOT. A trip for two to Curacao, 
                              Greece, Turkey, Miami or Ibiza! “So You Win Again” with Andy G.",
            "location" => "North England, UK",
            "image" => "https://100percentretro.com/wp-content/uploads/2022/07/Andy-G-Sat.jpg",
            "bio" => "https://100percentretro.com/schedule-saturday/",
            "duration" => 2
        ],
        16 => [
            "title" => "The Kim Wilde 80's Show",
            "author" => "Kim Wilde",
            "description" => "She’s a pop icon, selling over 30 million records worldwide… she topped the charts with 
                              “Kids in America” and “You Keep Me Hanging On”. And “she” is our 80s icon every Saturday 
                              and Sunday… Kim Wilde…with her own show every weekend on 100% RETRO. Tune in for 
                              entertainment news, exclusive tracks and unique stories from the legend herself. 
                              Broadcasting from our studio in London, UK, we’re going back in time with “The Kim Wilde 
                              80s Show”…every Saturday and Sunday!",
            "image" => "https://100percentretro.com/wp-content/uploads/2022/07/Kim-Wilde-Sat.jpg",
            "bio" => "https://100percentretro.com/schedule-saturday/",
            "location" => "London, UK",
            "duration" => 2
        ],
        18 => [
            "title" => "In the Mix",
            "author" => "Ben Liebrand",
            "description" => "Feel the Saturday Night Fever with Ben Liebrand… “In The Mix.” During his live radio 
                              show, Ben spins on no less than 4 decks, blending multiple songs live, to perfection. 
                              He’s a legendary mixmaster and has produced hit remixes for Salt’N’Pepa – Sting – Phil 
                              Collins – Bill Withers – The 4 Tops – The Sugarhill Gang – The Doobie Brothers – Grace 
                              Jones and so many more. His “Grandmixes” are regarded as the absolute benchmark in 
                              mixing and are still charting at #1 positions on iTunes and the regular sales charts. 
                              Ben Liebrand…“In The Mix”… Saturdays on 100% RETRO.",
            "location" => "Foothills, CA",
            "image" => "https://100percentretro.com/wp-content/uploads/2022/07/Ben-Liebrand-Sat.jpg",
            "bio" => "https://100percentretro.com/schedule-saturday/",
            "duration" => 1
        ],
        19 => [
            "title" => "On the Beat",
            "author" => "Steven Purvis",
            "description" => "Broadcasting from North England, UK and spinning the best remixes in retro pop and dance 
                              music! Stephen Purvis is “On The Beat.” Remixing yesterday’s classics to the modern deep 
                              house grooves of today. Join us every Saturday for a taste of your favorite dance music 
                              from the 70s, 80s and 90s. Stephen drops the beat and gets you moving… bringing flavor 
                              to the dancefloor! A born and bred entertainer, singer, producer… he looks back at the 
                              past with the fresh and modern sounds of today.",
            "location" => "North England, UK",
            "image" => "https://100percentretro.com/wp-content/uploads/2022/07/Stephen-Purvis-Sat.jpg",
            "bio" => "https://100percentretro.com/schedule-saturday/",
            "duration" => 2
        ],
        21 => [
            "title" => "Funky Town",
            "author" => "Monday Midnite",
            "description" => "Get your groove on and feel the good vibrations every Saturday with “Funky Town.” 
                              Legendary MC, funk and nightlife DJ, Monday Midnite spins the biggest hits in the world 
                              of retro funk. Broadcasting from the Capital of Europe, he takes you on a world-tour of 
                              this popular musical genre that draws influences from RandB, jazz, gospel and soul. Feel 
                              the infectious grooves and contagious basslines with Monday Midnite. He gets you 
                              grooving and spins the soundtrack for feel-good Saturdays, only on 100% RETRO.",
            "location" => "Brussels, BE",
            "image" => "https://100percentretro.com/wp-content/uploads/2022/07/Monday-Midnite-Sat-1.jpg",
            "bio" => "https://100percentretro.com/schedule-saturday/",
            "duration" => 1
        ],
        22 => [
            "title" => "Let's Groove",
            "author" => "Ronny Caslow",
            "description" => "Broadcasting from The Capital of Europe, Brussels Belgium, it’s Ronny Caslo with “Let’s 
                              Groove”… revisiting the best in classic dance music from across the decades. What were 
                              the biggest club hits in the 80s? What were the floor fillers when Saturday Night Fever 
                              took over the globe in the 70’s? Plus, a few dance grooves from the 60s and a touch of 
                              synth sounds from the 90s! “Let’s Groove” with Ronny Caslo is your weekly appointment 
                              for dance, disco and funk every Saturday on 100% RETRO.",
            "location" => "Brussels, BE",
            "image" => "https://100percentretro.com/wp-content/uploads/2022/07/Ronny-Caslo-Sat.jpg",
            "bio" => "https://100percentretro.com/schedule-saturday/",
            "duration" => 1
        ],
        23 => [
            "title" => "And the Beat Goes On",
            "author" => "Jeny Preston",
            "description" => "Bringing you the best remixes in retro pop and dance classics! It’s Jeny Preston… “And 
                              The Beat Goes On.” Broadcasting from Montpellier, France, she remixes yesterday’s smash 
                              hits to the funky house beats of today. It’s your Saturday night date with Jeny and your 
                              appointment with the past! Join us for an exclusive retro dance show with a modern flair 
                              and a touch of French charm. “And The Beat Goes On” with Jeny Preston. Check out her 
                              mixing skills every Saturday on 100% RETRO., every Saturday on 100% RETRO.",
            "location" => "Montpellier, FR",
            "image" => "https://100percentretro.com/wp-content/uploads/2022/07/Jeny-Preston-Sat.jpg",
            "bio" => "https://100percentretro.com/schedule-saturday/",
            "duration" => 1
        ]
    ],
    7 => [
        0 => [
            "title" => "Always On My Mind",
            "author" => "Ben Weston",
            "description" => "Ben Weston goes back in time to relive those magical moments and memories as the host 
                              of “Always On My Mind.” He plays the biggest classics and tells you the stories that 
                              made those legendary hit songs so famous. With Ben’s extraordinary knowledge of retro 
                              music, songs, artists and bands, his program is a weekly “must hear.” Ben has 100% RETRO 
                              in his DNA and after every episode, you’ll understand the inspiration and creativity 
                              behind the songs. Join us every Sunday for your flashback to the past!",
            "location" => "Bahrain, AE",
            "image" => "https://100percentretro.com/wp-content/uploads/2022/07/Ben-Weston-Sun.jpg",
            "bio" => "https://100percentretro.com/schedule-sunday/",
            "duration" => 2
        ],
        2 => [
            "title" => "History Of Rock 'N Roll",
            "author" => "Wink Martindale and Gary Theroux",
            "description" => "What’s that song?  Who’s the performer?  How did they get that name?  What inspired those 
                              words?  Why does this music touch my heart… make me smile… make me cry… make me want to 
                              dance, romance and fall in love?  Whatever happened to the great hits I grew up with?  
                              For the answers, tune in to The History of Rock ‘N’ Roll with the legendary Wink 
                              Martindale.  Each episode is built around a theme spotlighting a big era in rock and pop 
                              history.  Experience the songs and stars who made the music magic.",
            "location" => "Los Angeles, US",
            "image" => "https://100percentretro.com/wp-content/uploads/2022/07/Wink-Martindale-Sun.jpg",
            "bio" => "https://100percentretro.com/schedule-sunday/",
            "duration" => 2
        ],
        4 => [
            "title" => "Retro Country USA",
            "author" => "Big Steve Kelly",
            "description" => "From his home base in the USA, where Country music has its origins, Big Steve Kelly 
                              revives the pearls of the past! Broadcasting from his ranch in Pennsylvania, “Retro 
                              Country USA” is 2 hours of the greatest Country hits of all time. Weekly features include 
                              the “Retro Rewind”…a look back at a particular year or artists featuring a hit song from 
                              that year and the “Retro Country Classic.” Steve keeps you up-to-date with the latest 
                              entertainment news in the world of Country music, birthdays and so much more.",
            "location" => "Pennsylvania, US",
            "image" => "https://100percentretro.com/wp-content/uploads/2022/07/Big-Steve-Kelly-S.jpg",
            "bio" => "https://100percentretro.com/schedule-sunday/",
            "duration" => 2
        ],
        6 => [
            "title" => "Calling America",
            "author" => "PJ Butta",
            "description" => "PJ Butta is your weekend wake-up call in the UK, Europe, Africa and the Middle East. Join 
                              us for “Calling America”… 100% RETRO’s breakfast show for London, Brussels, Barcelona, 
                              Johannesburg… your European drive time in the morning! When the stars are shining in 
                              North and South America, it’s tea or coffee with PJ Butta, and a taste of your favorite 
                              classics. Broadcasting from Los Angeles, California and across the planet. PJ Butta 
                              serves up sweet and savory every weekend on 100% RETRO.",
            "location" => "Los Angeles, US",
            "image" => "https://100percentretro.com/wp-content/uploads/2022/07/PJ-Butta-Sun.jpg",
            "bio" => "https://100percentretro.com/schedule-sunday/",
            "duration" => 3
        ],
        9 => [
            "title" => "Turn Back the Clock",
            "author" => "James Hall",
            "description" => "James Hall hosts “Turn Back The Clock” weekend mornings in the UK, Europe, Africa and 
                              the Middle East. Broadcasting from Manchester, UK and across the planet, James serves up 
                              your favorite classics with 100% RETRO’s weekend midday show for London, Brussels, 
                              Barcelona, Johannesburg… your European brunch time! Join us for a taste of the fun and 
                              laidback vibes every Saturday and Sunday with James Hall. He goes back in time and spins 
                              your all-time favorites, every weekend on 100% RETRO.",
            "location" => "Manchester, UK",
            "image" => "https://100percentretro.com/wp-content/uploads/2022/07/James-Hall-Sun.jpg",
            "bio" => "https://100percentretro.com/schedule-sunday/",
            "duration" => 3
        ],
        12 => [
            "title" => "Totally 80's",
            "author" => "Gary King",
            "description" => "Broadcasting from London, UK… Gary King takes you back in time, every Saturday and 
                              Sunday, in “Totally 80s”. Gary focuses on what the stars of the “decadent decade” are 
                              doing today! Which means there’s always a constant stream of new and interesting stories. 
                              Each week, Gary King is joined by a Star Guest who reveals their story and memories of 
                              the 1980s. Weekly contemporary features like “The ’80s Mash Up” and the guess-the-year 
                              segment “Total Recall”. If you experienced the 80s as the best time of your life… Gary 
                              King’s “Totally 80s” is your weekend appointment with the past!",
            "location" => "London, UK",
            "image" => "https://100percentretro.com/wp-content/uploads/2022/07/Gary-King-Sun-1.jpg",
            "bio" => "https://100percentretro.com/schedule-sunday/",
            "duration" => 1
        ],
        13 => [
            "title" => "Totally 90's",
            "author" => "Gary King",
            "description" => "Every Saturday and Sunday, you can relive the decade that brought us the PlayStation, 
                              Friends and Girl Power! Broadcasting from London, UK, it’s Gary King with “Totally 90s.” 
                              Join us every weekend when Gary interviews a Star Guest who reveals their best memories 
                              of the 1990s. Features like “The 90s Mash Up” or “Remember The Time”… where you 
                              “guess-the-year” in pop culture. Plus, entertainment news on the biggest names from the 
                              90s. It’s Gary King with “Totally 90s”…your flashback to the past…every weekend on 
                              100% RETRO.",
            "location" => "London, UK",
            "image" => "https://100percentretro.com/wp-content/uploads/2022/07/Gary-King-Sun-1.jpg",
            "bio" => "https://100percentretro.com/schedule-sunday/",
            "duration" => 1
        ],
        14 => [
            "title" => "The Final Countdown",
            "author" => "Gerard Teuma",
            "description" => "Put your finger on the pulse of 100% RETRO’s weekly Top 40 countdown. Gerard Teuma rounds 
                              up the most popular songs, artists and bands of the week as requested by our listeners. 
                              You decide which songs we play during “The Final Countdown” based on an artist’s or 
                              band’s popularity and listener requests. Join us every Sunday for your regular update on 
                              chart positions, music news and entertainment on the weekly Top 40. Revisit 100% RETRO’s 
                              most popular requests during “The Final Countdown”.",
            "location" => "The Rock of Gibraltar",
            "image" => "https://100percentretro.com/wp-content/uploads/2022/07/Gerard-Teuma-Sun.jpg",
            "bio" => "https://100percentretro.com/schedule-sunday/",
            "duration" => 2
        ],
        16 => [
            "title" => "The Kim Wilde 80's Show",
            "author" => "Kim Wilde",
            "description" => "She’s a pop icon, selling over 30 million records worldwide… she topped the charts with 
                              “Kids in America” and “You Keep Me Hanging On”. And “she” is our 80s icon every Saturday 
                              and Sunday… Kim Wilde…with her own show every weekend on 100% RETRO. Tune in for 
                              entertainment news, exclusive tracks and unique stories from the legend herself. 
                              Broadcasting from our studio in London, UK, we’re going back in time with “The Kim Wilde 
                              80s Show”…every Saturday and Sunday!",
            "location" => "London, UK",
            "image" => "https://100percentretro.com/wp-content/uploads/2022/07/Kin-Wilde-Sun.jpg",
            "bio" => "https://100percentretro.com/schedule-sunday/",
            "duration" => 2
        ],
        18 => [
            "title" => "La Isla Bonita",
            "author" => "Patricia Bermudez",
            "description" => "As the sun shines high in the South American sky, 100% RETRO’s Latin Specialist, Patricia 
                              Bermudez, takes you on a trip through time and space. Join us every Sunday for “La Isla 
                              Bonita” and explore all the flavors of Latin America’s rich musical history! Broadcasting 
                              from Mexico City, Mexico, Patricia plays the biggest Latin classics, talks about the 
                              songs, the countries, the artists and the stories behind the music. “La Isla Bonita” with 
                              Patricia Bermudez, a touch of Latin on Sunday, only on 100% RETRO.",
            "location" => "Mexico City, MX",
            "image" => "https://100percentretro.com/wp-content/uploads/2022/07/Patricia-Bermudez-S.jpg",
            "bio" => "https://100percentretro.com/schedule-sunday/",
            "duration" => 1
        ],
        19 => [
            "title" => "Gavin Goes to Hollywood",
            "author" => "Gavin Prins",
            "description" => "What happened to the stars of the past? Where are they today? Which celebrities made 
                              headlines this week? Broadcasting from Johannesburg, South Africa… Gavin Prins follows 
                              the biggest stars around the world… to the front row in New York, LA, Cannes, London and 
                              Paris. He’s your celebrity tour guide! Every Sunday, Gavin “Goes to Hollywood.” Follow 
                              the red carpet and keep your finger on the pulse of showbiz with exclusive interviews, 
                              news, celebrity gossip and more, exclusively on 100% RETRO.",
            "location" => "Johannesburg, SA",
            "image" => "https://100percentretro.com/wp-content/uploads/2022/07/Gavin-Prins-Sun.jpg",
            "bio" => "https://100percentretro.com/schedule-sunday/",
            "duration" => 2
        ],
        21 => [
            "title" => "Movie Star",
            "author" => "Annie Einan",
            "description" => "Do you remember who stood on the bow of the Titanic? What about the dance moves in the 
                              musical Grease? And which classics were featured in Dirty Dancing? Join us every Sunday 
                              for “Movie Star” with Annie Eanen… a backstage tour of your favorite movies. Broadcasting 
                              from New York, USA, Annie takes you behind the scenes to explore the actors, the music 
                              and the secrets behind the silver screen. Annie selects and plays the biggest hits made 
                              popular by the movies, only on 100% RETRO.",
            "location" => "New York, US",
            "image" => "https://100percentretro.com/wp-content/uploads/2022/07/Annie-Einan-Sun.jpg",
            "bio" => "https://100percentretro.com/schedule-sunday/",
            "duration" => 2
        ],
        23 => [
            "title" => "The Dave Koz Radio Show",
            "author" => "Dave Koz",
            "description" => "Looking for a laidback weekend? What about a jazzy Sunday afternoon? Join us for the 
                              smooth contemporary jazz sounds of The Dave Kozz Radio Show. He’s received nine Grammy 
                              nominations, had nine No. 1 albums, and has been honored with a star on the Hollywood 
                              Walk of Fame. Playing his favorite tunes, sharing stories, influences and featuring his 
                              latest discoveries… it’s your weekly jazz hangout, only on 100% RETRO. Experience the 
                              world of modern jazz with the critically acclaimed, Dave Kozz.",
            "location" => "Los Angeles, US",
            "image" => "https://100percentretro.com/wp-content/uploads/2022/07/Dave-Koz-Sun.jpg",
            "bio" => "https://100percentretro.com/schedule-sunday/",
            "duration" => 1
        ]
    ]
];


//##: Create Podcast ---------------------------------------------------------------------------------------------------
//----------------------------------------------------------------------------------------------------------------------
//This creates the podcast RSS and channel with global feed data
//----------------------------------------------------------------------------------------------------------------------
$podcast = new Podcast(
    "100% Retro - Live 24/7",
    $lg_description,
    $lg_link
);
$podcast->setValue("generator", "FreePod");
$podcast->addCategory("Music");
$podcast->addCategory("Music History");
$podcast->addCopyright("100% RETRO");
$podcast->webMaster = $lg_email_address;
$podcast->managingEditor = $lg_email_address;
$podcast->itunes_subtitle = "100% Retro - Feel Young Again - Live 24/7";
$podcast->itunes_image = $lg_feed_art_url;
$podcast->itunes_explicit = "no";
$podcast->image['width'] = 144;
$podcast->image['height'] = 144;
$podcast->podcast_guid = $lg_podcast_guid;
$podcast->podcast_medium = "music";
$podcast->valueRecipients[0] = [
    "name" => "100% Retro HQ",
    "type" => "node",
    "address" => "030a58b8653d32b99200a2334cfe913e51dc7d155aa0116c176657a4f1722677a3",
    "customKey" => "696969",
    "customValue" => "ROK7iZF18vHIWXNr54xr",
    "split" => "90"
];
$podcast->valueRecipients[1] = [
    "name" => "Stats",
    "type" => "node",
    "address" => "030a58b8653d32b99200a2334cfe913e51dc7d155aa0116c176657a4f1722677a3",
    "customKey" => "696969",
    "customValue" => "gq0Z8b1wEftMkFL4vj7E",
    "split" => "5"
];
$podcast->valueRecipients[2] = [
    "name" => "Podcast Index",
    "type" => "node",
    "address" => "030a58b8653d32b99200a2334cfe913e51dc7d155aa0116c176657a4f1722677a3",
    "split" => "5"
];


//##: Items ------------------------------------------------------------------------------------------------------------
//----------------------------------------------------------------------------------------------------------------------
//Add a single standard <item> so that the feed doesn't show up as blank to non-LIT aware podcast apps
//----------------------------------------------------------------------------------------------------------------------
$item1 = $podcast->newItem(
    "This week on 100% Retro",
    $lg_description,
    $lg_link,
    "03468468-F44C-4D0C-B4A9-27F3CD54C86C-1039-00000EE2D1D0014B-FFA"
);
$item1->itunes_keywords = array(
    "music",
    "live",
    "streaming",
    "retro",
    "70s",
    "80s"
);
$item1->itunes_author = "100% Retro";
$item1->itunes_subtitle = "Feel young again.";
$item1->itunes_image = "https://feeds.podcastindex.org/100retro.png";
$item1->itunes_duration = "33:33:33";
$item1->addEnclosure('https://mp3s.nashownotes.com/thisweek1.mp3');


//##: Date Math --------------------------------------------------------------------------------------------------------
//----------------------------------------------------------------------------------------------------------------------
//Get some time/date values we need to calculate where we are in the schedule array and how to walk forward
//----------------------------------------------------------------------------------------------------------------------
$pre_day = date("N");
$pre_hour = date("G");

$cur_times = _find_current_day_hour($schedule);
$num_day = $cur_times['day'];
$num_hour = $cur_times['hour'];
$num_month = $cur_times['month'];
$num_year = $cur_times['year'];
$num_moday = $cur_times['moday'];
$cur_start_ts = strtotime(date("$num_month/$num_moday/$num_year " . $num_hour . ":00:00"));
$cur_end_ts = $cur_start_ts + ($schedule[$num_day][$num_hour]['duration'] * 3600);

//Did a new show just start?  If our current day-hour doesn't match a schedule day-hour then
//we are between shows and shouldn't podping because we should only podping when a new show
//goes live in the stream
if ($pre_day == $num_day && $pre_hour == $num_hour) {
    $lg_new_show_started = TRUE;
}

//Should we toot?  We use the same logic as above with podping.  Only toot when a new show
//goes live.  If so, toot and grab the returned status url for later use in the socialInteract
//tag of the liveItem
$social_interact_uri = "";
if ($lg_new_show_started) {
    $lg_toot_text = "Come feel young again with " . $schedule[$num_day][$num_hour]['author']
        . "\n\n" .
        "The " . $schedule[$num_day][$num_hour]['title'] . " show is starting now!";
    $social_interact_uri = _toot($lg_mastodon_auth, $lg_mastodon_host, $lg_mastodon_user, $lg_toot_text);
    echo $social_interact_uri . "\n";
}


//##: Live Items -------------------------------------------------------------------------------------------------------
//----------------------------------------------------------------------------------------------------------------------
//First add the current live show as status="live" and then find and add the next five upcoming shows as "pending"
//----------------------------------------------------------------------------------------------------------------------
echo "Current Show: [".$schedule[$num_day][$num_hour]['title']."|".$schedule[$num_day][$num_hour]['author']."]\n";
$lit_meta = [
    'status' => "live",
    'start' => date(DATE_ATOM, $cur_start_ts),
    'end' => date(DATE_ATOM, $cur_end_ts),
    'chat' => $lg_chat
];
$item_meta = [
    'title' => $schedule[$num_day][$num_hour]['title'] . " - " . $schedule[$num_day][$num_hour]['author'],
    'subtitle' => "Feel young again.",
    'description' => $schedule[$num_day][$num_hour]['description'],
    'image' => $schedule[$num_day][$num_hour]['image'],
    'author' => $schedule[$num_day][$num_hour]['author'],
    'location' => $schedule[$num_day][$num_hour]['location'],
    'link' => $lg_link,
    'guid' => $lg_live_guid . "$num_year-$num_moday-$num_day-$num_hour",
    'duration' => "33:33:33"
];
$person_meta = [
    'name' => $schedule[$num_day][$num_hour]['author'],
    'bio' => $schedule[$num_day][$num_hour]['bio'],
    'image' => $schedule[$num_day][$num_hour]['image']
];
$social_meta = [
    'uri' => $social_interact_uri,
    'accountId' => "@$lg_mastodon_user",
    'accountUrl' => "https://$lg_mastodon_host/users/$lg_mastodon_user"
];
_add_live_item(
    $podcast,
    $lg_live_stream_url,
    $lit_meta,
    $item_meta,
    $person_meta,
    $social_meta
);
$podcast->itunes_image = $schedule[$num_day][$num_hour]['image'];
$podcast->podcast_location = $schedule[$num_day][$num_hour]['location'];
$podcast->podcast_person['name'] = $schedule[$num_day][$num_hour]['author'];
$podcast->podcast_person['href'] = $schedule[$num_day][$num_hour]['bio'];
$podcast->podcast_person['img'] = $schedule[$num_day][$num_hour]['image'];

//Add the next five upcoming shows
$count = 0;
while ($count <= 4) {
    $cur_times = _find_next_day_hour($schedule, $cur_start_ts);
    $num_day = $cur_times['day'];
    $num_hour = $cur_times['hour'];
    $num_month = $cur_times['month'];
    $num_year = $cur_times['year'];
    $num_moday = $cur_times['moday'];
    $cur_start_ts = strtotime(date("$num_month/$num_moday/$num_year " . $num_hour . ":00:00"));
    $cur_end_ts = $cur_start_ts + ($schedule[$num_day][$num_hour]['duration'] * 3600);

    echo "Next Up: [".$schedule[$num_day][$num_hour]['title']."|".$schedule[$num_day][$num_hour]['author']."]\n";
    $lit_meta = [
        'status' => "pending",
        'start' => date(DATE_ATOM, $cur_start_ts),
        'end' => date(DATE_ATOM, $cur_end_ts),
        'chat' => $lg_chat
    ];
    $item_meta = [
        'title' => $schedule[$num_day][$num_hour]['title'] . " - " . $schedule[$num_day][$num_hour]['author'],
        'subtitle' => "Feel young again.",
        'description' => $schedule[$num_day][$num_hour]['description'],
        'image' => $schedule[$num_day][$num_hour]['image'],
        'author' => $schedule[$num_day][$num_hour]['author'],
        'location' => $schedule[$num_day][$num_hour]['location'],
        'link' => $lg_link,
        'guid' => $lg_live_guid . "$num_year-$num_moday-$num_day-$num_hour",
        'duration' => "33:33:33"
    ];
    $person_meta = [
        'name' => $schedule[$num_day][$num_hour]['author'],
        'bio' => $schedule[$num_day][$num_hour]['bio'],
        'image' => $schedule[$num_day][$num_hour]['image']
    ];
    $social_meta = [
        'uri' => "",
        'accountId' => "",
        'accountUrl' => ""
    ];
    _add_live_item(
        $podcast,
        $lg_live_stream_url,
        $lit_meta,
        $item_meta,
        $person_meta,
        $social_meta
    );

    $count++;
}


//##: Build, Upload and Podping ----------------------------------------------------------------------------------------
//----------------------------------------------------------------------------------------------------------------------
//If we are at the top of the hour and a scheduled show just started build the feed XML, upload it to object storage
//and send a podping event
//----------------------------------------------------------------------------------------------------------------------
if ($lg_new_show_started) {
    //Dump the xml and make it pretty
    echo "Building feed...";
    $feedXmlText = $podcast->xml(TRUE);

    //Send to object storage
    echo "Uploading to S3...";
    _upload_to_s3($feedXmlText, $lg_s3_filename, $lg_s3_key, $lg_s3_secret, $lg_s3_bucket);

    //Send the podping
    echo "Sending podping...";
    podpingNotify($lg_external_url, "music", "live");
}


//##: Helper Functions -------------------------------------------------------------------------------------------------
//----------------------------------------------------------------------------------------------------------------------
//Upload to S3
function _upload_to_s3($feedXml, $filename, $s3_key, $s3_secret, $s3_bucket)
{
    //Put the files in S3
    $s3res = putInObjectStorage(
        $feedXml,
        $filename,
        $s3_bucket,
        $s3_key,
        $s3_secret,
        "application/json",
        FALSE,
        $endpoint = "https://us-east-1.linodeobjects.com",
        $region = "us-east-1"
    );
    if (!$s3res) {
        echo "Could not write feed to S3 in bucket: [$s3_bucket].";
        return(FALSE);
    } else {
        echo "Wrote feed to S3 in bucket: [$s3_bucket].";
    }

    return(TRUE);
}

//Find the current day and hour of live show
function _find_current_day_hour($schedule)
{
    $num_day = date("N");
    $num_hour = date("G");
    $num_month = date("n");
    $num_year = date("Y");
    $num_moday = date("j");

    $count = 1;
    while (!isset($schedule[$num_day][$num_hour])) {
        $back_step = time() - (3600 * $count);
        $num_day = date("N", $back_step);
        $num_hour = date("G", $back_step);
        $num_month = date("n", $back_step);
        $num_year = date("Y", $back_step);
        $num_moday = date("j", $back_step);
        $count++;
    }

    return ([
        'day' => $num_day,
        'hour' => $num_hour,
        'month' => $num_month,
        'year' => $num_year,
        'moday' => $num_moday
    ]);
}

//Find the day and hour of the next show
function _find_next_day_hour($schedule, $timestart)
{
    $cur_vals = _find_current_day_hour($schedule);
    $num_day = $cur_vals['day'];
    $num_hour = $cur_vals['hour'] + 1;
    $num_month = $cur_vals['month'];
    $num_year = $cur_vals['year'];
    $num_moday = $cur_vals['moday'];

    $count = 1;
    while (!isset($schedule[$num_day][$num_hour])) {
        $step = $timestart + (3600 * $count);
        $num_day = date("N", $step);
        $num_hour = date("G", $step);
        $num_month = date("n", $step);
        $num_year = date("Y", $step);
        $num_moday = date("j", $step);
        $count++;
    }

    return ([
        'day' => $num_day,
        'hour' => $num_hour,
        'month' => $num_month,
        'year' => $num_year,
        'moday' => $num_moday
    ]);
}

//Add a live item
function _add_live_item($podcast, $livestream, $litmeta, $itemmeta, $personmeta, $socialmeta)
{
    $datetimestart = new DateTime($litmeta['start']);
    $datetimeend = new DateTime($litmeta['end']);
    $item = $podcast->newLiveItem(
        $itemmeta['title'],
        $itemmeta['description'],
        $itemmeta['link'],
        $itemmeta['guid'],
        $litmeta['status'],
        $datetimestart->format(DateTime::ATOM),
        $datetimeend->format(DateTime::ATOM),
        $litmeta['chat']
    );
    $item->author = $itemmeta['author'];
    $item->itunes_keywords = array(
        "music",
        "live",
        "streaming",
        "retro",
        "70s",
        "80s"
    );
    $item->itunes_author = $itemmeta['author'];
    $item->itunes_subtitle = $itemmeta['subtitle'];
    $item->itunes_image = $itemmeta['image'];
    $item->itunes_duration = $itemmeta['duration'];
    $item->podcast_location = $itemmeta['location'];
    $item->podcast_person['name'] = $personmeta['name'];
    $item->podcast_person['href'] = $personmeta['bio'];
    $item->podcast_person['img'] = $personmeta['image'];
    if (!empty($socialmeta['uri'])) {
        $item->podcast_social_interact['uri'] = $socialmeta['uri'];
        $item->podcast_social_interact['accountId'] = $socialmeta['accountId'];
        $item->podcast_social_interact['accountUrl'] = $socialmeta['accountUrl'];
    }
    $item->addEnclosure($livestream);

    return (TRUE);
}

//Send a status update to mastodon
function _toot($bearer_auth = NULL, $host = NULL, $user = NULL, $content = NULL, $link = "", $media_id = "")
{
    //Check parameters
    if ($bearer_auth == NULL) {
        echo "The auth token is blank or corrupt: [$bearer_auth]";
        return (FALSE);
    }
    if ($host == NULL) {
        echo "The mastodon host is blank or corrupt: [$host]";
        return (FALSE);
    }
    if ($user == NULL) {
        echo "The mastodon user is blank or corrupt: [$user]";
        return (FALSE);
    }
    if ($content == NULL) {
        echo "The post content is blank or corrupt: [$content]";
        return (FALSE);
    }

    //Setup
    $charcount = 500;
    if (!empty($link)) {
        $charcount -= 22;
    }
    if (!empty($media_id)) {
        $charcount -= 22;
    }

    //Truncate text if too long to fit in remaining space
    if (strlen($content) > $charcount) {
        $twcontent = truncate_text($content, ($charcount - 3)) . "...";
    } else {
        $twcontent = $content;
    }

    //Assemble toot
    $toot = $twcontent . " " . $link;

    $twstatus = array('status' => $toot);
    if (!empty($media_id)) {
        $twstatus['media_ids'] = array($media_id);
    }

    //Make an API call to post the toot
    $result = _postUrlExtra(
        "https://$host/api/v1/statuses",
        $twstatus,
        array(
            "Authorization: Bearer $bearer_auth"
        ),
        TRUE);


    //Log and return
    if ($result['status_code'] == 200) {
        echo "Tooted a new post: [$toot].";
        $json_response = json_decode($result['body'], TRUE);
        return ("https://$host/@$user/" . $json_response["id"]);
    } else {
        echo "Tooting post did not work posting: [$toot].";
        return (FALSE);
    }
}

//Posts the data to a URL along with extra info returned */
function _postUrlExtra($url, $post_parameters = array(), $post_headers = array(), $as_json = FALSE, $timeout = 30)
{

    $curl = curl_init();
    $ua = "MegaCRON!/v1.1";
    curl_setopt($curl, CURLOPT_USERAGENT, $ua);
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_HEADER, FALSE);
    curl_setopt($curl, CURLOPT_COOKIEFILE, "");
    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, $timeout);
    curl_setopt($curl, CURLOPT_TIMEOUT, $timeout);
    curl_setopt($curl, CURLOPT_ENCODING, "");
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);
    curl_setopt($curl, CURLOPT_POST, 1);

    if ($as_json) {
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($post_parameters));
        $post_headers[] = "Content-Type: application/json";
    } else {
        curl_setopt($curl, CURLOPT_POSTFIELDS, $post_parameters);
    }


    if (!empty($post_headers)) {
        curl_setopt($curl, CURLOPT_HTTPHEADER, $post_headers);
    }

    $data = curl_exec($curl);
    $response = curl_getinfo($curl);

    $response['effective_url'] = $url;
    $response['status_code'] = $response['http_code'];
    $response['body'] = $data;

    curl_close($curl);


    return $response;
}