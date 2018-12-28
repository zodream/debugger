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
    <title><?=$info['name']?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?=$this->header()?>
</head>
<body>
    <div class="debugger-blue-screen">
        <div class="bs-header">
            <p><?=$info['name']?></p>
            <h1><?=$info['message']?></h1>
        </div>
        <?php foreach($exceptions as $ex):?>
        <div class="panel expanded">
            <div class="panel-header">
                <p class="name"><?=$ex['name']?>: <?=$ex['message']?></p>
                <p><?=$ex['file']?>: <?=$ex['line']?></p>
            </div>
            <div class="panel-body">
                <?php if(isset($ex['trace'])):?>
                <?php foreach($ex['trace'] as $item):?>
                <div class="panel expanded">
                    <div class="panel-header">
                        <p class="name">
                        <?php if(isset($item['class'])):?>
                        <?=$item['class']?><?=$item['type']?>
                        <?php endif;?>    
                        <?=$item['function']?>()</p>
                        <?php if(isset($item['file'])):?>
                        <p><?=$item['file']?>: <?=$item['line']?></p>
                        <?php endif;?>
                    </div>
                    <div class="panel-body">
                        <?=$ex['source']?>
                    </div>
                </div>
                <?php endforeach;?>
                <?php endif;?>
            </div>
        </div>
        <?php endforeach;?>
        
    </div>

    <?=$this->footer()?>
    <script>
    Debugger.blueScreen(<?=json_encode($info)?>, <?=json_encode($exceptions)?>);
    </script>
</body>
</html>