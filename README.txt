Simple PHP script for migrate customers emails from hotspotadmin.com to mailchimp.com service.

FILES:
-- migration.php - script. Here only interesting to change $debug variable at begin

-- mailchimp-api@xxxxxx - folder with 3rd party API.  You should upload that as subproject. Please see UPLOAD instructions as well

-- migration-settings.ini - main importing configuration. 

	Where:
	-- "[migration_1]" - any section name. Just showing on importing debug output. File can have multiple sections!
	-- "hotspotsystem_api_key" - some password for programm access to  hotspotadmin.com API. Can be founded\generated at user settings
	-- "hs_location_id" - source group. where you want import emails.
	-- "mc_api_key" - some password for programm access to MailChimp.com API. Can be founded at user settings
	-- "mc_list_id" - MC list ID where you want store imported emails. Can be founded at deep of group settings
	-- "debug_mode" - switch verbose ourput on\off.

-- last-imported-time.ini - not present. But will create on first run. Have strings like "12--->17woerego7 = 1000_2017-09-20 10:29:49"

	Where: 
	-- "12" - hotspotadmin site group. "hs_location_id"
	-- "17woerego7" - mailchimp list id. "mc_list_id"
	-- "1000" - offset in HTTP query. New emails in hotspotadmin will query from record number 1000.
	-- "2017-09-20 10:29:49" - time when last imported user logged in last time.


UPLOAD CODE:
1. Download the project "git clone https://github.com/stanislav888/hotspotadmin-mailchimp-migration.git"
2. Change current folder"cd hotspotadmin-mailchimp-migration"
3. Initialize the submodule "git submodule init"
4. Upload submodule code to the project "git submodule update" 

RUN MIGRATION:
"php migration.php" - from command line. Of course, must have installed PHP.
"http://<my-site>.com/hotspotadmin-mailchimp-migration/migration.php" - when you upload solution on WEB hosting. Debug output not nice in that case.

SUPPORT:
Any isuues please ask - https://www.upwork.com/o/profiles/users/_~017bf438dc32f73001/
