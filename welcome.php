<?php
session_start();

if(!$_SESSION['username'])
{

    header("Location: login.php");//redirect to the login page to secure the welcome page without login access.
}

?>
<!DOCTYPE html>
<html>
<head>
	<title>CDF Projects</title>

	<meta charset="utf-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1.0">


  <script src="https://code.jquery.com/jquery-3.4.1.min.js"integrity="sha256-CSXorXvZcTkaix6Yvo6HppcZGetbYMGWSFlBw8HfCJo="crossorigin="anonymous"></script>
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.5.1/dist/leaflet.css"
  integrity="sha512-xwE/Az9zrjBIphAcBb3F6JVqxf46+CDLwfLMHloNu6KEQCAWi6HcDUbeOfBIptF7tcCzusKFjFw2yuvEpDL9wQ=="
  crossorigin=""/>
<script src="https://unpkg.com/leaflet@1.5.1/dist/leaflet.js"
  integrity="sha512-GffPMF3RvMeYyc1LWMHtK8EbPv0iNZ8/oTtHPx9/cc2ILxQ+u905qIwdpULaqDkyBKgOaB57QTMg7ztg8Jm2Og=="
  crossorigin=""></script>

<style>
   	 html, body {
          margin: 0px;
          padding: 0px;
      }
      #map {
        position :absolute;
        width: 100%;
		    height: 100%;
        top: 0;
        bottom: 0;
      }

      .controls{
        position: relative;
        top: 100px;
        z-index: 99999;
        margin: 2%;
        width: 30%;
        overflow-x: scroll;
        background-color: #f4f4f4b4;
      }

      .display{
        min-height: 300px;
        max-height: 300px;
        overflow-y: scroll;
        padding: 2%;
      }

			.logoutLblPos{

       position:fixed;
       right:10px;
       top:5px;
}

</style>
</head>
<body>
	<?php
echo $_SESSION['username'];
?>


<div id="map">

  <div class="controls">
    <h2>Upload Nairobi Express  Project update</h2>
    <div>Filter By financial year <select data-financial-yr>
    	<option value="2013/2014">2013/2014</option>
    	<option value="2014/2015">2014/2015</option>
    	<option value="2015/2016">2015/2016</option>
    </select></div>&nbsp;&nbsp;<span data-fy-span></span><br>
    <div>View projects by Status <select data-prj-status></select> </div>&nbsp;&nbsp;<span data-status-span></span><br>
    <div>View projects by Remarks <select data-prj-remarks></select> </div>&nbsp;&nbsp;<span data-remarks-span></span><br>
    <input type="file" id="cdf_upload" value="Upload CDF"><hr>


		<div>
		<h1><a href="log_out.php">Logout here</a> </h1>
	</div>
    <div class="display">
        <h3>Click on a project on the map to view its details here</h3>
        <div id="prop-details"></div>
      </div>
  </div>


