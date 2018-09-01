<?php
/** @var $this \Zodream\Template\View */

$css = $this->assetFile('@debugger.css')->read();

$this->registerJsFile('@jquery.min.js')
    ->registerJsFile('@debugger.min.js')
    ->registerCssFile('@font-awesome.min.css')
    ->registerCssFile('@zodream.css')
    ->registerCss($css);
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>404, PAGE NO FOUND!</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?=$this->header()?>
</head>
<body>
    <div class="debugger-error">
        <div class="error-content">
            <div class="title">
                Sorry, the page you are looking for could not be found.  
            </div>
        </div>
    </div>

    <?=$this->footer()?>
</body>
</html>