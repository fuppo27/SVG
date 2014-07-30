<?php
$options = getopt("n:l:u:c:m:s:b:o:W:H:");
$NODE  = $options["n"];
$LINK  = $options["l"];
$URLID = $options["u"];
$COLOR = $options["c"];
$MARKER= $options["m"];
$SIZE  = $options["s"];
$BGCOL = $options["b"];
$SVG   = $options["o"];
$WIDTH = $options["W"];
$HEIGHT= $options["H"];

print_r($options);
if($SIZE == "") $SIZE = 2;


$wfp = fopen($SVG, "w");
//header( "Content-type: text/xml; charset= UTF-8");
setlocale(LC_ALL,"ja_JP");
mb_language("Japanese");
mb_http_output("UTF-8");
fwrite($wfp, "<?xml version=\"1.0\" standalone=\"no\"?>\n");
fwrite($wfp, "<!DOCTYPE svg PUBLIC \"-//W3C//DTD SVG 1.0//EN\"\n");
fwrite($wfp, "\"http://www.w3.org/TR/SVG/DTD/svg10.dtd\">\n\n");


$mflag = "no";
if($rad == ""){ $rad = 0.05; $iflag = "no"; }
if($after == "") $after = 0;

// ノードの読み込み.  
@$file = fopen($NODE, "r");
$i = 0;
while(1){
  @$str = fgets($file);
  $str = str_replace("\r","",$str);
  $str = str_replace("\n","",$str);
  if($str == "") break;
  $line = explode(',',$str);
  $x[] = (double)$line[0];
  $y[] = (double)$line[1];
  $tmp = mb_convert_encoding($line[2], "UTF-8", "auto");
  if($tmp == "") $tmp = $i+1;
  $msg[] = htmlspecialchars($tmp, ENT_QUOTES);
  $color[] = $line[3];
  $Nopacity[] = $line[4];
  $Nrad[] = $line[5];
  $str = $line[6];
  $url[] = htmlspecialchars($str, ENT_QUOTES);
  $marker[] = $line[7];
  if($line[7] != "o" || $line != "") $mflag = "yes";
  $i++;
}
if($Nrad[0] != "") $fflag = "yes";
@fclose($file);

// リンクの読み込み.
if($LINK != ""){
@$file=fopen($LINK, "r");
$opacity=array();
while(1){
  @$row=fgets($file);
  $row=str_replace("\r","",$row);
  $row=str_replace("\n","",$row);
  if($row=="") break;
  $line=explode(',',$row);
  $e1[]=(int)$line[0]-1;
  $e2[]=(int)$line[1]-1;
  $link_msg[]= mb_convert_encoding($line[2], "UTF-8", "auto");
  if($line[3]==NULL) $link_color[]="#999999";
  else $link_color[]=$line[3];
  if($line[4]==NULL) $opacity[]=1;
  else $opacity[]=$line[4];
  if($line[5]==NULL) $strokeW[]=1;
  else $strokeW[]=$line[5];
}
@fclose($file);
}

if($URLID != ""){
  // 名前の読み込み.
  @$file=fopen($URLID, "r");
  $msg=array();
  $url=array();
  while(1){
    @$str=fgets($file);
    $str=mb_convert_encoding($str, "UTF-8", "auto");
    $str=htmlspecialchars($str, ENT_QUOTES);
    $str=str_replace("\r","",$str);
    $str=str_replace("\n","",$str);
    if($str=="") break;
    $col = explode("\t", $str);
    $msg[] = $col[1];
    $url[] = $col[2];
  }
  @fclose($file);
}

if($COLOR != ""){
// 色の読み込み.
@$file=fopen($COLOR, "r");
$color=array();
while(1){
  @$str=fgets($file);
  $str=str_replace("\r","",$str);
  $str=str_replace("\n","",$str);
  if($str=="") break;
  $color[]=$str;
}
@fclose($file);
}

if($MARKER != ""){
  $mflag = "yes";
  // マーカーの読み込み.
  @$file=fopen($MARKER, "r");
  $marker=array();
  while(1){
    @$str=fgets($file);
    $str=str_replace("\r","",$str);
    $str=str_replace("\n","",$str);
    if($str=="") break;
    $marker[]=$str;
  }
  @fclose($file);
}


