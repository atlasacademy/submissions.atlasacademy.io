<?php namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Laravel\Lumen\Http\ResponseFactory;
use Submission\EventNodeRepository;
use Submission\SubmissionRepository;

class EventSubmissionsController extends Controller
{

    /**
     * @var EventNodeRepository
     */
    private $eventNodeRepository;
    /**
     * @var Request
     */
    private $request;
    /**
     * @var ResponseFactory
     */
    private $responseFactory;
    /**
     * @var SubmissionRepository
     */
    private $submissionRepository;

    public function __construct(EventNodeRepository $eventNodeRepository,
                                Request $request,
                                ResponseFactory $responseFactory,
                                SubmissionRepository $submissionRepository)
    {
        $this->eventNodeRepository = $eventNodeRepository;
        $this->request = $request;
        $this->responseFactory = $responseFactory;
        $this->submissionRepository = $submissionRepository;
    }

    public function get(string $event_uid, string $event_node_uid)
    {
        $eventNode = $this->eventNodeRepository->getNode($event_uid, $event_node_uid);
        if (!$eventNode)
            abort(404);

        $afterReceipt = $this->request->get("after_receipt", null);
        $limit = 50;

        $submissions = $this->submissionRepository->getSubmissions(
            $event_uid,
            $event_node_uid,
            $limit,
            $afterReceipt
        );

        $submissions = array_map(function ($submission) {
            $submission["drops"] = json_decode($submission["drops"]);

            return $submission;
        }, $submissions);

        return $this->responseFactory->json($submissions);
    }

}