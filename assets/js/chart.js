/**
 * Plot classes
 */

/**
 * Abstract plot base class
 * @param placeholder
 * @param data
 * @param options
 */
function BasePlot(placeholder, data, options) {
    this.p           = null;                     // Plot object
    this.placeholder = placeholder || '#plot';   // Selector
    this.data        = data || [];               // array of series

    // global min-max
    this.xmin        = null;
    this.xmax        = null;
    this.ymin        = null;
    this.ymax        = null;

    this._defaults = {
            // Plot and plugins settings
            series: {
                shadowSize: 0,    // Drawing is faster without shadows
            },
            xaxis: {
                show: true,

                //tickSize: 10,
                //minTickSize: 1,

                //min: null,  // auto
                //max: null,  // auto
            },
            yaxis: {
                show: true

                //tickSize: 10,
                //minTickSize: 1,

                //min: null,  // auto
                //min: this.getYMinValue()-1,
                //max: null,  // auto
                //max: this.getYMaxValue()+3,
            },
            points: {
                show: true,
                //fill: true,
                //fillColor: false,

                // Plugin: symbol
                //symbol: "circle", // jquery.flot.symbol.js: circle,square,diamond,triangle,cross
            },
            lines: {
                show: true,
                fill: true,
            },
            //bars: {
            //    show: true,
            //    barWidth: 1,
            //    align: "left",
            //},
            grid: {
                hoverable: true,
                clickable: true,
            },
    };
}

BasePlot.prototype.getYMinPoint = function(data){
    var min = null, result = null,
        d = (typeof data === "undefined" || data === null) ? this.data : data;
    $.each(d, function(si, series) {
        $.each(series.data, function(pi, point) {
            if (point !== null) {
                var v = parseFloat(point[1]);
                if(!isNaN(v) && (min === null || v < min)) {
                    min = v;
                    result = point;
                }
            }
        });
    });
    return result;
};
BasePlot.prototype.getYMinValue = function(data){
    var d = (typeof data === "undefined" || data === null) ? this.data : data,
        point = this.getYMinPoint(d);
    return ((point !== null) ? point[1] : null);
};

BasePlot.prototype.getYMaxPoint = function(data){
    var max = null, result = null,
        d = (typeof data === "undefined" || data === null) ? this.data : data;
    $.each(d, function(si, series) {
        $.each(series.data, function(pi, point) {
            if (point !== null) {
                var v = parseFloat(point[1]);
                if (!isNaN(v) && (max === null || v > max)) {
                    max = v;
                    result = point;
                }
            }
        });
    });
    return result;
};
BasePlot.prototype.getYMaxValue = function(data){
    var d = (typeof data === "undefined" || data === null) ? this.data : data,
        point = this.getYMaxPoint(d);
    return ((point !== null) ? point[1] : null);
};

BasePlot.prototype.getXMinPoint = function(data){
    var min = null, result = null,
        d = (typeof data === "undefined" || data === null) ? this.data : data;
    $.each(d, function(si, series) {
        $.each(series.data, function(pi, point) {
            if (point !== null) {
                var v = parseFloat(point[0]);
                if (!isNaN(v) && (min === null || v < min)) {
                    min = v;
                    result = point;
                }
            }
        });
    });
    return result;
};
BasePlot.prototype.getXMinValue = function(data){
    var d = (typeof data === "undefined" || data === null) ? this.data : data,
        point = this.getXMinPoint(d);
    return ((point !== null) ? point[0] : null);
};

BasePlot.prototype.getXMaxPoint = function(data){
    var max = null, result = null,
        d = (typeof data === "undefined" || data === null) ? this.data : data;
    $.each(d, function(si, series) {
        $.each(series.data, function(pi, point) {
            if (point !== null) {
                var v = parseFloat(point[0]);
                if (!isNaN(v) && (max === null || v > max)) {
                    max = v;
                    result = point;
                }
            }
        });
    });
    return result;
};
BasePlot.prototype.getXMaxValue = function(data){
    var d = (typeof data === "undefined" || data === null) ? this.data : data,
        point = this.getXMaxPoint(d);
    return ((point !== null) ? point[0] : null);
};

BasePlot.prototype.refresh = function(){
    if (typeof this.p === 'undefined') {
        return;
    }

    this.p.setupGrid();
    this.p.draw();
};

BasePlot.prototype.setData = function(data){
    if (typeof this.p === 'undefined') {
        return;
    }
    this.data = data;

    // calculate global min-max
    var po = this.getMinMaxPoints(this.data);

    // update global min-max
    this.ymin  = po.pymin !== null ? po.pymin[1] : null;
    this.ymax  = po.pymax !== null ? po.pymax[1] : null;
    this.xmin  = po.pxmin !== null ? po.pxmin[0] : null;
    this.xmax  = po.pxmax !== null ? po.pxmax[0] : null;

    // TODO: filter out unknown series before set data?
    this.p.setData(this.data);
};

BasePlot.prototype.appendData = function(data){
    // stub append data
    return 0;
};

