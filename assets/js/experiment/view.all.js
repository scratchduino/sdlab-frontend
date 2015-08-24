$(document).ready(function(){
    // Rescan sensors
	$('sensors-list .alert').alert();
    $('#sensors-rescan').click(function(){
        coreAPICall('Sensors.getSensors', {rescan: true, getinfo: true}, showRescanResults);
    });

    var delbtns=$('.experiment-delete-btn');
    if (delbtns.length) {
        delbtns.click(function(){
            var rq = coreAPICall('Experiment.isActive', {experiment: $(this).data('experiment')}, function(data, st, xhr){
                if (typeof data.error == 'undefined'){
                    if (data.result){
                        var msg;
                        if (data.items.length <= 1){
                            msg = 'В эксперименте в данный момент активна установка, всё равно удалить?';
                        } else {
                            msg = 'В эксперименте в данный момент активно несколько установок ('+data.items.length+'), всё равно удалить?';
                        }

                        if (confirm(msg) && typeof xhr.activator !== 'undefined'){
                            experimentDelete($(xhr.activator).data('experiment'), 1);
                        }
                    } else {
                        experimentDelete($(xhr.activator).data('experiment'), 0);
                    }
                } else {
                    alert('Ошибка');
                }
            });
            rq.activator = this;
        });
    }
})

function showRescanResults(data) {
    if(typeof data.error == 'undefined'){
        $('.sensors-list').empty();
        var i, c, sensor, info, vals = '';
        for (id in data){
            $('.sensors-list').append('\
                <div class="alert alert-info alert-dismissible" role="alert">\
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>\
                </div>');
            c = $('.sensors-list .alert').first();
            break;
        }
        for (id in data){
            sensor = data[id];
            sensor.id = id;
            info = (typeof sensor.sensor_name !== 'undefined') ? true : false;
            vals = '';
            if(info){
                for(i=0;i<sensor.Values.length;i++){
                    vals += '<br/><span>' + sensor.Values[i].value_name + ' (' + sensor.Values[i].si_name + ')' + ' [' + sensor.Values[i].Range.Min + '&nbsp;:&nbsp;' + sensor.Values[i].Range.Max + ']' + '<span>';
                }
            }
            $(c).append('\
                <div>\
                    <strong>' + sensor.id + '</strong>\
                        ' + vals +'\
                <div>'
            );
        }
    } else {
        alert('Ошибка');
    }
}

function experimentDelete(experiment, force) {
    var form = $('#sdform');
    if(form.length == 0) return;

    form.attr("action","/?q=experiment/delete/" + experiment);
    form.get(0)['force'].value = force;
    form.submit();
}