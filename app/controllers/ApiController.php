<?

class ApiController extends Controller
{
	function __construct()
	{
		$method_query = explode('.', isset($_GET['method']) ? $_GET['method'] : '');

		// Check values
		$method_query[0] = isset($method_query[0]) ? System::clean($method_query[0], 'class')  : '';
		$method_query[1] = isset($method_query[1]) ? System::clean($method_query[1], 'method') : '';

		$api_method_class = $method_query[0].'Controller';

		$this->controller = new $api_method_class;
		$this->method = $method_query[1];
		$this->params = isset($_GET['params']) ? $_GET['params'] : array();
	}

	function api()
	{
		if(method_exists($this->controller, $this->method))
		{
			// Inject App in called controller
			// xxx: cannot do that in constructor of new sub controller, because it creates when no binded $this->app in the current api controller!
			$this->controller->app = $this->app;


			// Call method
			$result = $this->controller->{$this->method}($this->params);

			if($result)
			{
				$this->json_result = json_encode($result);
			}
			else
			{
				/*todo: errno & errstring */
				$api_error = array(
					'error' => $this->controller->error()
				);
				$this->json_error = json_encode($api_error);
			}
		}
		else
		{
			$api_error = array(
				'error' => 'Method not exist.'
			);
			$this->json_error = json_encode($api_error);
		}

	}

	/**
	 * Override default render method
	 */
	function renderView()
	{
		/* execute */
		$this->api();
		header('Content-Type: application/json');
		if(!isset($this->json_error) && isset($this->json_result))
		{
			print $this->json_result;
		}
		else
		{
			print $this->json_error;
		}
	}
}