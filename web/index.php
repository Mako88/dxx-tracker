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
        <p class="buttons">Show: <a href="#" id="all">All</a> <a href="#" id="d1x">D1X</a> <a href="#" id="d2x">D2X</a></p>

        <table>
            <thead>
                <tr>
                    <th class="version">Version</th>
                    <th class="name">Name</th>
                    <th class="mission">Mission</th>
                    <th class="players">Players</th>
                    <th class="mode">Mode</th>
                    <th class="status">Status</th>
                    <th class="host">Host</th>
                </tr>
            </thead>
            <tbody id="games">

            </tbody>
        </table>
        
        <span>Tracker Backend Status: </span><span id="backend"></span>
    </div>
</body>
<script src="https://code.jquery.com/jquery-3.1.1.min.js" integrity="sha256-hVVnYaiADRTO2PzUGmuLJr8BLUSjGIZsDYGmIJLv2b8=" crossorigin="anonymous"></script>
<script type="text/javascript">
    $(document).ready(function(){
        var check = "games.php?d1x=yes&d2x=yes";
        $('#games').load(check);
        window.interval = setInterval(function() { $('#games').load(check); }, 5000);
        $('#backend').load('backend.php');
        backendInterval = setInterval(function() { $('#backend').load('backend.php'); }, 5000);
        
        $("#all").click(function(){
            check = "games.php?d1x=yes&d2x=yes";
            refreshTable(check);
        });
        
        $("#d1x").click(function(){
            check = "games.php?d1x=yes";
            refreshTable(check);
        });
        
        $("#d2x").click(function(){
            check = "games.php?d2x=yes";
            refreshTable(check);
        });
    });

    function refreshTable(check){
        clearInterval(interval);
        $('#games').load(check);
        interval = setInterval(function() { $('#games').load(check); }, 5000);
    }
</script>

</html>