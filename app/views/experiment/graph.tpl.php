<script>
	var plot;
	var g;
	var experiment = <?php echo (int)$this->view->content->experiment->id; ?>;

	$(document).ready(function(){
		var choiceContainer = $(".available-sensors");

		$('#graph-refesh').click(function(){
			var list = $('input', choiceContainer);
			var clist = list.filter(':checked');
			var params = {'experiment': experiment};
			if(list.length>0){
				if(clist.length>0){
					params['show-sensor'] = []
					$.each(clist, function(){
						params['show-sensor'].push($(this).val());
					});
				}
				var rq = coreAPICall('Detections.getGraphDataAll', params, dataRecived);
				rq.always(function(d,textStatus,err) {
					if (typeof params['show-sensor'] === 'undefined' || params['show-sensor'].length == 0){
						$('input', choiceContainer).prop('checked', true);
					}
				});
			}else{
				// error : no sensors in list
				coreAPICall('Detections.getGraphDataAll', params, dataRecived);
			}
		});

		/*
		$('input', choiceContainer).click(function(){
			$('#graph-refesh').trigger('click');
		});
		*/

		coreAPICall('Detections.getGraphDataAll', {'experiment' : experiment}, dataRecived);
	});

	function dataRecived(data){
		if(typeof data.error === 'undefined'){
			if (typeof g === 'undefined'){
				g = new Graph(data.result);
			}else{
				g.data = data.result;
			}

			var options = {
				xaxis: {
					//zoomRange: [data[0].data[0][0], data[0].data[data.length-1][0]],
					show: true,
					mode: 'time',
					timeformat: "%Y-%m-%d %H:%M:%S",
					minTickSize: [1, 'second'],
					timezone: null
				},
				yaxis: {
					min: g.getMinValue()-1,
					max: g.getMaxValue()+3
				},
				grid: {
					hoverable: true,
					clickable: true
				},
				plottooltip: true
			};
			if (typeof plot !== 'undefined'){
				plot.shutdown();
			}
			plot = buildGraph(data.result, $('#graph-all'), options);
		}
		else
		{
			$('#graph-all').empty();

			setInterfaceError($('#graph-msgs'), 'API error: ' + data.error, 3000);
		}
	}
</script>
<div class="row">
	<div class="col-md-12">
		<a href="/?q=experiment/view/<?php echo (int)$this->view->content->experiment->id; ?>" class="btn btn-sm btn-default">
			<span class="glyphicon glyphicon-chevron-left"></span> <?php echo htmlspecialchars($this->view->content->experiment->title, ENT_QUOTES, 'UTF-8'); ?>
		</a>
	</div>
	<div class="col-md-12">
		<h3><?php echo L::graph_TITLE_GRAPHS_FOR_2(htmlspecialchars($this->view->content->experiment->title, ENT_QUOTES, 'UTF-8')); ?></h3>
	</div>
</div>
<div class="row" style="display:none;">
	<div class="col-md-8">
		<table class="table table-bordered">
			<thead>
				<tr>
					<td><label>#</label></td>
					<td><label><?php echo L::graph_NAME; ?></label></td>
				</tr>
			</thead>
			<tbody>
				<?php foreach($this->view->content->list as $plot):?>
					<tr>
						<td>
							<?php echo ++$i; ?>
						</td>
						<td>
							<a href="?q=experiment/graph/<?php echo (int)$plot->exp_id;?>/<?php echo (int)$plot->id;?>"><?php echo L::GRAPH . ' #' . (int)$plot->id;?></a>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
			<?php if ($this->view->content->list->rowCount() == 0) : ?>
			<tfoot>
				<tr>
					<td colspan="2">
						<div class="alert alert-info">
						<?php echo L::graph_MSG_NO_SAVED_GRAPHS; ?>
						</div>
					</td>
				</tr>
			</tfoot>
			<?php endif; ?>
		</table>
	</div>
	<div class="col-md-4">
		<a href="?q=experiment/graph/<?php echo (int)$this->view->content->experiment->id;?>/add" class="btn btn-primary"><?php echo L::graph_ADD; ?></a>
	</div>
</div>
<hr style="display:none;" />
<div class="row">
	<div class="col-md-12">
		<h3><?php echo L::graph_TITLE_ALL_DETECTIONS_BY_TIME; ?></h3>
	</div>

	<div class="col-md-9">
		<div id="graph-msgs">
		</div>
		<div id="graph-all" style="height: 400px; padding-left: 15px;">
		</div>
	</div>
	<div class="col-md-3">
		<h4><?php echo L::SENSORS; ?></h4>

		<?php if (empty($this->view->content->available_sensors)) : ?>
		<div><?php echo L::graph_NO_SENSORS; ?></div>
		<?php endif; ?>
		<ul class="nav available-sensors">
			<?php foreach ($this->view->content->available_sensors as $sensor) :?>
				<li>
					<label class="chechbox"><input type="checkbox" <?php /* if (array_key_exists($sensor->sensor_id, $this->view->content->displayed_sensors)) echo 'checked';*/?> checked name="show-sensor[]" value="<?php 
						echo htmlspecialchars($sensor->sensor_id . '#' . (int)$sensor->sensor_val_id, ENT_QUOTES, 'UTF-8'); ?>"/>&nbsp;<?php 
						echo htmlspecialchars(constant('L::sensor_VALUE_NAME_' . strtoupper($sensor->value_name)), ENT_QUOTES, 'UTF-8') . ','
							. htmlspecialchars(constant('L::sensor_VALUE_SI_NOTATION_' . strtoupper($sensor->value_name) . '_' . strtoupper($sensor->si_notation)), ENT_QUOTES, 'UTF-8')
							. ' ('  . htmlspecialchars($sensor->sensor_id. '#' . (int)$sensor->sensor_val_id, ENT_QUOTES, 'UTF-8') . ')';
						?></label>
				</li>
			<?php endforeach; ?>
		</ul>
		<button type="button" id="graph-refesh" class="btn btn-primary"><?php echo L::REFRESH; ?></button>
	</div>
</div>