BasePlot.prototype.zoom = function(args) {
    // By axis zoom, upgraded version of plot.zoom().
    // Added axis option (x or y).
    // @see jquery.flot.navigate.js plot.zoom()
    // args : {amount, center, preventEvent, axis}

    // TODO: need refactor, use disabling axis zoom (opts.zoomRange) and call parent plot.zoom()

    if (typeof this.p === 'undefined') {
        return;
    }

    if (!args)
        args = {};

    var c = args.center,
        amount = args.amount || this.p.getOptions().zoom.amount,
        w = this.p.width(), h = this.p.height(),
        ax = args.axis;

    if (!c)
        c = { left: w / 2, top: h / 2 };

    if (!ax)
        ax = null;

    var xf = c.left / w,
        yf = c.top / h,
        minmax = {
            x: {
                min: c.left - xf * w / amount,
                max: c.left + (1 - xf) * w / amount
            },
            y: {
                min: c.top - yf * h / amount,
                max: c.top + (1 - yf) * h / amount
            }
        };

    $.each(this.p.getAxes(), function(_, axis) {
        if (ax === null || ax === axis.direction) {
            var opts = axis.options,
            min = minmax[axis.direction].min,
            max = minmax[axis.direction].max,
            zr = opts.zoomRange,
            pr = opts.panRange;

            if (zr === false) // no zooming on this axis
                return false;

            min = axis.c2p(min);
            max = axis.c2p(max);
            if (min > max) {
                // make sure min < max
                var tmp = min;
                min = max;
                max = tmp;
            }

            //Check that we are in panRange
            if (pr) {
                if (pr[0] != null && min < pr[0]) {
                    min = pr[0];
                }
                if (pr[1] != null && max > pr[1]) {
                    max = pr[1];
                }
            }

            var range = max - min;
            if (zr &&
                ((zr[0] != null && range < zr[0] && amount >1) ||
                 (zr[1] != null && range > zr[1] && amount <1)))
                return;

            opts.min = min;
            opts.max = max;
        }
    });

    this.p.setupGrid();
    this.p.draw();

    if (!args.preventEvent)
        this.p.getPlaceholder().trigger("plotzoom", [ this.p, args ]);
};

BasePlot.prototype.zoomOut = function(args) {
    // By axis zoom out, upgraded version of plot.zoomOut().
    // Added axis option (x or y).
    // @see jquery.flot.navigate.js plot.zoomOut()
    // args : {amount, center, preventEvent, axis}

    if (typeof this.p === 'undefined') {
        return;
    }

    if (!args)
        args = {};

    if (!args.amount)
        args.amount = this.p.getOptions().zoom.amount;

    args.amount = 1 / args.amount;
    return this.zoom(args);
};

BasePlot.prototype.autozoomY = function(args) {
    // Auto zoom on y axis in current range, upgraded version of plot.zoom().
    // @see jquery.flot.navigate.js plot.zoom()
    // args : {amount, center, preventEvent, axis}

    if (typeof this.p === 'undefined') {
        return false;
    }

    if (!args)
        args = {};
    // Override args
    args.amount = 1;
    args.center = null;
    args.axis   = "y";

    var minmax = {
            x: {
                min: null,
                max: null
            },
            y: {
                min: null,
                max: null
            }
        },
        pcnt = 0;

    // TODO: need truely getting minmax on multiple x or y axes, now get last
    $.each(this.p.getAxes(), function(_, axis) {
        if (axis.direction === 'x') {
            minmax[axis.direction].min = axis.min;
            minmax[axis.direction].max = axis.max;
        }
    });
    // No autozoomY if there is no x minmax valid interval
    if (minmax["x"].min === null ||  minmax["x"].max === null) {
        return false;
    }

    // Check all series points count
    $.each(this.p.getData(), function(si, series) {
        pcnt += series.data.length;
    });
    // No autozoomY if there is no points at all
    if (pcnt == 0) {
        return false;
    }

    // Found ymin-ymax on x minmax range
    $.each(this.p.getData(), function(si, series) {
        var isrange = false;
        $.each(series.data, function(pi, point) {
            if (point !== null) {
                var vx = parseFloat(point[0]),
                    vy = parseFloat(point[1]);

                // Check if inside x range interval or not
                if (!isNaN(vx) !== null) {
                    if (isrange) {
                        if ((vx < minmax["x"].min) || (vx > minmax["x"].max)) {
                            isrange = false;
                        }
                    } else {
                        if ((vx >= minmax["x"].min) && (vx <= minmax["x"].max)) {
                            isrange = true;
                        }
                    }
                }

                // ymin
                if (!isNaN(vy) && isrange && (minmax["y"].min === null || vy <= minmax["y"].min)) {
                    minmax["y"].min = vy;
                }
                // ymax
                if (!isNaN(vy) && isrange && (minmax["y"].max === null || vy >= minmax["y"].max)) {
                    minmax["y"].max = vy;
                }
            }
        });
    });
    // No autozoomY if no valid points on x minmax interval
    if (minmax["y"].min === null || minmax["y"].max === null) {
        return false;
    }

    if (minmax["y"].min > minmax["y"].max) {
        // make sure ymin < ymax
        var tmp = minmax["y"].min;
        minmax["y"].min = minmax["y"].max;
        minmax["y"].max = tmp;
    }

    $.each(this.p.getAxes(), function(_, axis) {
        if (axis.direction === 'y') {
            var opts = axis.options,
                min = minmax[axis.direction].min,
                max = minmax[axis.direction].max,
                zr = opts.zoomRange,
                pr = opts.panRange;

            if (zr === false) // no zooming on this axis
                return false;

            //Check that we are in panRange
            if (pr) {
                if (pr[0] != null && min < pr[0]) {
                    min = pr[0];
                }
                if (pr[1] != null && max > pr[1]) {
                    max = pr[1];
                }
            }

            // TODO: fix zoom range restrictions
            /*
            var range = max - min;
            if (zr &&
                ((zr[0] != null && range < zr[0] && amount >1) ||
                 (zr[1] != null && range > zr[1] && amount <1)))
                return false;
            */

            opts.min = min;
            opts.max = max;
        }
    });

    this.p.setupGrid();
    this.p.draw();

    if (!args.preventEvent)
        this.p.getPlaceholder().trigger("plotzoom", [ this.p, args ]);

    return true;
};

