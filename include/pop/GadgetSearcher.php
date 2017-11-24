<?php

/**
 * Created by PhpStorm.
 * User: ronnyschiatto
 * Date: 2017/11/23
 * Time: 下午3:06
 */
class GadgetSearcher
{
    private $usageMagics = ["__destruct", "__wakeup"];

    /**
     * 添加魔术方法的类型到 usageMagics 中，
     * 下一次循环开始时将扫描新加入的魔术方法类型。
     * @param array ...$magics
     */
    public function addMagics(...$magics)
    {
        foreach ($magics as $_magic) {
            // 保证不重复添加
            if(!in_array($_magic,$this->usageMagics)){
                $this->usageMagics[] = $_magic;
            }
        }
    }

}