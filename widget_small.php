<?php
include("lib/config/config.php");
define("LOCALE_MAIN", true);
require_once("lib/private/locale.php");
// On garde le fuseau actuel pour afficher les heures plus tard
$timezone = date_default_timezone_get();
// On passe en UTC pour correspondre au fuseau de l'API
date_default_timezone_set("UTC");

////////////////////// VARIABLES //////////////////////
// $token = "your token in the raidplanner";
// $url = "http://www.exemple.com/raidplannerdirectory/";
// $games = "wow,wowp,ff14,teso,sw,wsta"; choose yours !
// $columns = number; number of culumns
$token = "";
$url = '';
$games = "wowp";
$columns = 2;
////////////////// ADVANCED VARIABLES /////////////////             Name	API Default		Desc
$start = (time()-(60*60*4)); // 		start		0			Return raids starting after this UTC timestamp
$end = (time()+(60*60*24*7)); // 	end			2147483647	Return raids starting before this UTC timestamp
$limit = 5; // 						limit		10			Maximum number of raids to return. Passing 0 returns all raids
$offset = 0; // 					offset		0			Number of raids to skip if a limit is set
$location = ""; //					location	""			Return raids on specific locations. Comma separated list of location ids or names
$full = "true"; //					full		true		Do/do not return raids with all slots set
$free = "true"; // 					free		true		Do/do not return raids with not all slots set
$open = "true"; // 					open		true		Do/do not return raids which are open to attends
$closed = "true"; // 				closed		false		Do/do not return raids which are closed for attends
$canceled = "false"; //				canceled	false		Do/do not return raids which have been canceled
////////////////////// ROLE ICON //////////////////////
$roleicon = array(
	'tnk' => "role_tank",
	'med' => "role_heal",
	'dmg' => "role_melee",
	'atk' => "role_melee",
	'rgd' => "role_range",
	'mdd' => "role_melee",
	'rdd' => "role_range"
	);
////////////////////////////////////////////////////////
$ch = curl_init();
// On demande la liste des raids
curl_setopt($ch, CURLOPT_URL,$url.'lib/apihub.php?query=raid&start='.$start.'&end='.$end.'&limit=0&offset='.$offset.'&location='.$location.'&games='.$games.'&full='.$full.'&free='.$free.'&open='.$open.'&closed='.$closed.'&canceled='.$canceled.'&token='.$token);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$result = curl_exec($ch);
$raidlist = json_decode($result);
// On demande la liste des locations
curl_setopt($ch, CURLOPT_URL,$url.'lib/apihub.php?query=location&games='.$games.'&utf8=true&token='.$token);
$result = curl_exec($ch);
// curl_close($ch);
$locationresult = json_decode($result);
// On stock les locations dans une table
$location = array();
foreach($locationresult->result as $l){
	$location[$l->Id] = array(
	'Name' => $l->Name,
	'GameId' => $l->GameId,
	'Image' => $l->Image
	);
}
$locationfolder = $games;
if ($games == "wsta"){
	$locationfolder = "wildstar";
}elseif ($games == "wowp"){
	$locationfolder = "wow";
}elseif ($games == "sw"){
	$locationfolder = "swtor";
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html>
    <head>
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
        <title>Raidplaner Widget</title>
		<!-- <link rel="stylesheet" type="text/css" href="lib/layout/allstyles.php"/> -->
		<script type="text/javascript" src="lib/script/jquery-2.0.3.min.js"></script>
		
		<style>
		#raidlist {
			float: left;
			position: relative;
			text-align: left;
			font-size: 12px;
			font-family: Helvetica, Verdana, Arial, sans-serif;
			border-spacing: 0px 0px;
		}

		#nextRaids {
			width: 186px;
			margin: -5px;
		}

		.box_inlay {
			background-color: rgba(215, 215, 215, 0.5);
			border-radius: 5px;
			box-shadow: 1px 1px 4px 1px rgba(0, 0, 0, 0.25) inset;
		}
		
		#raidlist .raidSlot .locationImg {
			position: relative;
			float: left;
			left: 10px;
			top: 11px;
			width: 32px;
			height: 32px;
		}

		.raidSlot {
			display: inline-block;
			background-repeat: no-repeat;
			width: 175px;
			height: 80px;
			margin-right: 5px;
			margin-bottom: 1px;
			overflow: hidden;
		}

		.setupInfo {
			float: right;
			margin-top: -74px;
			margin-left: 20px;
			margin-right: -10px;
			width: 68px;
			height: 66px;
			overflow: hidden;
		}

		.setupInfoSlot {
			height: 27px;
			width: 140px;
			margin-bottom: 2px;
			padding-left: 32px;
			padding-top: 5px;
			font-size: 18px;
			color: rgb(170, 170, 170);
		}

		.raidSlot .raidInfo {
			display: block;
			padding: 11px;
		}

		.raidSlot .raidInfo .location {
			font-weight: bold;
			font-size: 12px;
			margin-bottom: 5px;
			height: 12px;
		}
		
		#raidlist .raidSlot .raidInfo {
			position: relative;
			float: left;
			font-size: 10px;
			left: 5px;
			top: -2px;
			width: 100px;
			margin-right: 20px;
			height: 60px;
		}
		</style>
    </head>
	<body style="min-width: 0px!important; min-height: 0px!important;">
	<div id="raidlist" style="width: <?php echo ($columns*180) ?>px!important;">
	<div id="nextRaids" style="width: <?php echo ($columns*180) ?>px!important;">