BasePlot.prototype.pan = function(args) {
    if (typeof this.p === 'undefined') {
        return;
    }

    if (!args)
        args = {};

    // Get plot width and height for default pan delta in pixels
    var w = this.p.width(), defw = 10, h = this.p.height(), defh = 10;
    if (w < defw)
        w = defw;
    if (h < defh)
        h = defh;

    // TODO: refactor to use inc/dec by width +Xw (+w, -2w, +0.5w) and by pixels +X (+2, -5)
    switch (args.left) {
    case "+":
        dx = +w;
        break;
    case "-":
        dx = -w;
        break;
    case "+/2":
        dx = +w/2;
        break;
    case "-/2":
        dx = -w/2;
        break;
    default:
        dx = +args.left;
        break;
    }

    switch (args.top) {
    case "+":
        dy = +h;
        break;
    case "-":
        dy = -h;
        break;
    case "+/2":
        dy = +h/2;
        break;
    case "-/2":
        dy = -h/2;
        break;
    default:
        dy = +args.top;
        break;
    }

    var delta = {
        left: +dx,
        top:  +dy
    };

    if (isNaN(delta.left))
        delta.left = 0;
    if (isNaN(delta.top))
        delta.top = 0;

    var newargs = {};
    $.extend(true, newargs, args, delta);

    this.p.pan(newargs);
};

BasePlot.prototype.getTotalPointsCount = function(data){
    var d = (typeof data === "undefined" || data === null) ? this.data : data;
    if (d.length <= 0) {
        return 0;
    }
    var c = 0;
    for (var i = 0; i < d.length; i++) {
        c = c + d[i].data.length;
    }
    return c;
};

/**
 * Time series data plot class.
 * One plot for multiple series.
 * @param   placeholder
 * @param   data
 * @param   options
 */
function TimeSeriesPlot(placeholder, data, options) {
    BasePlot.apply(this, arguments);

    // fulltime global min-max as is
    this.xmin_       = null;
    this.xmax_       = null;

    // local min-max (on xrange with scroll on)
    this.rxmin       = null;
    this.rxmax       = null;
    this.rymin       = null;
    this.rymax       = null;

    // auto scroll on new data and time range
    this.scrollenabled = true;
    this.xrange        = 3600;   // Time window/range in seconds (default 1h)
    this.xrangeymode   = 'auto'; // Y scale mode in xrange (auto - autoscale window, null - autoscale global, manual - no auto scale)

    var self = this;

    // Merge defaults
    $.extend(true, this._defaults, {
            // Plot and plugins settings
            xaxis: {
                mode: 'time',

                minTickSize: [1, 'second'],

                // Plugin: time
                //timezone: null,  // "browser" for local to the client or timezone for timezone-js
                timezone: 'browser',
                //timeformat: null,  // format string to use
                timeformat: "%Y-%m-%d %H:%M:%S",
                //twelveHourClock: false,  // 12 or 24 time in time mode
                //monthNames: null,  // list of names of months

                // Plugin: navigate
                //zoomRange: [1, 10],
                //zoomRange: null,  // or [ number, number ] (min range, max range) or false
                //panRange: [-10, 10],
                //panRange: null,   // or [ number, number ] (min, max) or false
            },
            yaxis: {
                // Plugin: navigate
                //zoomRange: [1, 10],
                //zoomRange: [data[0].data[0][0], data[0].data[data.length-1][0]],
                //zoomRange: null,  // or [ number, number ] (min range, max range) or false
                //panRange: [-10, 10],
                //panRange: null,   // or [ number, number ] (min, max) or false
            },

            // Plugin: navigate
            zoom: {
                interactive: true,
                //interactive: false,
                //trigger: "dblclick", // or "click" for single click
                //amount: 1.5,         // 2 = 200% (zoom in), 0.5 = 50% (zoom out)
            },
            pan: {
                interactive: true,
                //interactive: false,
                //cursor: "move",      // CSS mouse cursor value used when dragging, e.g. "pointer"
                //frameRate: 20,
            },
            //hooks : {
            //    plotpan: [function(event, plot) {
            //    }],
            //    plotzoom: [function(event, plot) {
            //    }],
            //},

            // Custom settings
            plottooltip   : true,
            plottooltipid : 'tooltip',
            xrange        : self.xrange,
            xrangeymode   : self.xrangeymode,
            scrollenabled : self.scrollenabled,
    });

    // Merge settings
    var settings = {};  //global settings
    if (typeof options !== 'undefined') {
        $.extend(true, settings, this._defaults, options);
    } else {
        $.extend(settings, this._defaults);
    }

    // TODO: add calc getMinMaxPoints for init data and xrange

    // Init Plot
    //$(this.placeholder).empty();
    this.p = $.plot(this.placeholder, this.data, settings);

    // attach plugins hooks (no autoadd through options.hooks)
    // add unknown hooks from options
    if (typeof this.p.getOptions().hooks !== 'undefined' && !jQuery.isEmptyObject(this.p.getOptions().hooks)) {
        for (var n in this.p.getOptions().hooks) {
            if (!this.p.hooks[n] && this.p.getOptions().hooks[n].length>0) {
                for (var i = 0; i < this.p.getOptions().hooks[n].length; i++) {
                    this.p.getPlaceholder().bind(n, self.p.getOptions().hooks[n][i]);
                }
            }
        }
    }

    // Fill properties with settings
    // Time range: x axis
    this.xrange      = (settings.xrange === null ? null : (isNaN(settings.xrange) ? null : parseInt(settings.xrange)) );
    // Time range: y mode
    this.xrangeymode = (settings.xrangeymode === null || settings.xrangeymode === 'auto' || settings.xrangeymode === 'manual') ? settings.xrangeymode : null;
    // Auto scroll on new data
    this.scrollenabled = (settings.scrollenabled) ? true : false;

    // Tooltips init
    if (settings.plottooltip && settings.plottooltipid !== null) {
        // Plot tooltip container id
        var tooltipid = String(settings.plottooltipid);

        // Create tooltip container if not exists
        if ($("#"+tooltipid).length == 0) {
            $("<div id='"+tooltipid+"'></div>").css({
                position: "absolute",
                display: "none",
                border: "1px solid #fdd",
                padding: "2px",
                "background-color": "#fee",
                opacity: 0.80
            }).appendTo("body");
        }

        $(this.placeholder).bind("plothover", function (event, pos, item) {
            /*
            if (self.plottooltip_pos) {
                var str = "(" + pos.x.toFixed(2) + ", " + pos.y.toFixed(2) + ")";
                $(self.plottooltip_pos_selector).text(str);
            }
            */
            //if (self.plottooltip_show)
            {
                if (item) {
                    var x = item.datapoint[0],
                        y = item.datapoint[1].toFixed(2),
                        xdt = (new Date(x)).toISOString();

                    $("#"+tooltipid).html(item.series.label + ": " + xdt + ", " + y)
                        .css({top: item.pageY+5, left: item.pageX+5})
                        .fadeIn(200);
                } else {
                    $("#"+tooltipid).hide();
                }
            }
        });
    }
}

