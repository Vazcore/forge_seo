<!DOCTYPE html>
<html lang="ru">
	<head>
		<title>Forge Coding</title>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
		<link rel="stylesheet" type="text/css" href="css/bootstrap.css">
	</head>
	<body>
	<div class="container">
		<h1 align="center">Alexey Gabrusev's Code</h1>
		<hr>
		<h3>Import Locations</h3>
		<ul>
			<form method="get" action="action.php">
				<input name="page_id" type="text" placeholder="id page"><br><br>
				<input name="city" type="text" placeholder="city">	<br><br>
				<input name="np_file" type="text" placeholder="Nova Pochta File"><br><br>
				<input name="al_site" type="text" placeholder="Auto Lux Site">	<br><br>		
				<input type="hidden" name="type" value="import_locations">			
				<input type="submit" value="Start importing">
			</form>			
		</ul>

		<h3>Import Locations</h3>
		<ul>
			<li><a href="action.php?type=gen_cities">Gen Cities</a></li>
		</ul>

		<h3>Parsing SE</h3>
		<ul>
			<li><a href="action.php?type=parse_yandex">Parse Yandex</a></li>
			<li><a href="action.php?type=parse_google">Parse Google</a></li>
			<li><a href="action.php?type=count_product">Count Product</a></li>
		</ul>
	</div>		
	</body>
</html>