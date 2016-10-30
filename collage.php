<?php
error_reporting (E_ALL ^ E_NOTICE);
/***************************
Last.fm Collage Generator
Developer: AntaresMHD (Twitter: @AntaresMHD)

CHANGELOG:

v.1.7 30/10-2016
- Changed the font used for an open free version (Fira Sans), available at https://www.fontsquirrel.com/fonts/fira-sans
- Complete code overhaul pending, including proper documentation of functions and optimization

v.1.65b 27/06/2011
Fixes:
In order to comply with Last.fm's new standards, some parts of the script
have been rewritten in order to make speedier the collage generation, using 
auto-generated 300x300 images directly from the server instead of the high
resolution pictures resized on-the-fly.

v.1.60b 04/06/2011
New:
- Added another parameter (caption) to attach the artist and band name

v.1.55b 28/04/2011
Fixes:
- Avoids "hipster victories" by collecting only the albums with covers from the server.
- Changes in code make the collage generation a tad faster.

v.1.50 22/04/2011
New:
- Frontend created by WaitWhat, new functions from previous version added to the GUI.

v.1.40b 14/03/2011
Fixes:
- Now generates every collage efficiently (using mod opers instead of shitty switches)
- Caching call action happens before making the last.fm call
- Less lines of code

New:
- If the second parameter "type" is set, it generates the collage with a different time period

v.1.0b 12/03/2011
- First version of the script, basic 7 days collage
- Shows "hipstervictory.jpg" when it can't find an album cover
- Basic cache: lasts 1h in server

***************************/
require("phplastfm/lastfmapi/lastfmapi.php");
set_time_limit(240);
//ini_set('max_execution_time', 240);

//Variables used for last.fm setup
$authVars = array(
	'apiKey' => 'xxx', //To obtain an api key, go to http://www.last.fm/api
	'secret' => 'xxx', //To obtain an api secret, go to http://www.last.fm/api, create an app and use the secret
	);
$config = array(
	'enabled' => true,
	'path' => './',
	'cache_length' => 1800
);
// Pass the array to the auth class to eturn a valid auth
$auth = new lastfmApiAuth('setsession', $authVars);

$apiClass = new lastfmApi();
$userClass = $apiClass->getPackage($auth, 'user', $config);

if ($_GET["type"]!=null && ($_GET["type"]== "overall" || "12month" || "6month" || "3month" || "1month" || "7day")){$period= $_GET["type"];
} else if ($_GET["type"]== null) {
	$period = "7day";
} else {
	echo "Invalid period set.";
	exit;
}

// Setup the variables
$methodVars = array(
	'user' => $_GET["user"],
	//'period' => '7day',
	'period' => $period,
	/* 'limit' => 9 */ 'limit' => 54
);

// Check if the newbs are doing things the way they should
if ($_GET['user']== null){
echo 'No user has been specified.';
exit;
} else if (is_cached($_GET['user'], $period)){ // Sends the cached 
	return is_cached($_GET['user'], $period);
	exit;
}

if ( $chart = $userClass->getTopAlbums($methodVars) ) { //get all the results according to the variables
// does the magic here
		generate_collage($chart, $period);
		exit;

} else { // if the user doesn't exist
	die('<b>Error '.$userClass->error['code'].' - </b><i>'.$userClass->error['desc'].'</i>');
		echo "The user specified doesn't exist.";
	}

// Functions

