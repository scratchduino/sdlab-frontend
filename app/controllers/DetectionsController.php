<?


/**
 * Class DetectionsController
 */
class DetectionsController extends Controller
{
	function __construct()
	{
		parent::__construct();
		$this->config = App::config();
	}


	/**
	 * Get detections data for plot.
	 * API method: Detections.getGraphData
	 * API params: plot
	 *
	 * @param  array  $params  Array of parameters:
	 *                           plot  - id of plot
	 *
	 * @return array  Result in form array('result' => array of objects) or False on error
	 */
	function getGraphData($params)
	{
		if($plot = (new Plot())->load($params['plot']))
		{
			$experiment = (new Experiment())->load($plot->exp_id);
			$setup = (new Setup())->load($experiment->setup_id);

			$db = new DB();

			$detections_query = $db->prepare('select * from detections where exp_id = :experiment_id and sensor_id = :sensor_id and sensor_val_id = :sensor_val_id');

			$ordinate_query = $db->query('select ordinate.*, setup_conf.name, setup_conf.sensor_id, setup_conf.sensor_val_id from ordinate left join setup_conf on setup_conf.setup_id = '.(int)$setup->id.' AND setup_conf.sensor_id = ordinate.id_sensor_y AND setup_conf.sensor_val_id = ordinate.sensor_val_id_y where id_plot = '.(int)$plot->id.' ', PDO::FETCH_OBJ);
			//System::dump($ordinate_query);
			if($ordinate_query)
			{
				$result = array();
				foreach($ordinate_query as $item)
				{
					//System::dump($item);
					$sensor_select = $detections_query->execute(array(
						':experiment_id' => $experiment->id,
						':sensor_id' => $item->sensor_id,
						':sensor_val_id' => $item->sensor_val_id
					));
					if($sensor_select)
					{
						$sensor_data = $detections_query->fetchAll(PDO::FETCH_OBJ);
						$graph_object = new StdClass();
						$graph_object->label = $item->name;
						$graph_object->color = ++$i;
						foreach($sensor_data as $point)
						{
							$time = explode('.', $point->time);
							$time = new DateTime($time[0]);
							$graph_object->data[] = array(
								$time->getTimestamp()*1000,
								$point->detection
							);
						}
						$result[] = $graph_object;
					}
				}
				return $result ? $result : false;
			}
			return false;
		}
		else
		{
			$this->error = L::ERROR_GRAPH_NOT_EXIST;
		}
	}


