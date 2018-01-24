<?php
/**
 * @var \Claromentis\Setup\SetupFacade $migrations
 */
$panels = $migrations->GetAdminPanelCreator();
$panels->Enable('thankyou');
