<?php defined('BASEPATH') OR exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * Easy!Appointments - Open Source Web Scheduler
 *
 * @package     EasyAppointments
 * @author      A.Tselegidis <alextselegidis@gmail.com>
 * @copyright   Copyright (c) 2013 - 2018, Alex Tselegidis
 * @license     http://opensource.org/licenses/GPL-3.0 - GPLv3
 * @link        http://easyappointments.org
 * @since       v1.2.0
 * ---------------------------------------------------------------------------- */

require_once __DIR__ . '/API_V1_Controller.php';

use \EA\Engine\Api\V1\Response;
use \EA\Engine\Api\V1\Request;
use \EA\Engine\Types\NonEmptyText;

/**
 * Cityadmin Controller
 *
 * @package Controllers
 * @subpackage API
 */
class Cityadmin extends API_V1_Controller {
    /**
     * Cityadmin Resource Parser
     *
     * @var \EA\Engine\Api\V1\Parsers\Cityadmin
     */
    protected $parser;

    /**
     * Class Constructor
     */
    public function __construct()
    {
        parent::__construct();
        $this->load->model('cityadmin_model');
        $this->parser = new \EA\Engine\Api\V1\Parsers\Cityadmin;
    }

    /**
     * GET API Method
     *
     * @param int $id Optional (null), the record ID to be returned.
     */
    public function get($id = NULL)
    {
        try
        {
            $condition = $id !== NULL ? 'id = ' . $id : NULL;
            $cityadmin = $this->cityadmin_model->get_batch($condition);

            if ($id !== NULL && count($cityadmin) === 0)
            {
                $this->_throwRecordNotFound();
            }

            $response = new Response($cityadmin);

            $response->encode($this->parser)
                ->search()
                ->sort()
                ->paginate()
                ->minimize()
                ->singleEntry($id)
                ->output();

        }
        catch (\Exception $exception)
        {
            $this->_handleException($exception);
        }
    }

    /**
     * POST API Method
     */
    public function post()
    {
        try
        {
            // Insert the cityadmin to the database.
            $request = new Request();
            $cityadmin = $request->getBody();
            $this->parser->decode($cityadmin);

            if (isset($cityadmin['id']))
            {
                unset($cityadmin['id']);
            }

            $id = $this->cityadmin_model->add($cityadmin);

            // Fetch the new object from the database and return it to the client.
            $batch = $this->cityadmin_model->get_batch('id = ' . $id);
            $response = new Response($batch);
            $status = new NonEmptyText('201 Created');
            $response->encode($this->parser)->singleEntry(TRUE)->output($status);
        }
        catch (\Exception $exception)
        {
            $this->_handleException($exception);
        }
    }

    /**
     * PUT API Method
     *
     * @param int $id The record ID to be updated.
     */
    public function put($id)
    {
        try
        {
            // Update the cityadmin record.
            $batch = $this->cityadmin_model->get_batch('id = ' . $id);

            if ($id !== NULL && count($batch) === 0)
            {
                $this->_throwRecordNotFound();
            }

            $request = new Request();
            $updatedSecretary = $request->getBody();
            $baseSecretary = $batch[0];
            $this->parser->decode($updatedSecretary, $baseSecretary);
            $updatedSecretary['id'] = $id;
            $id = $this->cityadmin_model->add($updatedSecretary);

            // Fetch the updated object from the database and return it to the client.
            $batch = $this->cityadmin_model->get_batch('id = ' . $id);
            $response = new Response($batch);
            $response->encode($this->parser)->singleEntry($id)->output();
        }
        catch (\Exception $exception)
        {
            $this->_handleException($exception);
        }
    }

    /**
     * DELETE API Method
     *
     * @param int $id The record ID to be deleted.
     */
    public function delete($id)
    {
        try
        {
            $result = $this->cityadmin_model->delete($id);

            $response = new Response([
                'code' => 200,
                'message' => 'Record was deleted successfully!'
            ]);

            $response->output();
        }
        catch (\Exception $exception)
        {
            $this->_handleException($exception);
        }
    }
}
