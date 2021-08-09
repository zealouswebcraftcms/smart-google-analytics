// Sub Continents name with code
var subcontinents = [{
    "label":"Australia and New Zeland",
    "code":"053"
},
{
    "label":"Caribbean",
    "code":"029"
},
{
    "label":"Central America",
    "code":"013"
},
{
    "label":"Central Asia",
    "code":"143"
},
{
    "label":"Eastern Africa",
    "code":"014"
},
{
    "label":"Eastern Asia",
    "code":"030"
},
{
    "label":"Eastern Europe",
    "code":"151"
},
{
    "label":"Melanesia",
    "code":"054"
},
{
    "label":"Micronesia",
    "code":"057"
},
{
    "label":"Middle Africa",
    "code":"017"
},
{
    "label":"Northern Africa",
    "code":"015"
},
{
    "label":"Northern America",
    "code":"021"
},
{
    "label":"Northern Europe",
    "code":"154"
},
{
    "label":"Polynesia",
    "code":"061"
},
{
    "label":"South America",
    "code":"005"
},
{
    "label":"South-Eastern Asia",
    "code":"035"
},
{
    "label":"Southern Africa",
    "code":"018"
},
{
    "label":"Southern Asia",
    "code":"034"
},
{
    "label":"Southern Europe",
    "code":"039"
},
{
    "label":"Western Africa",
    "code":"011"
},
{
    "label":"Western Asia",
    "code":"145"
},
{
    "label":"Western Europe",
    "code":"155"
}];

// Continents name with code
var continents = [{
    "label":"Africa",
    "code":"002"
},
{
    "label":"Americas",
    "code":"019"
},
{
    "label":"Asia",
    "code":"142"
},
{
    "label":"Europe",
    "code":"150"
},
{
    "label":"Oceania",
    "code":"009"
}];

