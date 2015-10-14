<?php
/**
 */

class ExperimentController extends Controller
{

	function __construct($action = 'create')
	{
		parent::__construct($action);

		/* используем id из строки experiment/edit/%id */
		$this->id = App::router(2);
		$this->config = App::config();
	}

	function index()
	{
		System::go('experiment/create');
	}

	/**
	 * Action: Create
	 * Create experiment
	 */
	function create()
	{
		self::setTitle(L::experiment_TITLE_CREATION);
		self::setContentTitle(L::experiment_TITLE_CREATION);

		$this->view->form = new Form('create-experiment-form');
		$this->view->form->submit->value = L::experiment_CREATE_EXPERIMENT;

		/* Установки как список опций для формы*/
		$this->view->form->setups = SetupController::loadSetups();


		if(isset($_POST) && isset($_POST['form-id']) && $_POST['form-id'] === 'create-experiment-form')
		{
			/* fill the Experiment properties */
			$experiment = new Experiment($this->session()->getKey());
			$experiment->set('title', htmlspecialchars(isset($_POST['experiment_title']) ? $_POST['experiment_title'] : ''));
			$setup_id = (isset($_POST['setup_id']) ? (int)$_POST['setup_id'] : '');
			$experiment->set('setup_id', $setup_id);
			$experiment->set('comments', htmlspecialchars(isset($_POST['experiment_comments']) ? $_POST['experiment_comments'] : ''));

			//$experiment->set('DateStart_exp', (new DateTime($_POST['experiment_date_start']))->format(DateTime::ISO8601));
			//$experiment->set('DateEnd_exp', (new DateTime($_POST['experiment_date_end']))->format(DateTime::ISO8601));


			if(empty($experiment->title))
			{
				// Error: Not save
				return;
			}

			// Check setup available
			if($setup_id)
			{
				$found = false;
				foreach ($this->view->form->setups as $s)
				{
					if ($s->id == $setup_id)
					{
						$found = true;
						break;
					}
				}
				if (!$found)
				{
					// Reset setup, not found
					$setup_id = '';
					$experiment->set('setup_id', $setup_id);
				}
			}

			/* Access Experiment in view*/
			$this->view->form->experiment = $experiment;

			if($experiment->save() && !is_null($experiment->id))
			{
				// Set master of setup if set setup with no master
				if($setup_id)
				{
					$setup = (new Setup())->load($setup_id);
					if ($setup && !is_null($setup->id) && empty($setup->master_exp_id))
					{
						$setup->set('master_exp_id', $experiment->id);
						$result = $setup->save();
						if (!$result)
						{
							// Error update setup master
							// Ignore
						}
					}
				}

				System::go('experiment/view/'.$experiment->id);
			}
		}
		else
		{
			$this->view->form->experiment = new Experiment();
		}
	}

