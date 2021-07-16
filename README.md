![RSS-Bridge](static/logo_600px.png)
===
[![LICENSE](https://img.shields.io/badge/license-UNLICENSE-blue.svg)](UNLICENSE) [![GitHub release](https://img.shields.io/github/release/rss-bridge/rss-bridge.svg?logo=github)](https://github.com/rss-bridge/rss-bridge/releases/latest) [![Debian Release](https://img.shields.io/badge/dynamic/json.svg?logo=debian&label=debian%20release&url=https%3A%2F%2Fsources.debian.org%2Fapi%2Fsrc%2Frss-bridge%2F&query=%24.versions%5B0%5D.version&colorB=blue)](https://tracker.debian.org/pkg/rss-bridge) [![Guix Release](https://img.shields.io/badge/guix%20release-unknown-blue.svg)](https://www.gnu.org/software/guix/packages/R/) [![Actions Status](https://img.shields.io/github/workflow/status/RSS-Bridge/rss-bridge/Tests/master?label=GitHub%20Actions&logo=github)](https://github.com/RSS-Bridge/rss-bridge/actions) [![Docker Build Status](https://img.shields.io/docker/cloud/build/rssbridge/rss-bridge?logo=docker)](https://hub.docker.com/r/rssbridge/rss-bridge/)

RSS-Bridge is a PHP project capable of generating RSS and Atom feeds for websites that don't have one. It can be used on webservers or as a stand-alone application in CLI mode.

**Important**: RSS-Bridge is __not__ a feed reader or feed aggregator, but a tool to generate feeds that are consumed by feed readers and feed aggregators. Find a list of feed aggregators on [Wikipedia](https://en.wikipedia.org/wiki/Comparison_of_feed_aggregators).

Supported sites/pages (examples)
===

* `Bandcamp` : Returns last release from [bandcamp](https://bandcamp.com/) for a tag
* `Cryptome` : Returns the most recent documents from [Cryptome.org](http://cryptome.org/)
* `DansTonChat`: Most recent quotes from [danstonchat.com](http://danstonchat.com/)
* `DuckDuckGo`: Most recent results from [DuckDuckGo.com](https://duckduckgo.com/)
* `Facebook` : Returns the latest posts on a page or profile on [Facebook](https://facebook.com/)
* `FlickrExplore` : [Latest interesting images](http://www.flickr.com/explore) from Flickr
* `GoogleSearch` : Most recent results from Google Search
* `Identi.ca` : Identica user timeline (Should be compatible with other Pump.io instances)
* `Instagram`: Most recent photos from an Instagram user
* `OpenClassrooms`: Lastest tutorials from [fr.openclassrooms.com](http://fr.openclassrooms.com/)
* `Pinterest`: Most recent photos from user or search
* `ScmbBridge`: Newest stories from [secouchermoinsbete.fr](http://secouchermoinsbete.fr/)
* `ThePirateBay` : Returns the newest indexed torrents from [The Pirate Bay](https://thepiratebay.se/) with keywords
* `Twitter` : Return keyword/hashtag search or user timeline
* `Wikipedia`: highlighted articles from [Wikipedia](https://wikipedia.org/) in English, German, French or Esperanto
* `YouTube` : YouTube user channel, playlist or search

And [many more](bridges/), thanks to the community!

Output format
===

RSS-Bridge is capable of producing several output formats:

* `Atom` : Atom feed, for use in feed readers
* `Html` : Simple HTML page
* `Json` : JSON, for consumption by other applications
* `Mrss` : MRSS feed, for use in feed readers
* `Plaintext` : Raw text, for consumption by other applications

You can extend RSS-Bridge with your own format, using the [Format API](https://github.com/RSS-Bridge/rss-bridge/wiki/Format-API)!

Screenshot
===

Welcome screen:

![Screenshot](https://github.com/RSS-Bridge/rss-bridge/wiki/images/screenshot_rss-bridge_welcome.png)

***

RSS-Bridge hashtag (#rss-bridge) search on Twitter, in Atom format (as displayed by Firefox):

![Screenshot](https://github.com/RSS-Bridge/rss-bridge/wiki/images/screenshot_twitterbridge_atom.png)

Requirements
===

RSS-Bridge requires PHP 5.6 or higher with following extensions enabled:

  - [`openssl`](https://secure.php.net/manual/en/book.openssl.php)
  - [`libxml`](https://secure.php.net/manual/en/book.libxml.php)
  - [`mbstring`](https://secure.php.net/manual/en/book.mbstring.php)
  - [`simplexml`](https://secure.php.net/manual/en/book.simplexml.php)
  - [`curl`](https://secure.php.net/manual/en/book.curl.php)
  - [`json`](https://secure.php.net/manual/en/book.json.php)
  - [`filter`](https://secure.php.net/manual/en/book.filter.php)
  - [`sqlite3`](http://php.net/manual/en/book.sqlite3.php) (only when using SQLiteCache)

Find more information on our [Wiki](https://github.com/rss-bridge/rss-bridge/wiki)

Enable / Disable bridges
===

RSS-Bridge allows you to take full control over which bridges are displayed to the user. That way you can host your own RSS-Bridge service with your favorite collection of bridges!

Find more information on the [Wiki](https://github.com/RSS-Bridge/rss-bridge/wiki/Whitelisting)

**Notice**: By default, RSS-Bridge will only show a small subset of bridges. Make sure to read up on [whitelisting](https://github.com/RSS-Bridge/rss-bridge/wiki/Whitelisting) to unlock the full potential of RSS-Bridge!

Deploy
===

Thanks to the community, hosting your own instance of RSS-Bridge is as easy as clicking a button!

[![Deploy on Scalingo](https://cdn.scalingo.com/deploy/button.svg)](https://my.scalingo.com/deploy?source=https://github.com/sebsauvage/rss-bridge)
[![Deploy to Heroku](https://www.herokucdn.com/deploy/button.svg)](https://heroku.com/deploy)

Getting involved
===

There are many ways for you to getting involved with RSS-Bridge. Here are a few things:

- Share RSS-Bridge with your friends (Twitter, Facebook, ..._you name it_...)
- Report broken bridges or bugs by opening [Issues](https://github.com/RSS-Bridge/rss-bridge/issues) on GitHub
- Request new features or suggest ideas (via [Issues](https://github.com/RSS-Bridge/rss-bridge/issues))
- Discuss bugs, features, ideas or [issues](https://github.com/RSS-Bridge/rss-bridge/issues)
- Add new bridges or improve the API
- Improve the [Wiki](https://github.com/RSS-Bridge/rss-bridge/wiki)
- Host an instance of RSS-Bridge for your personal use or make it available to the community :sparkling_heart:

Authors
===

We are RSS-Bridge community, a group of developers continuing the project initiated by sebsauvage, webmaster of [sebsauvage.net](http://sebsauvage.net), author of [Shaarli](http://sebsauvage.net/wiki/doku.php?id=php:shaarli) and [ZeroBin](http://sebsauvage.net/wiki/doku.php?id=php:zerobin).

**Contributors** (sorted alphabetically):
<!--
Use this script to generate the list automatically (using the GitHub API):
https://gist.github.com/LogMANOriginal/da00cd1e5f0ca31cef8e193509b17fd8
-->

* [16mhz](https://github.com/16mhz)
* [adamchainz](https://github.com/adamchainz)
* [Ahiles3005](https://github.com/Ahiles3005)
* [akirk](https://github.com/akirk)
* [Albirew](https://github.com/Albirew)
* [aledeg](https://github.com/aledeg)
* [alex73](https://github.com/alex73)
* [alexAubin](https://github.com/alexAubin)
* [AmauryCarrade](https://github.com/AmauryCarrade)
* [AntoineTurmel](https://github.com/AntoineTurmel)
* [arnd-s](https://github.com/arnd-s)
* [ArthurHoaro](https://github.com/ArthurHoaro)
* [Astalaseven](https://github.com/Astalaseven)
* [Astyan-42](https://github.com/Astyan-42)
* [AxorPL](https://github.com/AxorPL)
* [ayacoo](https://github.com/ayacoo)
* [az5he6ch](https://github.com/az5he6ch)
* [b1nj](https://github.com/b1nj)
* [benasse](https://github.com/benasse)
* [Binnette](https://github.com/Binnette)
* [captn3m0](https://github.com/captn3m0)
* [chemel](https://github.com/chemel)
* [Chouchen](https://github.com/Chouchen)
* [ckiw](https://github.com/ckiw)
* [cn-tools](https://github.com/cn-tools)
* [cnlpete](https://github.com/cnlpete)
* [corenting](https://github.com/corenting)
* [couraudt](https://github.com/couraudt)
* [csisoap](https://github.com/csisoap)
* [cyberjacob](https://github.com/cyberjacob)
* [da2x](https://github.com/da2x)
* [Daiyousei](https://github.com/Daiyousei)
* [dawidsowa](https://github.com/dawidsowa)
* [DevonHess](https://github.com/DevonHess)
* [disk0x](https://github.com/disk0x)
* [DJCrashdummy](https://github.com/DJCrashdummy)
* [Djuuu](https://github.com/Djuuu)
* [DnAp](https://github.com/DnAp)
* [dominik-th](https://github.com/dominik-th)
* [Draeli](https://github.com/Draeli)
* [Dreckiger-Dan](https://github.com/Dreckiger-Dan)
* [drego85](https://github.com/drego85)
* [drklee3](https://github.com/drklee3)
* [em92](https://github.com/em92)
* [eMerzh](https://github.com/eMerzh)
* [EtienneM](https://github.com/EtienneM)
* [fanch317](https://github.com/fanch317)
* [fivefilters](https://github.com/fivefilters)
* [floviolleau](https://github.com/floviolleau)
* [fluffy-critter](https://github.com/fluffy-critter)
* [Frenzie](https://github.com/Frenzie)
* [fulmeek](https://github.com/fulmeek)
* [ggiessen](https://github.com/ggiessen)
* [Ginko-Aloe](https://github.com/Ginko-Aloe)
* [Glandos](https://github.com/Glandos)
* [gloony](https://github.com/gloony)
* [GregThib](https://github.com/GregThib)
* [griffaurel](https://github.com/griffaurel)
* [Grummfy](https://github.com/Grummfy)
* [gsantner](https://github.com/gsantner)
* [guigot](https://github.com/guigot)
* [hollowleviathan](https://github.com/hollowleviathan)
* [hpacleb](https://github.com/hpacleb)
* [hunhejj](https://github.com/hunhejj)
* [husim0](https://github.com/husim0)
* [IceWreck](https://github.com/IceWreck)
* [j0k3r](https://github.com/j0k3r)
* [JackNUMBER](https://github.com/JackNUMBER)
* [jacquesh](https://github.com/jacquesh)
* [JasonGhent](https://github.com/JasonGhent)
* [jcgoette](https://github.com/jcgoette)
* [jdesgats](https://github.com/jdesgats)
* [jdigilio](https://github.com/jdigilio)
* [JeremyRand](https://github.com/JeremyRand)
* [JimDog546](https://github.com/JimDog546)
* [Jocker666z](https://github.com/Jocker666z)
* [johnnygroovy](https://github.com/johnnygroovy)
* [johnpc](https://github.com/johnpc)
* [joni1993](https://github.com/joni1993)
* [joshcoales](https://github.com/joshcoales)
* [klimplant](https://github.com/klimplant)
* [kolarcz](https://github.com/kolarcz)
* [kranack](https://github.com/kranack)
* [kraoc](https://github.com/kraoc)
* [l1n](https://github.com/l1n)
* [laBecasse](https://github.com/laBecasse)
* [lagaisse](https://github.com/lagaisse)
* [lalannev](https://github.com/lalannev)
* [ldidry](https://github.com/ldidry)
* [Leomaradan](https://github.com/Leomaradan)
* [liamka](https://github.com/liamka)
* [Limero](https://github.com/Limero)
* [LogMANOriginal](https://github.com/LogMANOriginal)
* [lorenzos](https://github.com/lorenzos)
* [lukasklinger](https://github.com/lukasklinger)
* [m0zes](https://github.com/m0zes)
* [matthewseal](https://github.com/matthewseal)
* [mcbyte-it](https://github.com/mcbyte-it)
* [mdemoss](https://github.com/mdemoss)
* [melangue](https://github.com/melangue)
* [metaMMA](https://github.com/metaMMA)
* [mibe](https://github.com/mibe)
* [mightymt](https://github.com/mightymt)
* [mitsukarenai](https://github.com/mitsukarenai)
* [Monocularity](https://github.com/Monocularity)
* [MonsieurPoutounours](https://github.com/MonsieurPoutounours)
* [mr-flibble](https://github.com/mr-flibble)
* [mro](https://github.com/mro)
* [mschwld](https://github.com/mschwld)
* [mxmehl](https://github.com/mxmehl)
* [nel50n](https://github.com/nel50n)
* [niawag](https://github.com/niawag)
* [Niehztog](https://github.com/Niehztog)
* [Nono-m0le](https://github.com/Nono-m0le)
* [ObsidianWitch](https://github.com/ObsidianWitch)
* [OliverParoczai](https://github.com/OliverParoczai)
* [Ololbu](https://github.com/Ololbu)
* [ORelio](https://github.com/ORelio)
* [otakuf](https://github.com/otakuf)
* [Park0](https://github.com/Park0)
* [Paroleen](https://github.com/Paroleen)
* [PaulVayssiere](https://github.com/PaulVayssiere)
* [pellaeon](https://github.com/pellaeon)
* [PeterDaveHello](https://github.com/PeterDaveHello)
* [Peterr-K](https://github.com/Peterr-K)
* [Piranhaplant](https://github.com/Piranhaplant)
* [pit-fgfjiudghdf](https://github.com/pit-fgfjiudghdf)
* [pitchoule](https://github.com/pitchoule)
* [pmaziere](https://github.com/pmaziere)
* [Pofilo](https://github.com/Pofilo)
* [prysme01](https://github.com/prysme01)
* [Qluxzz](https://github.com/Qluxzz)
* [quentinus95](https://github.com/quentinus95)
* [rakoo](https://github.com/rakoo)
* [RawkBob](https://github.com/RawkBob)
* [regisenguehard](https://github.com/regisenguehard)
* [Riduidel](https://github.com/Riduidel)
* [rogerdc](https://github.com/rogerdc)
* [Roliga](https://github.com/Roliga)
* [ronansalmon](https://github.com/ronansalmon)
* [rremizov](https://github.com/rremizov)
* [sebsauvage](https://github.com/sebsauvage)
* [shutosg](https://github.com/shutosg)
* [simon816](https://github.com/simon816)
* [Simounet](https://github.com/Simounet)
* [somini](https://github.com/somini)
* [squeek502](https://github.com/squeek502)
* [stjohnjohnson](https://github.com/stjohnjohnson)
* [Strubbl](https://github.com/Strubbl)
* [sublimz](https://github.com/sublimz)
* [sudwebdesign](https://github.com/sudwebdesign)
* [sunchaserinfo](https://github.com/sunchaserinfo)
* [SuperSandro2000](https://github.com/SuperSandro2000)
* [sysadminstory](https://github.com/sysadminstory)
* [t0stiman](https://github.com/t0stiman)
* [tameroski](https://github.com/tameroski)
* [teromene](https://github.com/teromene)
* [tgkenney](https://github.com/tgkenney)
* [thefranke](https://github.com/thefranke)
* [ThePadawan](https://github.com/ThePadawan)
* [TheRadialActive](https://github.com/TheRadialActive)
* [theScrabi](https://github.com/theScrabi)
* [thezeroalpha](https://github.com/thezeroalpha)
* [TitiTestScalingo](https://github.com/TitiTestScalingo)
* [triatic](https://github.com/triatic)
* [VerifiedJoseph](https://github.com/VerifiedJoseph)
* [WalterBarrett](https://github.com/WalterBarrett)
* [wtuuju](https://github.com/wtuuju)
* [xurxof](https://github.com/xurxof)
* [yamanq](https://github.com/yamanq)
* [yardenac](https://github.com/yardenac)
* [ymeister](https://github.com/ymeister)
* [ZeNairolf](https://github.com/ZeNairolf)

Licenses
===

The source code for RSS-Bridge is [Public Domain](UNLICENSE).

RSS-Bridge uses third party libraries with their own license:

  * [`Parsedown`](https://github.com/erusev/parsedown) licensed under the [MIT License](http://opensource.org/licenses/MIT)
  * [`PHP Simple HTML DOM Parser`](http://simplehtmldom.sourceforge.net/) licensed under the [MIT License](http://opensource.org/licenses/MIT)
  * [`php-urljoin`](https://github.com/fluffy-critter/php-urljoin) licensed under the [MIT License](http://opensource.org/licenses/MIT)

Technical notes
===

  * RSS-Bridge uses caching to prevent services from banning your server for repeatedly updating feeds. The specific cache duration can be different between bridges. Cached files are deleted automatically after 24 hours.
  * You can implement your own bridge, [following these instructions](https://github.com/RSS-Bridge/rss-bridge/wiki/Bridge-API).
  * You can enable debug mode to disable caching. Find more information on the [Wiki](https://github.com/RSS-Bridge/rss-bridge/wiki/Debug-mode)

Rant
===

*Dear so-called "social" websites.*

Your catchword is "share", but you don't want us to share. You want to keep us within your walled gardens. That's why you've been removing RSS links from webpages, hiding them deep on your website, or removed feeds entirely, replacing it with crippled or demented proprietary API. **FUCK YOU.**

You're not social when you hamper sharing by removing feeds. You're happy to have customers creating content for your ecosystem, but you don't want this content out - a content you do not even own. Google Takeout is just a gimmick. We want our data to flow, we want RSS or Atom feeds.

We want to share with friends, using open protocols: RSS, Atom, XMPP, whatever. Because no one wants to have *your* service with *your* applications using *your* API force-feeding them. Friends must be free to choose whatever software and service they want.

We are rebuilding bridges you have willfully destroyed.

Get your shit together: Put RSS/Atom back in.