function charts(response) {
    $.each($.parseJSON(response), function (kData,vData) {
        var chart_options = {};
        if(vData.error){
            var chartId = vData.chartId;
            var chartName = vData.chartName;
            var order = vData.order;
            var html = '<div data-order="'+order+'" class="chart_div_'+chartId+' charts-block  chart-list-div"><div class="bar_chart_'+chartId+' chart-name-class">'+chartName+'</div><div class="chart-body bar_chart_id_'+chartId+'" id="bar_chart_id_'+chartId+'"><p class="chart-block-error"><b>Error:</b> '+vData.error+'</p></div></div>';

            $('.chart-listing-block').append(html);
            
            $('.form-standard').removeClass('zeal_loader_enabled');
        }
        else if(vData.chartType == 'BAR') {
            google.charts.load('current', {'packages':['bar']});
            google.charts.setOnLoadCallback(drawChart);
            function drawChart() {
                var chartDataRows = vData.chartData.rows;
                var dimensionKey = vData.dimensionKey;
                var metricsKey = vData.metricsKey;
                var data = dynamicChartData(chartDataRows, dimensionKey, metricsKey);
                var options = {
                    bars: 'horizontal' // Required for Material Bar Charts.
                };
                var chartId = vData.chartId;
                var chartName = vData.chartName;
                var order = vData.order;
                var flag = vData.flag;
                if(chartDataRows == null || flag == '0'){
                    var html = '<div data-order="'+order+'" class="chart_div_'+chartId+' charts-block  chart-list-div"><div class="bar_chart_'+chartId+' chart-name-class">'+chartName+'</div><div class="chart-body bar_chart_id_'+chartId+'" id="bar_chart_id_'+chartId+'"><p class="flag-chart-block">No data</p></div></div>';

                    $('.chart-listing-block').append(html);

                    $('.form-standard').removeClass('zeal_loader_enabled');

                }else{
                    var html = '<div data-order="'+order+'"  class="chart_div_'+chartId+' charts-block  chart-list-div"><div class="bar_chart_'+chartId+' chart-name-class">'+chartName+'</div><div class="chart-body bar_chart_id_'+chartId+'" id="bar_chart_id_'+chartId+'"></div></div>';
                    
                    $('.chart-listing-block').append(html);

                    $('.form-standard').removeClass('zeal_loader_enabled');

                    var chart = new google.charts.Bar(document.getElementById('bar_chart_id_'+chartId));
                    chart.draw(data, options);
                }
            }
        } else if(vData.chartType == 'PIE') {
            google.charts.load("current", {packages:["corechart"]});
            google.charts.setOnLoadCallback(drawChart);
            function drawChart() {
                var chartDataRows = vData.chartData.rows;
                var dimensionKey = vData.dimensionKey;
                var metricsKey = vData.metricsKey;
                var data = dynamicChartData(chartDataRows, dimensionKey, metricsKey);
                var options = {
                    is3D: true,
                };
                var chartId = vData.chartId;
                var chartName = vData.chartName;
                var order = vData.order;
                var flag = vData.flag;
                if(chartDataRows == null || flag == '0'){
                    var html = '<div data-order="'+order+'" class="chart_div_'+chartId+' charts-block  chart-list-div"><div class="bar_chart_'+chartId+' chart-name-class">'+chartName+'</div><div class="chart-body bar_chart_id_'+chartId+'" id="bar_chart_id_'+chartId+'"><p class="flag-chart-block">No data</p></div></div>';

                    $('.chart-listing-block').append(html);

                    $('.form-standard').removeClass('zeal_loader_enabled');

                }else{
                    var html = '<div data-order="'+order+'" class="chart_div_'+chartId+' charts-block  chart-list-div"><div class="pie_chart_'+chartId+' chart-name-class">'+chartName+'</div><div class="chart-body pie_chart_id_'+chartId+'" id="pie_chart_id_'+chartId+'"></div></div>';
                
                    $('.chart-listing-block').append(html);

                    $('.form-standard').removeClass('zeal_loader_enabled');
                    
                    var chart = new google.visualization.PieChart(document.getElementById('pie_chart_id_'+chartId));
                    chart.draw(data, options);
                }
            }
        } else if(vData.chartType == 'LINE') {
            google.charts.load('current', {'packages':['corechart']});
            google.charts.setOnLoadCallback(drawChart);
            function drawChart() {
                var chartDataRows = vData.chartData.rows;
                var dimensionKey = vData.dimensionKey;
                var metricsKey = vData.metricsKey;
                var data = dynamicChartData(chartDataRows, dimensionKey, metricsKey);
                var options = {
                    curveType: 'function',
                    legend: { position: 'bottom' }
                };
                var chartId = vData.chartId;
                var chartName = vData.chartName;
                var order = vData.order;
                var flag = vData.flag;

                if(chartDataRows == null || flag == '0'){
                    var html = '<div data-order="'+order+'" class="chart_div_'+chartId+' charts-block  chart-list-div"><div class="bar_chart_'+chartId+' chart-name-class">'+chartName+'</div><div class="chart-body bar_chart_id_'+chartId+'" id="bar_chart_id_'+chartId+'"><p class="flag-chart-block">No data</p></div></div>';

                    $('.chart-listing-block').append(html);

                    $('.form-standard').removeClass('zeal_loader_enabled');

                }else{
                    var html = '<div data-order="'+order+'" class="chart_div_'+chartId+' charts-block  chart-list-div"><div class="line_chart_'+chartId+' chart-name-class">'+chartName+'</div><div class="chart-body line_chart_id_'+chartId+'" id="line_chart_id_'+chartId+'"></div></div>';

                    $('.chart-listing-block').append(html);

                    $('.form-standard').removeClass('zeal_loader_enabled');

                    var chart = new google.visualization.LineChart(document.getElementById('line_chart_id_'+chartId));
                    chart.draw(data, options);
                }
            }
        } else if(vData.chartType == 'COLUMN') {
            google.charts.load("current", {packages:['corechart']});
            google.charts.setOnLoadCallback(drawChart);
            function drawChart() {
                var chartDataRows = vData.chartData.rows;
                var dimensionKey = vData.dimensionKey;
                var metricsKey = vData.metricsKey;
                var data = dynamicChartData(chartDataRows, dimensionKey, metricsKey);
                var options = {
                    width: '95%',
                    height: '400px',
                    bar: {groupWidth: "95%"},
                    legend: { position: "none" },
                };
                var chartId = vData.chartId;
                var chartName = vData.chartName;
                var order = vData.order;
                var flag = vData.flag;

                if(chartDataRows == null || flag == '0'){
                    var html = '<div data-order="'+order+'" class="chart_div_'+chartId+' charts-block  chart-list-div"><div class="bar_chart_'+chartId+' chart-name-class">'+chartName+'</div><div class="chart-body bar_chart_id_'+chartId+'" id="bar_chart_id_'+chartId+'"><p class="flag-chart-block">No data</p></div></div>';

                    $('.chart-listing-block').append(html);

                    $('.form-standard').removeClass('zeal_loader_enabled');

                }else{
                    var html = '<div data-order="'+order+'" class="chart_div_'+chartId+' charts-block  chart-list-div"><div class="column_chart_'+chartId+' chart-name-class">'+chartName+'</div><div class="chart-body column_chart_id_'+chartId+'" id="column_chart_id_'+chartId+'"></div></div>';

                    $('.chart-listing-block').append(html);

                    $('.form-standard').removeClass('zeal_loader_enabled');

                    var chart = new google.visualization.ColumnChart(document.getElementById('column_chart_id_'+chartId));
                    chart.draw(data, options);
                }
            }
        } else if(vData.chartType == 'GEO') {
            google.charts.load('current', {
                'packages':['geochart'],
                'mapsApiKey': MAPAPIKEY
            });
            google.charts.setOnLoadCallback(drawChart);

            if(vData.dimensionKey == 'Sub Continent'){
                chart_options = {
                    height:200,
                    resolution: 'subcontinents',
                };
            } else if(vData.dimensionKey == 'Continent') {                
                chart_options = {
                    height:200,
                    resolution: 'continents',
                };
            } else if(vData.dimensionKey == 'City') {
                chart_options = {
                    height:200,                 
                    displayMode: 'marker',   
                };
            }
            else if(vData.dimensionKey == 'Country') {
                chart_options = {
                    height:200, 
                };
            }

            function drawChart() {
                var chartDataRows = vData.chartData.rows;
                var dimensionKey = vData.dimensionKey;
                var metricsKey = vData.metricsKey;
                var data = dynamicChartData(chartDataRows, dimensionKey, metricsKey);
                var options = chart_options;
                var chartId = vData.chartId;
                var chartName = vData.chartName;
                var order = vData.order;
                var flag = vData.flag;
                
                if(chartDataRows == null || flag == '0'){
                    var html = '<div data-order="'+order+'" class="chart_div_'+chartId+' charts-block  chart-list-div"><div class="bar_chart_'+chartId+' chart-name-class">'+chartName+'</div><div class="chart-body bar_chart_id_'+chartId+'" id="bar_chart_id_'+chartId+'"><p class="flag-chart-block">No data</p></div></div>';

                    $('.chart-listing-block').append(html);

                    $('.form-standard').removeClass('zeal_loader_enabled');

                }else{
                    var html = '<div data-order="'+order+'" class="chart_div_'+chartId+' charts-block  chart-list-div"><div class="geo_chart_'+chartId+' chart-name-class">'+chartName+'</div><div class="chart-body geo_chart_id_'+chartId+'" id="geo_chart_id_'+chartId+'"></div></div>';

                    $('.chart-listing-block').append(html);

                    $('.form-standard').removeClass('zeal_loader_enabled');

                    var chart = new google.visualization.GeoChart(document.getElementById('geo_chart_id_'+chartId));
                    chart.draw(data, options);
                }
            }
            
        } else if(vData.chartType == 'STAT') {
           
            var chartDataRows = vData.chartData.rows;
            var metricsKey = vData.metricsKey;
            var chartId = vData.chartId;
            var chartName = vData.chartName;
            var order = vData.order;
            var startDate = vData.startDate;
            var endDate = vData.endDate;
            var data = 0;
            if(chartDataRows == null){
                var html = '<div data-order="'+order+'" class="chart_div_'+chartId+' '+vData.chartType+'-chart-block charts-block  chart-list-div"><div class="stat_chart_'+chartId+' chart-name-class">'+chartName+'</span></div><div class="chart-body stat_chart_id_'+chartId+'" id="stat_chart_id_'+chartId+'"><div class="number">'+data+'</div><div class="range">'+metricsKey+' from '+startDate+' to '+endDate+' </div></div></div>';

                $('.chart-listing-block').append(html);
                $('.form-standard').removeClass('zeal_loader_enabled');
                
            }else{
                $.each(chartDataRows, function (key,val) {
                    data = parseInt(val[0]);

                    var html = '<div data-order="'+order+'" class="chart_div_'+chartId+' '+vData.chartType+'-chart-block charts-block  chart-list-div"><div class="stat_chart_'+chartId+' chart-name-class">'+chartName+'</span></div><div class="chart-body stat_chart_id_'+chartId+'" id="stat_chart_id_'+chartId+'"><div class="number">'+data+'</div><div class="range">'+metricsKey+' from '+startDate+' to '+endDate+' </div></div></div>';

                    $('.chart-listing-block').append(html);

                    $('.form-standard').removeClass('zeal_loader_enabled');
                });
            }
        } else if(vData.chartType == 'LIST') {
            google.charts.load('current', {'packages':['table']});
            google.charts.setOnLoadCallback(drawTable);
            function drawTable() {
                var chartDataRows = vData.chartData.rows;
                var dimensionKey = vData.dimensionKey;
                var metricsKey = vData.metricsKey;
                var chartId = vData.chartId;
                var chartName = vData.chartName;
                var order = vData.order;
                var flag = vData.flag;
                var dataArray = [];
                var titleArray = [];

                $.each(chartDataRows, function (key,val) {
                    dataArray.push(val);
                });
                
                $.each(dataArray, function (key,val) {
                    val[1] = parseInt(val[1]);
                    titleArray.push(val);
                });

                var data = new google.visualization.DataTable();
                data.addColumn('string', dimensionKey);
                data.addColumn('number', metricsKey);
                data.addRows(
                    titleArray
                );

                if(chartDataRows == null || flag == '0'){
                    var html = '<div data-order="'+order+'" class="chart_div_'+chartId+' charts-block  chart-list-div"><div class="bar_chart_'+chartId+' chart-name-class">'+chartName+'</div><div class="chart-body bar_chart_id_'+chartId+'" id="bar_chart_id_'+chartId+'"><p class="flag-chart-block">No data</p></div></div>';

                    $('.chart-listing-block').append(html);

                    $('.form-standard').removeClass('zeal_loader_enabled');

                }else{
                    var html = '<div data-order="'+order+'" class="chart_div_'+chartId+' charts-block  chart-list-div"><div class="list_chart_'+chartId+' chart-name-class">'+chartName+'</div><div class="chart-body list_chart_id_'+chartId+'" id="list_chart_id_'+chartId+'"></div></div>';

                    $('.chart-listing-block').append(html);

                    $('.form-standard').removeClass('zeal_loader_enabled');

                    var table = new google.visualization.Table(document.getElementById('list_chart_id_'+chartId));

                    table.draw(data, {showRowNumber: true, width: '100%', height: '100%', pageSize: 1});
                }
            }
        }
    });
}

