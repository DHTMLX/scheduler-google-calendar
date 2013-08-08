Date converter between dhtmlxScheduler and Google Calendar
----------------------------------------------------------

This class can be used to load a data from the Google Calendar to the dhtmlxScheduler,
or to save changes from the dhtmlxScheduler back to the Google Calendar.


### Usage

#### Preparing credentials

- login to google API console - https://code.google.com/apis/console
- press "Create new project"
- enable Calendar API on "Services" screen
- go to API Access screen and click on "Create an OAuth2.0 Client ID", enter your name, upload a logo, and click Next, select the Service account option and press Create client ID. As result you will have private key file, Client ID and  Email address - they will be necessary later
- login to the google calendar, which you want to use for the app, and share it on "Email address", which was generated on previous step
- locate and save the calendar id 


Location of calendar id - https://drupal.org/node/589310



#### Server side code

Create a php file data.php with code like

~~~php
<?php

include('../src/google_proxy.php');

$calendar = new GoogleCalendarProxy(
	"<account>@developer.gserviceaccount.com",
	"<account>.apps.googleusercontent.com",
    file_get_contents("<key>"),
    "<calendar id>"
);

$calendar->connect();
?>
~~~

- <account> - take from Google Console API
- <key> - path to private key from Google Console API
- <calendar id> - can be taken from the settings of the related Google Calendar

#### Client side code

On client side you can init scheduler in any legal way, with any configuration. 
After scheduler's initialization, place the next lines

~~~js
	//load data from google calendar
	scheduler.load("./data.php", "json");

	//save changes back to google calendar
	var dp =  new dataProcessor("./data.php");
	dp.init(scheduler);
	dp.setTransactionMode("POST", false);
~~~

If you need readonly access - just ignore the second part of above code. 

### Troubleshooting

If you have some problems with data loading, try to open the data.php directly in the browser - it will show details of authorization error. 

### License

Distributed under the MIT software license  
Copyright (c) 2013 Dinamenta UAB
