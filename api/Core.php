<?php

class Core{

	private $db;
	
	function readCSVFile($path){
		$res = array();
		if (($handle = fopen($path, "r")) !== FALSE) {
    		while (($data = fgetcsv($handle, 1000, ";")) !== FALSE) {
    			$res[] = $data;
    		}
    		return $res;
    	}else{
    		return false;
    	}

	}

	function getBD(){
		$this->db = mysqli_connect("localhost", "alex", "1234", "admin_intimsim");
		if ($this->db->connect_errno) {
		    echo "Не удалось подключиться к MySQL: ".$this->db->connect_error;
		}
		$this->db->set_charset("utf8");
	}

	function translit($string)
	{
	$rus = array('ё','ж','ц','ч','ш','щ','ю','я','Ё','Ж','Ц','Ч','Ш','Щ','Ю','Я');
	$lat = array('yo','zh','tc','ch','sh','sh','yu','ya','YO','ZH','TC','CH','SH','SH','YU','YA');
	$string = str_replace($rus,$lat,$string);
	$string = strtr($string,
	     "АБВГДЕЗИЙКЛМНОПРСТУФХЪЫЬЭабвгдезийклмнопрстуфхъыьэ",
	     "ABVGDEZIJKLMNOPRSTUFH_I_Eabvgdezijklmnoprstufh_i_e");
	  
	return($string);
	}

	function getTableAL($data){
		$lines = explode('<tr>', $data);
		unset($lines[0]);
		unset($lines[count($lines)]);
		$data = "<h3>Автолюкс</h3><table class='cities_dost'><tr><td>Услуги</td><td>Адрес</td><td>Телефон</td><td>Время работы</td></tr>";
		foreach ($lines as $key => $line) {
			$data .= "<tr>".$line;
		}
		$data .= "</table>";
		return $data;
	}

	function getTableNP($data){
		$html = "<h3>Новая почта</h3><table class='cities_dost'>";
		foreach ($data as $key1 => $d) {
			$html .= '<tr>';			
			foreach ($d as $key2 => $tab) {
				if($key2 != 0){
					$html .= "<td>".$tab."</td>";
				}				
			}
			$html .= '</tr>';
		}
		$html .= "</table>";
		return $html;
	}

	function addTable($table, $id){		
		$this->getBD();
		$id = intval($id);
		$res = $this->db->query("SELECT body FROM s_pages WHERE id='$id' LIMIT 1");
		$row = $res->fetch_array();
		$body = $row['body'];
		$legend = "<p><img src='files/pages/np_icon.png'>- Новая Почта, <img src='files/pages/al_icon.png'>- Автолюкс</p>";
		$tables = $legend.$body.$table;
		//$tables = htmlspecialchars($tables);				

		//$res = $this->db->query("UPDATE s_pages SET body=".$tables." WHERE id=".$id."");
		print_r($tables);
	}

