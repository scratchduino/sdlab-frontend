<?php 
// TODO: refactor for use as Simple plot with Save Plot form. Save x & y ranges, zoom, x & y labels and custom plot title.
?>
<script>
	var data = [
		{
			label: "Temperature-test",
			color: 1,
			data: [
				[(new Date("2014-04-05 17:00:04.1234").getTime()), 20],
				[(new Date("2014-04-05 17:00:05.2342").getTime()), 19.7],
				[(new Date('2014-04-05 17:00:06.411').getTime()), 20.1],
				[(new Date('2014-04-05 17:00:06.52345').getTime()), 20.7],
				[(new Date('2014-04-05 17:00:07.7868').getTime()), 19.9],
				[(new Date('2014-04-05 17:00:09.8785').getTime()), 20.3],
				[(new Date('2014-04-05 17:03:10.46346').getTime()), 21.5]
			]
		},
		{
			label: "Pressure-test",
			color: 2,
			data: [
				[(new Date("2014-04-05 17:00:01.1234").getTime()), 16],
				[(new Date("2014-04-05 17:00:03.2342").getTime()), 4.7],
				[(new Date('2014-04-05 17:00:04.411').getTime()), 21.1],
				[(new Date('2014-04-05 17:00:04.52345').getTime()), 13.7],
				[(new Date('2014-04-05 17:00:05.7868').getTime()), 16.9],
				[(new Date('2014-04-05 17:00:08.8785').getTime()), 14.3],
				[(new Date('2014-04-05 17:03:09.46346').getTime()), 17.5]
			]
		}];

	function dataReceived(data){
		var g = new BasePlot(data);
		var options = {
			xaxis: {
				//zoomRange: [data[0].data[0][0], data[0].data[data.length-1][0]],
				show: true,
				mode: 'time',
				timeformat: "%Y/%m/%d %H:%m:%S",
				minTickSize: [1, 'second'],
				timezone: 'browser'
			},
			yaxis: {
				min: g.getMinValue()-1,
				max: g.getMaxValue()+3
			}
		};

		buildGraph(data, $('#graph_workspace'), options)
	}

	$(document).ready(function(){

		coreAPICall('Detections.getGraphSingleData', {
			plot: 1
		}, dataReceived)

	})
</script>


<div class="col-md-12">
	<a href="/?q=experiment/graph/<?php echo (int)$this->view->content->experiment->id; ?>" class="btn btn-sm btn-default">
		<span class="glyphicon glyphicon-chevron-left"></span> <?php echo L('graph_TITLE_ALL_GRAPHS_FOR_2',array(htmlspecialchars($this->view->content->experiment->title, ENT_QUOTES, 'UTF-8'))); ?>
	</a>
</div>
<br><br>
<div class="col-md-12">
	<div class="col-md-10 well">
		<form class="form-inline">
			<input type="hidden" name="form-id" value="<?php echo htmlspecialchars($this->view->form->id, ENT_QUOTES, 'UTF-8'); ?>"/>
			<div class="form-group col-md-6">
				<label for="x_axis" class="col-md-12">
					<?php echo L('graph_ABSCISSA'); ?>:
				</label>
				<div class="col-md-12">
					<?php echo L('graph_EXPRESSION'); ?>: <input id="x_axis" class="form-control" type="text" value="t" disabled="disabled"/>
				</div>
			</div>
			<div class="form-group col-md-6">
				<label for="y_axis" class="col-md-12">
					<?php echo L('graph_ORDINATE'); ?>:
				</label>
				<div class="col-md-12">
					<div class="ordinate-item">
						<select name="ordinate-sensor-id[]" class="form-control">
							<option><?php echo L('sensor_SELECT_OPTION'); ?></option>
						</select>
						<?php echo L('graph_EXPRESSION'); ?>: <input id="ordinate_scale" class="form-control" type="text" value="" placeholder="<?php echo L('graph_EXPRESSION'); ?>"/>
					</div>
				</div>
				<div class="col-md-12">
					<br/>
					<a href="#" class="btn btn-default"><?php echo L('sensor_ADD'); ?></a>
				</div>
			</div>
		</form>
	</div>
	<div class="col-md-2">
		<input class="btn btn-default" value="<?php echo htmlspecialchars($this->view->form->submit->value, ENT_QUOTES, 'UTF-8'); ?>"/>
	</div>
</div>

<div class="col-md-12">
	<div class="col-md-12" id="graph_workspace" style="height: 400px; padding-left: 15px;">
		&nbsp;
	</div>
</div>



<div class="col-md-12">
	<?php System::dump($this->view->content->plot); ?>
</div>
