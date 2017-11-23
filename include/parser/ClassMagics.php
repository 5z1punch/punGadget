<?php

/**
 * Created by PhpStorm.
 * User: ronnyschiatto
 * Date: 2017/11/22
 * Time: 下午4:25
 * 存储类信息：类名、类中的魔术方法、类所在的文件
 */
class ClassMagics
{
    private $className;
    private $magicFuncList;
    private $codeFile;
    public $callbackPos;
    private $checkFunc = [ "__construct()", "__destruct()", "__call()", "__callStatic()", "__get()", "__set()", "__isset()", "__unset()", "__sleep()", "__wakeup()", "__toString()", "__invoke()", "__set_state()", "__clone()", "__debugInfo()"];

    function __construct(PhpParser\Node $classNode, $codeFile)
    {
        $this->codeFile = $codeFile;
        $this->className = $classNode->name;
    }

    function parserClass(PhpParser\Node $node,ClassMethodVisitor $visitor,$pos){
        if($node->getType()=="Stmt_ClassMethod" && in_array($node->name,$this->checkFunc)) {
            $this->magicFuncList[$node->name] = $node;
            $visitor->removeEnterCallback($pos);
            $visitor->addLeaveCallback(function($node,$visitor,$pos){
                $this->callbackPos = $visitor->addEnterCallback([$this,"parserClass"]);
                $visitor->removeLeaveCallback($pos);
            });
        }
    }

    function getMethod($methodName){
        if(isset($this->magicFuncList[$methodName])) {
            return $this->magicFuncList[$methodName];
        }
        else{
            return null;
        }
    }
}