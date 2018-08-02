<?php

class Model {

    private $servername;
    private $username;
    private $password;
    private $dbname;


    function connect() {

        //Connect to database

        $this->servername   = 'localhost';
        $this->username     = 'root';
        $this->password     = '';
        $this->dbname       = 'mogo_test';

        $conn = new mysqli($this->servername, $this->username, $this->password, $this->dbname);

        return $conn;
    }


    function generateDivisionTable($rowCount, $division)
    {
        //Connect to database

        $conn = $this->connect();

        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        }


        //Drop table if exists and create a new one

        $table = 'division_'.$division;

        $sql = "DROP TABLE IF EXISTS `".$table."`";
        mysqli_query($conn, $sql);


        $createColumns = $columns = [];
        for ($x = 1; $x <= $rowCount; $x++) {
            $createColumns[] = "`".$division . $x."` TINYINT(1) DEFAULT NULL";
            $columns[]       = "`".$division . $x."`";
        }

        $sql =
            "CREATE TABLE IF NOT EXISTS `".$table."`(
                `id` VARCHAR(100) NOT NULL PRIMARY KEY,
                ".implode(',', $createColumns).",
                `score` INT(100) DEFAULT NULL
              )";

        mysqli_query($conn, $sql);


        //Generate new results for the division table

        $result = false;

        for ($i = 1; $i <= $rowCount; $i++) {

            $values = [];
            for ($x = 1; $x <= $rowCount; $x++) {
                if ($x == $i) {
                    $values[$x] = "NULL";
                } else {
                    $values[$x] = rand(0, 1);
                }
            }

            $sum = array_sum($values);

            $sql =
                "INSERT INTO `".$table."` (`id`, ".implode(',', $columns).", `score`) 
                VALUES ('".$division . $i."', ".implode(',', $values).", ".$sum.")";

            $result = mysqli_query($conn, $sql);

            if ($result === false) {
                return false;
            }
        }

        $conn->close();

        return $result;
    }


    function generateEliminationTable($divisions)
    {
        $result = $this->generateQuarterfinalTable($divisions);

        if ($result == false) {
            return false;
        }

        $result = $this->generateFinalsTables('semifinal', 'quarterfinal', 4);

        if ($result == false) {
            return false;
        }

        $result = $this->generateFinalsTables('final', 'semifinal', 2);

        return $result;
    }


    function generateQuarterfinalTable($divisions)
    {
        //Connect to database

        $conn = $this->connect();

        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        }


        //Get best teams of each division

        $bestTeams = [];
        foreach ($divisions as $division) {

            $sql =
                "SELECT id
                FROM division_".$division."
                ORDER BY score DESC
                LIMIT 4";

            $result = $conn->query($sql);

            if ($result === false) {
                return false;
            }

            if ($result->num_rows > 0) {
                while($row = $result->fetch_assoc()) {
                    $bestTeams[$division][] = $row["id"];
                }
            } else {
                return false;
            }
        }


        $this->createFinalsTables('quarterfinal');


        //Generate new results for the table

        krsort($bestTeams['a']); //Sort A division from lowest to highest score
        $bestTeams['a'] = array_values($bestTeams['a']);

        $games = [];
        for ($i = 0; $i < 4; $i++) {

            $winner = rand(1,2);

            $games[$i] = [
                'team_1' => $bestTeams['a'][$i],
                'team_2' => $bestTeams['b'][$i],
                'winner' => $winner == 1 ? $bestTeams['a'][$i] : $bestTeams['b'][$i]
                ];
        }


        $result = false;
        foreach ($games as $game => $scores) {
            $sql =
                "INSERT INTO `quarterfinal` (`id`, `team_1`, `team_2`, `winner`) 
                VALUES ('".$game."', '".$scores['team_1']."', '".$scores['team_2']."', '".$scores['winner']."')";

            $result = mysqli_query($conn, $sql);
        }


        $conn->close();

        return $result;

    }


    function generateFinalsTables($table, $lastTable, $teamCount)
    {
        //Connect to database

        $conn = $this->connect();

        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        }


        //Get best teams from previous table

        $sql =
            "SELECT `winner`
            FROM `".$lastTable."`";

        $result = $conn->query($sql);

        $bestTeams = [];
        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $bestTeams[] = $row["winner"];
            }
        } else {
            return false;
        }


        $this->createFinalsTables($table);


        //Generate results for the new table

        $games = [];
        for ($i = 0; $i < $teamCount; $i += 2) {

            $winner = rand(1,2);

            $games[$i] = [
                'team_1' => $bestTeams[$i],
                'team_2' => $bestTeams[$i + 1],
                'winner' => $winner == 1 ? $bestTeams[$i] : $bestTeams[$i + 1]
            ];
        }


        $result = false;
        foreach ($games as $game => $scores) {
            $sql =
                "INSERT INTO `".$table."` (`id`, `team_1`, `team_2`, `winner`) 
                VALUES ('".$game."', '".$scores['team_1']."', '".$scores['team_2']."', '".$scores['winner']."')";

            $result = mysqli_query($conn, $sql);
        }


        $conn->close();

        return $result;

    }


    function createFinalsTables($table)
    {
        //Connect to database

        $conn = $this->connect();

        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        }


        //Drop table if exists and create a new one

        $sql = "DROP TABLE IF EXISTS `".$table."` ";

        mysqli_query($conn, $sql);


        $sql =
            "CREATE TABLE IF NOT EXISTS `".$table."` (
                `id` VARCHAR(100) NOT NULL PRIMARY KEY,
                `team_1` VARCHAR(100) NOT NULL,
                `team_2` VARCHAR(100) NOT NULL,
                `winner` VARCHAR(100) NOT NULL
              )";

        mysqli_query($conn, $sql);
    }


    public function getTable($table)
    {
        //Connect to database

        $conn = $this->connect();

        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        }


        //Get table as array

        $sql = "SELECT * FROM `".$table."`";

        $result = $conn->query($sql);

        if ($result === false) {
            return false;
        }

        $tableAsArray = [];
        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $tableAsArray[$row["id"]] = $row;
                unset($tableAsArray[$row["id"]]['id']);
            }
        } else {
            return false;
        }


        $conn->close();

        return $tableAsArray;
    }

}