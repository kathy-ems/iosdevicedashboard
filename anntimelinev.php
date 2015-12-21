/* Author: Kathy Ems
Web development project using perl, PHP, Ajax, MySQL, and Google Charts
9/22/2009
*/

<html>
  <head>
    <form method="post">
    Select an IP Address:
    <select id="ipAddress" name="ipAddress" onchange="submit()">
    <?php
      date_default_timezone_set('America/Los_Angeles');

      // ~~~~~~~~~~Make a MySQL Connection~~~~~~~
      $con = mysql_connect('localhost', 'hwte', 'jeff123');
      if (!$con) {
       die('Could not connect: ' . mysql_error());
      }

      mysql_select_db("DCPLHealth", $con);

      //~~~~~~Drop down on DCPLHealth.php page~~~~~~~~~~~
      $sql="SELECT DCS_ip FROM dcsQueues GROUP BY DCS_ip ORDER BY DCS_ip";
      $result = mysql_query($sql);

     	echo "<option value=\"NULL\">Select IP Address</option>\n";
    	echo "<option>ALL</option>\n";
    	while ($row = mysql_fetch_assoc($result)) {
    		$List1 .= "<option>{$row['DCS_ip']}</option>\n";
    	}

    	if (!$result = mysql_query("SELECT * FROM dcsQueues")) {
    		echo "Database is down";
    	}
    	echo $List1;
    	echo "</select></form>";

    	if ($_POST['ipAddress'] == 'ALL') {
    		$ips = mysql_query("SELECT DCS_ip FROM dcsQueues GROUP BY DCS_ip" ); // Use only the IP address pulled from URL
    	}else{
    		$ips = mysql_query("SELECT DCS_ip FROM dcsQueues WHERE DCS_ip = '{$_POST['ipAddress']}' GROUP BY DCS_ip" ); // Use only the IP address pulled from URL
    	}
      //$ips = mysql_query("SELECT DCS_ip FROM dcsQueues GROUP BY DCS_ip" );

      //~~~~~~Pull Data from DB in prep to load into graph~~~~~~~~~~~
      $count = 0;
      $AllChartDivs = "";

      while($IProw = mysql_fetch_array($ips)){
      	$string = "";
      	$CCstring = "";
      	$HDstring = "";
      	$count++;
      	$ip = $IProw['DCS_ip'];
        //	print $count . "\t" . $ip . "<br>";


        //Load process queue data for Graph
      	mysql_select_db("DCPLHealth", $con);
      	$result = mysql_query("SELECT DATE_FORMAT(DCS_date,'%H') AS Hour, " +
          "DATE_FORMAT(DCS_date,'%i') AS Minute, DATE_FORMAT(DCS_date,'%m') " +
          "AS Month, DATE_FORMAT(DCS_date,'%Y') AS Year, " +
          "DATE_FORMAT(DCS_date,'%d') AS Day, DCS_PQ0, DCS_PQ1, DCS_PQ2, " +
          "DCS_PQ3, DCS_PQ4 FROM dcsQueues WHERE DCS_ip= '$ip' " +
          "ORDER BY DCS_date ASC")
      	or die (mysql_error());

      	while($row = mysql_fetch_array($result)) {
      		$PQyear =$row['Year'];
      		$PQmonth = $row['Month'];
      		$PQmonth = $PQmonth - 1;
      		$PQday=$row['Day'];
      		$PQday = $PQday - 0;
      		$PQhour = $row['Hour'];
      		$PQminute = $row['Minute'];
      		$chartData0 =$row['DCS_PQ0'];
      		$chartData1=$row['DCS_PQ1'];
      		$chartData2=$row['DCS_PQ2'];
      		$chartData3=$row['DCS_PQ3'];
      		$chartData4=$row['DCS_PQ4'];
      		$totalPQ = $totalPQ + $chartData0 + $chartData1 + $chartData2 + $chartData3 + $chartData4;
      		$string .= "\t\t\t[new Date($PQyear, $PQmonth, $PQday, $PQhour, $PQminute), $chartData0, $chartData1, $chartData2, $chartData3, $chartData4, undefined, undefined], \n";
      	}

        //Load CSV and CAL queue data for Graph
      	$result = mysql_query("SELECT DATE_FORMAT(dcsCC_date,'%H') AS Hour, " +
                  "DATE_FORMAT(dcsCC_date,'%i') AS Minute, " +
                  "DATE_FORMAT(dcsCC_date,'%m') AS Month, " +
                  "DATE_FORMAT(dcsCC_date,'%Y') AS Year, " +
                  "DATE_FORMAT(dcsCC_date,'%d') AS Day, dcsCC_Cal, dcsCC_csv " +
                  "FROM dcsCsvCal WHERE dcsCC_ip= '$ip' " +
                  "ORDER BY dcsCC_date ASC")
      	or die (mysql_error());

      	while($row = mysql_fetch_array($result)) {
      		$CCyear =$row['Year'];
      		$CCmonth = $row['Month'];
      		$CCmonth = $CCmonth - 1;
      		$CCday=$row['Day'];
      		$CCday = $CCday - 0;
      		$CChour = $row['Hour'];
      		$CCminute = $row['Minute'];
      		$cal =$row['dcsCC_Cal'];
      		$totalCal = $totalCal + $cal;
      		$csv=$row['dcsCC_csv'];
      		$totalCsv = $totalCsv + $csv;
      		$CCstring .= "\t\t\t[new Date($CCyear, $CCmonth, $CCday, $CChour, $CCminute), $cal, $csv], \n";
      	}

        //Load HD data for Graph
      	$resultHD = mysql_query("SELECT DATE_FORMAT(dcsHDs_date,'%m') AS HDMonth, " +
                    "DATE_FORMAT(dcsHDs_date,'%Y') AS HDYear, " +
                    "DATE_FORMAT(dcsHDs_date,'%d') AS HDDay, " +
                    "DATE_FORMAT(dcsHDs_date,'%i') AS HDMinute, " +
                    "DATE_FORMAT(dcsHDs_date,'%H') AS HDHour, dcsHDs_diskCap, " +
                    "dcsHDs_diskInode FROM dcsHDs WHERE dcsHDs_ip= '$ip' AND " +
                    "dcsHDs_HDName = '/' ORDER BY dcsHDs_date ASC")
      	or die(mysql_error());

      	while($HDrow = mysql_fetch_array($resultHD)){
      		$HDyear =$HDrow['HDYear'];
      		$HDmonth = $HDrow['HDMonth'];
      		$HDmonth = $HDmonth - 1;
      		$HDday=$HDrow['HDDay'];
      		$HDday = $HDday - 0;
      		$HDminute = $HDrow['HDMinute'];
      		$HDhour = $HDrow['HDHour'];
      		$diskCap =$HDrow['dcsHDs_diskCap'];
      		$diskInode=$HDrow['dcsHDs_diskInode'];
      		$HDstring .= "\t\t\t[new Date($HDyear, $HDmonth, $HDday, $HDhour, $HDminute), $diskCap, $diskInode], \n";
      	}

      	//	Loading Wordpess entries to display
      	mysql_select_db("wordpress", $con);
      	$wordpressResult = mysql_query("SELECT DATE_FORMAT(DATE_ADD(wp_posts.post_date,interval 15 hour),'%H') " +
                          "AS Hour, DATE_FORMAT(wp_posts.post_date,'%i') " +
                          "AS Minute, DATE_FORMAT(DATE_ADD(wp_posts.post_date,interval 15 hour),'%m') " +
                          "AS Month, DATE_FORMAT(DATE_ADD(wp_posts.post_date,interval 15 hour),'%Y') " +
                          "AS Year, DATE_FORMAT(DATE_ADD(wp_posts.post_date,interval 15 hour),'%d') " +
                          "AS Day, wp_posts.post_title AS Title, wp_posts.guid AS postURL " +
                          "FROM wp_posts JOIN wp_term_relationships ON " +
                          "wp_posts.ID = wp_term_relationships.object_id " +
                          "JOIN wp_terms ON wp_terms.term_id = wp_term_relationships.term_taxonomy_id " +
                          "WHERE wp_terms.name = '$ip' ORDER BY wp_posts.post_date ASC")
      	or die (mysql_error());

        while($WProw = mysql_fetch_array($wordpressResult)){
      		$year =$WProw['Year'];
      		$month = $WProw['Month'];
      		$month = $month - 1;
      		$day=$WProw['Day'];
      		$day = $day - 0;
      		$hour = $WProw['Hour'];
      		$minute = $WProw['Minute'];
      		$chartData5 ='<a target="_blank" href="' . $WProw['postURL'] . '">' . $WProw['Title'] . '</a>';
          // $string .= "\t\t\t[new Date($year, $month, $day, $hour, $minute ), undefined, $chartData5, $chartData6, undefined, undefined, undefined, undefined], \n";
      		$string .= "\t\t\t[new Date($year, $month, $day, $hour, $minute ), undefined, undefined, undefined, undefined, undefined, 0, '$chartData5' ], \n";
      	}


        //	print $string. "<br><br>";
        $AllChartDivs .= "<div class='background' style='background:#F5FFFA; border-style:solid; border-width:1px; margin-bottom:10px; padding: 10px 0px 10px 10px; width: 1000px; position: relative;'>".
        					        "<div style='padding:3px; text-align:center; font-size:1.5em; line-height:.2em'>$ip</div>\n";

      	if ($string) {
      		$chartDiv = "chart_div" . $count;
      		$AllChartDivs .=
      			"<div> \n".
      				"<div>Realtime Process Queues</div> \n\t".
      				"<div id='$chartDiv' style='width: 850px; height: 250px; position: relative; float:left;'></div>\n".
      				"<div style='margin: 20px; position: relative; float: left;'>Curent Status<br><u>".($PQmonth + 1)."/".$PQday." at ".$PQhour.":".$PQminute."</u><br>PQ0: $chartData0 <br>PQ1: $chartData1 <br>PQ2: $chartData2 <br>PQ3: $chartData3 <br>PQ4: $chartData4</div>\n".
      			"</div>";
      		print " <script type='text/javascript' src='http://www.google.com/jsapi'></script>\n";
      		print " <script type='text/javascript'>\n";
      		print "\t google.load('visualization', '$count', {'packages':['annotatedtimeline']});\n";
      		print "\t google.setOnLoadCallback(drawChart);\n";
      		print "\t function drawChart() {\n";
      		print "\t\t var data = new google.visualization.DataTable();\n";
      		print "\t\t data.addColumn('datetime', 'Date');\n";
      		print "\t\t data.addColumn('number', 'Queue 0 - ');\n";
      		print "\t\t data.addColumn('number', 'Queue 1 - ');\n";
      		print "\t\t data.addColumn('number', 'Queue 2 - ');\n";
      		print "\t\t data.addColumn('number', 'Queue 3 - ');\n";
      		print "\t\t data.addColumn('number', 'Queue 4 - ');\n";
      		print "\t\t data.addColumn('number', 'Posts ');\n";
      		print "\t\t data.addColumn('string', 'titleUrl');\n";
      		print "\t\t data.addRows([\n";
      		print "$string";
      		print "\t\t ]);\n";
      		print "\t\t var chart = new google.visualization.AnnotatedTimeLine(document.getElementById('$chartDiv'));\n";
      	  // print "\t\t chart.draw(data, {displayAnnotations: true, allowHtml: true, legendPosition: 'newRow'});\n";
      		print "\t\t chart.draw(data, {thickness: 2, displayAnnotations: true, allowHtml: true, legendPosition: 'newRow', zoomStartTime: new Date($PQyear, $PQmonth, ($PQday - 1), $PQhour, 00)});\n";
      		print "\t }\n";
      		print " </script>\n\n";
      	}

      	if ($CCstring) {
      		$CCchartDiv = "CCchart_div" . $count;
      		$AllChartDivs .=
      			"<div style='position: relative; float: left;'> \n" .
      				"<div>CSV and CalData</div>\n\t" .
      				"<div id='$CCchartDiv' style='position: relative; float: left; width: 350px; height: 200px;'></div> \n\t " .
      				"<div style='margin: 5px; position: relative; float: left; width: 100px;'>Curent Status<br><u>".($CCmonth + 1)."/".$CCday." at ".$CChour.":".$CCminute."</u><br>CSVs: $csv <br>Cal: $cal</div>\n".
      			"</div>";
      		print " <script type='text/javascript'>\n";
      		print "\t google.load('visualization', '1', {'packages':['annotatedtimeline']});\n";
      		print "\t google.setOnLoadCallback(drawChart);\n";
      		print "\t function drawChart() {\n";
      		print "\t\t var data = new google.visualization.DataTable();\n";
      		print "\t\t data.addColumn('datetime', 'Date');\n";
      		print "\t\t data.addColumn('number', 'Cal Files - ');\n";
      		print "\t\t data.addColumn('number', 'CSV Files - ');\n";
      		print "\t\t data.addRows([\n";
      		print $CCstring;
      		print "\t\t ]);\n";
      		print "\t\t var chart = new google.visualization.AnnotatedTimeLine(document.getElementById('$CCchartDiv'));\n";
      		print "\t\t chart.draw(data, {thickness: 2, annotationsWidth: 0, allowHtml: true, legendPosition: 'newRow', zoomStartTime: new Date($CCyear, $CCmonth, ($CCday - 1), $CChour, 00)});\n";
      		print "\t }\n";
      		print " </script>\n\n";
      	}

      	if ($HDstring) {
      		$HDchartDiv = "HDchart_div" . $count;
      		$AllChartDivs .=
      			"<div style='position: relative; float: left;'> \n" .
      				"<div>Disk Space</div> \n\t ".
      				"<div id='$HDchartDiv' style='width: 350px; height: 200px; position: relative; float: left;'></div> \n\t\t" .
      				"<div style='margin: 5px; position: relative; float: left; width: 100px''>Curent Status<br><u>".($HDmonth + 1)."/".$HDday." at ".$HDhour.":".$HDminute."</u><br>Used: $diskCap % <br>inodes: $diskInode %</div><p style='clear: both'>\n </div><p style='clear: both'>\n".
      			"</div>";

      		print " <script type='text/javascript' src='http://www.google.com/jsapi'></script>\n";
      		print "<script type='text/javascript'>\n";
      		print "\t google.load('visualization', '1', {'packages':['annotatedtimeline']});\n";
      		print "\t google.setOnLoadCallback(drawChart);\n";
      		print "\t function drawChart() {\n";
      		print "\t\t var data = new google.visualization.DataTable();\n";
      		print "\t\t data.addColumn('datetime', 'Date');\n";
      		print "\t\t data.addColumn('number', 'Disk Cap % - ');\n";
      		print "\t\t data.addColumn('number', 'Disk INode % - ');\n";
      		print "\t\t data.addRows([\n";
      		print $HDstring;
      		print "\t\t ]);\n";
      		print "\t\t var chart = new google.visualization.AnnotatedTimeLine(document.getElementById('$HDchartDiv'));\n";
      		print "\t\t chart.draw(data, {displayAnnotations: true, legendPosition: 'newRow', zoomStartTime: new Date($HDyear, $HDmonth, ($HDday -1), $HDhour, $HDminute)});\n";
      		print "\t }\n";
      		print "</script>\n\n";
      	}
      }

      print "</head>\n\t<body>\n\n";
      print "\t <div style='position:relative; text-align:center; width:1000px'><h1><b>DCS Queue Report</b></h1>\n\t".
      			"<div style='position:absolute; top:0; right:0;  text-align:center;'><b>$totalPQ</b> Total DB Inserts Queued<br><b>$totalCsv</b> Total CSV<br><b>$totalCal</b> Total CalData</div>".
      		"</div><p style='clear: both'>\n\n";
      print $AllChartDivs;
      mysql_free_result($result); //clears $results for saving memory
      mysql_close($con); // Close DB connection
    ?>
  </body>
</html>
