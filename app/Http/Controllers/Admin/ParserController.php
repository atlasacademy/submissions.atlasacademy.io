<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\TempFile;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Http\Request;
use Laravel\Lumen\Http\ResponseFactory;
use Submission\DropRepository;
use Submission\DropTemplateRepository;
use Submission\EventNodeDropRepository;
use Submission\EventNodeRepository;
use Submission\Parser\ParserAdapter;
use Symfony\Component\HttpKernel\Exception\HttpException;
use ZipArchive;

class ParserController extends Controller
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
     * @var EventNodeRepository
     */
    private $eventNodeRepository;
    /**
     * @var EventNodeDropRepository
     */
    private $eventNodeDropRepository;
    /**
     * @var ParserAdapter
     */
    private $parserAdapter;
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
                                EventNodeRepository $eventNodeRepository,
                                EventNodeDropRepository $eventNodeDropRepository,
                                ParserAdapter $parserAdapter,
                                Request $request,
                                ResponseFactory $responseFactory)
    {
        $this->dispatcher = $dispatcher;
        $this->dropRepository = $dropRepository;
        $this->dropTemplateRepository = $dropTemplateRepository;
        $this->eventNodeRepository = $eventNodeRepository;
        $this->eventNodeDropRepository = $eventNodeDropRepository;
        $this->parserAdapter = $parserAdapter;
        $this->request = $request;
        $this->responseFactory = $responseFactory;
    }

    public function downloadNodeSettings()
    {
        $eventUid = $this->request->input("event_uid", "");
        $eventNodeUid = $this->request->input("event_node_uid", "");
        $eventNode = $this->eventNodeRepository->getNode($eventUid, $eventNodeUid);
        if (!$eventNode) {
            throw new HttpException(422, "Unrecognized event node.");
        }

        $settings = $this->parserAdapter->generateSettings($eventUid, $eventNodeUid);
        $templates = $this->parserAdapter->generateTemplates($eventUid, $eventNodeUid);

        $path = TempFile::make();
        $zip = new ZipArchive;
        $zip->open($path, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
        $zip->addFromString("settings.json", json_encode($settings));

        foreach ($templates as $templateFilename => $templateBody) {
            $zip->addFromString("files/{$templateFilename}", base64_decode($templateBody));
        }

        $zip->close();

        return response()->download($path, "settings.zip");
    }

    public function updateDropTemplate()
    {
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
