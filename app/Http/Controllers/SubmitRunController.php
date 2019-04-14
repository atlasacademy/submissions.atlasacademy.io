<?php namespace App\Http\Controllers;

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

    public function __construct(EventRepository $eventRepository,
                                EventNodeRepository $eventNodeRepository,
                                EventNodeDropRepository $eventNodeDropRepository,
                                Request $request,
                                ResponseFactory $responseFactory,
                                SubmissionRepository $submissionRepository)
    {
        $this->eventRepository = $eventRepository;
        $this->eventNodeRepository = $eventNodeRepository;
        $this->eventNodeDropRepository = $eventNodeDropRepository;
        $this->request = $request;
        $this->responseFactory = $responseFactory;
        $this->submissionRepository = $submissionRepository;
    }

    public function post()
    {
        $event_uid = $this->request->get("event_uid");
        $event = $this->eventRepository->getEvent($event_uid);
        if (!$event || !$event["active"] || !$event["submittable"]) {
            throw new HttpException(422, "Invalid event uid.");
        }

        $event_node_uid = $this->request->get("event_node_uid");
        $node = $this->eventNodeRepository->getNode($event_uid, $event_node_uid);
        if (!$node || !$node["active"]) {
            throw new HttpException(422, "Invalid event node uid.");
        }

        $submitter = $this->request->get("submitter");
        if ($submitter !== null && strlen($submitter) > 255) {
            throw new HttpException(422, "Submitter name is too long. Maximum 255 characters.");
        }

        $cleanDrops = [];
        $drops = $this->request->get("drops");
        if (!$drops || !is_array($drops)) {
            throw new HttpException(422, "Drops field is required and must be an array.");
        }

        $nodeDrops = $this->eventNodeDropRepository->getDrops($event_uid, $event_node_uid);
        $nodeDropUidQuantity = array_map(function ($nodeDrop) {
            return $nodeDrop["uid"] . "_" . $nodeDrop["quantity"];
        }, $nodeDrops);

        foreach ($drops as $k => $drop) {
            $uid = strtoupper(Arr::get($drop, "uid"));
            $quantity = Arr::get($drop, "quantity");
            $countRaw = Arr::get($drop, "count");
            $count = intval($countRaw);
            $ignored = Arr::get($drop, "ignored");
            $uidQuantity = $uid . "_" . $quantity;
            $eventNodeDrop = $this->eventNodeDropRepository->getDrop($event_uid, $event_node_uid, $uid, $quantity);

            if (!$eventNodeDrop) {
                throw new HttpException(422, "Invalid drop uid + quantity combination on field drops[{$k}].");
            } else if ($countRaw !== null && strlen($countRaw) !== strlen(strval($count))) {
                throw new HttpException(422, "Invalid count on field drops[{$k}][count].");
            } else if (!is_int($count) && !$ignored) {
                throw new HttpException(422, "Either count or ignored must be provided on field drops[{$k}].");
            } else if ($count < 0 && !$ignored) {
                throw new HttpException(422, "Invalid count on field drops[{$k}][count].");
            } else if (!in_array($uidQuantity, $nodeDropUidQuantity)) {
                throw new HttpException(422, "Duplicate uid and quantity on field drops[{$k}].");
            }

            $nodeDropUidQuantity = array_filter($nodeDropUidQuantity, function ($value) use ($uidQuantity) {
                return $value !== $uidQuantity;
            });

            $cleanDrops[] = Arr::only($drop, ["uid", "quantity", "count", "ignored"]);
        }

        if (count($nodeDropUidQuantity)) {
            throw new HttpException(422, "Not all drops required were submitted.");
        }

        $receipt = $this->submissionRepository->create(
            $event_uid,
            $event_node_uid,
            $cleanDrops,
            $submitter
        );

        return $this->responseFactory->json([
            "status" => "Success",
            "receipt" => $receipt
        ]);
    }

}