<?php
		// Boucle principale
		$index = 0;
		foreach($raidlist->result as $v){
			if ($index < $limit){
			$apiurl = $url.'lib/apihub.php?query=raid&raid='.$v->RaidId.'&attends=true&full='.$full.'&free='.$free.'&open='.$open.'&closed='.$closed.'&canceled='.$canceled.'&token='.$token;
			curl_setopt($ch,CURLOPT_URL,$apiurl);
			curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
			$result = curl_exec($ch);
			$resultapi = json_decode($result);

			// On recupere les infos du raid
			date_default_timezone_set("UTC");
			$Status = $v->Status;
			$Start = $v->Start;
			$End = $v->End;
			$Size = $v->Size;
			$Usedslots = 0;
			$available = array(); // Place restante et non utilisee !
			foreach($v->Slots as $x => $y){ // Pour chaque slot
				$Usedslots += $v->Available->$x; // Total Slots remplis
				if (max($v->Slots->$x - $v->Available->$x,0) > 0){ // Si place libre on ajoute au tableau
					$available[$x] = $v->Slots->$x - $v->Available->$x;
				}
			}
			date_default_timezone_set($timezone);
			if ($Status == "locked"){
				$islocked = "true";
			}else{
				$islocked = "false";
			};
			
			// On affiche
			echo "<span class=\"raidSlot box_inlay\">\r\n";
			echo "<a href=\"index.php#raid,setup,".$v->RaidId."\" target=\"_blank\"><span id=\"raid".$v->RaidId."\" class=\"locationImg clickable\" index=\"".$index++."\" locked=\"".$islocked."\">\r\n";
			echo "<img src=\"themes/icons/".$locationfolder."/raidsmall/".$location[$v->LocationId]['Image']."\">\r\n";
			echo "<div class=\"overlayStatus overlayStatus".ucfirst($Status)."\"></div></span></a>\r\n";
			echo "<span class=\"raidInfo\">\r\n";
			echo "<div class=\"location\">".$location[$v->LocationId]['Name']." (".$Size.")</div>\r\n";
			echo date('d ', $Start)." ".$gLocale[date('F', $Start)]."<br>";
			echo date('H:i', $Start)." - ".date('H:i', $End)."</br>";
			echo "<div style=\"line-height: 2.5em\">\r\n";
			echo $Usedslots." / ".$Size." Joueurs</div></span>\r\n";
			echo "<span class=\"setupInfo\">\r\n";
			if (!empty($available)) {
				foreach($available as $role => $rolevalue){
						echo "<div class=\"setupInfoSlot\" style=\"background-image: url(&quot;lib/layout/images/".$roleicon[$role].".png&quot;); display: block;\">\r\n";
						echo "+".$rolevalue."</div>\r\n";
					}
			}else{
				echo "<div class=\"setupInfoSlot\" style=\"background-image: url(lib/layout/images/slot_ok.png)\"></div>\r\n";
			}
			echo "</span></span>";
			}
		}
?>
	</div>
	</div>
	<script type="text/javascript">
		var Interval = 0;
		window.clearInterval(Interval);

		Interval = window.setInterval( function() {
		   $(".setupInfo").each( function() {
			   var Frame = $(this);

			   if ( $(this).children().length > 2 )
			   {
				   var FirstElement = Frame.children().first();
				   var OriginalHeight = FirstElement.height();

				   FirstElement.slideUp( 1000, function() {
					   $(this).detach().appendTo(Frame).show();
				   });
			   }
		   });
		}, 3000);
	</script>
	</body>
</html>