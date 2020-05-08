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
 * Citybusiness Controller
 *
 * @package Controllers
 * @subpackage API
 */
class Citybusiness extends API_V1_Controller {
    /**
     * Citybusiness Resource Parser
     *
     * @var \EA\Engine\Api\V1\Parsers\Citybusiness
     */
    protected $parser;

    /**
     * Class Constructor
     */
    public function __construct()
    {
        parent::__construct();
        $this->load->model('citybusiness_model');
        $this->parser = new \EA\Engine\Api\V1\Parsers\Citybusiness;
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
            $citybusiness = $this->citybusiness_model->get_batch($condition);

            if ($id !== NULL && count($citybusiness) === 0)
            {
                $this->_throwRecordNotFound();
            }

            $response = new Response($citybusiness);

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
            // Insert the citybusiness to the database.
            $request = new Request();
            $citybusiness = $request->getBody();
            $this->parser->decode($citybusiness);

            if (isset($citybusiness['id']))
            {
                unset($citybusiness['id']);
            }

            $id = $this->citybusiness_model->add($citybusiness);

            // Fetch the new object from the database and return it to the client.
            $batch = $this->citybusiness_model->get_batch('id = ' . $id);
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
            // Update the citybusiness record.
            $batch = $this->citybusiness_model->get_batch('id = ' . $id);

            if ($id !== NULL && count($batch) === 0)
            {
                $this->_throwRecordNotFound();
            }

            $request = new Request();
            $updatedSecretary = $request->getBody();
            $baseSecretary = $batch[0];
            $this->parser->decode($updatedSecretary, $baseSecretary);
            $updatedSecretary['id'] = $id;
            $id = $this->citybusiness_model->add($updatedSecretary);

            // Fetch the updated object from the database and return it to the client.
            $batch = $this->citybusiness_model->get_batch('id = ' . $id);
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
            $result = $this->citybusiness_model->delete($id);

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