	function import_locations(){
		$np = $_GET['np_file'];
		$al = $_GET['al_site'];
		$page_id = $_GET['page_id'];
		$city = $_GET['city'];
		//$city = urlencode($city);
		$city = urldecode($city);
		
		$data = $this->readCSVFile($np);
		// Forming HTML table for Nova Pochta
		$np_table = $this->getTableNP($data);	

		$it = 0;
		
		// Nova Pochta
		$locs = array();
		foreach ($data as $key => $d) {
			if($key == 0){
				continue;
			}else{
				$locs[$key]->page_id = $page_id;
				// Adress
				list($title_mark, $location) = explode(':', $d[1]);
				$locs[$key]->location = $d[0].",".$location;
				$locs[$key]->type = 'np';
				$locs[$key]->title = $title_mark;
			}
			$it = $key;
		}
		// Auto Lux
		$data = file_get_contents($al);
		list($trash, $table) = explode('<table class="tbl-agencies" cellspacing="0">', $data);
		list($table, $trash) = explode('<div id="sidebar">', $table);
		// Array for city
		$cities = explode('td class="area em" colspan="4"', $table);
		unset($cities[0]);		

		$s_index = 0;
		foreach ($cities as $key => $c) {
			list($s_city,$city_info) = explode('</td>', $c);
			$s_city = str_replace('>', '', $s_city);
			$s_city = trim($s_city);			
			if($s_city == $city){
				$s_index = $key;
				break;
			} 
		}

		// Parse search city		
		$al_locs = explode('<tr>', $cities[$s_index]);

		// Forming HTML table for AutoLux		
		$al_table = $this->getTableAL($cities[$s_index]);

		// Merge 2 tables
		$data_tables = $np_table.$al_table;

		unset($al_locs[0]);
		unset($al_locs[count($al_locs)]);
		
		foreach ($al_locs as $key => $loc) {
			$it++;
			$loc_tabs = explode('<td>', $loc);
			unset($loc_tabs[0]);
			$locs[$it]->page_id = $page_id;
			$locs[$it]->location = $city.", ".$loc_tabs[2];
			$locs[$it]->type = 'al';
			$locs[$it]->title = $loc_tabs[1];

			$locs[$it]->location = trim(str_replace('</td>', '', $locs[$it]->location));
			$locs[$it]->title = trim(str_replace('</td>', '', $locs[$it]->title));
		}

		$this->getBD();

		// Adding coords(locations) to BD
		foreach ($locs as $key => $loc) {			
			//$this->toBDLoc($loc, $this->db);	
		}

		// Adding tables to BD 
		$this->addTable($data_tables, $page_id);
	}

	function toBDLoc($data, $db){	
		$data->page_id = intval($data->page_id);
		$res = $db->query("INSERT INTO s_maps_locations (page_id,location,title,type) VALUES ($data->page_id,'$data->location','$data->title','$data->type')");		
	}

	function parseFromTill($from, $till, $string){
		// in future, maybe
	}

	function gen_cities(){
		$line = "Доставка прозводится в такие города: ";
		$this->getBD();
		$cities = array();
		$res = $this->db->query("SELECT name, url FROM s_pages WHERE menu_id=3");
		$it = 0;
		while($row=$res->fetch_array()){
			$cities[$it]->name = $row['name'];
			$cities[$it]->url = $row['url'];
			// Get city
			$names = explode(' ', $cities[$it]->name);
			$cities[$it]->city = $names[count($names)-1];
			$line .= "<a href='/pages/".$cities[$it]->url."'>".$cities[$it]->city."</a>, ";
			$it++;
		}

		print_r($line);

	}


function parse_yandex($data){
		$links = array();
		//$data = file_get_contents('C:/Work/Stat/data_y.txt');
		$items = explode('class="b-serp-item__title-link"', $data);
		unset($items[0]);
		
		$it = 1;
		foreach ($items as $key => $item) {			
			$item = str_replace(';', '', $item);
			$item = str_replace('&amp', '', $item);
			list($trash, $link) = explode('href="', $item);
			
						
			list($link, $trash) = explode('"', $link);

			if(!strstr($link, 'yabs') && !strstr($link, 'yandex')){
				$links[] = $link;
				$it++;				
				if($it > 5){
					break;
				}				
			}
		}

		return $links;
	}

	public function form_rel_res($links, $word){
		$line = "";
		if(!empty($links)){
			if(count($links) > 0){
				foreach ($links as $key => $link) {
					$line .= $word.";".$link.";\n";
				}
			}
		}
		return $line;
	}	

	function parse_google($data){
		$links = array();
		//$data = file_get_contents('C:/Work/Stat/data_g.txt');
		$data = strtolower($data);
		$items = explode('h3 class=r><a href="/url?q=', $data);
		unset($items[0]);
		$it = 1;
		foreach ($items as $key => $item) {
			$item = str_replace(';', '', $item);
			
			if(strstr($item, '&amp')){
				list($link, $trash) = explode('&amp', $item);	
			}else{
				list($link, $trash) = explode('"', $item);
			}		
			


			$links[] = $link;
			$it++;
			if($it > 5){
				break;
			}	
		}

		return $links;
	}