	/**
	 * Get experiment detections for timeseries graph.
	 * API method: Detections.getGraphDataAll
	 * API params: experiment, show-sensor[]
	 * 
	 * @param  array  $params  Array of parameters:
	 *                           experiment  - id of experiment,
	 *                           show-sensor - list of sensors identificators strings in format "sensor_id + # + value_id"
	 * 
	 * @return array  Result in form array('result' => array of objects) or False on error
	 */
	function getGraphDataAll($params)
	{
		if(!empty($params['experiment']))
		{
			$experiment = (new Experiment())->load($params['experiment']);
			if(!empty($experiment->setup_id))
			{
				// Check access to experiment
				if(!($experiment->session_key == $this->session()->getKey() || $this->session()->getUserLevel() == 3))
				{
					$this->error = L::ACCESS_DENIED;

					return false;
				}

				$sensors_show = array();
				if(isset($params['show-sensor']) && !empty($params['show-sensor']) && is_array($params['show-sensor']))
				{
					foreach($params['show-sensor'] as $sensor_show_id)
					{
						$sensors_show[$sensor_show_id] = $sensor_show_id;
					}
				}

				$db = new DB();

				// Get unique sensors list from detections data of experiment
				$query = 'select a.sensor_id, a.sensor_val_id, '
							. 's.value_name, s.si_notation, s.si_name, s.max_range, s.min_range, s.resolution '
						. 'from detections as a '
						. 'left join sensors as s on a.sensor_id = s.sensor_id and a.sensor_val_id = s.sensor_val_id '
						. 'where a.exp_id = :exp_id '
						. 'group by a.sensor_id, a.sensor_val_id order by a.sensor_id';
				$load = $db->prepare($query);
				$load->execute(array(
						':exp_id' => $experiment->id
				));
				$sensors = $load->fetchAll(PDO::FETCH_OBJ);
				if(empty($sensors))
				{
					$sensors = array();
				}

				$available_sensors = $displayed_sensors = array();

				// Prepare available_sensors list
				foreach($sensors as $sensor)
				{
					$key = '' . $sensor->sensor_id . '#' . (int)$sensor->sensor_val_id;
					if(!array_key_exists($key, $available_sensors))
					{
						$available_sensors[$key] = $sensor;
					}
				}
				//$result['available_sensors'] = $available_sensors;

				// Filter requested sensors
				if(!empty($sensors_show))
				{
					$displayed_sensors = array_intersect_key($available_sensors, $sensors_show);
				}
				else
				{
					$displayed_sensors = $available_sensors;
				}

				// XXX: return time in msec (>PHP_INT_MAX)
				$query = //'select strftime(\'%Y.%m.%d %H:%M:%f\', time) as time, detection '
						 'select (strftime(\'%s\',time) - strftime(\'%S\',time) + strftime(\'%f\',time))*1000 as time, detection '
						. 'from detections '
						. 'where exp_id = :exp_id and sensor_id = :sensor_id and sensor_val_id = :sensor_val_id and (error isnull or error = \'\')'
						. 'order by strftime(\'%s\', time)';
				$load = $db->prepare($query);

				$result = array();
				$i = 0;
				foreach($displayed_sensors as $sensor)
				{
					$data = new stdClass();
					// TODO: add to label name of sensor from setup_info (but unknown setup id for each detection, setup can be changed)
					$data->label         = empty($sensor->value_name) ? L::sensor_UNKNOWN : constant('L::sensor_VALUE_NAME_' . strtoupper($sensor->value_name));
					$data->sensor_id     = $sensor->sensor_id;
					$data->sensor_val_id = $sensor->sensor_val_id;
					$data->color         = ++$i;

					$res = $load->execute(array(
							':exp_id'        => $experiment->id,
							':sensor_id'     => $sensor->sensor_id,
							':sensor_val_id' => $sensor->sensor_val_id
					));
					$detections = $load->fetchAll(PDO::FETCH_NUM);

					if(!empty($detections))
					{
						$data->data = $detections;
						foreach ($data->data as $k => $val)
						{
							$t = (string)$val[0];
							$dotpos = strpos($t,'.');
							if ($dotpos !== false )
							{
								// cut fractional part with dot from time in msec (14235464000.0 -> 14235464000)
								$data->data[$k][0] = substr($t, 0, $dotpos);
							}
						}
					}
					else 
					{
						$data->data = array();
					}

					$result[] = $data;
/*
					foreach($sensor_data as $point)
					{
						$time = explode('.', $point->time);
						$time = new DateTime($time[0]);
						$graph_object->data[] = array(
								$time->getTimestamp()*1000,
								$point->detection
						);
					}
*/
				}
				return array('result' => $result);
			}
			else
			{
				$this->error = L::ERROR_EXPERIMENT_NOT_FOUND;

				return false;
			}
		}
		else
		{
			$this->error = L::ERROR_EXPERIMENT_NOT_FOUND;
		
			return false;
		}

		return false;
	}


	/**
	 * Delete data from detections.
	 * API method: Detections.delete
	 * API params: id[]
	 *
	 * @param  array $params  Array of parameters
	 *
	 * @return array  Result in form array('result' => True) or False on error
	 */
	function delete($params)
	{
		if(!empty($params['id']))
		{
			$ids = array();
			if(is_array($params['id']))
			{
				foreach($params['id'] as $val)
				{
					if ((int)$val == 0) continue;
					$ids[(int)$val] = null;
				}
			}
			else if (is_numeric($params['id']))
			{
				if ((int)$params['id'] != 0)
				{
					$ids[(int)$val] = null;
				}
			}
			else
			{
				$this->error = L::ERROR_INVALID_PARAMETERS;

				return false;
			}

			if (empty($ids))
			{
				$this->error = L::ERROR_INVALID_PARAMETERS;

				return false;
			}

			$cnt_ids = count($ids);

			$db = new DB();

			// Check access to experiment
			if ($this->session()->getUserLevel() != 3)
			{
				// Get experiments with sessions identificators for detections ids
				$current_session = $this->session()->getKey();

				$exp_det_query = $db->prepare(
						'select d.id, d.exp_id, e.session_key from detections as d '
						. 'left join experiments as e on e.id = d.exp_id ' 
						. 'where e.exp_id notnull and d.id in (' . implode(',', array_fill(0, $cnt_ids, '?')) . ')'
				);
				$stmt = $db->prepare($exp_det_query);
				try
				{
					$res = $stmt->execute(array_keys($ids));
					if (!$res)
					{
						error_log('PDOError: '.var_export($stmt->errorInfo(),true));  //DEBUG

						$this->error = L::FATAL_ERROR;
						return false;
					}
				}
				catch (PDOException $e)
				{
					error_log('PDOException delete(): '.var_export($e->getMessage(),true));  //DEBUG

					$this->error = L::FATAL_ERROR;
					return false;
				}
				$ids_exps = (array) $stmt->fetchAll(PDO::FETCH_ASSOC);

				$denied = 0;
				foreach ($ids_exps as $k => $val)
				{
					if ($val['session_key'] != $current_session)
					{
						unset($ids_exps[$k]);
						$denied++;
					}
				}

				// Set only available date
				foreach ($ids_exps as $val)
				{
					$ids[(int)$val['id']] = (int)$val['exp_id'];
				}
				$ids = array_filter($ids);

				// Denied access to some detections data
				if ($denied > 0)
				{
					$this->error = L::ACCESS_DENIED;
					//return false;  // But try delete only available
				}
			}

			if (empty($ids))
			{
				// Use only first error
				if (empty($this->error))
				{
					$this->error = L::ERROR_DETECTIONS_NOT_FOUND;
				}
				return false;
			}

			// Update experiment stop
			$sql_delete_query = 'delete from detections where id in (' . implode(',', array_fill(0, count($ids), '?')) . ')';
			$stmt = $db->prepare($sql_delete_query);
			try
			{
				$res = $stmt->execute(array_keys($ids));
				if (!$res)
				{
					error_log('PDOError: '.var_export($stmt->errorInfo(),true));  //DEBUG

					$this->error = L::FATAL_ERROR;
					return false;
				}
			}
			catch (PDOException $e)
			{
				error_log('PDOException delete(): '.var_export($e->getMessage(),true));  //DEBUG

				$this->error = L::FATAL_ERROR;
				return false;
			}

			return array('result' => true);
		}
		else
		{
			$this->error = L::ERROR_DETECTIONS_NOT_FOUND;

			return false;
		}

		return false;
	}