	/**
	 * Action: View
	 * View single experiment or all
	 */
	function view()
	{
		if(!is_null($this->id) && is_numeric($this->id))
		{
			self::addJs('functions');
			self::addJs('class/Sensor');
			self::addJs('experiment/view');
			// Add language translates for scripts
			Language::script(array(
					'GRAPH', 'INFO',  // class/Sensor
					'RUNNING_', 'STROBE', 'ERROR_NOT_COMPLETED', 'experiment_ERROR_CONFIGURATION_ORPHANED'  // experiment/view
			));

			$experiment = (new Experiment())->load($this->id);
			if($experiment->session_key == $this->session()->getKey() || $this->session()->getUserLevel() == 3)
			{
				$this->view->content->experiment = $experiment;
				if($experiment->setup_id)
				{
					$this->view->content->setup = (new Setup())->load($experiment->setup_id);
					$this->view->content->sensors = SetupController::getSensors($experiment->setup_id, true);
					$monitors = (new Monitor())->loadItems(
							array(
									'exp_id' => (int)$experiment->id,
									'setup_id' => (int)$experiment->setup_id,
									'deleted' => 0
							),
							'id', 'DESC', 1
					);
					$this->view->content->monitor = (!empty($monitors)) ? $monitors[0] : null;

					// Get last monitoring info from api
					if (!empty($this->view->content->monitor))
					{
						// Prepare parameters for api method
						$query_params = array($this->view->content->monitor->uuid);

						// Send request for get monitor info
						$socket = new JSONSocket($this->config['socket']['path']);
						$result = $socket->call('Lab.GetMonInfo', $query_params);

						// Get results
						if($result)
						{
							//Prepare results
							$nd = System::nulldate();

							if(isset($result->Created) && ($result->Created === $nd))
							{
								$result->Created = null;
							}

							if(isset($result->StopAt) && ($result->StopAt === $nd))
							{
								$result->StopAt = null;
							}

							if(isset($result->Last) && ($result->Last === $nd))
							{
								$result->Last = null;
							}

							$this->view->content->monitor->info = $result;
						}
						else
						{
							// TODO: error get monitor data from backend api, may by need show error
						}
					}
				}

				if($this->session()->getUserLevel() == 3)
				{
					$this->view->content->session = (new Session())->load($experiment->session_key);
				}
				else
				{
					$this->view->content->session = $this->session();
				}
				self::setTitle($experiment->title);
			}
			else
			{
				System::go('experiment/view');
			}
		}
		else
		{
			// All experiments
			$this->view->content->list = self::experimentsList();
			self::setViewTemplate('view.all');
			self::setTitle(L::experiment_TITLE_ALL);

			self::addJs('functions');
			self::addJs('experiment/view.all');
			// Add language translates for scripts
			Language::script(array(
					'journal_QUESTION_REMOVE_EXPERIMENT_WITH_1', 'journal_QUESTION_REMOVE_EXPERIMENT_WITH_JS_N', 'ERROR'  // experiment/view.all
			));

			//View all available experiments in this session
		}
	}


	/** Action: Edit
	 * Edit experiment
	 */
	function edit()
	{
		if(!empty($this->id) && is_numeric($this->id))
		{
			$experiment = (new Experiment())->load($this->id);
			if($experiment->session_key == $this->session()->getKey() || $this->session()->getUserLevel() == 3)
			{
				self::setViewTemplate('create');
				self::setTitle(L::TITLE_EDIT_OF($experiment->title));
				self::setContentTitle(L::TITLE_EDIT_OF_2($experiment->title));

				/*Объект формы*/
				$this->view->form = new Form('edit-experiment-form');
				$this->view->form->submit->value = L::SAVE;
				$this->view->form->experiment = $experiment;

				/* Установки как список опций для формы*/
				$this->view->form->setups = SetupController::loadSetups();

				if(isset($_POST) && isset($_POST['form-id']) && $_POST['form-id'] === 'edit-experiment-form')
				{
					$experiment->set('title', htmlspecialchars(isset($_POST['experiment_title']) ? $_POST['experiment_title'] : ''));
					$setup_id = (isset($_POST['setup_id']) ? (int)$_POST['setup_id'] : '');
					$experiment->set('setup_id', $setup_id);
					$experiment->set('comments', htmlspecialchars(isset($_POST['experiment_comments']) ? $_POST['experiment_comments'] : ''));

					if(empty($experiment->title))
					{
						// Error: Not save
						return;
					}

					// Check setup available
					if($setup_id)
					{
						$found = false;
						foreach ($this->view->form->setups as $s)
						{
							if ($s->id == $setup_id)
							{
								$found = true;
								break;
							}
						}
						if (!$found)
						{
							// Reset setup, not found

							// XXX: No reset old orphaned setups

							//$setup_id = '';
							//$experiment->set('setup_id', $setup_id);
						}
					}

					if($experiment->save() && !is_null($experiment->id))
					{
						// Set master of setup if set setup with no master
						if($setup_id && $found)
						{
							$setup = (new Setup())->load($setup_id);
							if ($setup && !is_null($setup->id) && empty($setup->master_exp_id))
							{
								$setup->set('master_exp_id', $experiment->id);
								$result = $setup->save();
								if (!$result)
								{
									// Error update setup master
									// Ignore
								}
							}
						}

						System::go('experiment/view/'.$experiment->id);
					}
				}
			}
			else
			{
				System::go('experiment/view');
			}

		}
		else
		{
			System::go('experiment/create');
		}
	}

