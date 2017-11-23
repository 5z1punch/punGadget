<?php

/**
 * Created by PhpStorm.
 * User: ronnyschiatto
 * Date: 2017/11/22
 * Time: 下午9:24
 */
class InitFullVisitor extends PhpParser\NodeVisitorAbstract
{
    private $enterCallbackList = array();
    private $enterCallbackCount = 0;
    private $leaveCallbackList = array();
    private $leaveCallbackCount = 0;
    public $codeFile;

    function __construct($codeFile)
    {
        $this->codeFile = $codeFile;
    }

    private function addCallback($addType,$callback,...$callbackArgs){
        $callbackList = $addType."CallbackList";
        $callbackCount = $addType."CallbackCount";
        $this->$callbackList[$this->$callbackCount]["callback"] = $callback;
        $this->$callbackList[$this->$callbackCount]["callbackArgs"] = $callbackArgs;
        $this->$callbackCount += 1;
        return $this->$callbackCount-1;
    }

    public function addEnterCallback($callback,...$callbackArgs){
        return $this->addCallback("enter",$callback,$callbackArgs);
    }

    public function addLeaveCallback($callback,...$callbackArgs){
        return $this->addCallback("leave",$callback,$callbackArgs);
    }

    private function removeCallback($removeType,$callbackPos){
        $callbackList = $removeType."CallbackList";
        unset($this->$callbackList[$callbackPos]);
    }

    public function removeLeaveCallback($callbackPos){
        $this->removeCallback("leave",$callbackPos);
    }

    public function removeEnterCallback($callbackPos){
        $this->removeCallback("enter",$callbackPos);
    }

    /**
     * 将node节点传入回调函数，同时会传入该visitor对象本身，和当前callback的pos
     * 传入callback的第一个参数为node，第二个为this，第三个为pos
     * @param $type
     * @param \PhpParser\Node $node
     */
    private function callbackNode($type,PhpParser\Node $node){
        $callbackList = $type."CallbackList";
        $tmpCallbackList = $this->$callbackList;
        foreach ($tmpCallbackList as $pos => $callback){
            $tmp = $callback["callbackArgs"];
            array_unshift($tmp,$node, $this, $pos);
            call_user_func_array($callback["callback"],$tmp);
        }
    }

    public function enterNode(PhpParser\Node $node)
    {
        $this->callbackNode("enter",$node);
    }

    public function leaveNode(PhpParser\Node $node){
        $this->callbackNode("leave",$node);
    }

}