<script>
  const map = L.map('map').setView([0.53, 36.88], 13);
  let starehe_cdfs = null;
  let starehe_cdf = null;
  let feature_props_status = [];
  let feature_props_remarks = [];
  let stareheGeojsonLayer = L.featureGroup([]);
  let stareheGeojson;

  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
  }).addTo(map);

  L.marker([0.53, 36.88]).addTo(map)
    .bindPopup('<h3>Martin Mwendwa</h3><p>EEGI/04882/2013s</p>')
    .openPopup();

  $(document).ready(function(e){
    $("#cdf_upload").on('change', function(){
      const input = document.getElementById("cdf_upload").files[0];
      const reader = new FileReader();
      $("[data-financial-yr]").on('change', function() {
      	console.log($(this).val());
      	starehe_cdfs = JSON.parse(JSON.stringify(starehe_cdf));
      	starehe_cdfs.features = starehe_cdfs.features.filter(cdf => cdf.properties['Financial Year'] === $(this).val());
        $("[data-fy-span]").text(starehe_cdfs.features.length);
        $("[data-remarks-span]").text('');
        $("[data-status-span]").text('');
		    loadData(starehe_cdfs);
      	console.log(starehe_cdfs);
        console.log(starehe_cdf);
      });

      $("[data-prj-status]").on('change', function() {
        console.log($(this).val());
        starehe_cdfs = JSON.parse(JSON.stringify(starehe_cdf));
        starehe_cdfs.features = starehe_cdfs.features.filter(cdf => cdf.properties['status'] === $(this).val());
        $("[data-status-span]").text(starehe_cdfs.features.length);
        $("[data-fy-span]").text('');
        $("[data-remarks-span]").text('');
        loadData(starehe_cdfs);
        console.log(starehe_cdfs);
        console.log(starehe_cdf);
      });


      $("[data-prj-remarks]").on('change', function() {
        console.log($(this).val());
        starehe_cdfs = JSON.parse(JSON.stringify(starehe_cdf));
        starehe_cdfs.features = starehe_cdfs.features.filter(cdf => cdf.properties['remarks'] === $(this).val());
        $("[data-remarks-span]").text(starehe_cdfs.features.length);
        $("[data-status-span]").text('');
        $("[data-fy-span]").text('');
        loadData(starehe_cdfs);
        console.log(starehe_cdfs);
        console.log(starehe_cdf);
      });

      reader.onload = function(){
        starehe_cdf = JSON.parse(reader.result);
        starehe_cdfs = JSON.parse(reader.result);
        console.log(starehe_cdf);

        loadData(starehe_cdf);
        loadAttr();
      }
      reader.readAsText(input);
    });
  });

  function loadData(data) {
    try{
      stareheGeojsonLayer.removeLayer(stareheGeojson);
      map.removeLayer(stareheGeojsonLayer);

    } catch (err) {
      console.log("loading for the first time");
    } finally {
      stareheGeojson = L.geoJSON(data,{
          pointToLayer: function (feature, latlng) {
              return L.circleMarker(latlng, geojsonMarkerOptions(feature));
          },
          onEachFeature:onEachFeature
        });
      stareheGeojsonLayer.addLayer(stareheGeojson);
      stareheGeojsonLayer.addTo(map);
      map.fitBounds(stareheGeojsonLayer.getBounds());
    }
  }

  function loadAttr() {
    feature_props_status = Array.from(new Set(feature_props_status));
    feature_props_remarks = Array.from(new Set(feature_props_remarks));
    $("[data-prj-status]").html('');
    $("[data-prj-remarks]").html('');
    feature_props_status.map(val => {
      $("[data-prj-status]").append($("<option>", {'value':val, 'text':val}));
    });

    feature_props_remarks.map(val => {
      $("[data-prj-remarks]").append($("<option>", {'value':val, 'text':val}));
    });
  }

  var geojsonMarkerOptions = function(feature) {
    let red = "#E94D4D";
    let orange = "#ff7800";
    let green = "#0A7F07";
    let colr = ''

    props = feature.properties;
    /*$opt_year = $("<option>", {'value':props['Financial Year'], 'text':props['Financial Year']});
    $("[data-financial-yr]").append($opt_year);*/
    switch(props.remarks){
      case "all funds received" : {
        colr = green;
        return {
      radius: 8,
      fillColor: colr,
      color: colr,
      weight: 1,
      opacity: 1,
      fillOpacity: 0.8
    }
      };
      case "funds partly received" : {
        colr = orange;
        return {
      radius: 8,
      fillColor: colr,
      color: colr,
      weight: 1,
      opacity: 1,
      fillOpacity: 0.8
    }
      };
      case "not approved" : {
        colr = red;
        return {
      radius: 8,
      fillColor: colr,
      color: colr,
      weight: 1,
      opacity: 1,
      fillOpacity: 0.8
    }
      }
    }

};

  function displayProperties(properties){
    $header = $("<h4>"+properties["Project Name"]+"</h4>");
    Object.keys(properties).map(key=>{
      $prop = $("<h6>"+key+"</h6>", {'class':'property-name'})
        .append($("<p>"+properties[key]+"</p>", {'class':'property-value'})).append($("<br>"));
      $header.append($prop);
    });
    $("#prop-details").html($header);
  }

  function onEachFeature(feature, layer) {
    // does this feature have a property named popupContent?
    if (feature.properties) {
        let prop = feature.properties;
        feature.properties.status = prop.status.toLowerCase();
        feature.properties.remarks = prop.remarks.toLowerCase();
        feature_props_status.push(prop.status.toLowerCase());
        feature_props_remarks.push(prop.remarks.toLowerCase());
        layer.bindPopup("<h4>"+prop.code+"</h4><hr>"+"<p>Name : "+prop["Project Name"]+"</p>");
       layer.on('click', function(){
         displayProperties(feature.properties);
       });

    }

}

