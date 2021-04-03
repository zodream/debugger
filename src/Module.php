<?php
namespace Zodream\Debugger;
/**
 * Created by PhpStorm.
 * User: ZoDream
 * Date: 2017/1/1
 * Time: 19:22
 */

use Zodream\Route\Exception\NotFoundHttpException;
use Zodream\Route\Controller\Module as BaseModule;

class Module extends BaseModule {

    public function boot()
    {
        if (!app()->isDebug()) {
            throw new NotFoundHttpException('当前模块不允许');
        }
    }
}