<!DOCTYPE html>
<?php

    error_reporting(E_ALL);
    ini_set('display_errors', '1');

    $total_days = 400; // the number of days to display from today.

    $this_julian_day = unixtojd(time());

    // get a handle on the sqlite db
    $db = new SQLite3('year_planner.db');

    $db->exec("CREATE TABLE IF NOT EXISTS year_ahead (
        `julian_day` INTEGER,
        `colour` TEXT,
        `initials` TEXT,
        `description` TEXT,
        `date_string` TEXT
    )");

    // delete anything that occured before today - we only look forward
    $db->exec("DELETE FROM year_ahead WHERE `julian_day` < {$this_julian_day}");

    // if we have some stuff to store then store it.
    if(@$_GET['julian_day'] && @$_GET['initials']){

        $julian_day = $_GET['julian_day'];
        $initials = $_GET['initials'];

        // remove anything on that day with those initials.
        $db->exec("DELETE FROM year_ahead WHERE `julian_day`  = {$julian_day} AND  `initials` = '{$initials}'");

        // if we aren't flagged to delete then create a new one

        if(!@$_GET['delete']){
            // put the new one in
            $stm = $db->prepare('INSERT INTO year_ahead (`julian_day`, `initials`, `description`, `colour`, `date_string`) VALUES (?,?,?,?,?)');
            $stm->bindValue(1, $julian_day, SQLITE3_INTEGER);
            $stm->bindValue(2, $initials, SQLITE3_TEXT);
            $stm->bindValue(3, @$_GET['description'], SQLITE3_TEXT);
            $stm->bindValue(4, @$_GET['colour'], SQLITE3_TEXT);
            $now = jdtounix($julian_day);
            //$date = DateTime::createFromTimestamp($now);
            $date = new DateTime();
            $date->setTimestamp($now);
            
            $stm->bindValue(5, $date->format('c'), SQLITE3_TEXT);
            $stm->execute();
        }

    }

    // load the upcoming events
    $days = array();
    $res = $db->query("SELECT * FROM year_ahead ORDER BY `julian_day`, `initials`");

    while($row = $res->fetchArray(SQLITE3_ASSOC)){
        $days[$row['julian_day']][] = $row;
    }

?>
<html lang="en">
  <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Year Ahead</title>
    <style>

        body{
            font-family: "Gill Sans", sans-serif;
        }

        .day{
            position: relative;
            float: left;
            border: solid 1px black;
            width: 50px;
            height: 100px;
            padding: 2px;
            margin: 1px;
            overflow: hidden;
        }

        .Sunday,
        .Saturday{
            background-color: lightgray;
        }

        .month{
            position: absolute;
            font-size: 24px;
            color: rgba(0 2 5 / 0.6);
            width: 100%;
            padding: 3px;
            margin-left: -2px;
            text-align: center;
            bottom: 10%;
            left: 0;
            z-index: 0;
        }

        .initial{
            position: absolute;
            font-size: 24px;
            color: rgba(0 2 5 / 0.2);
            width: 100%;
            padding: 3px;
            margin-left: -2px;
            text-align: center;
            bottom: 10%;
            left: 0;
            z-index: 0;
        }

        button, input[type="submit"], input[type="reset"] {
            background: none;
            color: inherit;
            border: none;
            padding: 0;
            font: inherit;
            cursor: pointer;
            outline: inherit;
        }

        .item{
            font-size: 80%;
            font-weight: bold;
            background-color: hwb(0 100% 0% / 0.5);
            padding: 2px;
            text-decoration: none;
        }

        .items{
            position: absolute;
            z-index: 10;
        }

    </style>
  </head>
  <body>
    <form>
    <div>
            Initials: <input type="text" minlength="1" maxlength="6" name="initials" size="5" value="<?php echo @$_GET["initials"] ?>"/>
            Description: <input type="text" minlength="0" maxlength="100" name="description" value="<?php echo @$_GET["description"] ?>"/>
            Colour:   <input type="color" id="colour" name="colour" value="<?php echo @$_GET["colour"] ? $_GET["colour"] : "#ff0000"; ?>" />
    </div>
<hr/>
  <?php

    $start = new DateTime();
    $period = new DatePeriod($start, DateInterval::createFromDateString('1 day'), $total_days);

    foreach ($period as $date) {

        $day_name = $date->format("l"); // day of week
        $day_initial = substr($day_name, 0, 1);
        $day_of_month = $date->format('d');
        $month_name_long = $date->format("F"); // month of year
        $month_name_short = $date->format("M"); // month of year
        $year = $date->format("Y");
        $julian_day = unixtojd($date->getTimestamp());

        echo "<div class=\"day {$day_name} {$month_name_long}\">";
        $days_till = $julian_day - $this_julian_day;
        echo "<button title=\"{$days_till} days time.\" name=\"julian_day\" value=\"$julian_day\">{$day_of_month}</button>";
        echo '<br/>';
        
        if($day_of_month == "01"){
            echo "<div class=\"month\">{$month_name_short}<br/>{$year}</div>";   
        }else{
            echo "<div class=\"initial\">$day_initial</div>";
        }

        // write in the events for the day
        if(isset($days[$julian_day])){
            echo '<div class="items">';
            foreach($days[$julian_day] as $row){

                // add a delete flag to the row
                $row['delete'] = 'true';

                // pass that data back in as the query string
                $query = http_build_query($row);

                echo "<a href=\"index.php?{$query}\" class=\"item\" style=\"color: {$row['colour']}\" title=\"{$row["description"]}\" >{$row["initials"]}</a><br/>";
            }
            echo '</div>';
        }

        echo "</div>";
    }
    
    ?>
  
    </form>

    <script> 
    
    </script>
  </body>
</html>