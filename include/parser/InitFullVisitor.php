<?php

/**
 * Created by PhpStorm.
 * User: ronnyschiatto
 * Date: 2017/11/22
 * Time: 下午9:24
 */
class InitFullVisitor
{
    private $enterCallbackList = array();
    private $enterCallbackCount = 0;
    private $leaveCallbackList = array();
    private $leaveCallbackCount = 0;
    function addCallback($addType,$callback,...$callbackArgs){
        $callbackList = $addType."CallbackList";
        $callbackCount = $addType."CallbackCount";
        $this->$callbackList[$this->$callbackCount]["callback"] = $callback;
        $this->$callbackList[$this->$callbackCount]["callbackArgs"] = $callbackArgs;
        $this->$callbackCount += 1;
        return $this->$callbackCount-1;
    }

    function addEnterCallback($callback,...$callbackArgs){
        return $this->addCallback("enter",$callback,$callbackArgs);
    }

    function leaveEnterCallback($callback,...$callbackArgs){
        return $this->addCallback("leave",$callback,$callbackArgs);
    }

    public function removeCallback($removeType,$callbackPos){
        $callbackList = $removeType."CallbackList";
        unset($this->$callbackList[$callbackPos]);
    }

    public function removeLeaveCallback($callbackPos){
        $this->removeCallback("leave",$callbackPos);
    }

    public function removeEnterCallback($callbackPos){
        $this->removeCallback("enter",$callbackPos);
    }

    public function callbackNode($type,PhpParser\Node $node){
        $callbackList = $type."CallbackList";
        $tmpCallbackList = $this->$callbackList;
        foreach ($tmpCallbackList as $callback){
            $tmp = $callback["callbackArgs"];
            array_unshift($tmp,$node);
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