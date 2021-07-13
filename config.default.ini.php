; <?php exit; ?> DO NOT REMOVE THIS LINE

; This file contains the default settings for RSS-Bridge. Do not change this
; file, it will be replaced on the next update of RSS-Bridge! You can specify
; your own configuration in 'config.ini.php' (copy this file).

[system]

; Defines the timezone used by RSS-Bridge
; Find a list of supported timezones at
; https://www.php.net/manual/en/timezones.php
; timezone = "UTC" (default)
timezone = "UTC"

[cache]

; Defines the cache type used by RSS-Bridge
; "file" = FileCache (default)
type = "file"

; Allow users to specify custom timeout for specific requests.
; true  = enabled
; false = disabled (default)
custom_timeout = false

[admin]
; Advertise an email address where people can reach the administrator.
; This address is displayed on the main page, visible to everyone!
; ""    = Disabled (default)
email = ""

; Show Donation information for bridges if available.
; This will display a 'Donate' link on the bridge view
; and a "Donate" button in the HTML view of the bridges feed.
; true  = enabled (default)
; false = disabled
donations = true

[proxy]

; Sets the proxy url (i.e. "tcp://192.168.0.0:32")
; ""    = Proxy disabled (default)
url = ""

; Sets the proxy name that is shown on the bridge instead of the proxy url.
; ""    = Show proxy url
name = "Hidden proxy name"

; Allow users to disable proxy usage for specific requests.
; true  = enabled
; false = disabled (default)
by_bridge = false

[bridges]
; Whitelist all bridges from the config or by environment variable.
; This will overwrite bridges configured in the whitelist.txt file!
; false    = disabled (default)
; true     = enabled
whitelistall = false

[authentication]

; Enables authentication for all requests to this RSS-Bridge instance.
;
; Warning: You'll have to upgrade existing feeds after enabling this option!
;
; true  = enabled
; false = disabled (default)
enable = false

; The username for authentication. Insert this name when prompted for login.
username = ""

; The password for authentication. Insert this password when prompted for login.
; Use a strong password to prevent others from guessing your login!
password = ""

[error]

; Defines how error messages are returned by RSS-Bridge
;
; "feed" = As part of the feed (default)
; "http" = As HTTP error message
; "none" = No errors are reported
output = "feed"

; Defines how often an error must occur before it is reported to the user
report_limit = 1

; --- Cache specific configuration ---------------------------------------------

[SQLiteCache]
file = "cache.sqlite"

[MemcachedCache]
host = "localhost"
port = 11211