// Inherit
TimeSeriesPlot.prototype = Object.create(BasePlot.prototype);

// Save constructor
TimeSeriesPlot.prototype.constructor = BasePlot;

TimeSeriesPlot.prototype.setData = function(data){
    if (typeof this.p === 'undefined') {
        return;
    }
    this.data = data;

    // calculate global and last x range min-max
    var po = this.getMinMaxPoints(this.data, this.xrange);

    // update global min-max
    this.ymin  = po.pymin !== null ? po.pymin[1] : null;
    this.ymax  = po.pymax !== null ? po.pymax[1] : null;
    this.xmin  = po.pxmin !== null ? po.pxmin[0] : null;
    this.xmin_ = po.pxmin !== null ? po.pxmin[3] : null;
    this.xmax  = po.pxmax !== null ? po.pxmax[0] : null;
    this.xmax_ = po.pxmax !== null ? po.pxmax[3] : null;

    // update last x range min-max
    this.rymin = po.rpymin !== null ? po.rpymin[1] : null;
    this.rymax = po.rpymax !== null ? po.rpymax[1] : null;
    this.rxmin = po.rxmin;
    this.rxmax = po.rxmax;

    // TODO: filter out unknown series before set data?
    this.p.setData(this.data);
};

TimeSeriesPlot.prototype.appendData = function(data){
    if (typeof this.p === 'undefined') {
        return 0;
    }

    var newcnt = 0,     // added points count
        sindexes = [];  // passed series indexes list

    for (var i = 0; i < data.length; i++) {
        var idx = this._getSeriesIndexBySensor(data[i].sensor_id, data[i].sensor_val_id);
        if (idx >= 0) {
            // data series exists
            // check repeated series and skip
            if (sindexes.indexOf(idx) >= 0) {
                continue;
            }
            sindexes.push(idx);

            if (data[i].data.length > 0) {
                // nonempty data
                // add only future values, skip past and incorrect

                // get current series xmax point
                // get not null x point
                var plast = null,
                    j = this.data[idx].data.length-1;
                while (j>=0) {
                    if (this.data[idx].data[j] !== null && this.data[idx].data[j][0] !== null) {
                        plast = this.data[idx].data[j];
                    }
                    j--;
                }

                // add new points
                var pd, pass;
                for (var j = 0; j < data[i].data.length; j++) {
                    pd = data[i].data[j];
                    pass = false;
                    if (pd === null) {  // check null point
                        pass = true;
                    } else {
                        if (pd[0] === null) {  // check x null
                            pass = true;
                        } else {
                            if (plast === null || this.comparePointsX(pd,plast) > 0) {
                                plast = pd;
                                pass = true;
                            }
                        }
                    }
                    if (pass) {
                        this.data[idx].data.push($.extend(true, [], pd));
                        newcnt++;
                    }
                }
            } else {
                // no data, just info
                // no action
            }
        } else {
            // TODO: add new data series?
            /*
            // add new data series points
            var pd = null;
            for (var j = 0; j < data[i].data.length; j++) {
                newcnt++;
            }
            this.data.push($.extend(true, {}, data[i]));
            */
        }
    }

    if (newcnt > 0) {
        // update global min-max

        // TODO: use adaptive update minmax?
        // xxx: last xrange adaptive calc problem

        // use full update
        var po = this.getMinMaxPoints(this.data, this.xrange);

        this.ymin  = po.pymin !== null ? po.pymin[1] : null;
        this.ymax  = po.pymax !== null ? po.pymax[1] : null;
        this.xmin  = po.pxmin !== null ? po.pxmin[0] : null;
        this.xmin_ = po.pxmin !== null ? po.pxmin[3] : null;
        this.xmax  = po.pxmax !== null ? po.pxmax[0] : null;
        this.xmax_ = po.pxmax !== null ? po.pxmax[3] : null;

        // update last x range min-max
        this.rymin = po.rpymin !== null ? po.rpymin[1] : null;
        this.rymax = po.rpymax !== null ? po.rpymax[1] : null;
        this.rxmin = po.rxmin;
        this.rxmax = po.rxmax;

        // TODO: filter out unknown series?
        this.p.setData(this.data);
    }
    return newcnt;
};