	/**
	 * Action: Delete
	 * Deleting experiment.
	 * Deletes all data related to experiment.
	 */
	function delete()
	{
		if (!empty($this->id) && is_numeric($this->id))
		{
			$experiment = (new Experiment())->load($this->id);

			// Only admin can delete experiment
			if ($experiment && $experiment->id /*&& $experiment->session_key == $this->session()->getKey()*/ && $this->session()->getUserLevel() == 3)
			{
				$db = new DB();

				// Check active experiment
				$query = $db->prepare('select id from setups where master_exp_id = :master_exp_id and flag > 0');
				$query->execute(array(
						':master_exp_id' => $this->id
				));
				$setups = (array) $query->fetchAll(PDO::FETCH_COLUMN, 0);
				$cnt_active = count($setups);

				// Force delete experiment if has active setups
				$force = (isset($_POST) && isset($_POST['force']) && is_numeric($_POST['force'])) ? (int) $_POST['force'] : 0;
				if ($cnt_active && !$force)
				{
					// Error: experiment with active setups
					System::go('experiment/view');
					return;
				}


				// Speed db operations within transaction
				$db->beginTransaction();

				try
				{
					// Unactivate setup and unset master experiment
					$sql_setups_update_query = 'update setups set flag = NULL, master_exp_id = NULL where master_exp_id = :master_exp_id';
					$update = $db->prepare($sql_setups_update_query);
					$result = $update->execute(array(':master_exp_id' => $this->id));
					if (!$result)
					{
						error_log('PDOError: '.var_export($update->errorInfo(),true));  //DEBUG
					}

					// Remove rows from tables
					// Be careful of delete order, because used foreign keys between tables (if enabled)

					// Need stop monitors before
					// Get all monitors of experiment
					$query = $db->prepare('select uuid from monitors where exp_id = :exp_id');
					$query->execute(array(
							':exp_id' => $this->id
					));
					$monitors = (array) $query->fetchAll(PDO::FETCH_COLUMN, 0);
					// Remove monitors call for backend sensors API (auto stop)
					$delmons = array();
					$errmons = array();
					foreach($monitors as $uuid)
					{
						// Send request for removing monitor
						$query_params = array((string) $uuid);
						$socket = new JSONSocket($this->config['socket']['path']);
						$result = $socket->call('Lab.RemoveMonitor', $query_params);
						if ($result)
						{
							$delmons[] = $uuid;
						}
						else
						{
							$errmons[] = $uuid;
						}
						unset($socket);
					}
					if (!empty($errmons))
					{
						error_log('Error remove monitors: {'. implode('},{', $errmons) . '}');  //DEBUG
					}

					// Remove monitors from DB
					$delete = $db->prepare('delete from monitors where exp_id=:exp_id');
					$result = $delete->execute(array(':exp_id' => $this->id));
					if (!$result)
					{
						error_log('PDOError: '.var_export($delete->errorInfo(),true));  //DEBUG
					}

					// Remove consumers
					// XXX: consumers table not used now
					$delete = $db->prepare('delete from consumers where exp_id=:exp_id');
					$result = $delete->execute(array(':exp_id' => $this->id));
					if (!$result)
					{
						error_log('PDOError: '.var_export($delete->errorInfo(),true));  //DEBUG
					}

					// Remove ordinate
					$delete = $db->prepare('delete from ordinate where id_plot IN (select id from plots where exp_id=:exp_id)');
					$result = $delete->execute(array(':exp_id' => $this->id));
					if (!$result)
					{
						error_log('PDOError: '.var_export($delete->errorInfo(),true));  //DEBUG
					}

					// Remove plots
					$delete = $db->prepare('delete from plots where exp_id=:exp_id');
					$result = $delete->execute(array(':exp_id' => $this->id));
					if (!$result)
					{
						error_log('PDOError: '.var_export($delete->errorInfo(),true));  //DEBUG
					}

					// Remove detections
					$delete = $db->prepare('delete from detections where exp_id=:exp_id');
					$result = $delete->execute(array(':exp_id' => $this->id));
					if (!$result)
					{
						error_log('PDOError: '.var_export($delete->errorInfo(),true));  //DEBUG
					}

					// Remove experiments
					$delete = $db->prepare('delete from experiments where id=:id');
					$result = $delete->execute(array(':id' => $this->id));
					if (!$result)
					{
						error_log('PDOError: '.var_export($delete->errorInfo(),true));  //DEBUG
					}
				}
				catch (PDOException $e)
				{
					error_log('PDOException Experiment::delete(): '.var_export($e->getMessage(),true));  //DEBUG
					var_dump($e->getMessage());
				}

				$db->commit();

				// TODO: Show info about errors while delete or about success (need session saved msgs)

				System::go('experiment/view');
			}
			else
			{
				// Error: experiment not found or no access
				System::go('experiment/view');
			}
		}
		else
		{
			// Error: incorrect experiment id
			System::go('experiment/view');
		}
	}

