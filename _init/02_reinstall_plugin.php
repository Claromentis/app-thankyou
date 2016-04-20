<?php
/** @var $migrations \Claromentis\Setup\SetupFacade */

// re-add plugin to make sure it's installed when Claromentis is upgraded to 8
$plugins = $migrations->GetPluginsRepository();
$plugins->Add('thankyou', 'Claromentis\ThankYou\Plugin');
