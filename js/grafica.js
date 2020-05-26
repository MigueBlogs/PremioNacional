$(function() {
    if (porcentajeEstados.length == 0) return;

    var chartDiv = document.getElementById("barrasConstancia");

    var margin = {top: 40, right: 7, bottom: 80, left: 45},
    width = chartDiv.clientWidth - margin.left - margin.right,
    height = 400 - margin.top - margin.bottom;

    var greyColor = "#898989";
    var barColor = d3.scale.linear()
                    .domain([1, 16, 32])
                    .range(["red", "yellow", "green"])
                    .interpolate(d3.interpolateHcl);
    var highlightColor = "gold";
    var highlightColorText = "black";

    var svg = d3.select("#barsvg").append("svg").attr("id","barAnimated")
        .attr("width", width + margin.left + margin.right)
        .attr("height", height + margin.top + margin.bottom)
    .append("g")
        .attr("transform", "translate(" + margin.left + "," + margin.top + ")");

    var x = d3.scale.ordinal()
            .rangeRoundBands([0, width], 0.4);

    var y = d3.scale.linear()
        .range([height, 0]);

    var xAxis = d3.svg.axis()
            .scale(x)
            .orient("bottom");
            
    var max = porcentajeEstados[0].total;
    var ticks = max > 10 ? 10 : max;
    var yAxis = d3.svg.axis()
            .scale(y)
            .orient("left")
            .ticks(ticks);
 
    x.domain(porcentajeEstados.map( d => { return d.estado; }));
    y.domain([0, max]);

    svg.append("g")
        .attr("class", "x axis xtext")
        .attr("transform", "translate(0," + height + ")")
        .call(xAxis)
        .selectAll("text")  
        .style("text-anchor", "end")
        .attr("dx", "-0.8em")
        .attr("dy", ".15em")
        .attr("transform", "rotate(-65)" );

    svg.append("g")
        .attr("class","y axis")
        .call(yAxis);
   
    const sleep = (milliseconds) => {
            return new Promise(resolve => setTimeout(resolve, milliseconds))          }

    function constanciasGraphicAll(){
        
        //remueve la gráfica anterior si ya se cargó la página
        d3.select("#barSvgTop").selectAll("svg > g > rect").remove();
        d3.select("#barSvgTop").selectAll("svg > g > text").remove();
        $("#barSvgTop").hide();
        $("#barsvg").show();

        svg.selectAll(".barAll")
            .data(porcentajeEstados)
            .enter().append("rect")
            .attr("class", "barAll")
            .style("display", d => { return d.total === null ? "none" : null; })
            .style("fill",  d => { 
                return d.total === d3.max(porcentajeEstados,  d => { return d.total; }) 
                ? highlightColor : barColor(d.total)
                })
            .attr("x",  d => { return x(d.estado); })
            .attr("width", x.rangeBand())
                .attr("y",  d => { return height; })
                .attr("height", 0)
                    .transition()
                    .duration(750)
                    .delay(function (d, i) {
                        return i * 150;
                    })
            .attr("y",  d => { return y(d.total); })
            .attr("height",  d => { return height - y(d.total); });

        svg.selectAll(".labelAll")        
            .data(porcentajeEstados)
            .enter()
            .append("text")
            .attr("class", "labelAll porciento labelPercent")
            .style("display",  d => { return d.total === null ? "none" : null; })
            .attr("x", ( d => { return x(d.estado) + (x.rangeBand() / 2) -8 ; }))
                .style("fill",  d => { 
                    return d.total === d3.max(porcentajeEstados,  d => { return d.total; }) 
                    ? highlightColorText : greyColor
                    })
            .attr("y",  d => { return height; })
                .attr("height", 0)
                    .transition()
                    .duration(750)
                    .delay((d, i) => { return i * 150; })
            .text( d => { return d.total; })
            .attr("y",  d => { return y(d.total) + .1; })
            .attr("dy", "-.7em")
            .attr("dx", ".3em")
            .attr("transform-origin",  d => { return ((x(d.estado) + (x.rangeBand() / 2) - 8))+"px "+(y(d.total)+ .1)+"px"} )
            .attr("transform", "rotate(0)" );
    }

    function redraw(){
        //console.log($("#barAnimated").attr(height));
            width = chartDiv.clientWidth;
            //console.log(width);

            d3.select('#barAnimated')
                .attr("width", width);
            width = width- margin.left - margin.right;
            //Updating X axis
            x = d3.scale.ordinal()
                .rangeRoundBands([0, width], 0.4);
            xAxis = d3.svg.axis()
                .scale(x)
                .orient("bottom"); 
            x.domain(porcentajeEstados.map( d => { return d.estado; }));
            svg.select(".x.axis")
                .attr("transform", "translate(0," + height + ")")
                .call(xAxis)
                .selectAll("text")  
                .style("text-anchor", "end")
                .attr("dx", "-.8em")
                .attr("dy", ".15em")
                .attr("transform", "rotate(-65)" );
            
            // Force D3 to recalculate and update the graphic
            d3.selectAll(".barAll")
                .attr("x",  d => { return x(d.estado) })
                .attr("width", x.rangeBand());

            d3.selectAll(".porciento")        
                .attr("x",  d => { return x(d.estado) })
                .attr("x", ( d => { return x(d.estado) + (x.rangeBand() / 2) -8 ; }))
                .attr("y",  d => { return height; })
                .attr("y",  d => { return y(d.total) + .1; })
                .attr("transform-origin",  d => { return ((x(d.estado) + (x.rangeBand() / 2) - 8))+"px "+(y(d.total)+ .1)+"px"} )
                .attr("transform", "rotate(0)" );
        };
    constanciasGraphicAll();
    window.addEventListener("resize", redraw);
    
});