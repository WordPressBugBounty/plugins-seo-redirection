<?php

//  class checkoption     
/*
A simple class to create checkbox options     

----------------------------------------------------------
example:
----------------------------------------------------------
$check = new checkoption('redirect_control_panel');
$check->check($options['redirect_control_panel'],'1')

or 

$check = new checkoption('redirect_control_panel',$options['redirect_control_panel'],'1');

*/


if(!class_exists('checkoption')){
class checkoption{

var $name;


    function __construct($name,$check=null,$value='1')
    {

        $this->name=$name;
        $checked="";
        if(isset($check) && $check==$value)
            $checked="checked=checked";

        echo '<input type="checkbox" name="' . esc_attr($name) . '"  id="' . esc_attr($name) . '" ' . esc_attr($checked) . '  value="1">';	

    }
 
}}



?>