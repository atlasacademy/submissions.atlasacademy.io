<?php namespace App\Http\Controllers;

use App\Jobs\ExportSubmissionJob;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Laravel\Lumen\Http\ResponseFactory;
use Submission\EventNodeDropRepository;
use Submission\EventNodeRepository;
use Submission\EventRepository;
use Submission\SubmissionRepository;
use Symfony\Component\HttpKernel\Exception\HttpException;

class SubmitRunController extends Controller
{

    /**
     * @var Dispatcher
     */
    private $dispatcher;
    /**
     * @var EventRepository
     */
    private $eventRepository;
    /**
     * @var EventNodeRepository
     */
    private $eventNodeRepository;
    /**
     * @var EventNodeDropRepository
     */
    private $eventNodeDropRepository;
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

    public function __construct(Dispatcher $dispatcher,
                                EventRepository $eventRepository,
                                EventNodeRepository $eventNodeRepository,
                                EventNodeDropRepository $eventNodeDropRepository,
                                Request $request,
                                ResponseFactory $responseFactory,
                                SubmissionRepository $submissionRepository)
    {
        $this->dispatcher = $dispatcher;
        $this->eventRepository = $eventRepository;
        $this->eventNodeRepository = $eventNodeRepository;
        $this->eventNodeDropRepository = $eventNodeDropRepository;
        $this->request = $request;
        $this->responseFactory = $responseFactory;
        $this->submissionRepository = $submissionRepository;
    }

    public function post()
    {
        // Verify event is valid and submittable
        $event_uid = $this->request->get("event_uid");
        $event = $this->eventRepository->getEvent($event_uid);
        if (!$event || !$event["active"] || !$event["submittable"]) {
            throw new HttpException(422, "Invalid event uid.");
        }

        // Verify node is valid
        $event_node_uid = $this->request->get("event_node_uid");
        $node = $this->eventNodeRepository->getNode($event_uid, $event_node_uid);
        if (!$node || !$node["active"]) {
            throw new HttpException(422, "Invalid event node uid.");
        }

        // Verify submitter name isn't too long
        $submitter = $this->request->get("submitter");
        if ($submitter !== null && strlen($submitter) > 255) {
            throw new HttpException(422, "Submitter name is too long. Maximum 255 characters.");
        }

        // Ensure drops are passed as an array
        $drops = $this->request->get("drops");
        if (!$drops || !is_array($drops)) {
            throw new HttpException(422, "Drops field is required and must be an array.");
        }

        // Map out expected drop_quantity combinations
        $nodeDrops = $this->eventNodeDropRepository->getDrops($event_uid, $event_node_uid);
        $nodeDropUidQuantity = array_map(function ($nodeDrop) {
            return $nodeDrop["uid"] . "_" . $nodeDrop["quantity"];
        }, $nodeDrops);

        $cleanDrops = [];
        foreach ($drops as $k => $drop) {
            // Extract drop data
            $uid = strtoupper(Arr::get($drop, "uid"));
            $quantity = Arr::get($drop, "quantity");
            $countRaw = Arr::get($drop, "count");
            $count = intval($countRaw);
            $ignored = Arr::get($drop, "ignored");
            $uidQuantity = $uid . "_" . $quantity;

            if ($countRaw !== null && $countRaw !== strval($count)) { // Validate count is an integer if not null
                throw new HttpException(422, "Invalid count on field drops[{$k}][count].");
            } else if (!is_int($count) && !$ignored) { // Validate either ignored or count is passed
                throw new HttpException(422, "Either count or ignored must be provided on field drops[{$k}].");
            } else if ($count < 0 && !$ignored) { // Validate count isn't negative
                throw new HttpException(422, "Invalid count on field drops[{$k}][count].");
            } else if (!in_array($uidQuantity, $nodeDropUidQuantity)) { // Validate the same drop + quantity wasn't passed twice
                throw new HttpException(422, "Duplicate uid and quantity on field drops[{$k}].");
            }

            // Remove drop + quantity combination. This is used to check the same drop + quantity wasn't passed twice
            $nodeDropUidQuantity = array_filter($nodeDropUidQuantity, function ($value) use ($uidQuantity) {
                return $value !== $uidQuantity;
            });

            // After drop is valid, add only relevant keys
            $cleanDrops[] = Arr::only($drop, ["uid", "quantity", "count", "ignored"]);
        }

        // Check if user missed any drops that were expected. Report back to user
        $containsAllExpectedDrops = count($nodeDropUidQuantity) === 0;

        // Generate receipt
        $receipt = $this->submissionRepository->create(
            $event_uid,
            $event_node_uid,
            $cleanDrops,
            $submitter
        );

        // Queue export job
        $this->dispatcher->dispatch(new ExportSubmissionJob($receipt));

        // Generate response
        return $this->responseFactory->json([
            "status" => "Success",
            "receipt" => $receipt,
            "missing_drops" => !$containsAllExpectedDrops
        ]);
    }

}
