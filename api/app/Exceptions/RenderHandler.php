<?php

namespace App\Exceptions;

use Exception;

class RenderException extends Exception
{
    /**
     * 报告异常
     *
     * @return void
     */
    public function report()
    {
        //
    }

    /**
     * 渲染异常为 HTTP 响应
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function render($request)
    {
        echo 1;die;
        return false;
    }
}