	/**
	 * Action: Journal
	 * View experiment journal.
	 */
	function journal()
	{
		if(!empty($this->id) && is_numeric($this->id))
		{
			$experiment = (new Experiment())->load($this->id);
			if($experiment->session_key == $this->session()->getKey() || $this->session()->getUserLevel() == 3)
			{

				self::setTitle(L::journal_TITLE_JOURNAL_OF($experiment->title));
				self::setContentTitle(L::journal_TITLE_JOURNAL_OF_2($experiment->title));
				self::addJs('functions');
				self::addJs('experiment/journal');
				// Add language translates for scripts
				Language::script(array(
						'journal_QUESTION_CLEAN_JOURNAL', 'ERROR'  // experiment/journal
				));

				/*Объект формы*/
				$this->view->form = new Form('experiment-journal-form');
				$this->view->form->submit->value = L::REFRESH;
				$this->view->form->experiment = $experiment;


				if(isset($_POST) && isset($_POST['form-id']) && $_POST['form-id'] === 'experiment-journal-form')
				{
					if(isset($_POST['show-sensor']) && !empty($_POST['show-sensor']) && is_array($_POST['show-sensor']))
					{
						foreach($_POST['show-sensor'] as $sensor_show_id)
						{
							$sensors_show[$sensor_show_id] = $sensor_show_id;
						}
					}
				}

				/* Возможно стоит вынести все в отдельный контроллер или модель*/
				$db = new DB();

				$query = 'select id, exp_id, strftime(\'%Y.%m.%d %H:%M:%f\', time) as time, sensor_id, sensor_val_id, detection, error from detections where exp_id = '.(int)$experiment->id . ' order by strftime(\'%s\', time)';
				$detections = $db->query($query, PDO::FETCH_OBJ);

				/* Формирование вывода на основе датчиков в установке. */
				$sensors = SetupController::getSensors($experiment->setup_id, true);
				$available_sensors = $displayed_sensors = array();

				/*Формируем список доступных датчиков*/
				foreach($sensors as $sensor)
				{
					$key = '' . $sensor->id . '#' . (int)$sensor->sensor_val_id;
					if(!array_key_exists($key, $available_sensors))
					{
						$available_sensors[$key] = $sensor;
					}
				}
				$this->view->content->available_sensors = $available_sensors;

				/*Если из формы пришел список то формируем список отображаемых датчиков*/
				if(!empty($sensors_show))
				{
					$this->view->content->displayed_sensors = array_intersect_key($available_sensors, $sensors_show);
				}
				else
				{
					$this->view->content->displayed_sensors = $available_sensors;
				}

				/* сам массив значений сгруппированых по временной метке. */
				$journal = array();
				foreach($detections as $row)
				{
					/*если есть в списке доступных датчиков, то добавим в вывод журнала*/
					$key = '' . $row->sensor_id . '#' . (int)$row->sensor_val_id;
					if(array_key_exists($key, $this->view->content->displayed_sensors))
					{
						$journal[$row->time][$key] = $row;
					}
				}
				$this->view->content->detections = &$journal;

			}
			else
			{
				System::go('experiment/view');
			}

		}
		else
		{
			System::go('experiment/create');
		}
	}

