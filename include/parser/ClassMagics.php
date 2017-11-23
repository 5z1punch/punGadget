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
    private $checkFunc = [ "__construct()", "__destruct()", "__call()", "__callStatic()", "__get()", "__set()", "__isset()", "__unset()", "__sleep()", "__wakeup()", "__toString()", "__invoke()", "__set_state()", "__clone()", "__debugInfo()"];

    function __construct(PhpParser\Node $classNode, $codeFile)
    {
        $this->codeFile = $codeFile;
        $this->parserClass($classNode);
    }

    function parserClass(PhpParser\Node $classNode){
        $this->className = $classNode->name;
        $classMethodVisitor = new ClassMethodVisitor($this->checkFunc);
        $traverser = new PhpParser\NodeTraverser;
        $traverser->addVisitor($classMethodVisitor);
        $traverser->traverse($classNode);
        $this->magicFuncList = $classMethodVisitor->methodStmtsList;
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