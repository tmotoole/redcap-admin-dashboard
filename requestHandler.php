<?php

$module = new \UIOWA\AdminDash\AdminDash();

if ($_REQUEST['type'] == 'getVisData') {
    $module->getVisData();
}
elseif ($_REQUEST['type'] == 'saveVisibilitySettings') {
    $module->saveVisibilitySettings();
}