	function writeStatistic($path, $ylinks, $glinks, $keyword){
		if(file_exists($path)){
			$data = file_get_contents($path);
		}else{
			file_put_contents($path, '');
			$data = file_get_contents($path);
		}
		
		if(strlen($data) < 10){
			$header = "Запрос;Позиция;топ 5 Я;топ 5 G;\n";
			$data = $header;
		}		
		
		foreach ($ylinks as $key => $link) {
			$glink = "";
			if(isset($glinks[$key])){
				$glink = $glinks[$key];
			}
			$data .= $keyword.";".($key+1).";".$link.";".$glink.";\n";
		}

		file_put_contents($path, $data);
	}

	function count_product($cat_name){
		error_reporting(E_ALL ^ E_NOTICE);
		$this->getBD();
		$cats = array();
		// Get Id category
		$cat_name = trim($cat_name);
		$res = $this->db->query("SELECT id, parent_id FROM s_categories WHERE name='$cat_name' LIMIT 1");
		if($res->num_rows > 0){
			$row= $res->fetch_array();
			$cat_id = $row['id'];
			$cat_id = intval($cat_id);
			$cats[] = $cat_id;
			// Check inserted cats if main cat
			if($row['parent_id'] == 0){
				$res = $this->db->query("SELECT id FROM s_categories WHERE parent_id='$cat_id'");
				if($res->num_rows > 0){
					while($row = $res->fetch_array()){
						$cats[] = intval($row['id']);
					}
				}
			}
		}else{			
			return false;
		}	

		$count = 0;

		foreach ($cats as $key => $cat) {
			// Count products
			$res = $this->db->query("SELECT product_id FROM s_products_categories WHERE category_id = '$cat' ");
			$count += $res->num_rows;
		}

		return $count;		

	}

	function get_parse_line(){
		$line = "";
		$it = 0;
		if (($handle = fopen('D:/Work/Statistic/stat.csv', "r")) !== FALSE) {
    		while (($data = fgetcsv($handle, 1000, ";")) !== FALSE) {    			
    			
    			if($it == 0){    				
    				$line .= $data[0].";".$data[1].";".$data[2].";".$data[3].";"."Кол-во продуктов;\n";
    			}else{    				    				
    				$pr_count = $this->count_product($data[0]);
    				$line .= $data[0].";".$data[1].";".$data[2].";".$data[3].";".$pr_count.";\n";
    			}
    			$it++;
    		}

    		file_put_contents('D:/Work/Statistic/stat_plus_count.csv', $line);
    	}
	}

	// Adding unique shops domain to Base
	public function addDomain($path, $domains){
		$data = $this->readCSVFile($path);
		$e_domains = array();
		if(!empty($data) && $data){
			foreach ($data as $key => $d) {
				$e_domains[] = $d[0];	
			}

			$data = file_get_contents($path);			
		}		
		
		$line = "";
		foreach ($domains as $key => $domain) {
			if(!array_search($domain, $e_domains)){				
				$line .= $domain.";;\n";
			}
			
		}

		if(!$data){
			$data = $line;
		}else{
			$data .= $line;
		}		

		file_put_contents($path, $data);
		
		
	}

	public function whos_expires($whos){
		if(strstr($whos, 'expires:')){
			list($trash, $expires_part) = explode('expires:', $whos);
			list($expires, $trash) = explode('source:', $expires_part);
			echo $expires;
		}else{
			return false;
		}
	}

	function start($type){
		switch ($type) {
			case 'import_locations':
				$this->import_locations();
				break;
			case 'gen_cities':
				$this->gen_cities();
				break;
			case 'parse_yandex':
				$this->parse_yandex();
				break;
			case 'parse_google':
				$this->parse_google();
				break;
			case 'count_product':
				$this->get_parse_line();
				break;
			case 'shop_domain':
				$data = file_get_contents('D:\Work\Statistic\shop_domains\whos.txt');
				$this->whos_expires($data);
				break;
			
			default:
				# code...
				break;
		}
	}
}