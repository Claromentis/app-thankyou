# "Thank you" application

A simple application to say thanks to fellow employees.

 * Version 3.2 - for Claromentis 8.2 and 8.3 (new features - see change log)
 * Version 3.1 - for Claromentis 8.2
 * Version 3.0 - for Claromentis 8.1 (includes Pages app component) 
 * Version 2.x - for Claromentis 8.0
 * Version 1.x - for Claromentis 7.3 and 7.4

## Installation

### Composer

Add the module to `modules.json`

```json
"claromentis/thankyou":"*"
```

then run the installer using `./clc resolve`

### Manually

* Clone this repository to `/web/intranet/thankyou`
* Run `./clc app:install thankyou` in the Claromentis application directory

## Usage

Once installed, this application adds "Thanks" tab on each user's profile that displays list of latest 10 thanks.
It's also possible to add a global list of thanks on the intranet home page or any other page using a component:

```html
<component class_key="thankyou" allow_new="1" limit="10">
```