	function graph()
	{
		if (empty($this->id))
		{
			System::go('experiment/view');
		}

		$this->view->content->experiment = $experiment = (new Experiment())->load($this->id);

		if (is_numeric(App::router(3)))
		{
			// View/Edit graph

			self::setViewTemplate('graphsingle');
			self::setTitle(L::graph_TITLE_GRAPH_FOR($experiment->title));
			self::addJs('lib/jquery.flot');
			self::addJs('lib/jquery.flot.time.min');
			self::addJs('lib/jquery.flot.navigate');
			self::addJs('functions');
			self::addJs('chart');
			// Add language translates for scripts
			Language::script(array(
					'sensor_VALUE_NAME_TEMPERATURE'  // chart
			));

			$plot_id = (int)App::router(3);
			if (empty($plot_id))
			{
				System::go('experiment/graph');
				return;
			}

			// Get graph
			$plot = (new Plot())->load($plot_id);
			if (empty($plot))
			{
				// Error: graph not found
				System::go('experiment/graph');
				return;
			}

			$edit = App::router(4);
			if ($edit === 'edit')
			{
				// Edit graph

				$this->view->form = new Form('plot-edit-form');
				$this->view->form->submit->value = L::graph_SAVE;

				if(isset($_POST['form-id']) && $_POST['form-id'] === 'plot-edit-form')
				{
					// Save graph form
				
					// ...
				}


			}
			else
			{
				// View graph

				// ...
			}

			$this->view->content->plot = $plot;
		}
		elseif (App::router(3) == 'add')
		{
			// Add new graph

			self::setViewTemplate('graphsingle');
			self::setTitle(L::graph_TITLE_ADD_GRAPH_FOR($experiment->title));
			self::setContentTitle(L::graph_TITLE_ADD_GRAPH_FOR_2($experiment->title));
		}
		else
		{
			// List graphs

			self::setTitle(L::graph_TITLE_GRAPHS_FOR($experiment->title));
			//self::setContentTitle(L::graph_TITLE_GRAPHS_FOR_2($experiment->title));
			self::addJs('lib/jquery.flot');
			self::addJs('lib/jquery.flot.time.min');
			self::addJs('lib/jquery.flot.navigate');
			self::addJs('functions');
			self::addJs('chart');
			// Add language translates for scripts
			Language::script(array(
					'sensor_VALUE_NAME_TEMPERATURE'  // chart
			));

			$db = new DB();
			$query = 'select * from plots where exp_id = '.(int)$experiment->id;
			$plots = $db->query($query, PDO::FETCH_OBJ);

			$this->view->content->list = $plots;


			// Get available in detections sensors list

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

			$available_sensors = array();

			// Prepare available_sensors list
			foreach($sensors as $sensor)
			{
				$key = '' . $sensor->sensor_id . '#' . (int)$sensor->sensor_val_id;
				if(!array_key_exists($key, $available_sensors))
				{
					$available_sensors[$key] = $sensor;
				}
			}

			$this->view->content->available_sensors = &$available_sensors;

			// Add graph of all sensors on ajax script
			$this->view->content->detections = array();
		}
	}


