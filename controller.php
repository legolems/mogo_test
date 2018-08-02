<?php

class Controller {

    public $teamsInDivision = 9;
    public $divisions       = ['a', 'b'];
    public $results         = [];


    function handleRequest()
    {
        $model = new Model;

        $message = '';

        if (isset($_GET['generate'])) {

            if ($_GET['generate'] == 'a') {
                $result = $model->generateDivisionTable($this->teamsInDivision, 'a');
            } elseif ($_GET['generate'] == 'b') {
                $result = $model->generateDivisionTable($this->teamsInDivision, 'b');
            } elseif ($_GET['generate'] == 'elimination') {
                $result = $model->generateEliminationTable($this->divisions);
            } else {
                $result = false;
            }

            if ($result === false) {
                $message = '<div class="message">Error occurred. Please try again</div>';
            }
        }

        $divisionTables     = $this->displayDivisionTables($this->divisions);
        $eliminationTable   = $this->displayEliminationTable();

        include_once 'default.php';
    }


    function displayDivisionTables($tableIds)
    {
        $html = '';

        $uri = explode('?', $_SERVER['REQUEST_URI'], 2);

        $model = new Model;

        foreach ($tableIds as $tableId) {

            $url = '//'.$_SERVER['HTTP_HOST'].''.$uri[0].'?generate='.$tableId.'';

            $resultArray = $model->getTable('division_'.$tableId);

            if ($resultArray === false) {

                $html .=
                    '<div class="division column">
                        <h2>Division '.$tableId.'</h2>
                        <button onclick="document.location.href = \''.$url.'\'">
                            Generate
                        </button>
                    </div>';

            } else {

                $table = '';
                foreach ($resultArray as $key => $team) {
                    $teamResults = '';
                    for ($i = 1; $i <= $this->teamsInDivision; $i++) {
                        if ($team[$tableId . $i] == NULL) {
                            $teamResults .= '<td class="empty"></td>';
                        } else {
                            $result      = $team[$tableId . $i] == 1 ? '1:0' : '0:1';
                            $teamResults .= '<td>'.$result.'</td>';
                        }
                    }

                    $table .=
                        '<tr>
                            <th>'.$key.'</th>
                            '.$teamResults.'
                            <td class="score">'.$team['score'].'</td>
                        </tr>';

                    $this->results[$key] = $team['score'];
                }

                $header = '';
                for ($i = 1; $i <= $this->teamsInDivision; $i++) {
                    $header .= '<th>'.$tableId . $i.'</th>';
                }

                $header =
                    '<tr>
                        <th>Teams</th>
                        '.$header.'
                        <th class="score">Score</th>
                    </tr>';


                $html .=
                    '<div class="division column">
                        <h2>Division '.$tableId.'</h2>
                        <table>
                            '.$header.'
                            '.$table.'
                        </table>
                        <button onclick="document.location.href = \''.$url.'\'">
                            Generate
                        </button>
                    </div>';
            }
        }

        $html =
            '<div>
                '.$html.'
                <div class="clear"></div>
            </div>';

        return $html;
    }


    function displayEliminationTable()
    {
        // Display elimination game results
        $model = new Model;

        $uri = explode('?', $_SERVER['REQUEST_URI'], 2);
        $url = '//'.$_SERVER['HTTP_HOST'].''.$uri[0].'?generate=elimination';

        $finals = [
            'quarterfinal',
            'semifinal',
            'final'
        ];

        $html = $header = '';

        foreach ($finals as $final) {

            $resultArray = $model->getTable($final);

            if ($resultArray === false) {

                $html =
                    '<div>
                        <h2>Results</h2>
                        <button onclick="document.location.href = \''.$url.'\'">
                            Generate
                        </button>
                    </div>';

                return $html;

            } else {

                $games = '';
                foreach  ($resultArray as $game => $scores) {

                    if ($scores['winner'] == $scores['team_1']) {
                        $score = '1:0';
                        $this->results[$scores['team_1']] += 20;
                        $this->results[$scores['team_2']] += 10;
                    } else {
                        $score = '0:1';
                        $this->results[$scores['team_1']] += 10;
                        $this->results[$scores['team_2']] += 20;
                    }

                    $games .=
                        '<tr>
                            <td>'.$scores['team_1'].'</td>
                            <td rowspan="2" class="gameScore">'.$score.'</td>
                        </tr>
                        <tr>
                            <td>'.$scores['team_2'].'</td>
                        </tr>';
                }

                $html .=
                    '<td>
                        <table>
                            '.$games.'
                        </table>
                    </td>';

                $header .= '<th>'.$final.'</th>';
            }
        }


        // Display final results for all teams

        arsort($this->results);

        $resultsTable = [];
        $x = 1;
        foreach (array_keys($this->results) as $team) {
            $resultsTable[] =
                '<tr>
                    <th>'.$x.'</th>
                    <td>'.$team.'</td>
                </tr>';

            $x++;
        }

        $resultsTable = array_chunk($resultsTable, ceil(count($resultsTable) / 2));

        $html =
            '<div>
                <div class="column">
                    <h2>Elimination</h2>
                    <table class="elimination">
                        <tr>'.$header.'</tr>
                        <tr>'.$html.'</tr>
                    </table>
                </div>
                <div class="column results">
                    <h2>Results</h2>
                    <div class="column">
                        <table>
                            '.implode('', $resultsTable[0]).'
                        </table>
                    </div>
                    <div class="column">
                        <table>
                            '.implode('', $resultsTable[1]).'
                        </table>
                    </div>
                </div>
                <div class="clear"></div>
                <button onclick="document.location.href = \''.$url.'\'">
                    Generate
                </button>
            </div>';

        return $html;

    }

}