TimeSeriesPlot.prototype._getSeriesIndexBySensor = function(sensor_id,sensor_val_id){
    if (this.data.length<=0) {
        return -1;
    }
    for (var i = 0; i < this.data.length; i++) {
        if (this.data[i].sensor_id == sensor_id && this.data[i].sensor_val_id == sensor_val_id) {
            return i;
        }
    }
    return -1;
};

TimeSeriesPlot.prototype.setRangeX = function(value) {
    var old = this.xrange;
    this.xrange = ((value !== null && value > 0) ? value : null);

    var po = this.getMinMaxPoints(this.data, this.xrange);

    // update last x range min-max
    this.rymin = po.rpymin !== null ? po.rpymin[1] : null;
    this.rymax = po.rpymax !== null ? po.rpymax[1] : null;
    this.rxmin = po.rxmin;
    this.rxmax = po.rxmax;

    return old;
};
TimeSeriesPlot.prototype.getRangeX = function(){
    return this.xrange;
};

TimeSeriesPlot.prototype.refresh = function(userange){
    if (typeof this.p === 'undefined') {
        return;
    }
    userange = ((typeof userange === 'undefined') ? false : (userange ? true : false));

    var self = this;
    $.each(this.p.getAxes(), function(_, axis) {
        var opts = axis.options;
        if (axis.direction === 'y') {
            if (userange || self.scrollenabled){
                //if (self.xrange) {
                    if (self.xrangeymode === "auto") {
                        opts.min = self.rymin;  // may be null
                        opts.max = self.rymax;  // may be null
                    } else if (self.xrangeymode === "manual") {
                        // do nothing
                    } else {
                        // set to global
                        opts.min = self.ymin;  // may be null
                        opts.max = self.ymax;  // may be null
                    }
                //}
            }
            //opts.zoomRange = [data[0].data[0][1], data[0].data[data.length-1][1]];
            //opts.panRange = [-10, 10];
        }
        if (axis.direction === 'x') {
            if (userange || self.scrollenabled){
                opts.min = self.rxmin;
                opts.max = self.rxmax;
            }
            //opts.zoomRange = [data[0].data[0][0], data[0].data[data.length-1][0]];
            //opts.panRange = [-10, 10];
        }
    });

    this.p.setupGrid();
    this.p.draw();
};


/*
 * override for int compare time x values
 * */
