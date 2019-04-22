<?php namespace App\Http\Controllers;

use App\Jobs\SyncActiveEventsJob;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Http\Request;
use Laravel\Lumen\Http\ResponseFactory;
use Symfony\Component\HttpKernel\Exception\HttpException;

class AdminController extends Controller
{

    /**
     * @var Dispatcher
     */
    private $dispatcher;
    /**
     * @var Request
     */
    private $request;
    /**
     * @var ResponseFactory
     */
    private $responseFactory;

    public function __construct(Dispatcher $dispatcher,
                                Request $request,
                                ResponseFactory $responseFactory)
    {
        $this->dispatcher = $dispatcher;
        $this->request = $request;
        $this->responseFactory = $responseFactory;
    }

    public function syncEvents()
    {
        $key = $this->request->get("key");
        if ($key !== env("ADMIN_KEY")) {
            throw new HttpException(401, "Unauthorized.");
        }

        $this->dispatcher->dispatch(new SyncActiveEventsJob());

        return $this->responseFactory->json([
            "status" => "Success",
            "message" => "Updating events."
        ]);
    }

}