</script>
<!--
<script>
var url = 'CIPts.json';  //REST service

var map = L.map('map').setView([42.736424, -73.762713], 10);

var osm=new L.tileLayer('http://{s}.tile.osm.org/{z}/{x}/{y}.png',{
      attribution: '&copy; <a href="http://osm.org/copyright">OpenStreetMap</a> contributors'});
  osm.addTo(map);

////////////////////
var ci_data;


//Initial Setup  with layer Verified No
  ci_data = L.geoJson(null, {

        pointToLayer: function(feature, latlng) {

            return L.circleMarker(latlng, {
                color:'black',
                fillColor:  'red',
                fillOpacity: 1,
                radius: 8
            })
        },
    onEachFeature: function (feature, layer) {
      layer.bindPopup(feature.properties.Verified);
    },
        filter: function(feature, layer) {
             return (feature.properties.Verified !== "Y" );
        },

    });

     $.getJSON(url, function(data) {
     ci_data.addData(data);
    });

/// END Initial Setup

  //Using a Layer Group to add/ remove data from the map.
  var myData =  L.layerGroup([]);
    myData.addLayer(ci_data);
    myData.addTo(map);


  //If Radio Button one is clicked.
  document.getElementById("radioOne").addEventListener('click', function(event) {
  theExpression = 'feature.properties.Verified !== "Y" ';
  console.log(theExpression);

    myData.clearLayers();
    map.removeLayer(myData);

    ci_data = L.geoJson(null, {

      pointToLayer: function(feature, latlng) {

        return L.circleMarker(latlng, {
          color:'black',
          fillColor:  'red',
          fillOpacity: 1,
          radius: 8
        })
      },
      onEachFeature: function (feature, layer) {
        layer.bindPopup(feature.properties.Verified);
      },
      filter: function(feature, layer) {
         return (feature.properties.Verified !== "Y" );
      },

    });


    $.getJSON(url, function(data) {
         ci_data.addData(data);
    });

      myData.addLayer(ci_data);
      myData.addTo(map);;
    });



  //If Radio button two is clicked.
  document.getElementById("radioTwo").addEventListener('click', function(event) {
  theExpression = 'feature.properties.Verified == "Y" ';
  console.log(theExpression);
    map.removeLayer(myData);
    myData.clearLayers();

    ci_data = L.geoJson(null, {

      pointToLayer: function(feature, latlng) {

        return L.circleMarker(latlng, {
          color:'black',
          fillColor:  'green',
          fillOpacity: 1,
          radius: 8
        })
      },
      onEachFeature: function (feature, layer) {
        layer.bindPopup(feature.properties.Verified);
      },
      filter: function(feature, layer) {
         return (feature.properties.Verified == "Y" );
      },

    });

    $.getJSON(url, function(data) {
         ci_data.addData(data);
    });

      myData.addLayer(ci_data);
    myData.addTo(map);
    });


 </script>
-->

</body>
</html>
