<?php
include 'zip-api.php';
include 'api.php';

if(isset($_POST['zipcode']) && is_numeric($_POST['zipcode'])){
    $zipcode = $_POST['zipcode'];
}else{
    $output = 'Please enter a zip code or click a state';
}

$result = file_get_contents('http://weather.yahooapis.com/forecastrss?p=' . $zipcode . '&u=f');
$xml = simplexml_load_string($result);
 
//echo htmlspecialchars($result, ENT_QUOTES, 'UTF-8');
 
$xml->registerXPathNamespace('yweather', 'http://xml.weather.yahoo.com/ns/rss/1.0');
$location = $xml->channel->xpath('yweather:location');
 
if(!empty($location)){
    foreach($xml->channel->item as $item){
        $current = $item->xpath('yweather:condition');
        $forecast = $item->xpath('yweather:forecast');
        $current = $current[0];
        $output = <<<END
            <h2 style="margin-bottom: 0;">Weather for {$location[0]['city']}, {$location[0]['region']}</h2>
            <div id="weather-container">
            <small>{$current['date']}</small>
            <h3>Current Conditions</h3>
            <p class="weather">
            <span style="font-size:72px; font-weight:bold;">{$current['temp']}&deg;F</span>
            <br/>
            <div class=weather-{$current['code']}><span style="position: absolute; left: 80px;">{$current['text']}</span></div>
            </p>
            <h3>Forecast</h3>
            {$forecast[0]['day']} - {$forecast[0]['text']}. High: {$forecast[0]['high']} Low: {$forecast[0]['low']}
            <br/>
            {$forecast[1]['day']} - {$forecast[1]['text']}. High: {$forecast[1]['high']} Low: {$forecast[1]['low']}
            </p>
            </div>
END;
    }
}else {
    $output = '<h2>Please enter a zip code or click a state on the Map</h2>';
}

?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="">
    <meta name="author" content="">
    <link rel="shortcut icon" href="assets/img/favicon.png">

    <title>Weather Sniper</title>

    <script src="./lib/raphael.js"></script>
    <script src="http://ajax.googleapis.com/ajax/libs/jquery/1.6.2/jquery.js"></script>
    <script src="assets/js/color.jquery.js"></script>
    <script src="assets/js/jquery.usmap.js"></script>

    <script>

    $(document).ready(function () {

    	var cities = <?php echo json_encode($city_list); ?>;

        // couldn't make the button work in time, so used on change function

        $("#cities").change(function(){
            $("#" + this.value).show().siblings().hide();
        });

        $("#cities").change();




        $.ajax({
            url: "./api.php",
            dataType: "json"
        }).success(function (data) {
            $(cities).each(function (index, element) {
            	// lazily accessing the JSON - would loop through this better and create a better JSON structure with more time
                if (element.id === 1)
                    $('#austin').append('<ul class='+element.name+'><li>City: '+element.name+'</li><li>Population: '+element.population+'</li><li>Zip Code: '+element.zip+'</li></ul>');
                if (element.id === 2)
                    $('#houston').append('<ul class='+element.name+'><li>City: '+element.name+'</li><li>Population: '+element.population+'</li><li>Zip Code: '+element.zip+'</li></ul>').hide();
                if (element.id === 3)
                    $('#dallas').append('<ul class='+element.name+'><li>City: '+element.name+'</li><li>Population: '+element.population+'</li><li>Zip Code: '+element.zip+'</li></ul>').hide();
            });
        });

        $('#map').usmap({
            stateStyles: {
                fill: '#4fb2a8'
            },
            stateHoverStyles: {
                fill: '#ffffb2'
            },
            stateHoverAnimation: 300,
            labelBackingStyles: {
                fill: '#4fb2a8'
            },
            labelBackingHoverStyles: {
                fill: '#ffffb2'
            },
            click: function (event, data) {
                var state = data.name;
                $.ajax({
                    url: "./zip-api.php",
                    dataType: "json"
                }).success(function (data) {
                    var zip = <?php echo json_encode($zip_list); ?>;
                    $(zip).each(function (index, element) {
                        if (element.code == state)
                            $('#zipcode-map').val(element.zip);
                    });
                });
            }

        });
    });

    </script>

    <!-- Bootstrap -->
    <link href="assets/css/bootstrap.css" rel="stylesheet">
	<link href="assets/css/bootstrap-theme.css" rel="stylesheet">
	<link href="assets/css/bootstrap-responsive.css" rel="stylesheet">

    <!-- my styles -->
    <link href="assets/css/style.css" rel="stylesheet">
    
    <!-- HTML5 shim and Respond.js IE8 support of HTML5 elements and media queries -->
    <!--[if lt IE 9]>
      <script src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
      <script src="https://oss.maxcdn.com/libs/respond.js/1.3.0/respond.min.js"></script>
    <![endif]-->
  </head>

  <body>

    <!-- Fixed navbar -->
    <div class="navbar navbar-inverse navbar-fixed-top">
      <div class="container">
        <div class="navbar-header">
          <button type="button" class="navbar-toggle" data-toggle="collapse" data-target=".navbar-collapse">
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
          </button>
          <a class="navbar-brand" href="bootstrap.html">Weather Sniper</a>
        </div>
      </div>
    </div>

	<div id="header">
		<div class="container">
			<div class="row-fluid">
				<div class="span4">
					<h2 class="subtitle">Get the weather now</h2>
					<form id="the-form" class="form-inline signup" name="test" method="POST" action="">
					  <div class="form-group">
					  	<input type="text" id="zipcode" name="zipcode" class="form-control" placeholder="Enter a zip code">
					  </div>
					  <button type="submit" class="btn btn-theme">Get Weather</button>
					</form>					
				</div>
				<div class="span8">
					<div id="city-background">
				  		<div class="row-fluid padding">
							<div class="span4">
								<small>Click on a city and get city info</small>
								<select id="cities" name="cities" size="3">
									<?php foreach ($city_list as $city): ?>
									<option <?php if ($city["id"] === 1) {
											echo " selected"; } ?> value="<?php echo strtolower($city["name"]) ?>">
										<?php echo $city["name"] ?>
									</option>
									<?php endforeach; ?>
								</select>
							</form>
							</div>
							<div class="span8">
								<div id="austin"></div>
								<div id="houston"></div>
								<div id="dallas"></div>
							</div>
						</div>
					</div>
				</div>	
				
			</div>
			<hr>
			<div class="row-fluid">
			  <div class="span6"><?php echo $output; ?></div>
			  <div class="span6">
			  	<h2>Interactive Map</h2>
			  	<div id="map-background">
			  		<small>Click on a state to get the zip code</small>
			  		<form id="map-form" class="form-inline signup" name="test" method="POST" action="">
					  <div class="form-group">
					  	<input type="text" id="zipcode-map" name="zipcode" class="form-control" placeholder="Click a state">
					  </div>
					  <button type="submit" class="btn btn-theme">Get Weather</button>
					</form>
			  		<div id="map">
					</div>
				</div>
			</div>
			</div>
		</div>
	</div>

	<div id="footer">
	<div class="container">
		<div class="row">
			<div class="col-lg-6 col-lg-offset-3">
					<p class="copyright">Copyright &copy; 2014 - Carter Cochran</p>
			</div>
		</div>		
	</div>	
	</div>

    <script src="assets/js/bootstrap.min.js"></script>
  </body>
</html>
