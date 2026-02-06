<?php

////  class dropdown
/*
A simple class to create Drop down lists easily using PHP

----------------------------------------------------------
example:
----------------------------------------------------------
$drop = new dropdown('gendar');
$drop->add('mail','mail');`	
$drop->add('femail','femail');
$drop->dropdown_print();
$drop->select('femail');


//////////////////////////////

$drop = new dropdown('gendar');
$drop->data_bind('data_status');
$drop->dropdown_print();



*/
////@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@

if(!class_exists('dropdown')){
class dropdown{

var $name='drop';
var $options= array();
var $class='';
var $onchange='';

function __construct($str,$class='',$onchange='')
	{
	$this->name=$str;
	
	if($class!='')
	$this->class=$class;
	
	if($onchange!='')
	$this->onchange=$onchange;
	
	}
		
function add($name,$value)
	{
	//$this->options=$this->options. "<option value='$value'>$name</option>";
	
	$this->options[] = array(
			'key'=>esc_html($value),
			'name'=>esc_html($name)
		);
		
	}	
	
public function dropdown_print()
	{
		?>
			<select size='1' name='<?php esc_attr_e($this->name);?>' <?php if($this->onchange != ''){?> onchange='<?php echo esc_attr_e($this->onchange);?>' <?php } ?> id='<?php esc_attr_e($this->name);?>'>
			<?php 
			foreach($this->options as $options){
				?>
				<option value="<?php esc_html_e($options['key']);?>"><?php esc_html_e($options['name']);?></option>
				<?php
			}
			?>
			</select>
			<?php
		
	}
		
public function select($str)
	{
		?>
		<script>document.getElementById('<?php echo esc_js($this->name);?>').value='<?php echo esc_js($str);?>'</script>
		<?php
	}	
	
function data_bind($tbl,$name="name",$id="id",$where="",$order="",$limit="")
	{
		global $wpdb;
		$res = $wpdb->get_results("select $id,$name from PREFIX_$tbl $where $order $limit ", ARRAY_A);
		foreach ( $res as $ar){ 
			$this->add($ar[1],$ar[0]);
		}

		
	}		

}}





?>