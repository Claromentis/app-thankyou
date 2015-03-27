# "Thank you" application

A simple application to say thanks to fellow employee

## Installation

Via composer

``composer require claromentis/thankyou:"*"``

Manually:
  * clone this repository to /intranet/thankyou
  * go to "web" folder in Claromentis and run ``phing -Dapp=thankyou install``

## Usage

Once installed, this application adds "Thanks" tab on each user's profile that displays list of latest 10 thanks.
It's also possible to add a global list of thanks on the intranet home page or any other page using a component:

``<component class="\Claromentis\ThankYou\UI\Say" allow_new="1" limit="10">``

