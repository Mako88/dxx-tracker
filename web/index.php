<?php
    // TODO: Styling
?>
<!doctype html>

<html lang="en">
<head>
  <meta charset="utf-8">

  <title>DXX-Rebirth Tracker</title>
  <meta name="description" content="A game tracker for DXX-Rebirth.">
  <meta name="author" content="A Future Pilot">

  <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div id="wrapper">
        <h1>DXX-Rebirth Tracker</h1>

        <table>
            <thead>
                <tr>
                    <th class="version">Game Version</th>
                    <th class="name">Game Name</th>
                    <th class="mission">Mission</th>
                    <th class="players">Players</th>
                    <th class="mode">Game Mode</th>
                    <th class="status">Status</th>
                    <th class="host">Host</th>
                </tr>
            </thead>
            <tbody id="games">

            </tbody>
        </table>
        
        <span>Tracker Backend is: </span><span id="backend"></span>
    </div>
</body>
<script src="https://code.jquery.com/jquery-3.1.1.min.js" integrity="sha256-hVVnYaiADRTO2PzUGmuLJr8BLUSjGIZsDYGmIJLv2b8=" crossorigin="anonymous"></script>
<script type="text/javascript">
    $(document).ready(function(){
        refreshTable();
        checkBackend();
    });

    function refreshTable(){
        $('#games').load('games.php', function(){
           setTimeout(refreshTable, 5000);
        });
    }
    
    function checkBackend(){
        $('#backend').load('backend.php', function(){
           setTimeout(checkBackend, 1000);
        });
    }
</script>

</html>