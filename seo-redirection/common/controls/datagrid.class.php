<?php
/****************************************************************************
* datagrid.class.php
*
* version 1.0
*
* This script can be used to create dynamically HTML Tables for your website.
* 
* ----------------------------------------------------------------
*
* A demo example is included in demo folder.
*
* Copyright (C) 2012 Fakhri Alsadi <fakrhi.s@hotmail.com>
* 
*******************************************************************************/

require_once "pagination.class.php";

if(!class_exists('datagrid')){
class datagrid
{

	public $pagination;		
	private $table;
	private $cols;
	private $ext_fields;
	private $header;
	private $body;
	private $footer;	
	private $data_source;
	private $filter;
	private $order;
	private $paged=true;
	private $sql;
	private $no_data_text='No data available to display!';

//-----------------------------------------------------------------
	
	public function __construct($data_source='',$filter='')
	{

		$this->cols = array();
		$this->ext_fields = array();
		$this->table = array();
		
		$this->header = array();
		$this->body = array();
		$this->footer = array();
		
		$this->pagination = new cf_pagination($data_source,$filter);
		$this->set_data_source($data_source);
		$this->set_filter($filter);
		$this->set_table_attr('class','grid');

	}
	

//-----------------------------------------------------------------
	
	public function set_data_source($data_source)
	{
		$this->data_source=$data_source;
		$this->pagination->set_data_source($data_source);
	}

//-----------------------------------------------------------------

	public function get_data_source()
	{
		return $this->data_source;
	}
		
//-----------------------------------------------------------------
	
	public function set_filter($filter)
	{
		$this->filter=$filter;
		$this->pagination->set_filter($filter);
	}

//-----------------------------------------------------------------

	public function get_filter()
	{
		return $this->filter;
	}

//-----------------------------------------------------------------
	
	public function set_order($order)
	{
		$this->order=$order;
	}

//-----------------------------------------------------------------

	public function get_order()
	{
		return $this->order;
	}
//-----------------------------------------------------------------
	public function get_cols_count()
	{
		return count($this->cols);
	}
//-----------------------------------------------------------------
	
	public function set_paged($val)
	{
		if($val)
		$this->paged = true;
		else
		$this->paged = false;
	}

//-----------------------------------------------------------------
	
	public function is_paged()
	{
		return $this->paged;
	}

//-----------------------------------------------------------------
	
	public function set_no_data_text($text)
	{
		$this->no_data_text=$text;
	}
	
//-----------------------------------------------------------------
	
	public function get_no_data_text()
	{
		return $this->no_data_text;
	}



//-----------------------------------------------------------------

	public function add_data_col($field, $title='')
	{
		$newindex=count($this->cols);	
		$this->cols[$newindex]['field']= $field;
		$this->cols[$newindex]['title']= $title;
	}

//-----------------------------------------------------------------

	public function add_html_col($html, $title='')
	{
		$newindex=count($this->cols);	
		$this->cols[$newindex]['html']= $html;
		$this->cols[$newindex]['title']= $title;
	}

//-----------------------------------------------------------------

	public function add_php_col($php, $title='')
	{
		$newindex=count($this->cols);
		$this->cols[$newindex]['php']= $php;
		$this->cols[$newindex]['title']= $title;
	}

//-----------------------------------------------------------------

	public function add_template_col($template,$param='', $title='')
	{
		$newindex=count($this->cols);
		$this->cols[$newindex]['template']= $template;
		$this->cols[$newindex]['title']= $title;
		$this->cols[$newindex]['param']= $param;
	}

//-----------------------------------------------------------------
	
	private function get_rs_field_name($field)
	{
		//$field=strtolower($field);
		$fileds=explode(".",$field);
		$field=$fileds[count($fileds)-1];
		$fileds=explode("as",$field);
		$field=trim($fileds[count($fileds)-1]);
		return $field;
	}

//-----------------------------------------------------------------

	private function get_select_fields()
	{
		$result="";
		for($i=0;$i<count($this->cols);$i++)
		{
			
			if(array_key_exists('field',$this->cols[$i]) && $this->cols[$i]['field']!='')
			{
				if($result=="")
					$result=$this->cols[$i]['field'];
				else
					$result= $result . ',' . $this->cols[$i]['field'];
			}
		}
		
	
		for($i=0;$i<count($this->ext_fields);$i++)
		{
			if($this->ext_fields[$i]!='')
			{
				if($result=="")
					$result=$this->ext_fields[$i];
				else
					$result= $result . ',' . $this->ext_fields[$i];
			}
		}
	
		
	return $result;
	}

//-----------------------------------------------------------------

	public function add_select_field($field)
	{
		$this->ext_fields[count($this->ext_fields)]=$field;
	}


//-----------------------------------------------------------------


	private function &get_handler($group='')
	{
		$group = strtolower($group);
		$handler="";
		if($group=='header')
			$handler=&$this->header;
		else if($group=='footer')
			$handler=&$this->footer;
		else
			$handler=&$this->body;
			
		return $handler;
	}

//-----------------------------------------------------------------

	public function set_col_attr($index,$attr,$val,$group='')
	{
		
		if($index>0)
		$index = $index -1;
		else
		$index=0;
		
		$handler=&$this->get_handler($group);
		$handler['col'][intval($index)][$attr]=$val;
		
	}
	
//-----------------------------------------------------------------

	public function get_col_attr($index,$attr,$group='')
	{
		
		if($index>0)
		$index = $index -1;
		else
		$index=0;
		
		$handler=&$this->get_handler($group);
		return $handler['col'][intval($index)][$attr];
	}

//-----------------------------------------------------------------

	public function set_rows_attr($attr,$val,$group='')
	{
		
		$handler=&$this->get_handler($group);
		$handler[rows][$attr]=$val;
		
	}
	
//-----------------------------------------------------------------

	public function get_rows_attr($attr,$group='')
	{
		$handler=&$this->get_handler($group);
		return $handler[rows][$attr];
	}
	
//-----------------------------------------------------------------

	public function set_table_attr($attr,$val)
	{
		$this->table[$attr]=$val;
		
	}
	
//-----------------------------------------------------------------

	public function get_table_attr($attr)
	{
		return $this->table[$attr];
	}

//-----------------------------------------------------------------

	private function is_html_attr($attr)
	{
		$reserved[0]='data_field';
		$reserved[1]='html';
		$reserved[2]='text';
		$reserved[3]='php';
		
		return !in_array(strtolower($attr),$reserved);
	}




//-----------------------------------------------------------------

	private function fill_data()
	{
		global $wpdb;
		
		if($this->get_data_source() == '' )
			die("No Data Source Specified!");
		
		$title= array();
		for($i=0;$i<count($this->cols);$i++)
		{
			$title[$i]=$this->cols[$i]['title'];
		}
		if(is_array($title))
		$this->insert_row($title,'header');
			
			
		$fields=$this->get_select_fields();
		$tables=$this->get_data_source();
		$filter=$this->get_filter();
		$limit='';
		$order=$this->get_order();
		
		if($order!='')
		$order=' order by ' . $order;
		
		if($filter !='')
		{
			$filter = "where $filter";
		}
		
		
		if($this->is_paged())
		{
			$limit= $this->pagination->get_sql_limit();
		}
		
		
		$sql= " select $fields from $tables $filter $order $limit  ";
		$this->sql=$sql;


		$res= $wpdb->get_results($sql,ARRAY_A);
		$row_count=0;
		
		foreach ( $res as $ar){ 
		
		$row_count++;
		
		$ar['row_count']=$row_count;
		
		extract($ar, EXTR_PREFIX_ALL, "db"); 
				
			$row= array();
			for($i=0;$i<count($this->cols);$i++)
			{
				if(array_key_exists('field',$this->cols[$i]) && $this->cols[$i]['field']!='')
				{
					$row[$i]=$ar[$this->get_rs_field_name($this->cols[$i]['field'])];
					
				}else if(array_key_exists('php',$this->cols[$i]) && $this->cols[$i]['php']!='')
				{

					$patterns = array();
					$patterns[] = '/DB_ID/';
					$patterns[] = '/db_redirect_from_type/';
					$patterns[] = '/db_enabled/';
					$patterns[] = '/db_redirect_from_url/';
					$patterns[] = '/db_redirect_from/';
					$patterns[] = '/db_redirect_to_type/';
					$patterns[] = '/db_redirect_to_url/';
					$patterns[] = '/db_redirect_to/';
					$patterns[] = '/db_link/';
					$patterns[] = '/db_link_url/';
					$patterns[] = '/db_date_y/';
					$patterns[] = '/db_date_h/';
					$patterns[] = '/db_ip/';
					$patterns[] = '/db_rfrom_url/';
					$patterns[] = '/db_rfrom/';
					$patterns[] = '/db_rto_url/';
					$patterns[] = '/db_rto/';
					$patterns[] = '/db_rsrc_custom/';
					$patterns[] = '/db_referrer_var/';

					$replacements = array();
					$replacements[] = isset($db_ID)?absint($db_ID):'';
					$replacements[] = isset($db_redirect_from_type)?esc_html($db_redirect_from_type):'';
					$replacements[] = isset($db_enabled)?absint($db_enabled):'';
					$replacements[] = isset($db_redirect_from)?WPSR_make_absolute_url(esc_url($db_redirect_from)):'';
					$replacements[] = isset($db_redirect_from)?esc_html($db_redirect_from):'';
					$replacements[] = isset($db_redirect_to_type)?esc_html($db_redirect_to_type):'';
					$replacements[] = isset($db_redirect_to)?WPSR_make_absolute_url(esc_url($db_redirect_to)):'';
					$replacements[] = isset($db_redirect_to)?esc_html($db_redirect_to):'';
					$replacements[] = isset($db_link)?esc_url($db_link):'';
					$replacements[] = isset($db_link)?WPSR_make_absolute_url(esc_url($db_link)):'';
					$replacements[] = isset($db_ctime)?esc_html(date('Y-n-j',strtotime($db_ctime))):'';
					$replacements[] = isset($db_ctime)?esc_html(date('H:i:s',strtotime($db_ctime))):'';
					$replacements[] = isset($db_ip)?esc_html(preg_replace('/([0-9]+\.[0-9]+\.[0-9]+)\.[0-9]+/', '\1.***', $db_ip)):'';
					$replacements[] = isset($db_rfrom)?WPSR_make_absolute_url(esc_url($db_rfrom)):'';
					$replacements[] = isset($db_rfrom)?SR_cut_string(esc_url($db_rfrom),0,120):'';
					$replacements[] = isset($db_rto)?WPSR_make_absolute_url(esc_url($db_rto)):'';
					$replacements[] = isset($db_rto)?SR_cut_string(esc_url($db_rto),0,120):'';
					$rsrc_var = '';
					if(isset($db_rsrc) && $db_rsrc=='404'){$rsrc_var .=$db_rsrc;}
					if(isset($db_rsrc) && $db_rsrc=='Custom'){$rsrc_var .= sprintf("<a target='blank' href='?page=wp-seo-redirection.php&edit=%d'>%s</a>",absint($db_rID),esc_html($db_rsrc));}
					if(isset($db_rsrc) && $db_rsrc=='Post'){$rsrc_var .= sprintf("<a target='blank' href='post.php?action=edit&post=%d'>%s</a>",absint($db_postID),esc_html($db_rsrc));}
					$replacements[] = $rsrc_var;
					$db_referrer_var = '';
					if(isset($db_referrer) && $db_referrer !=""){
						$db_referrer_var =  sprintf("<a target='_blank' title='%s' href='%s'><span class='link'></span></a>",esc_url($db_referrer),esc_url($db_referrer));
					}
					$replacements[] = $db_referrer_var;

					$row[$i]= $this->replace_text_var($patterns,$replacements,$this->cols[$i]['php']);
			
				}else if(array_key_exists('html',$this->cols[$i]) && $this->cols[$i]['html']!='')
				{
					$html = $this->cols[$i]['html'];
					foreach ($ar as $key => $value)
					{
						$key_var = "db_" . $key;
						$html=str_ireplace('{' . $key_var . '}', $$key_var , $html);
					}

					$row[$i]= $html;					
				
				}else if(array_key_exists('template',$this->cols[$i]) && $this->cols[$i]['template']!='')

				{
					global $template;
					$temp = $this->cols[$i]['template'];
					$params = $this->cols[$i]['param'];
					$content = $template[$temp]['content'];
					if(is_array($params))
					for($j=0;$j<count($params);$j++)
					{
						$content=str_ireplace('{param' . $j . '}', $params[$j]  , $content);
					}else
					{
						$content=str_ireplace('{param}' , $params , $content);

					}
					if(is_array($template[$temp]['options']))
					{
						foreach ($template[$temp]['options'] as $key => $value )
						{
							$this->set_col_attr($i+1,$key,$value);
						}

					}
					foreach ($ar as $key => $value)
					{
						$key_var = "db_" . $key;
						$content = str_ireplace('{' . $key_var . '}', $$key_var ?? '', $content);
					}
					$row[$i]= $content;
				}
			}
			
			$this->insert_row($row);
		}
		
		
	}

//-----------------------------------------------------------------
	public function replace_text_var($patterns=array(),$replacements=array(),$string=''){
		return preg_replace($patterns, $replacements, $string);
	}
	public function run()
	{
	
	$this->fill_data();			
	$this->show_table();
	if(!array_key_exists('data',$this->body) || count($this->body['data'])==0)
	{
		echo '<div><p align="center">' . esc_html($this->no_data_text) . '</p></div>';

	}	
	if($this->is_paged())
		{
			echo "<BR/>";
			$this->pagination->print_pagination();
		}
	}	
	
	
//-----------------------------------------------------------------
	
	private function insert_row($array, $group='')
	{
		$handler=&$this->get_handler($group);
		
		if(!array_key_exists('data',$handler))
		{
		    $handler['data']= array();
		}
		
		$handler['data'][count($handler['data'])]=$array;
	}	
	

//-----------------------------------------------------------------
	
	private function update_row($index, $array, $group='')
	{
		$handler=&$this->get_handler($group);
		$handler['data'][$index]=$array;
	}	
	
//-----------------------------------------------------------------

	private function show_table()
	{
		
		$body=&$this->get_handler();
		$header=&$this->get_handler('header');	
		$footer=&$this->get_handler('footer');	
		
		// print the table tag start
		
		echo '<table class="wp-list-table widefat fixed striped" width="100%">';
		
		if(array_key_exists('data',$header) && count($header['data'])>0){
			echo '<thead>';
			for($i=0;$i< count($header['data']);$i++)
				$this->get_html_row($i,'header');
			echo '</thead>';
		}
		
		if(array_key_exists('data',$footer) && count($footer['data'])>0){
			echo '<tfoot>';
			for($i=0;$i< count($footer['data']);$i++)
				$this->get_html_row($i,'footer');
			echo '</tfoot>';
		}
		
		if(array_key_exists('data',$body) && count($body['data'])>0){
			echo '<tbody>';
			for($i=0;$i< count($body['data']);$i++)
				$this->get_html_row($i);
			echo '</tbody>';
		}
		//print the table tag end
		echo '</table>';
	}	
	
//-----------------------------------------------------------------

	private function get_html_row($index, $group='')
	{
		
		$handler=&$this->get_handler($group);
		$row=$handler['data'][$index];
		
		$col_limit=$this->get_cols_count();
		if(count($row)<$col_limit)
		$col_limit=count($row) ;
		
				
		
		if(array_key_exists('rows',$handler))
			
	
		echo '<tr>';		
		for($i=0;$i<$col_limit;$i++)
		{

		

			echo '<td>' . sprintf('%s',$row[$i]) . '</td>';
		}
		echo '</tr>';
			
	}
	
//-----------------------------------------------------------------

	public function get_sql()
	{
		return $this->sql;
	}
	


}}




?>
