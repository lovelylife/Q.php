<?php
/*
 $ COMMAND class
 $ author: Q
 $ create: 2012-07-26 17:01:33 
 $ command 模块都是服务于命令行程序的
*/


class COMMAND {
    // 输出通道
    private $_out;
    function __construct($theApp, $argc, $argv) {
        $this->_out = "";
    }

    function AppTool($theApp, $argc, $argv) {
        $this->__construct($theApp, $argc, $argv);
    }

    function out($value) { $this->_out .= $value."\n"; }

    function& stdout() {
        // 输出
        return $this->_out;
    }
}

?>
