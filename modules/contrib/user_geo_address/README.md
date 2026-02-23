CONTENTS OF THIS FILE
---------------------
   
 * Introduction
 * Requirements
 * Installation
 * Configuration
 * Troubleshooting
 * FAQ
 * Maintainers
 

INTRODUCTION
---------------------

This module provides a block which shows users Geographical address in a block. It
is required Google API key for getting address from Google API service.

REQUIREMENTS
---------------------

This module only need API key which can be get from https://console.cloud.google.com .


INSTALLATION
---------------------

Install as you would  normally install a Druapl contributed module. Visit:
https://www.drupal.org/docs/8/extending-drupal-8/installing-drupal-8-modules
for further informtaion.


CONFIGURATION
---------------------

Go to Administration » Configuration » Development » MAPS API Key Configuration:
  * Enter your Google api key.
  * Save Configuration.


How to get a Google API key
---------------------------

Go to https://console.developers.google.com/cloud-resource-manager
* Create a new project
* Enable Geocoding API, Geolocation API. 
(Go to https://console.cloud.google.com/apis/library?project=YOUR_PROJECT).
* Go to Credentials
* Create New API Key
* Select Applications restrictions as None.
* Copy the API key.
* Paste it in the module configuration page, at 
 '/admin/config/services/usergeoaddress'


HOW TO USE
---------------------

* Go to Administration » Structure » Block Layout.
* Click on Place Block button (For respective region).
* Search for 'User Geo Location' and place it in respective the region.


MAINTAINERS
---------------------

Current maintainers:
  * Akshay Sawant : (https://www.drupal.org/u/aks22)
