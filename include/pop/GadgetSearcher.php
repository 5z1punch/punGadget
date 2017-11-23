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
    public function addMagics(...$magics)
    {
        foreach ($magics as $_magic) {
            if(!in_array($_magic,$this->usageMagics)){
                $this->usageMagics[] = $_magic;
            }
        }
    }

}