TimeSeriesPlot.prototype.autozoomY = function(args) {
    // Auto zoom on y axis in current range, upgraded version of plot.zoom().
    // @see jquery.flot.navigate.js plot.zoom()
    // args : {amount, center, preventEvent, axis}

    if (typeof this.p === 'undefined') {
        return false;
    }

    if (!args)
        args = {};
    // Override args
    args.amount = 1;
    args.center = null;
    args.axis   = "y";

    var minmax = {
            x: {
                min: null,
                max: null
            },
            y: {
                min: null,
                max: null
            }
        },
        pcnt = 0;

    // TODO: need truely getting minmax on multiple x or y axes, now get last
    $.each(this.p.getAxes(), function(_, axis) {
        if (axis.direction === 'x') {
            minmax[axis.direction].min = axis.min;
            minmax[axis.direction].max = axis.max;
        }
    });
    // No autozoomY if there is no x minmax valid interval
    if (minmax["x"].min === null ||  minmax["x"].max === null) {
        return false;
    }

    // Check all series points count
    $.each(this.p.getData(), function(si, series) {
        pcnt += series.data.length;
    });
    // No autozoomY if there is no points at all
    if (pcnt == 0) {
        return false;
    }

    // Found ymin-ymax on x minmax range
    $.each(this.p.getData(), function(si, series) {
        var isrange = false;
        $.each(series.data, function(pi, point) {
            if (point !== null) {
                var vx = point[0],
                    vy = parseFloat(point[1]);

                // Check if inside x range interval or not
                if (vx !== null) {
                    if (isrange) {
                        if ((vx < minmax["x"].min) || (vx > minmax["x"].max)) {
                            isrange = false;
                        }
                    } else {
                        if ((vx >= minmax["x"].min) && (vx <= minmax["x"].max)) {
                            isrange = true;
                        }
                    }
                }

                // ymin
                if (!isNaN(vy) && isrange && (minmax["y"].min === null || vy <= minmax["y"].min)) {
                    minmax["y"].min = vy;
                }
                // ymax
                if (!isNaN(vy) && isrange && (minmax["y"].max === null || vy >= minmax["y"].max)) {
                    minmax["y"].max = vy;
                }
            }
        });
    });
    // No autozoomY if no valid points on x minmax interval
    if (minmax["y"].min === null || minmax["y"].max === null) {
        return false;
    }

    if (minmax["y"].min > minmax["y"].max) {
        // make sure ymin < ymax
        var tmp = minmax["y"].min;
        minmax["y"].min = minmax["y"].max;
        minmax["y"].max = tmp;
    }

    $.each(this.p.getAxes(), function(_, axis) {
        if (axis.direction === 'y') {
            var opts = axis.options,
                min = minmax[axis.direction].min,
                max = minmax[axis.direction].max,
                zr = opts.zoomRange,
                pr = opts.panRange;

            if (zr === false) // no zooming on this axis
                return false;

            //Check that we are in panRange
            if (pr) {
                if (pr[0] != null && min < pr[0]) {
                    min = pr[0];
                }
                if (pr[1] != null && max > pr[1]) {
                    max = pr[1];
                }
            }

            // TODO: fix zoom range restrictions
            /*
            var range = max - min;
            if (zr &&
                ((zr[0] != null && range < zr[0] && amount >1) ||
                 (zr[1] != null && range > zr[1] && amount <1)))
                return false;
            */

            opts.min = min;
            opts.max = max;
        }
    });

    this.p.setupGrid();
    this.p.draw();

    if (!args.preventEvent)
        this.p.getPlaceholder().trigger("plotzoom", [ this.p, args ]);

    return true;
};

TimeSeriesPlot.prototype.getXMinPoint = function(data){
    var min = null, result = null,
        d = (typeof data === "undefined" || data === null) ? this.data : data;
    $.each(d, function(si, series) {
        // get not null x point
        var i = 0, v;
        while (i<series.data.length) {
            if (series.data[i] !== null) {
                v = series.data[i][0];
                if (v !== null) {
                    if (min === null || v < min) {
                        min = v;
                        result = series.data[i];
                    }
                    break;
                }
            }
            i++;
        }
    });
    return result;
};

TimeSeriesPlot.prototype.getXMaxPoint = function(data){
    var max = null, result = null,
        d = (typeof data === "undefined" || data === null) ? this.data : data;
    $.each(d, function(si, series) {
        // get not null x point
        var i = series.data.length-1, v;
        while (i>=0) {
            if (series.data[i] !== null) {
                v = series.data[i][0];
                if (v !== null) {
                    if (max === null || v > max) {
                        max = v;
                        result = series.data[i];
                    }
                    break;
                }
            }
            i--;
        }
    });
    return result;
};
TimeSeriesPlot.prototype.getMinMaxPoints = function(data, xlastrange){
    var d = (typeof data === "undefined" || data === null) ? this.data : data,
        result = {
            pxmin: null,
            pxmax: null,
            pymin: null,
            pymax: null,
            rxmin: null,
            rxmax: null,
            rpymin: null,
            rpymax: null,
        };

    xlastrange = (typeof xlastrange === "undefined") ? null : xlastrange;
    if (xlastrange !== null) {
        xlastrange = parseInt(xlastrange);
        if (isNaN(xlastrange)) {
            xlastrange = null;
        }
    }

    // get xmin-xmax the first step
    $.each(d, function(si, series) {
        var v, i;
        // xmin
        // get not null x point
        i = 0;
        while (i<series.data.length) {
            if (series.data[i] !== null) {
                v = series.data[i][0];
                if (v !== null) {
                    if (result.pxmin === null || v < result.pxmin[0]) {
                        result.pxmin = series.data[i];
                    }
                    break;
                }
            }
            i++;
        }
        // xmax
        // get not null x point
        i = series.data.length-1;
        while (i>=0) {
            if (series.data[i] !== null) {
                v = series.data[i][0];
                if (v !== null) {
                    if (result.pxmax === null || v > result.pxmax[0]) {
                        result.pxmax = series.data[i];
                    }
                    break;
                }
            }
            i--;
        }
    });

    if (xlastrange && result.pxmax !== null) {
        result.rxmax = result.pxmax[0];
        result.rxmin = result.rxmax - xlastrange * 1000;
    }
    // get other
    $.each(d, function(si, series) {
        // ymin-ymax, rpymin-rpymax
        var isrange = false;
        $.each(series.data, function(pi, point) {
            if (point !== null) {
                var vx = point[0],
                    vy = parseFloat(point[1]);
                if (!isNaN(vy)) {
                    // ymin
                    if (result.pymin === null || vy < result.pymin[1]) {
                        result.pymin = point;
                    }
                    // ymax
                    if (result.pymax === null || vy > result.pymax[1]) {
                        result.pymax = point;
                    }
                }
                // x last range
                if (xlastrange && (result.pxmax !== null)) {
                    // check if inside range interval or not
                    if (vx !== null) {
                        if (isrange) {
                            if ((vx < result.rxmin) || (vx > result.rxmax)) {
                                isrange = false;
                            }
                        } else {
                            if ((vx >= result.rxmin) && (vx <= result.rxmax)) {
                                isrange = true;
                            }
                        }
                    }
                    // rpymin
                    if (!isNaN(vy) && isrange && (result.rpymin === null || vy <= result.rpymin[1])) {
                        result.rpymin = point;
                    }
                    // rpymax
                    if (!isNaN(vy) && isrange && (result.rpymax === null || vy >= result.rpymax[1])) {
                        result.rpymax = point;
                    }
                }
            }
        });
    });
    // default rpymin-rpymax
    if (xlastrange === null) {
        result.rpymin = result.pymin;
    }
    if (xlastrange === null) {
        result.rpymax = result.pymax;
    }
    return result;
};