	/**
	 * Delete data from detections of experiment by datetime.
	 * API method: Detections.deletebytime
	 * API params: dt[], exp_id
	 *
	 * @param  array $params  Array of parameters
	 *
	 * @return array  Result in form array('result' => True) or False on error
	 */
	function deletebytime($params)
	{
		if(!empty($params['dt']) && !empty($params['exp_id']))
		{
			$dts = array();

			$experiment = (new Experiment())->load((int)$params['exp_id']);

			// Check experiment
			if (!$experiment || empty($experiment->id))
			{
				$this->error = L::ERROR_EXPERIMENT_NOT_FOUND;
				return false;
			}

			// Check access
			if ($experiment->session_key == $this->session()->getKey() || $this->session()->getUserLevel() == 3)
			{
				// Get datetimes
				if(is_array($params['dt']))
				{
					foreach($params['dt'] as $val)
					{
						$dt = DateTime::createFromFormat('Y.m.d?H:i:s.u+', $val);
						$err = DateTime::getLastErrors();
						if ($dt === false || $err['error_count'] > 0) continue;
						/*
						try {
							$dt = DateTime::createFromFormat('Y.m.d?H:i:s.u+', $val);
						} catch (Exception $e) {
							// echo $e->getMessage();
						}
						*/

						// Cut from 6 to 3 msec digits
						$dts[substr($dt->format('Y-m-d\TH:i:s.u'), 0, -3)] = null;  // only 3 digits used for msec in sqlite
					}
				}
				else if (is_string($params['dt']))
				{
					$dt = DateTime::createFromFormat('Y.m.d?H:i:s.u+', $params['dt']);
					$err = DateTime::getLastErrors();
					if ($dt === false || $err['error_count'] > 0) continue;
					/*
					try{
						$dt = DateTime::createFromFormat('Y.m.d?H:i:s.u+', $params['dt']);
					} catch (Exception $e) {
						// echo $e->getMessage();
					}
					*/

					// Cut from 6 to 3 msec digits
					$dts[substr($dt->format('Y-m-d\TH:i:s.u'), 0, -3)] = null;  // only 3 digits used for msec in sqlite
				}
				else
				{
					$this->error = L::ERROR_INVALID_PARAMETERS;
					return false;
				}

				if (empty($dts))
				{
					$this->error = L::ERROR_INVALID_PARAMETERS;
					return false;
				}

				$db = new DB();

				// Delete detections
				$sql_delete_query = 'delete from detections where exp_id = ' . (int)$experiment->id . ' and strftime(\'%Y-%m-%dT%H:%M:%f\', time) in (' . implode(',', array_fill(0, count($dts), '?')) . ')';
				$stmt = $db->prepare($sql_delete_query);
				try
				{
					$res = $stmt->execute(array_keys($dts));
					if (!$res)
					{
						error_log('PDOError: '.var_export($stmt->errorInfo(),true));  //DEBUG

						$this->error = L::FATAL_ERROR;
						return false;
					}
				}
				catch (PDOException $e)
				{
					error_log('PDOException delete(): '.var_export($e->getMessage(),true));  //DEBUG

					$this->error = L::FATAL_ERROR;
					return false;
				}
			}
			else
			{
				$this->error = L::ACCESS_DENIED;
				return false;
			}

			return array('result' => true);
		}
		else
		{
			$this->error = L::ERROR_INVALID_PARAMETERS;

			return false;
		}

		return false;
	}


	function error()
	{
		return $this->error;
	}
}