	/**
	 * Action: Clean
	 * Clean detections journal.
	 * Deletes all detections data related to experiment.
	 */
	function clean()
	{
		if (!empty($this->id) && is_numeric($this->id))
		{
			$experiment = (new Experiment())->load($this->id);

			// Check access to experiment
			if ($experiment && $experiment->id && ($experiment->session_key == $this->session()->getKey() || $this->session()->getUserLevel() == 3))
			{
				if(isset($_POST) && isset($_POST['form-id']) && $_POST['form-id'] === 'experiment-journal-form')
				{
					$db = new DB();

					// Remove detections
					$delete = $db->prepare('delete from detections where exp_id=:exp_id');
					$result = $delete->execute(array(':exp_id' => $this->id));
					if (!$result)
					{
						error_log('PDOError: '.var_export($delete->errorInfo(),true));  //DEBUG
					}

					if(isset($_GET['destination']) && $_GET['destination'] != $_GET['q'])
					{
						System::go(System::clean($_GET['destination'], 'path'));
					}
					else
					{
						System::go('experiment/journal/'.$this->id);
					}
				}
				else 
				{
					// TODO: check return back link from $_GET['destination'] or $_POST['destination']

					System::go('experiment/journal/'.$this->id);
				}

				// TODO: Show info about errors while clean or about success (need session saved msgs)

				return;
			}
			else
			{
				// Error: experiment not found or no access
				System::go('experiment/view');
			}
		}
		else
		{
			// Error: incorrect experiment id
			System::go('experiment/view');
		}
	}


	/**
	 * Check if some Setup is active in experiment.
	 * Return boolean value and ids of active setups.
	 * 
	 * API method: Experiment.isActive
	 * API params: experiment
	 *
	 * @param  array $params  Array of parameters
	 *
	 * @return array  Result in form array('result' => bool, 'items' => array()) or False on error
	 */
	function isActive($params)
	{
		// Check id 
		if(!isset($params['experiment']) && empty($params['experiment']))
		{
			$this->error = L::ERROR_EXPERIMENT_NOT_FOUND;
			return false;
		}

		// Load experiment
		$experiment = (new Experiment())->load((int)$params['experiment']);
		if(!$experiment || !($experiment->id))
		{
			$this->error = L::ERROR_EXPERIMENT_NOT_FOUND;
			return false;
		}

		// Check access to experiment
		if(!($experiment->session_key == $this->session()->getKey() || $this->session()->getUserLevel() == 3))
		{
			$this->error = L::ACCESS_DENIED;
			return false;
		}

		$db = new DB();

		// Check active setups in experiment
		// TODO: use sql Count for query or return array of active
		$query = $db->prepare('select id from setups where master_exp_id = :master_exp_id and flag > 0');
		$res = $query->execute(array(
				':master_exp_id' => $experiment->id
		));
		if (!$res)
		{
			error_log('PDOError: '.var_export($query->errorInfo(),true));  //DEBUG

			$this->error = L::ERROR;
			return false;
		}

		$setups = (array) $query->fetchAll(PDO::FETCH_COLUMN, 0);

		return array(
				'result' => ((count($setups) > 0) ? true : false),
				'items'  => $setups
		);
	}

	/**
	 * Get list of experiments.
	 * Returns only own experiments or all for admin
	 * 
	 * @return array
	 */
	protected function experimentsList()
	{
		if($this->session()->getUserLevel() == 3)
		{
			$list = self::loadExperiments();
		}
		else
		{
			$list = self::loadExperiments($this->session()->getKey());
		}

		foreach($list as $key => $item)
		{
			$list[$key] = (new Experiment())->load($item->id);
		}
		return $list;
	}

	/**
	 * @param null $session_key
	 * @return array
	 */
	static function loadExperiments($session_key = null)
	{
		$db = new DB();

		if (is_numeric($session_key) && strlen($session_key) == 6)
		{
			$search = $db->prepare('select id from experiments where session_key = :session_key');
			$search->execute(array(
				':session_key' => $session_key
			));
		}
		else if($session_key == null)
		{
			$search = $db->prepare('select id from experiments');
			$search->execute();
		}

		return $search->fetchAll(PDO::FETCH_OBJ);
	}
}