function dynamicChartData(chartDataRows, dimensionKey, metricsKey) {
    var dataArray = [];
    var titleArray = [];
    $.each(chartDataRows, function (key,val) {
        // For adding region code in geo chart
        switch(dimensionKey) {
            case "Sub Continent":
                var found_data = subcontinents.filter(function(item) { return item.label === val[0]; });
                if(found_data[0] != undefined && found_data[0]['code'] != undefined){
                    val.unshift(found_data[0]['code']);
                } else {
                    val.unshift('0');
                }
                break;
            case "Continent":
                var found_data = continents.filter(function(item) { return item.label === val[0]; });
                if(found_data[0] != undefined && found_data[0]['code'] != undefined){
                    val.unshift(found_data[0]['code']);
                } else {
                    val.unshift('0');
                }
                break;
        }
        dataArray.push(val);
    });

    if(dimensionKey == 'Sub Continent' || dimensionKey == 'Continent') {
        titleArray.push(["Region Code", dimensionKey, metricsKey]);
    } else {
        titleArray.push([dimensionKey, metricsKey]);
    }
    $.each(dataArray, function (key,val) {
        if(dimensionKey == 'Sub Continent' || dimensionKey == 'Continent') {
            val[2] = parseInt(val[2]);
        } else {
            val[1] = parseInt(val[1]);
        }
        titleArray.push(val);
    });
    
    var data = google.visualization.arrayToDataTable(
        titleArray
    );
    return data;
}

