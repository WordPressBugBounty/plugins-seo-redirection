<?php
/*


////  class datemenu  

/*

A simple class to create Date menu easily using PHP



----------------------------------------------------------

example:

----------------------------------------------------------



$datemenu = new datemenu();

$datemenu->date_print('nama','1-1-2009');

											

Note it must beside a form!

*/

////@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@




if(!class_exists('datemenu')){
class datemenu{

function __construct()

	{
		
	wp_enqueue_script('custom', plugins_url() . '/' . basename(dirname(__FILE__)) . '/cframework/cf_javascript/calendarDateInput.js', array('jquery'), false, true);

	}

		

function date_print($name,$value="")

{

	if($value=="")
	{
    ?>
	<script>DateInput("<?php echo esc_js($name)?>", true, 'YYYY-MM-DD')</script>
	<?php
	}
    else
	{
    ?>
	<script>DateInput("<?php echo esc_js($name)?>", true, 'YYYY-MM-DD',"<?php echo esc_js($value)?>")</script>
	<?php

	}	
}
				



}}













?>