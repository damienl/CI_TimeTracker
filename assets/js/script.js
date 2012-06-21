/* Author:

*/

$(function() {
    //log('script init');

   $('table.piechart-data').each(function() {
       var cpt_line= $(this).find('tbody tr').length;
       if (cpt_line>1) init_piechart( $(this) );
    });

     $('div.ttgraph').each(function() {
        graph=init_graph( $(this) );
    });

        /*** TEST ***/



});





// lightweight wrapper for console.log
// usage: log('inside coolFunc',this,arguments);
// http://paulirish.com/2009/log-a-lightweight-wrapper-for-consolelog/
window.log = function(){
  log.history = log.history || [];   // store logs to an array for reference
  log.history.push(arguments);
  if(this.console){
    console.log( Array.prototype.slice.call(arguments) );
  }
};



mysqlDate2time = function (str) {
    var t= str.split(/[- :]/);
    if (t.length>3)
        var d = new Date(t[0], t[1]-1, t[2], t[3], t[4], t[5]);
    else
        var d = new Date(t[0], t[1]-1, t[2], 0, 0, 0);

    return d;
    }


/*** datepicker ***/


$(function() {
    var DP1= $('#datefrom').datepicker();
    var DP2= $('#dateto').datepicker();
    log(DP1);


    $('#datepicker_select a').click( function(){

        $('#datefrom').val( $(this).data("fromdate") );
        $('#datefrom').data( 'date', $(this).data("fromdate") );

        $('#dateto').val( $(this).data("todate") );
        $('#dateto').data( 'date', $(this).data("todate") );

        $('#date_form').submit();
        return false;
    });
});






/*** d3.js ***/

/** piechart **/

function init_piechart(obj) {
    var target= obj.parent().parent().find('.piechart-target');
    piechart= new_piechart(obj,target,300);

}



 var new_piechart= function( data_table, target, width ) {
        var self={};

        if (!width) width=200;

        self.data_table=data_table;
        self.target=target;

        self.data=[];
        self.tr=[];

        data_table.find('tr').each(function() {
            var obj={};
            obj.id= $(this).attr('data-id');
            obj.value= $(this).attr('data-value');
            if (obj.value && obj.id) {
                self.data.push(obj);
                self.tr['slice_'+obj.id]= $(this);

                $(this).hover(  // TODO add focus on off link
                    function () { self.mouseover('slice_'+obj.id) },
                    function () { self.mouseout('slice_'+obj.id) }
                );
            }
        });


        var w=width, h=w, r=w/2, color = d3.scale.category20c();

        var vis = d3.select(self.target[0])
            .append("svg:svg")
            .data([self.data])
                .attr("width", w)
                .attr("height", h)
                .attr("viewBox",0+" "+0+" "+w+" "+h)
            .append("svg:g")
                .attr("transform", "translate(" + w/2 + "," + h/2 + ")");

        var arc = d3.svg.arc()
            .outerRadius(3/4*r);

         var arc_hover = d3.svg.arc()
            .outerRadius(r);

        var pie = d3.layout.pie()
            .value(function(d) { return d.value; });

        var arcs = vis.selectAll("g.slice")
            .data(pie)
            .enter()
                .append("svg:g")
                    .attr("id", function(d) { return 'slice_'+d.data.id;  })
                    .on("mouseover", function(){
                        self.mouseover( this.id );
                        })
                    .on("mouseout",  function(){
                        self.mouseout( this.id );
                        })
                    .attr("class", "slice");

            arcs.append("svg:path")
                    .attr("fill", function(d, i) { return color(i); } )
                    .attr("d", arc);




        self.mouseover= function(id) {
            var gslice=d3.select(self.target[0]).select('#'+id);
            gslice.selectAll("path").transition().attr("d", arc_hover );
            self.tr[id].addClass('hover');
        }

        self.mouseout= function(id) {
            var gslice=d3.select(self.target[0]).select('#'+id);
            gslice.selectAll("path").transition().attr("d", arc );
            self.tr[id].removeClass('hover');
        }

        return self

    }




/** graph **/