$width = ($WIDTH != "")? $WIDTH : 700;
$height= ($HEIGHT!= "")? $HEIGHT: 700;

// 上, 左, 下のマージン. 右のマージンはポップアップ表示が切れないようにその2倍.
$margin=20;

$xmin=min($x);
$xmax=max($x);
$xscale=$width/($xmax-$xmin);

$ymin=min($y);
$ymax=max($y);
$yscale=$height/($ymax-$ymin);


fwrite($wfp, "<svg width=\"".($width+3*$margin)."\" height=\"".($height+2*$margin)."\" xmlns=\"http://www.w3.org/2000/svg\" xmlns:xlink=\"http://www.w3.org/1999/xlink\">\n");
if($BGCOL != "") fwrite($wfp, "<rect x=\"0\" y=\"0\" width=\"".($width+3*$margin)."\" height=\"".($height+2*$margin)."\" fill=\"".$BGCOL."\" fill-opacity=\"1\" stroke-width=\"0\" />\n");

// リンクの表示.
for($i=0; $i<count($e1); $i++){
  $x1=$xscale*($x[$e1[$i]]-$xmin)+$margin;
  $y1=$yscale*($ymax-$y[$e1[$i]])+$margin;
  $x2=$xscale*($x[$e2[$i]]-$xmin)+$margin;
  $y2=$yscale*($ymax-$y[$e2[$i]])+$margin;
  if($opacity[$i] == "") $opacity[$i] = 1;
  if($link_msg[$i] != "") fwrite($wfp, "<a xlink:title=\"".$link_msg[$i]."\">\n");
  fwrite($wfp, "<line x1=\"".$x1."\" y1=\"".$y1."\" x2=\"".$x2."\" y2=\"".$y2."\" stroke=\"".$link_color[$i]."\" stroke-opacity=\"".$opacity[$i]."\" stroke-width=\"".$strokeW[$i]."\"/>\n");
  if($link_msg[$i] != "") fwrite($wfp, "</a>\n");
}

include("/var/www/html/semi/plot/Markers.php");
// ノードの表示.
if($mflag == "no"){
  for($i=0; $i<count($x); $i++){
    if($Nrad[$i] == ""){ $Nrad[$i] = $SIZE; }
    if($Nrad[$i] <= 0.1) continue;
    $x0=$xscale*($x[$i]-$xmin)+$margin;
    $y0=$yscale*($ymax-$y[$i])+$margin;
    if($Nopacity[$i] == "") $Nopacity[$i] = 1;
    if($msg[$i] != "") fwrite($wfp, "<a xlink:href=\"".$url[$i]."\" xlink:title=\"".$msg[$i]."\" xlink:show=\"new\">\n");
    //  if($iflag != "no"){ $Nrad[$i] = $rad; }
    fwrite($wfp, "<circle cx=\"".$x0."\" cy=\"".$y0."\" r=\"".$Nrad[$i]."\" fill=\"".$color[$i]."\" opacity=\"".$Nopacity[$i]."\" />\n");
    if($msg[$i] != "") fwrite($wfp, "</a>\n");
  }
}else{
  for($i=0; $i<count($x); $i++){
    if($Nrad[$i] == ""){ $Nrad[$i] = $SIZE; }
    if($Nrad[$i] <= 0.1) continue;
    $x0=$xscale*($x[$i]-$xmin)+$margin;
    $y0=$yscale*($ymax-$y[$i])+$margin;
    if($Nopacity[$i] == "") $Nopacity[$i] = 1;
    if($msg[$i] != "") fwrite($wfp, "<a xlink:href=\"".$url[$i]."\" xlink:title=\"".$msg[$i]."\" xlink:show=\"new\">\n");
    //  if($iflag != "no"){ $Nrad[$i] = $rad; }
    marker($marker[$i], $x0, $y0, $Nrad[$i], $color[$i], $Nopacity[$i]);
    //    print($marker[$i]." ".$x0." ".$y0." ".$Nrad[$i]." ".$color[$i]." ".$Nopacity[$i]."\n");
    if($msg[$i] != "") fwrite($wfp, "</a>\n");
  }
}

fwrite($wfp, "</svg>");
fclose($wfp);

$png = str_replace(".svg", ".png", $SVG);

$cmd = "/usr/bin/rsvg-convert ".$SVG." -f png -o ".$png;
exec($cmd);
?>
