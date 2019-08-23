<?php namespace App\Http\Controllers;

use App\Jobs\SyncActiveEventsJob;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Laravel\Lumen\Http\ResponseFactory;
use Submission\DropRepository;
use Submission\DropTemplateRepository;
use Symfony\Component\HttpKernel\Exception\HttpException;

class AdminController extends Controller
{

    /**
     * @var Dispatcher
     */
    private $dispatcher;
    /**
     * @var DropRepository
     */
    private $dropRepository;
    /**
     * @var DropTemplateRepository
     */
    private $dropTemplateRepository;
    /**
     * @var Request
     */
    private $request;
    /**
     * @var ResponseFactory
     */
    private $responseFactory;

    public function __construct(Dispatcher $dispatcher,
                                DropRepository $dropRepository,
                                DropTemplateRepository $dropTemplateRepository,
                                Request $request,
                                ResponseFactory $responseFactory)
    {
        $this->dispatcher = $dispatcher;
        $this->dropRepository = $dropRepository;
        $this->dropTemplateRepository = $dropTemplateRepository;
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

    public function updateDropTemplate()
    {
        $key = $this->request->input("key");
        if ($key !== env("ADMIN_KEY")) {
            throw new HttpException(401, "Unauthorized.");
        }

        $dropUid = $this->request->input("drop_uid", "");
        $drop = $this->dropRepository->getDrop($dropUid);
        if (!$drop) {
            throw new HttpException(422, "Invalid drop uid.");
        }

        $quantity = intval($this->request->input("quantity", 0)) ?: null;
        $bonus = intval($this->request->input("bonus", 0)) ?: null;

        $template = $this->request->file("template");
        if (!$template) {
            throw new HttpException(422, "Missing template.");
        } else if (is_array($template)) {
            throw new HttpException(422, "Too many templates.");
        } else if ($template->getMimeType() !== "image/png" || $template->getClientOriginalExtension() !== "png") {
            throw new HttpException(422, "Invalid template file.");
        }

        $templateImage = base64_encode($template->get());

        $this->dropTemplateRepository->update($dropUid, $quantity, $bonus, $templateImage);

//        $this->dispatcher->dispatch(new UpdateParserTemplatesContainingDrop($dropUid));

        return $this->responseFactory->json([
            "status" => "Success",
            "message" => "Updated drop template. Updating parser."
        ]);
    }

}