TimeSeriesPlot.prototype.comparePointsX = function(p1, p2){
    // Compare milliseconds
    if (p1[0] > p2[0]) return  1;
    if (p1[0] < p2[0]) return -1;
    // Compare nanoseconds parts
    var np1 = parseFloat('0.' + (String(p1[3]).split(".")[1] || 0)),
        np2 = parseFloat('0.' + (String(p2[3]).split(".")[1] || 0));
    if (np1 > np2) return  1;
    if (np1 < np2) return -1;
    return 0;
};


/**
 * Scatter data plot class.
 * One plot for multiple scatter series.
 * @param   placeholder
 * @param   data
 * @param   options
 */
function ScatterPlot(placeholder, data, options) {
    BasePlot.apply(this, arguments);

    var self = this;

    // Merge defaults
    $.extend(true, this._defaults, {
            // Plot and plugins settings
            series: {
                // Plugin: bubbles
                bubbles: {
                    active: true,
                    show: false,
                    fill: true,
                    lineWidth: 2,
                    //drawbubble: function(ctx, serie, x, y, v, r, c,overlay){},
                    //bubblelabel:{
                    //    show: true
                    //}
                    debug:{active:false},  // xxx: fix buggy options in standalone plugin
                    //minBubbleSize: 1,
                    //maxBubbleSize: 100,
                    //multiColors: false,
                },
                // Plugin: heatmap
                heatmap:{
                    active: true,
                    show: false,
                    //backImage: null,
                    //radiusIn: 10,
                    //radiusOut: 20,
                    max: 100,
                    //opacity: 180,
                    //gradient: { 0.45: "rgb(0,0,255)", 0.55: "rgb(0,255,255)", 0.65: "rgb(0,255,0)", 0.95: "yellow", 1.0: "rgb(255,0,0)"},
                    debug:{active:false},  // xxx: fix buggy options in standalone plugin
                    //gradient: {"0.45": "rgb(0,0,255)", "0.55": "rgb(0,255,255)", "0.65": "rgb(0,255,0)", "0.95": "yellow", "1.0": "rgb(255,0,0)"},
                    //gradient: {"0.01": "rgb(0,0,64)", "0.45": "rgb(0,0,255)", "0.55": "rgb(0,255,255)", "0.65": "rgb(0,255,0)", "0.95": "yellow", "1.0": "rgb(255,0,0)"},
                    //gradient: { 0.25: "rgb(0,0,255)", 0.55: "rgb(0,255,0)", 0.85: "yellow", 1.0: "rgb(255,0,0)"}, // heatmap.js default
                },
            },
            xaxis: {
                // Plugin: navigate
                //zoomRange: [1, 10],
                //zoomRange: null,  // or [ number, number ] (min range, max range) or false
                //panRange: [-10, 10],
                //panRange: null,   // or [ number, number ] (min, max) or false
            },
            yaxis: {
                // Plugin: navigate
                //zoomRange: [1, 10],
                //zoomRange: [data[0].data[0][0], data[0].data[data.length-1][0]],
                //zoomRange: null,  // or [ number, number ] (min range, max range) or false
                //panRange: [-10, 10],
                //panRange: null,   // or [ number, number ] (min, max) or false
            },
            points: {
                fill: true,
                fillColor: false,
            },
            lines: {
                show: false,
                fill: false,
            },

            // Plugin: navigate
            zoom: {
                interactive: true,
                //interactive: false,
                //trigger: "dblclick", // or "click" for single click
                //amount: 1.5,         // 2 = 200% (zoom in), 0.5 = 50% (zoom out)
            },
            pan: {
                interactive: true,
                //interactive: false,
                //cursor: "move",      // CSS mouse cursor value used when dragging, e.g. "pointer"
                //frameRate: 20,
            },
            //hooks : {
            //    plotpan: [function(event, plot) {
            //    }],
            //    plotzoom: [function(event, plot) {
            //    }]
            //},

            // Custom settings
            plottooltip: true,
            plottooltipid : 'tooltip',
    });

    // Merge settings
    var settings = {};  //global settings
    if (typeof options !== 'undefined') {
        $.extend(true, settings, this._defaults, options);
    } else {
        $.extend(settings, this._defaults);
    }

    // TODO: add calc getMinMaxPoints for init data

    // Init Plot
    //$(this.placeholder).empty();
    this.p = $.plot(this.placeholder, this.data, settings);

    // attach plugins hooks (no autoadd through options.hooks)
    // add unknown hooks from options
    if (typeof this.p.getOptions().hooks !== 'undefined' && !jQuery.isEmptyObject(this.p.getOptions().hooks)) {
        for (var n in this.p.getOptions().hooks) {
            if (!this.p.hooks[n] && this.p.getOptions().hooks[n].length>0) {
                for (var i = 0; i < this.p.getOptions().hooks[n].length; i++) {
                    this.p.getPlaceholder().bind(n, self.p.getOptions().hooks[n][i]);
                }
            }
        }
    }

    // Fill properties with settings

    // Tooltips init
    if (settings.plottooltip && settings.plottooltipid !== null) {
     // Plot tooltip container id
        var tooltipid = String(settings.plottooltipid);

        if ($("#"+tooltipid).length == 0) {
            $("<div id='"+tooltipid+"'></div>").css({
                position: "absolute",
                display: "none",
                border: "1px solid #fdd",
                padding: "2px",
                "background-color": "#fee",
                opacity: 0.80
            }).appendTo("body");
        }

        $(this.placeholder).bind("plothover", function (event, pos, item) {
            /*
            if (self.plottooltip_pos) {
                var str = "(" + pos.x.toFixed(2) + ", " + pos.y.toFixed(2) + ")";
                $(self.plottooltip_pos_selector).text(str);
            }
            */
            //if (self.plottooltip_show)
            {
                if (item) {
                    var x = item.datapoint[0],
                        y = item.datapoint[1].toFixed(2),
                        text = item.series.label + ": " + x + ", " + y;
                    if (item.series.data[item.dataIndex][2] && item.series.data[item.dataIndex][2]>0) {
                        text += " (" + item.series.data[item.dataIndex][2] + ")";
                    }
                    $("#"+tooltipid).html(text)
                        .css({top: item.pageY+5, left: item.pageX+5})
                        .fadeIn(200);
                } else {
                    $("#"+tooltipid).hide();
                }
            }
        });
    }
};