function init_graph(obj) {
    var self={};
    self.target=obj;
    self.json_param=jQuery.parseJSON(self.target.attr( 'data-graph' ));
    self.timelapse=gettimelapse( self.json_param.group_by );

    self.updateJsonParam= function( id, data ) {
        self.json_param[id]=data;
       // if (self.graph) self.loadJson( function(){ self.graph.update(); });
        }

    self.update= function(){
        if (self.graph) self.loadJson( function(){ self.graph.update(); });
    }

    self.loadJson= function( callback ) {
        var url= BASE_URL+'tt/'+self.json_param.username+'/histo/'+self.json_param.cat+'/'+self.json_param.id+'/'+self.json_param.datefrom+'_'+self.json_param.dateto+'/'+self.json_param.groupby+'.json';
        $.getJSON(url, function(data) {  self.data=data; callback()   });
    }

    self.buildGraph=function () {
        if (self.json_param.type_graph== 'histo') self.graph=self.histograph();
    }

    $('#groupby_select').change(
        function(){
            graph.updateJsonParam('groupby', $(this).val() );
            graph.update();
        }
    );

    $('#date_form').submit(
        function(){
            graph.updateJsonParam('datefrom', $(this).find('#datefrom').val() );
            graph.updateJsonParam('dateto',   $(this).find('#dateto').val() );
            graph.update();
            return false
        }
    );

    //init
    self.loadJson( function(){ self.buildGraph() } );


    function gettimelapse(timelapsename) {
        var r={
            hour:       60*60 *1000,
            day:        60*60*24 *1000,
            week:       60*60*24*7 *1000
            }
        return r[timelapsename]
    }








    /* histograph */
    self.histograph=function() {

        var histograph_obj={};
        self.mousepos=[0,0];

        var w = 1170, h = 600, color = d3.scale.category20(),
        bar_width= (1/2) * w/self.data.times.length;

        var vis = d3.select(self.target[0]).append("svg:svg")
            .attr("width", w)
            .attr("height", h+20)
            .attr("viewBox",0+" "+0+" "+w+" "+(h+20));

        var graphgroup= vis.append("svg:g").attr("id", "graph_g")
            .on('mousemove',function(){ self.mousepos=d3.mouse(this) });
        vis.append("svg:g").attr("id", "xaxis_g");
        vis.append("svg:g").attr("id", "yaxis_g");

        var tooltip=vis.append("svg:g")
            .attr("id", "tooltip")
            .style("pointer-events","none")
            .attr('transform','translate(100,100)');
        tooltip.append("svg:rect")
                .attr('class','tt_background')
                .attr('fill','white')
                .attr('fill-opacity', 0.8 )
                .attr('width','100');
        tooltip.style('opacity',0);




        histograph_obj.showtip=function(textarray) {

            tooltip.selectAll('text').remove();

            for (i in textarray)
                 tooltip.append('svg:text').text(textarray[i]).attr('y', i*20+20 ).attr('x','10');

            var maxw=50;
            tooltip.selectAll('text').each( function() { var bb=this.getBBox(); if (bb.width>maxw) maxw=bb.width; } );
            tooltip.wtt= maxw+20;
            tooltip.htt= 20*textarray.length +10;
            tooltip.select('rect.tt_background')
                .attr('height', tooltip.htt )
                .attr('width', tooltip.wtt);
            tooltip.transition().style('opacity',1);


        }

        histograph_obj.actutip=function() {
            x=self.mousepos[0];
            y=self.mousepos[1];

            var dx = 40;
            var xb= x + dx;
            var yb= y + dx; //- tooltip.htt/2;

            if (xb+tooltip.wtt > w)   xb= x - tooltip.wtt -dx;


            if (yb+tooltip.htt > h)   yb= h - tooltip.htt;

            tooltip.attr('transform','translate('+xb+','+yb+')');

              }

        histograph_obj.hidetip=function() {      tooltip.transition().style('opacity',0);     }


        histograph_obj.update=function() {
            var data=self.data;
            bar_width= (1/2) * (w-100)/self.data.times.length;

            var fx = d3.time.scale().domain([mysqlDate2time(data.min), mysqlDate2time(data.max) ]).range([100, w]);

            var fy = d3.scale.linear().domain([0, d3.max( data.times, function(d){ return d.total } )]).range([h, 10]);
            var fh = d3.scale.linear().domain([0, d3.max(data.times, function(d){ return d.total } )]).range([0, h-10]);

            var f_yaxis = d3.scale.linear().domain([0, d3.max(data.times, function(d){ return d.total/(60*60) } )]).range([h, 10]);
            var f_xaxis = d3.time.scale().domain([mysqlDate2time(data.min), mysqlDate2time(data.max) ]).range([100+bar_width/2, w+bar_width/2]);



            var format_date= function(t, fulldate) {
                if (fulldate==null)  fulldate=false;
                var formatD = d3.time.format("%Y-%m-%d");
                var d=formatD(t);

                var formatT = d3.time.format("%H:%M");
                var t=formatT(t);

                if (fulldate) res=d+' '+t;
                else if (t!='00:00') res=t;
                    else res=d;

                return res
            }


            var format_duration= function(duration) {

                if (duration==0) return '';

                var h= Math.floor(duration/60/60);
                var m= Math.floor(duration/60) % 60;
                var s= duration%60;

                if (m<10) m= '0'+m;

                if (h==0) res= m+' min';//' '+s+'s';
                    else if (h<24) {
                            if (m=='00') res= h+' h';
                                else res= h+' h '+m+' min';
                        }
                        else {
                            var d= Math.floor( h/24);
                            h= h%24;
                            if (h==0) res= d+' days';
                                else if (m=='00') res= d+' days '+h+' h';
                                    else res= d+' days '+h+' h '+m+' min';
                            }

                return res
            }



            var timegroups = vis.select('#graph_g').selectAll('g.timegroup').data(data.times, function(d) { return d.time;});

            timegroups.enter().append('svg:g')
                .attr('class', 'timegroup')
                .attr('transform', function(d) {  return 'translate('+fx( mysqlDate2time(d.time) )+',0)'       });

            timegroups.transition().attr('transform', function(d) {    return 'translate('+fx( mysqlDate2time(d.time) )+',0)'       });

            timegroups.exit().remove();

            function addparent(d,parent) {
                for (i in d) d[i].parent= parent;
                return d
            }



                var activityrects = timegroups.selectAll('rect.activity').data(function(d) { return addparent(d.activities,d)}, function(d) { return d.activity_ID });
                activityrects.enter().append('svg:rect')
                    .attr('class', 'activity')
                    .attr('width', bar_width )
                    .attr('y', fy(0) )
                    .attr('height', 0)
                    .attr('fill', function(d, i) { return color(d.activity_ID)  })
                    .on('mouseover', function(d) {
                            var t=[];
                            t.push( format_date(mysqlDate2time(d.parent.time), true) );
                            t.push( d.activity);
                            t.push( format_duration(d.duration) );
                            histograph_obj.showtip(t)
                         })
                    .on('mouseout', function() { histograph_obj.hidetip() } )
                    .on('mousemove', function() { histograph_obj.actutip() } )
                    .on('click', function(d) {          alert(d.parent);  });


                activityrects.transition(2000)
                    .attr('width', function() {         return bar_width    })
                    .attr('y', function(d,i) {
                        if (i==0) cumul=0;
                        var oy=fy( cumul + d.duration);
                        cumul+=d.duration;
                        return oy
                        })
                    .attr('height', function(d) {  return fh(d.duration)  });

                activityrects.exit().remove();



                var xaxis = vis.select('#xaxis_g').attr("transform", "translate(0," + h + ")");
                xaxis.transition(800).call(d3.svg.axis().scale(f_xaxis).ticks(7).tickFormat( function(d) { return format_date(d) } ) );


                var yaxis = vis.select('#yaxis_g').attr("transform", "translate(100,0)");
                 yaxis.transition(800).call( d3.svg.axis().orient('left').scale(f_yaxis).ticks(6).tickFormat( function(d) { return format_duration(d*60*60) } ) );

            }



            histograph_obj.update();
            return histograph_obj;
        }



    return self
}