//function that generates the chart according to the values passed via parameter
function generate_collage ($chart, $period) {

$img = array(); //array that will store the image resources
//$caption_artist = array(); //in case the parameter is set to true, this will add the artist - album name.
$caption_album = array(); //in case the parameter is set to true, this will add the artist - album name.

// Set a maximum height and width
$width = 150; //in the future, these two values might be passed via parameters, maybe not, who knows
$height = 150;

// Content type
header('Content-type: image/jpeg');

// Create main image
//$output = imagecreatetruecolor(900, 900);

$img_url = array(); //array that will contain all the url pics

for ($j=0; $j<count($chart); $j++) { //this cycle (v.1.57b

	if ((preg_match('/noimage/', $chart[$j]['images']['large'], $matches)==1) && $j< count($chart)) {
		// $img_url= 'res/HIPSTERVICTORY.jpg'; //change it for artist - album?
		continue;
	} else {
		
		if (preg_match('/userserve/', $chart[$j]['images']['large'], 

$matches)==1){
			//$img_url[]= str_replace('/126/', '/_/', $chart[$j]['images']['large']);
			$img_url[]= str_replace('/126/', '/300x300/', $chart[$j]['images']['large']);
				 if ($_GET['caption'] == true) { 

$caption_artist[] = $chart[$j]['name']; 
$caption_playcount[] = "Plays: " . $chart[$j]['playcount'];  } 
		} else {
			$img_url[]= str_replace('/_/', '/300x300/', $chart[$j]['images']['large']);
				if ($_GET['caption'] == true) { 

$caption_artist[] = $chart[$j]['name']; 
$caption_playcount[] = "Plays: " . $chart[$j]['playcount'];  }
		}
		
	}
	
	if (count($img_url)>15){
		break;
	}
}


// Create main image // moved down here
$y_size_of_image =  ceil(count($img_url) / 2 ) * 150; //dynamically changes the height of the collage picture!  (add to the 3x3) 
$output = imagecreatetruecolor(300, $y_size_of_image);

// Get and resample images

for ($i=0; $i<count($img_url); $i++) {

	// Get the extension
	$ext = substr($img_url[$i], -3);
	
	// Get new dimensions
		list($width_orig, $height_orig) = getimagesize($img_url[$i]);
	
	switch ($ext) {
		case 'png':
			$img[$i]= imagecreatefrompng($img_url[$i]);
		break;

		case 'jpg':
			$img[$i]= imagecreatefromjpeg($img_url[$i]);
		break;

		case 'gif':
			$img[$i]= imagecreatefromgif($img_url[$i]);
		break;		
	}

	if (preg_match('/300x300/',  $img_url[$i], $matches)!=1){ //if the image hasn't been resized on the server as a
	
		$img_res = imagecreatetruecolor(150, 150); //temp image used for resizing that will be deleted later
		imagecopyresampled($img_res, $img[$i], 0, 0, 0, 0, $width, $height, $width_orig, $height_orig);
	} else {
		$img_res = $img[$i];
	}
	
	// Get the coordinates where the image should be placed
		$x = ($i % 2)* 150; $y = (int) ($i / 2)*150; //the magic of math...
	
	//places image into collage
	imagecopymerge($output, $img_res, $x, $y, 0, 0, 150, 150, 100);

	
	if ($_GET['caption'] == true) 
{
//color of the font of the artist & band name
		
		$start_x = 1;
		$start_y = 1;
$color_tran = imagecolorsforindex($img_res, imagecolorat($img_res, $start_x, $start_y)); //extracts the color from the image at the specified index

		//assigns the color
		
		$font_color = imagecolorallocate($output, 255, 255, 255); 
		$black = imagecolorallocate($output, 0, 0, 0);
		// Path to our ttf font file
		$font_file = './FiraSans-Book.otf';

		// Draw the artist & band name with the specified params

if($_GET['playcount'] == true)
{
imagettftext($output, 9, 0, ($x + 1), ($y + 13), $black, $font_file, $caption_artist[$i]);
imagettftext($output, 9, 0, ($x + 2), ($y + 12), $font_color, $font_file, $caption_artist[$i]);
imagettftext($output, 9, 0, ($x + 1), ($y + 25), $black, $font_file, $caption_playcount[$i]);
imagettftext($output, 9, 0, ($x + 2), ($y + 24), $font_color, $font_file, $caption_playcount[$i]);
}
else
{
imagettftext($output, 9, 0, ($x + 1), ($y + 13), $black, $font_file, $caption_artist[$i]);
imagettftext($output, 9, 0, ($x + 2), ($y + 12), $font_color, $font_file, $caption_artist[$i]);
}
               	 //5th parameter (y) should not be less than 10
                

		//imagefttext($output, 9, 0, ($x + 2), ($y + 12), $font_color, $font_file, $caption_album[$i]); //5th parameter (y) should not be less than 10

		//imagefttext($output, 9, 0, ($x + 2), ($y + 12), $font_color, $font_file, $img_url[$i]); //5th parameter (y) should not be less than 10 //For tests!
		//imagefttext($output, 9, 0, ($x + 2), ($y + 48), $font_color_opposite, $font_file, $opposite); //5th parameter (y) should not be less than 10

	}

	//destroys image to clear up space in memory
	imagedestroy($img_res);
}

// Now finally, display the image... While saving it in the cache, seeing as it takes way too long
	
	if ($_GET['caption']== true) { 
		$cache = 'cache/'. strtolower($_GET['user']) . '_' . $period . '_sidebarCAPTION.jpg';
	} else {
		$cache = 'cache/'. strtolower($_GET['user']) . '_' . $period . 'sidebar.jpg';
	}
	
	imagejpeg($output, null, 90);
	imagejpeg($output, $cache, 90); //saves a copy to the cache

	//destroys image afterwards, to clear up space in memory
	imagedestroy($output);
}

// function to create or return a cache
function is_cached($user, $period) {
 
if ($_GET['caption']== true) { 
$possible_cached_file= strtolower($user). '_' . $period . '_sidebarCAPTION.jpg';
} else {
$possible_cached_file= strtolower($user). '_' . $period . 'sidebar.jpg';
 
}
 
$path = scandir('cache/');
 
if (array_search($possible_cached_file, $path) != false) { //if the file is present, let's proceed to analyze it
    $fpath= 'cache/' . $possible_cached_file;
    //$age = time() - (7 * 24 * 60 * 60); //seven days old
    //$age = time() - (12 * 60 * 60); //12 hours old
    $age = time() - (2 * 60 * 60); //2 hours old
     
    if ($age < filectime($fpath)){ //if the file cached on the server is less than the specified age
        header('Content-type: image/jpeg');
        readfile($fpath);
        exit;
    } else { // if it isn't
        return false;
    }
 
}  else { // if the file isn't there
    return false; 
}
 
}
?>