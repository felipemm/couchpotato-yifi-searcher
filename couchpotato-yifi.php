<?php
//header("Content-Type: text/plain; charset=utf-8");
header('Content-Type: application/json; charset=utf-8');

//$username = $_GET["username"];
//$passkey  = $_GET["passkey"];
$imdbid   = (isset($_GET["imdbid"]) ? $_GET["imdbid"] : "");
$search   = (isset($_GET["search"]) ? urlencode($_GET["search"]) : "");;
$url      = "https://yts.ag/api/v2/list_movies.json?query_term=".$imdbid."&quality=1080p";
$agent= 'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1; .NET CLR 1.0.3705; .NET CLR 1.1.4322)';

//print_r($url);
$actual_link = "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
$filename = realpath(dirname(__FILE__))."/run.log";
$log = fopen($filename, "a");
fwrite($log, date("Y-m-d\TH:i:sP")." - REQUEST: ".$actual_link."\n");
fwrite($log, date("Y-m-d\TH:i:sP")." - RESPONSE: ".$url."\n");
fclose($log);


try{
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ch, CURLOPT_VERBOSE, true);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_USERAGENT, $agent);
	$result=curl_exec($ch);
	
	
	$result = json_decode($result, true);
	
	//print_r($result);
	if($result['data']['movie_count'] <= 0){
		echo json_encode(array("error" => "not found"));
		exit;
	}
	
	$response = array();
	
	foreach($result['data']['movies'] as $row){
		//var_dump($row);
		$name = $row['title_english'].".".$row['year'].'.1080p.BluRay-[YTS.AG]';
		
		$movie['release_name'] = $name;
		$movie['torrent_id']   = $row['id'];
		$movie['details_url']  = $row['url'];
		$movie['imdb_id']      = $row['imdb_code'];
		
		foreach($row['torrents'] as $torrent){
			if($torrent['quality'] == '1080p'){
				$movie['download_url'] = $torrent['url'];
				$movie['freeleech']    = true;
				$movie['type']         = 'movie';
				$movie['size']         = round($torrent['size_bytes']/1024/1024,2);
				$movie['leechers']     = $torrent['peers'];
				$movie['seeders']      = $torrent['seeds'];
				break;
			}
			
		}
		array_push($response, $movie);
	}
	//print_r($response);
	//print_r(json_encode(array("results"=>$response, "total_results"=>count($response))));
	echo json_encode(array("results"=>$response, "total_results"=>count($response)),JSON_NUMERIC_CHECK);
	
} catch (Exception $e) {
	echo "error: ". $e->getMessage();
} 


?>