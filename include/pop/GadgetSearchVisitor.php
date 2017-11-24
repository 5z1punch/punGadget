<?php

/**
 * Created by PhpStorm.
 * User: ronnyschiatto
 * Date: 2017/11/23
 * Time: 下午3:19
 */
class GadgetSearchVisitor extends PhpParser\NodeVisitorAbstract
{
    private $sourceVar;
    private $spreadVars = array();
    // $foundMarker 的节点细节里，感觉只存文件名、类名和行号比较好
    public $foundMarker = array();
    private $svarFlag = null;
    const SVAR_SET = 1;

    /**
     * 定义源变量，从此变量开始查找传播路径
     * GadgetSearchVisitor constructor.
     * @param $sourceVar
     */
    public function __construct($sourceVar)
    {
        $this->sourceVar = $sourceVar;
        $this->spreadVarsAppend($sourceVar);
    }

    /**
     * 判断一个节点是不是来自源变量
     * todo 源变量的范围如何判定
     * todo 表达式计算
     * @param \PhpParser\Node $node
     * @return bool
     */
    public function fetchStruct(PhpParser\Node $node, $praperty=False){
        if($praperty and $node->getType()!="Expr_PropertyFetch"){
            return False;
        }
        if($node->getType()=="Expr_PropertyFetch" or $node->getType()=="Expr_ArrayDimFetch"){
            $node = $node->var;
        }
        if($node->getType()=="Expr_ConstFetch" and in_array($node->name->parts[0],$this->spreadVars)){
            return True;
        }
        elseif($node->getType()=="Expr_Variable" and in_array($node->name,$this->spreadVars)){
            return True;
        }
        else{
            return False;
        }
    }

    /**
     * 判断源变量的状态是被get还是被set，
     * 如果其类型为属性，则将其将其添加到对应的魔术方法中。
     * @param \PhpParser\Node $node
     */
    public function set1get(PhpParser\Node $node){
        if( $node->getType()=="Stmt_Foreach" ){
            if($this->fetchStruct($node->keyVar,True)){
                $node->keyVar->setAttribute("ifSet",self::SVAR_SET);
            }
            if( $this->fetchStruct($node->valueVar,True) ){
                $node->valueVar->setAttribute("ifSet",self::SVAR_SET);
            }
        }
        elseif($node->getType()=="Expr_Assign" and $this->fetchStruct($node->var,True)){
            $node->var->setAttribute("ifSet",self::SVAR_SET);
        }
        // TODO 函数调用会触发 __set 吗？
//        elseif($node->getType()=="Expr_FuncCall"){
//            /**
//             * 目前仅仅处理preg_match
//             */
//            if($node->name->parts[0]=="preg_match" and $this->fetchStruct($node->args[2]->vaule,True)){
//                $this->svarFlag = self::SVAR_SET;
//                $this->svarCheckNode = $node->args[2];
//            }
//        }
    }

    /**
     * 如果传播变量列表中不存在var，则加入
     * @param $var
     */
    public function spreadVarsAppend($var){
        if (!in_array($var, $this->spreadVars)){
            $this->spreadVars[] = $var;
        }
    }

    /**
     * 识别该node上的语法结构是否是一个传播结构，
     * 如果是，则将该被污染的变量加入到扫描队列中
     * @param \PhpParser\Node $node
     */
    public function spreadStruct(PhpParser\Node $node){
        if($node->getType()=="Stmt_Foreach" and $this->fetchStruct($node->expr)){
            $this->spreadVarsAppend($node->keyVar->name);
            $this->spreadVarsAppend($node->valueVar->name);
        }
        elseif($node->getType()=="Expr_Assign" and $this->fetchStruct($node->expr)){
            if($node->var->getType()=="Expr_Variable"){
                $this->spreadVarsAppend($node->var->name);
            }
            else{
                /**
                 * 赋值左侧可能不是变量
                 */
                // todo
            }
        }
        elseif($node->getType()=="Expr_FuncCall"){
            // TODO
            /**
             * 目前仅仅处理常量定义函数
             */
            if($node->name->parts[0]=="define" and $this->fetchStruct($node->args[1]->value)){
                $this->spreadVarsAppend($node->args[0]->vaule->vaule);
            }
        }
    }

    /**
     * 检查可控的属性经过了哪些可以造成隐式调用的语法结构
     * NodeVisitorAbstract抽象函数
     * @param \PhpParser\Node $node
     * @return int|null|\PhpParser\Node|void
     */
    public function enterNode(PhpParser\Node $node){
        $this->set1get($node);
        if(
            $node->getType()=="Expr_New"
            and $node->class->parts[0]=="ReflectionClass"
            and $this->fetchStruct($node->args[0]->value)
        ) {
            $this->foundMarker["__construct"][] = $node;
        }
        elseif ($this->fetchStruct($node)){
            if($node->getType()=="Expr_MethodCall"){
                $this->foundMarker["__call"][] = $node;
            }
            elseif ($node->getType()=="Expr_StaticCall") {
                $this->foundMarker["__callStatic"][] = $node;
            }
        }
        elseif($node->getType()=="Expr_PropertyFetch"){
            if ($this->fetchStruct($node->var)){
                if ($node->getAttribute("ifSet")===self::SVAR_SET){
                    $this->foundMarker["__set"][] = $node;
                }
                else{
                    $this->foundMarker["__get"][] = $node;
                }
            }
        }
        elseif ($node->getType()=="Expr_Isset"){
            foreach ($node->vars as $var){
                if($this->fetchStruct($var)){
                    $this->foundMarker["__isset"][] = $var;
                }
            }
        }
        elseif ($node->getType()=="Expr_Empty"){
            if($this->fetchStruct($node->expr)){
                $this->foundMarker["__isset"][] = $node;
            }
        }
        elseif ($node->getType()=="Stmt_Unset"){
            foreach ($node->vars as $var){
                if($this->fetchStruct($var)){
                    $this->foundMarker["__unset"][] = $var;
                }
            }
        }
        elseif (
            $node->getType()=="Expr_FuncCall"
            and $node->name->parts[0]=="serialize"
            and $this->fetchStruct($node->args[0]->value)
        ){
            $this->foundMarker["__sleep"][] = $node;
        }
        // todo __string
        elseif ($node->getType()=="Expr_BinaryOp_Concat"){
            // todo 表达式计算
        }
        elseif ($node->getType()=="Stmt_Echo"){
            // todo
        }
        elseif ($node->getType()=="Expr_Print"){
            // todo
        }
        elseif ($node->getType()=="Expr_Exit"){
            // todo
        }
        elseif ($node->getType()=="Expr_FuncCall"){
            // todo
        }
    }
}