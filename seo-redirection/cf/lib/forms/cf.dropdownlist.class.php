<?php
/*
	Author: Fakhri Alsadi
	Date: 16-7-2010
	Contact: www.clogica.com   info@clogica.com
	A simple class to create Drop down lists easily using PHP
	----------------------------------------------------------
	example:
	----------------------------------------------------------
	$drop = new dropdownlist('gendar');
	$drop->add('mail','mail');`
	$drop->add('femail','femail');
	$drop->run();
	$drop->select('femail');
	//////////////////////////////
	$drop = new dropdownlist('gendar');
	$drop->data_bind('data_status');
	$drop->run();
*/

if(!class_exists('dropdown_list')){
class dropdown_list{

	private $name='drop';
	private $options='';
	private $class='';
	private $onchange='';

	function __construct($str,$class='',$onchange='')
	{
		$this->name=$str;

		if($class!='')
		{
			$this->class=$class;
		}

		if($onchange!='')
		{
			$this->onchange=$onchange;
		}
	}

	public function add($name,$value,$data_icon='')
	{
		if($data_icon!='')
		{
			$this->options=$this->options. "<option data-icon='".esc_attr($data_icon)."' value='".esc_attr($value)."'>".esc_html($name)."</option>";
		}else
		{
			$this->options=$this->options. "<option value='".esc_attr($value)."'>".esc_html($name)."</option>";
		}
	}

	public function onchange($onchange)
	{
		$this->onchange=$onchange;
	}

	public function run(&$jforms=null)
	{
		if($this->onchange == '')
		{
			echo "<select data-size='5' class='selectpicker'  name='" . esc_attr($this->name). "' id='" . esc_attr($this->name). "' >" . sprintf('%s',$this->options) . "</select>";

		}else
		{
			echo "<select data-size='5' class='selectpicker' name='" . esc_attr($this->name). "' id='" . esc_attr($this->name). "'  onchange='" . esc_attr($this->onchange) . "' >" .  sprintf('%s',$this->options) . "</select>";
		}

		if(!is_null($jforms))
		{
			$jforms->add_select_picker();
		}
	}

	public function select($str)
	{
		echo "<script>document.getElementById('" . esc_js($this->name) . "').value='".esc_html($str)."'</script>";
	}

	public function select_array_option($array,$key)
	{
		if(array_key_exists($key,$array))
		{
			$this->select($array[$key]);
		}
	}

	public function data_bind($tbl,$name="name",$id="id",$where="",$order="",$limit="")
	{
		global $wpdb;
		$res = $wpdb->get_results("select $id,$name from PREFIX_$tbl $where $order $limit ", ARRAY_A);
		foreach ( $res as $ar){
			$this->add($ar[1],$ar[0]);
		}
	}

	}}
