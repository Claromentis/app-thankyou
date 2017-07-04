# "Thank you" application

A simple application to say thanks to fellow employee

 * Version 3.1 - for Claromentis 8.2
 * Version 3.0 - for Claromentis 8.1 (includes Pages app component) 
 * Version 2.x - for Claromentis 8.0
 * Version 1.x - for Claromentis 7.3 and 7.4


## Installation

Via composer

Add this line to `modules.json`

``"claromentis/thankyou":"*"``

then run the installer


Manually:
  * clone this repository to /intranet/thankyou
  * go to "application" folder in Claromentis and run ``./clc app:install thankyou``

## Usage

Once installed, this application adds "Thanks" tab on each user's profile that displays list of latest 10 thanks.
It's also possible to add a global list of thanks on the intranet home page or any other page using a component:

``<component class="\Claromentis\ThankYou\UI\Say" allow_new="1" limit="10">``