//Inherit
ScatterPlot.prototype = Object.create(BasePlot.prototype);

// Save constructor
ScatterPlot.prototype.constructor = BasePlot;

ScatterPlot.prototype._getSeriesIndexBySensor = function(sensor_id_x,sensor_val_id_x, sensor_id_y,sensor_val_id_y){
    if (this.data.length<=0) {
        return -1;
    }
    for (var i = 0; i < this.data.length; i++) {
        if (this.data[i].sensor_id_x == sensor_id_x && this.data[i].sensor_val_id_x == sensor_val_id_x
            && this.data[i].sensor_id_y == sensor_id_y && this.data[i].sensor_val_id_y == sensor_val_id_y) {
            return i;
        }
    }
    return -1;
};

ScatterPlot.prototype.getMinMaxPoints = function(data){
    var d = (typeof data === "undefined" || data === null) ? this.data : data,
        result = {
            pxmin: null,
            pxmax: null,
            pymin: null,
            pymax: null,
        };

    // get min-max
    $.each(d, function(si, series) {
        // xmin-xmax, ymin-ymax
        $.each(series.data, function(pi, point) {
            if (point !== null) {
                var vx = parseFloat(point[0]),
                    vy = parseFloat(point[1]);
                if (!isNaN(vx)) {
                    // xmin
                    if (result.pxmin === null || vx < result.pxmin[0]) {
                        result.pxmin = point;
                    }
                    // xmax
                    if (result.pxmax === null || vx > result.pxmax[0]) {
                        result.pxmax = point;
                    }
                }
                if (!isNaN(vy)) {
                    // ymin
                    if (result.pymin === null || vy < result.pymin[1]) {
                        result.pymin = point;
                    }
                    // ymax
                    if (result.pymax === null || vy > result.pymax[1]) {
                        result.pymax = point;
                    }
                }
            }
        });
    });

    return result;
};

function exportPlot(plot,ftype) {
    if (ftype !== 'pdf' && ftype !== 'jpg' && ftype !== 'png') return false;

    var oldbg = plot.getPlaceholder().get(0).style.backgroundColor;  // save bg
    plot.getPlaceholder().get(0).style.backgroundColor = "white";  // change bg

    html2canvas(plot.getPlaceholder().get(0), {
        onrendered: function(canvas) {
            plot.getPlaceholder().get(0).style.backgroundColor = oldbg;  // restore bg

            var filename = 'plot'+formatDate(new Date(), 'yyyyMMddHHmmss');
            switch (ftype) {
            case "pdf":
                var mimeType = "image/png",
                    imgData = canvas.toDataURL(mimeType),
                    width = canvas.width,
                    height = canvas.height,
                    k = height/width,
                    nw = 180, nh = nw*k;
                var doc = new jsPDF('landscape', 'mm', 'a4');
                doc.addImage(imgData, 'PNG', 10, 10, nw+10, nh+10);
                doc.save(filename+'.pdf');
                break;

            case "jpg":
                var mimeType = "image/jpeg",
                    // toDataURL defaults to png, so we need to request a jpeg, then convert for file download.
                    imgData = canvas.toDataURL(mimeType).replace(mimeType, "image/octet-stream");

                //window.open(imgData);
                return downloadData(imgData, filename + ".jpg", mimeType);
                break;

            case "png":
            default:
                var mimeType = "image/png",
                    imgData = canvas.toDataURL(mimeType);

                //window.open(imgData);
                return downloadData(imgData, filename + ".png", mimeType);
                break;
            }
        }
    });
}
